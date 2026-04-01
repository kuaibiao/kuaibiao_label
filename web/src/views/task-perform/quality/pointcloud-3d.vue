<template>
    <div style="position:relative; min-height: 100%;">
        <div class="task-header">
            <quality-info :qualityRate="qualityDataSelf"/>
            <div class="task-btn-group" v-show="taskListIsNull">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble = "tableData.length > 0"
                ></task-progress>
                <Button type="primary" size="small"
                        @click.native="batchSelect"> {{ selectAllText }} </Button>
                <Button type="primary" size="small"
                        @click.native="batchPass"
                        v-if="selectedTask.length > 0">{{$t('tool_batch_pass')}}</Button>
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillReject('', true)">{{$t('tool_batch_rejection')}}</Button>
                <!-- <Button type="warning" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}</Button> -->
                <Button type="error" size="small" @click="exitfirmModal = true">{{$t('tool_clear_and_exit')}}</Button>
            </div>
        </div>
        <div class="audit-wrapper">
            <Table  ref="selection"
                    :columns="columnsConfig"
                    :data="tableData"
                    @on-selection-change ="onSelectChange"
            ></Table>
        </div>
        <Spin fix v-if="loading">{{ loadingText }}</Spin>
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
               :transition-names="[]"
        >
            <pointcloud-audit-view
                    :taskList = "tableData"
                    :index="currentTaskIndex"
                    :taskInfo="taskInfo"
                    :timeout="timeout"
                    :categoryView = "categoryInfo.view"
                    :canHandleKeyboard="canHandleKeyboard"
                    :needEdit="false"
                    :isAudit="false"
                    @close ="viewModal = false"
                    @task-pass ="taskPass"
                    @task-reject="taskReject"
                    @task-setDifficult="taskSetDifficult"
                    ref="viewModal"
            ></pointcloud-audit-view>
        </Modal>
        <Modal v-model="rejectModal"
               :title="$t('tool_fillreject_reason')"
               @on-ok= "handleModalOnOk"
               @on-visible-change="rejectModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
        >
            <Input v-model="rejectReason" autofocus ref="rejectModalInput" @on-enter="handleModalOnOk"/>
        </Modal>
        <Modal v-model="resetModal"
               :title="$t('tool_fillreset_reason')"
               @on-ok= "handleResetModalOnOk"
               @on-visible-change="resetModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
        >
            <Input v-model="resetReason" autofocus ref="resetModalInput" @on-enter="handleResetModalOnOk"/>
        </Modal>
        <Modal
                v-model="exitfirmModal"
                :title="$t('tool_operate_tips')">
            <p>{{$t('tool_exit_review')}}</p>
            <div slot="footer">
                <Button type="text" @click="exitfirmModal = false">{{$t('tool_cancel')}}</Button>
                <Button type="error" @click="exit" :loading="loading">{{$t('tool_quit')}}</Button>
            </div>
        </Modal>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import EventBus from '@/common/event-bus';
    import util from "@/libs/util";
    import TaskInfo from '../components/task-info.vue';
    import QualityInfo from '../components/quality-rate.vue';
    import cloneDeep from 'lodash.clonedeep';
    import dataIsValid from '../../../common/dataIsValid'; // 数据清洗结果类型 Yes No Unknown
    import ErrorTaskReasonShow from '../../../common/components/error-task-reason-show.vue';
    import commonMixin from '../mixins/commom';

    export default {
        name: 'quality-pointcloud-3d',
        mixins: [commonMixin],
        props: {
            templateInfo: {
                type: Array,
                default: [],
            },
            taskList: {
                type: Array,
                required: true,
            },
            categoryInfo: {
                type: Object,
                required: true,
            },
            serverTime: {
                type: Number,
                required: true,
            },
            qualityData: {
                type: Object,
                required: true,
            },
            taskInfo: {
                type: Object,
                required: true,
            },
            timeout: {
                type: Number,
                required: true,
            },
            taskStat: {
                type: Object,
                required: true,
            },
            stepInfo: {
                type: Object,
                required: true,
            },
        },
        data () {
            return {
                exitfirmModal: false,
                currentTaskIndex: 0,
                userId: this.$store.state.user.userInfo.id,
                clientTime: Math.floor(new Date().valueOf() / 1000),
                loading: false,
                submiting: false,
                loadingText: this.$t('tool_loading'),
                editModal: false,
                viewModal: false,
                selectedTask: [],
                selectAllIsOn: false,
                columnsConfig: [
                    {
                        type: 'selection',
                        width: 60,
                        align: 'center'
                    },
                    {
                        title: this.$t('tool_job_id'),
                        key: 'data',
                        width: 100,
                        render: (h, params) => {
                            return h('span', params.row.data.id);
                        }
                    },
                    {
                        title: this.$t('tool_filename'),
                        key: 'data',
                        ellipsis: true,
                        render: (h, params) => {
                            return h('Tooltip', {
                                props: {
                                    content: params.row.data.name,
                                    placement: 'top-start',
                                    transfer: true
                                },
                                'class': 'tool_tip',
                                style: {
                                    display: 'inline'
                                }
                            }, [
                                h('span', params.row.data.name)
                            ]);
                        }
                    },
                    {
                        title: this.$t('tool_created_time'),
                        key: 'data',
                        render: (h, params) => {
                            let parentWorks = params.row.parentWorks;
                            let parentWorksCreateTime = parentWorks.map((works) => {
                                return works.created_at;
                            }).sort();
                            return h('span', util.timeFormatter(new Date(parentWorksCreateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss'));
                        }
                    },
                    {
                        title: this.$t('tool_updated_time'),
                        key: 'data',
                        render: (h, params) => {
                            let parentWorks = params.row.parentWorks;
                            let parentWorksUpdateTime = parentWorks.map((works) => {
                                return works.updated_at;
                            }).sort((a, b) => a - b);
                            return h('span', util.timeFormatter(new Date(parentWorksUpdateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss'));
                        }
                    },
                    {
                        title: this.$t('tool_status'),
                        key: 'data',
                        render: (h, params) => {
                            if (params.row.workResult.feedback) {
                                return h('div', [
                                    h('span', {
                                    }, this.$t('tool_rejected')),
                                    h('span', [
                                        h('Poptip', {
                                            props: {
                                                trigger: "hover",
                                                title: this.$t('tool_reject_reason'),
                                                // content: params.row.workResult.feedback,
                                                transfer: true,
                                                placement: 'right-start',
                                            },
                                        }, [
                                            h('Icon', {
                                                style: {
                                                    marginLeft: '6px',
                                                    verticalAlign: 'top'
                                                },
                                                props: {
                                                    type: 'ios-help-circle-outline',
                                                    size: 18
                                                },
                                            }),
                                            h(ErrorTaskReasonShow, {
                                                props: {
                                                    reason: params.row.workResult.feedback,
                                                },
                                                slot: 'content'
                                            })

                                        ])
                                    ]),
                                ]);
                            }
                        }
                    },
                    {
                        title: this.$t('tool_handle'),
                        width: 320,
                        align: 'center',
                        render: (h, params) => {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        type: 'info',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.viewModal = true;
                                            this.currentTaskIndex = params.index;
                                            this.$refs.viewModal.init(params.index);
                                        }
                                    }
                                }, this.$t('tool_view')),
                                h('Button', {
                                    props: {
                                        type: 'primary',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskPass(params.row.data.id, params.row.parentWorkResults[params.row.parentWorkResults.length - 1].work_id);
                                        }
                                    }
                                }, this.$t('tool_pass')),
                                h('Button', {
                                    props: {
                                        type: 'error',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskWillReject(params.row.data.id);
                                        }
                                    }
                                }, this.$t('tool_reject'))
                                // h('Button', {
                                //     props: {
                                //         size: 'small',
                                //         type: 'warning'
                                //     },
                                //     style: {
                                //         marginRight: '5px',
                                //     },
                                //     on: {
                                //         click: () => {
                                //             this.taskSetDifficult(params.row.data.id);
                                //             // this.taskReject(params.row.data.id, this.rejectReason)
                                //         }
                                //     }
                                // }, this.$t('tool_diffcult_job'))
                            ]);
                        }

                    }
                ],
                tableData: [],
                isBatch: false,
                rejectReason: '',
                resetReason: '',
                rejectModal: false,
                resetModal: false,
                rejectTaskId: '',
                resetTaskId: '',
                currentUserId: '',
                dataIdsCache: {},
                taskIsTimeOut: false,
                taskItemInfo: '',
                taskItemInfoMore: {},
                isReady: false,
                currentTaskResource: null,
                qualityDataSelf: {},
            };
        },
        computed: {
            selectAllText () {
                let text = '';
                if (this.selectedTask.length > 0) {
                    this.selectAllIsOn = true;
                    text = this.$t('tool_all_cancel');
                } else {
                    text = this.$t('tool_all_select');
                    this.selectAllIsOn = false;
                }
                return text;
            },
            taskListIsNull () {
                if (!this.taskList.length) {
                    this.loading = false;
                }
                return this.taskList.length;
            },
            canHandleKeyboard () {
                return this.viewModal && !(this.rejectModal || this.editModal || this.resetModal || this.taskIsTimeOut);
            },
        },
        watch: {
            taskList (newV, oldV) {
                this.tableData = cloneDeep(this.taskList);
                this.dataIdsCache = {};
                this.taskIsTimeOut = false;
                if (newV.length === 0) {
                    this.viewModal = false;
                    // EventBus.$emit('perform-fetchTask');
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_no_jobs'),
                        duration: 3,
                    });
                }
            }
        },
        mounted () {
            this.tableData = cloneDeep(this.taskList);
            this.dataIdsCache = {};
            this.taskIsTimeOut = false;
            this.qualityDataSelf = this.qualityData;

            if (this.tableData.length === 0) {
                // 一进来就没有作业
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_no_jobs'),
                    duration: 3,
                });
            }
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('clear-fetchTask', this.userIdChange);
            EventBus.$on('formElementChange', this.saveRegionInfo);
            Vue.nextTick(() => {
                this.loading = false;
            });
        },
        methods: {
            // updateWorkerInfo (target) {
            //     target.mBy = this.userId;
            //     target.mTime = this.getTime();
            //     target.step = this.taskInfo.step_id;
            // },
            // showEditModal (index, resource) {
            //     if (this.taskIsTimeOut) {
            //         this.$Message.destroy();
            //         this.$Message.warning({
            //             content: this.$t('tool_timed_out'),
            //             duration: 2,
            //         });
            //         return;
            //     }
            //     this.editModal = true;
            //     this.currentTaskIndex = index;
            //     this.currentTaskResource = resource;
            // },
            checkTaskList () {
                if (this.tableData.length === 0) {
                    EventBus.$emit('perform-fetchTask');
                }
            },
            userIdChange (oldId, newId) {
                this.currentUserId = newId;
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.taskIsTimeOut = true;
            },
            onSelectChange (selection) {
                this.selectedTask = selection;
            },
            batchReject () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskReject(task.data.id, this.rejectReason);
                    }, 100 * index);
                });
            },
            batchReset () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskReset(task.data.id, this.resetReason);
                    }, 100 * index);
                });
            },
            batchPass () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskPass(task.data.id, task.parentWorkResults[task.parentWorkResults.length - 1].work_id);
                    }, 100 * index);
                });
            },
            batchSelect () {
                if (this.selectAllIsOn) {
                    this.$refs.selection.selectAll(false);
                } else {
                    this.$refs.selection.selectAll(true);
                }
            },
            taskWillSetDifficult () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskSetDifficult(task.data.id);
                    }, 100 * index);
                });
            },
            taskSetDifficult (dataId) {
                if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                    return;
                }
                let result = {};
                result[dataId] = {
                    is_difficult: 1
                };
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: dataId,
                    user_id: this.currentUserId,
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
                    return;
                }
                this.loading = true;
                this.dataIdsCache[dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            delete this.dataIdsCache[dataId];
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.qualityDataSelf = res.data;
                            let taskIndex = '';
                            this.tableData.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.tableData.splice(taskIndex, 1);
                            this.selectedTask.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.selectedTask.splice(taskIndex, 1);
                            this.editModal = false;
                            this.checkTaskList();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            delete this.dataIdsCache[dataId];
                        });
                    }
                });
            },
            taskPass (dataId, currentId) {
                if (this.taskIsTimeOut && this.editModal) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                    return;
                }
                let result = {};
                result[dataId] = {
                    verify: {
                        verify: 1,
                        feedback: '',
                        correct_work_id: currentId,
                    }
                };
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: dataId,
                    user_id: this.currentUserId,
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
                    return;
                }
                this.loading = true;
                this.dataIdsCache[dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            delete this.dataIdsCache[dataId];
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.qualityDataSelf = res.data;
                            let taskIndex = '';
                            this.tableData.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.tableData.splice(taskIndex, 1);
                            this.selectedTask.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.selectedTask.splice(taskIndex, 1);
                            this.editModal = false;
                            this.checkTaskList();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            delete this.dataIdsCache[dataId];
                        });
                    }
                });
            },
            taskWillReject (dataId, isBatch = false) {
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                this.rejectModal = true;
                this.rejectTaskId = dataId;
                this.isBatch = isBatch;
            },
            taskWillReset (dataId, isBatch = false) {
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                this.resetModal = true;
                this.resetTaskId = dataId;
                this.isBatch = isBatch;
            },
            handleModalOnOk () {
                if (this.rejectReason.trim() === '') {
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_reject_empty'),
                        duration: 2,
                    });
                    return;
                }
                if (this.selectAllIsOn && this.selectedTask.length > 0 && this.isBatch) {
                    this.batchReject();
                } else {
                    this.taskReject(this.rejectTaskId, this.rejectReason);
                }
            },
            handleResetModalOnOk () {
                if (this.resetReason.trim() === '') {
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_reset_empty'),
                        duration: 2,
                    });
                    return;
                }
                if (this.selectAllIsOn && this.selectedTask.length > 0 && this.isBatch) {
                    this.batchReset();
                } else {
                    this.taskReset(this.resetTaskId, this.resetReason);
                }
            },
            taskReject (dataId, reason) {
                if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                    return;
                }
                let result = {};
                result[dataId] = {
                    verify: {
                        verify: 0,
                        feedback: reason
                    }
                };
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    data_id: dataId,
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
                    return;
                }
                this.loading = true;
                this.dataIdsCache[dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            delete this.dataIdsCache[dataId];
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            // let selectedIndex = this.selectedTask.indexOf(dataId);
                            // if(selectedIndex !== -1)  {
                            //     this.selectedTask.splice(selectedIndex, 1);
                            // }
                            this.qualityDataSelf = res.data;
                            this.rejectModal = false;
                            let taskIndex = '';
                            this.tableData.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.tableData.splice(taskIndex, 1);
                            this.selectedTask.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.selectedTask.splice(taskIndex, 1);
                            this.editModal = false;
                            this.checkTaskList();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            delete this.dataIdsCache[dataId];
                        });
                    }
                });
            },
            taskReset (dataId, reason) {
                if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                    return;
                }
                let result = {};
                result[dataId] = {
                    verify: {
                        verify: 2,
                        feedback: reason
                    }
                };
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    data_id: dataId,
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
                    return;
                }
                this.loading = true;
                this.dataIdsCache[dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            delete this.dataIdsCache[dataId];
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            // let selectedIndex = this.selectedTask.indexOf(dataId);
                            // if(selectedIndex !== -1)  {
                            //     this.selectedTask.splice(selectedIndex, 1);
                            // }
                            this.resetModal = false;
                            let taskIndex = '';
                            this.tableData.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.tableData.splice(taskIndex, 1);
                            this.selectedTask.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            this.selectedTask.splice(taskIndex, 1);
                            this.editModal = false;
                            this.checkTaskList();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            delete this.dataIdsCache[dataId];
                        });
                    }
                });
            },
            rejectModalVisibleChange (flag) {
                if (flag) {
                    this.rejectReason = '';
                    Vue.nextTick(() => {
                        this.$refs.rejectModalInput.$el.querySelector('input').focus();
                    });
                }
            },
            resetModalVisibleChange (flag) {
                if (flag) {
                    this.resetReason = '';
                    Vue.nextTick(() => {
                        this.$refs.resetModalInput.$el.querySelector('input').focus();
                    });
                }
            },
            // editModalVisibleChange (flag) {
            //     if (flag) {
            //         this.$nextTick(() => {
            //             this.editTask(this.tableData[this.currentTaskIndex]);
            //         });
            //     } else {
            //         this.isReady = false;
            //     }
            // },
            getAttrInfo () {
                return this.$refs.templateView.getData();
            },
            getGlobalInfo () {
                return this.$refs.templateView.getGlobalData();
            },
            exit () {
                this.loading = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'POST',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        user_id: this.currentUserId,
                        op: 'clear',
                    },
                    success: () => {
                        this.$store.commit('removeTag', 'perform-task');
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                    },
                    error: () => {
                        this.$store.commit('removeTag', 'perform-task');
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                    }
                });
            },
        },
        beforeDestroy () {
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('clear-fetchTask', this.userIdChange);
            EventBus.$off('formElementChange', this.saveRegionInfo);
        },
        components: {
            'template-view': () => import('components/template-produce'),
            'task-progress': () => import('../components/taskprogress.vue'),
            'pointcloud-audit-view': () => import('components/task-audit-view/pointcloud-audit-view.vue'),
            'task-info': TaskInfo,
            'quality-info': QualityInfo,
            ErrorTaskReasonShow,
        }
    };
</script>

<style lang="scss">
    .edit-modal-wrapper {
        .edit-modal-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            .task-info {
                margin-right: 20px;
            }
        }
        .ivu-modal {
            width: 100%;
            height: 100%;
            top: 0;
        }
        .ivu-modal-content {
            height: 100%;
            border-radius: 0;
        }
        .ivu-modal-header {
            text-align: right;
            padding: 6px 15px;
        }
        .ivu-modal-body {
            padding: 0;
        }
        .ivu-modal-footer {
            display: none;
        }
        .edit-btn-group {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            button {
                margin-right: 5px;
            }
        }
    }

</style>

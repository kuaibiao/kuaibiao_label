<template>
    <div class="voice-transcription" style="position:relative; min-height: 100%;">
        <div class="task-header">
            <quality-info :qualityRate="qualityDataSelf"/>
            <div class="task-btn-group">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                ></task-progress>
                <Button type="primary" size="small"
                        @click.native="batchSelect"> {{ selectAllText }}
                </Button>
                <Button type="primary" size="small"
                        @click.native="batchPass"
                        v-if="selectedTask.length > 0">{{$t('tool_batch_pass')}}
                </Button>
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillReject('', true)">{{$t('tool_batch_rejection')}}
                </Button>
                <!-- <Button type="warning" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}
                </Button> -->
            </div>
        </div>
        <div class="audit-wrapper">
            <Table ref="selection"
                   :columns="columnsConfig"
                   :data="taskList"
                   @on-selection-change="onSelectChange"
            ></Table>
        </div>
        <Spin fix v-if="loading">{{ loadingText }}</Spin>
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
        >
            <voice-audit-view
                    :taskList="tableData"
                    :index="currentTaskIndex"
                    :taskInfo="taskInfo"
                    :timeout="timeout"
                    :isAudit="false"
                    :categoryView="categoryInfo.view"
                    :canHandleKeyboard="canHandleKeyboard"
                    :needEdit="false"
                    @edit="showEditModal"
                    @close="viewModal = false"
                    @task-pass="taskPass"
                    @task-reject="taskReject"
                    @task-setDifficult="taskSetDifficult"
                    ref="viewModal"
            ></voice-audit-view>
        </Modal>
        <Modal v-model="rejectModal"
               :title="$t('tool_fillreject_reason')"
               @on-ok="handleModalOnOk"
               @on-visible-change="rejectModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
        >
            <Input v-model="rejectReason" autofocus ref="rejectModalInput" @on-enter="handleModalOnOk"/>
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
    import commonMixin from '../mixins/commom';
    export default {
        name: 'quality-audio-translate',
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
            taskInfo: {
                type: Object,
                required: true,
            },
            qualityData: {
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
                currentId: '',
                currentIds: [],
                currentTaskIndex: 0,
                userId: this.$store.state.user.userInfo.id,
                dataId: '',
                loading: false,
                submiting: false,
                editLoading: false,
                taskItemInfo: '',
                taskItemInfoMore: {},
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
                        title: this.$t('tool_handle'),
                        align: 'center',
                        width: 320,
                        render: (h, params) => {
                            return h('div', [
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
                rejectModal: false,
                rejectTaskId: '',
                parentWorkResults: [], // 作业结果
                parentWorks: null, // 作业人员
                dataResult: {},
                qualityDataSelf: {},
                dataIdsCache: {},
                taskIsTimeOut: false,
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
            canHandleKeyboard () {
                return !(this.rejectModal || this.taskIsTimeOut);
            },
        },
        watch: {
            taskList (newV, oldV) {
                this.tableData = newV;
                this.dataIdsCache = {};
                this.taskIsTimeOut = false;
                if (newV.length === 0) {
                    this.viewModal = false;
                    EventBus.$emit('perform-fetchTask');
                }
            }
        },
        mounted () {
            this.tableData = this.taskList;
            this.qualityDataSelf = this.qualityData;
            this.dataIdsCache = {};
            this.taskIsTimeOut = false;
            EventBus.$on('task-timeout', this.setTaskTimeout);
        },
        methods: {
            showEditModal () {

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
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let taskIndex = '';
                            this.qualityDataSelf = res.data;
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
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let taskIndex = '';
                            this.qualityDataSelf = res.data;
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
                this.rejectModal = true;
                this.rejectTaskId = dataId;
                this.isBatch = isBatch;
            },
            handleModalOnOk () {
                if (this.rejectReason.trim() === '') {
                    this.$Message.error({
                        content: this.$t('tool_reason_empty'),
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
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            // let selectedIndex = this.selectedTask.indexOf(dataId);
                            // if(selectedIndex !== -1)  {
                            //     this.selectedTask.splice(selectedIndex, 1);
                            // }
                            this.rejectModal = false;
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
        },
        beforeDestroy () {
            EventBus.$off('task-timeout', this.setTaskTimeout);
        },
        components: {
            'template-view': () => import('components/template-produce'),
            'task-progress': () => import('../components/taskprogress.vue'),
            'voice-audit-view': () => import('components/task-audit-view/voice-audit-view'),
            'task-info': TaskInfo,
            'quality-info': QualityInfo,
        }
    };
</script>

<style lang="scss">
    @import url('../../../styles/table.css');

    .voice-transcription {
        .file-placeholder {
            background: #fff !important;
        }
    }
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

        .result-operator {
            padding-right: 15px;
            color: cornflowerblue;
        }

        .audio-result-content {
            overflow-y: auto;
            height: calc(100vh - 55px);
            padding: 15px;
        }
    }
</style>

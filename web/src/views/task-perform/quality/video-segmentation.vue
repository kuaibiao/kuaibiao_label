<!--质检：视频分割-->
<template>
    <div  style="position:relative; min-height: 100%;">
        <div class="task-header">
            <div class="flex-space-between">
                <quality-info :qualityRate="qualityDataSelf" />
                <Poptip trigger="hover" placement="bottom">
                    <div class="task-info" style="cursor:pointer;">
                        {{$t('video_s_process_name')}}:{{tasksInfo.stepName}} <!--工序名-->
                    </div>
                    <task-info slot="content" :taskInfo = "tasksInfo" />
                </Poptip>
            </div>
            <div class="task-btn-group">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
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
            </div>
        </div>
        <div class="audit-wrapper">
            <Table  ref="selection"
                    :columns="columnsConfig"
                    :data="taskList"
                    @on-selection-change ="onSelectChange"
            ></Table>
        </div>
        <Spin fix v-if="loading">{{loadingText}}</Spin>

        <!--弹窗：查看-->
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :closable="false"
               :mask-closable="false"
               :transition-names="[]">
            <video-segmentation-view
                    :taskList = "tableData"
                    :index = "currentTaskIndex"
                    :taskInfo = "taskInfo"
                    :timeout = "timeout"
                    :categoryView = "categoryInfo.view"
                    :canHandleKeyboard = "canHandleKeyboard"
                    :needEdit="false"
                    :isAudit="false"
                    @edit = "showEditModal"
                    @close = "viewModal = false"
                    @task-pass = "taskPass"
                    @task-reject = "taskReject"
                    @task-setDifficult = "taskSetDifficult"
                    ref = "viewModal">
            </video-segmentation-view>
        </Modal>

        <!--弹窗:编辑-->
        <!--<Modal v-model="editModal"-->
        <!--:class="'edit-modal-wrapper'"-->
        <!--width="100"-->
        <!--style="min-height:100%"-->
        <!--:closable="false"-->
        <!--:mask-closable="false"-->
        <!--scrollable-->
        <!--@on-visible-change="editModalVisibleChange">-->
        <!--<div slot="header" class="edit-modal-header">-->
        <!--<Poptip trigger="hover" placement="bottom">-->
        <!--<div class="task-info" style="cursor:pointer;">-->
        <!--{{taskItemInfo}}-->
        <!--</div>-->
        <!--<task-info slot="content" :taskInfo = "taskItemInfoMore" />-->
        <!--</Poptip>-->
        <!--<div class="edit-btn-group">-->
        <!--&lt;!&ndash; <span style="padding-right: 15px; color: #000;">选择最佳答案通过:</span> &ndash;&gt;-->
        <!--&lt;!&ndash; <Button type="primary" size="small" @click="submitEditTask()">提交</Button> &ndash;&gt;-->
        <!--<Button type="success" @click="taskPass(dataId,currentId)">{{$t('tool_d')}} (D)</Button>-->
        <!--<Button type="error" @click="taskWillReject(dataId)">{{$t('tool_w')}}(W)</Button>-->
        <!--<Button type="warning" @click.native="taskSetDifficult(dataId)">{{$t('tool_diffcult_job')}}</Button>-->
        <!--<Button @click="editModal = false">{{$t('tool_cancel')}}</Button>-->
        <!--</div>-->
        <!--</div>-->
        <!--<div id="text_audit">-->
        <!--<Row type="flex" justify="center"  align="top" >-->
        <!--<i-col span="16">-->
        <!--<Row type="flex" justify="center"  align="middle" style="min-height:100%;">-->
        <!--<div class="data-container-wrapper">-->
        <!--{{ $t('tool_text_loading') }}-->
        <!--</div>-->
        <!--</Row>-->
        <!--</i-col>-->
        <!--<i-col span="8">-->
        <!--<Row >-->
        <!--<resultItemAnalysis-->
        <!--:data="dataResult.info || []"-->
        <!--:index="-1"-->
        <!--:user="{}" />-->
        <!--</Row>-->
        <!--</i-col>-->
        <!--</Row>-->
        <!--</div>-->
        <!--<Spin fix v-if="editLoading">{{loadingText}}</Spin>-->
        <!--<span slot="footer"></span>-->
        <!--</Modal>-->

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
    </div>
</template>

<script>
    import Vue from 'vue';
    import api from '@/api';
    import EventBus from '@/common/event-bus';
    import util from "@/libs/util";
    import cloneDeep from 'lodash.clonedeep';        
    import TaskInfo from '../components/task-info.vue';
    import QualityInfo from '../components/quality-rate.vue';
    import commonMixin from '../mixins/commom';    
    export default {
        name: "quality-video-classify",
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
                currentTaskIndex: 0,
                userId: this.$store.state.user.userInfo.id,
                dataId: '',
                loading: true,
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
                                        type: 'success',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskPass(params.row.data.id, params.row.workResult.work_id);
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
                                            // this.taskReject(params.row.data.id, this.rejectReason)
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
                parentWorkResults: [],
                parentWorks: null,
                qualityDataSelf: {},
                dataResult: {},
                taskIsTimeOut: false,
                dataIdsCache: {}, // 提交中或提交过的数据ID缓存 防止针对同一数据ID同时执行驳回通过重置等操作
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
                return this.viewModal && !(this.rejectModal || this.taskIsTimeOut);
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
            Vue.nextTick(() => {
                this.loading = false;
            });
            EventBus.$on('task-timeout', this.setTaskTimeout);
        },
        methods: {
            showEditModal (index) {
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 2,
                    });
                    return;
                }
                this.editModal = true;
                this.currentTaskIndex = index;
                this.dataId = this.tableData[index].data.id;
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.taskIsTimeOut = true;
            },
            cloneDeep (a) {
                return cloneDeep(a);
            },
            onSelectChange (selection) {
                this.selectedTask = selection;
            },
            initTask () {
                this.getTaskResource(this.dataId);
                this.dataResult = this.taskList[this.currentTaskIndex].dataResult.result || {};
                Vue.nextTick(() => {
                    this.currentId = this.taskList[this.currentTaskIndex].workResult.work_id;
                });
            },
            getTaskResource (dataId) {
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: dataId,
                    type: 'ori',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.data_id) {
                    return;
                }
                this.loading = true;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        this.editLoading = false;
                        if (res.error) {
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            let file = resource[0][1];
                            Vue.nextTick(() => {
                                let taskData = this.taskList[this.currentTaskIndex].data;
                                this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                                this.taskItemInfoMore = {
                                    ...this.taskInfo,
                                    dataName: taskData.name,
                                    dataId: taskData.id,
                                };
                                $('#text_audit .data-container-wrapper').html(`
                                <video
                                    src="${api.download.file + '?file=' + file}"
                                     style="max-height: calc(100vh - 180px); margin: 0 auto; display: block; width: 100%;"
                                     autoplay="autoplay"
                                     controls
                                     oncontextmenu="return false">
                                     <p>` + this.$t('tool_not_support_video_playback') + `</p>
                                 </video>`);
                            });
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                        this.editLoading = false;
                        this.$Message.error({
                            content: this.$t('tool_request_failed'),
                            duration: 2,
                        });
                    }
                });
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
                        this.taskPass(task.data.id, task.workResult.work_id);
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
            taskWillReject (dataId, isBatch = false) {
                this.rejectModal = true;
                this.rejectTaskId = dataId;
                this.isBatch = isBatch;
            },
            handleModalOnOk () {
                if (this.rejectReason.trim() === '') {
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
                            this.rejectModal = false;
                            this.qualityDataSelf = res.data;
                            let taskIndex = -1;
                            this.tableData.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            if (taskIndex > -1) {
                                this.tableData.splice(taskIndex, 1);
                            }
                            taskIndex = -1;
                            this.selectedTask.forEach((task, index) => {
                                if (task.data.id == dataId) {
                                    taskIndex = index;
                                }
                            });
                            if (taskIndex > -1) {
                                this.selectedTask.splice(taskIndex, 1);
                            }
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
            editModalVisibleChange (flag) { // true 打开 false 关闭
                if (flag) {
                    this.editLoading = true;
                    this.initTask();
                } else {
                    this.editLoading = false;
                    EventBus.$emit('needConfirmLeave', false);
                    $('#text_audit .data-container-wrapper').html(this.$t('tool_data_loading'));
                }
            },
        },
        beforeDestroy () {
            EventBus.$off('task-timeout', this.setTaskTimeout);
        },
        components: {            
            'video-segmentation-view': () => import("../../../common/components/task-audit-view/video-segmentation-view"), //视频分割
            'template-view': () => import('components/template-produce'),
            'task-progress': () => import('../components/taskprogress.vue'),            
            'task-info': TaskInfo,
            'quality-info': QualityInfo,
        }
    };
</script>
<style  lang="scss">
    .edit-modal-wrapper {
        .edit-modal-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            .task-info {
                margin-right: 15px;
            }
        }
        .data-container-wrapper {
            position: fixed;
            top: 68px;
            width: 56%;
            max-height: calc(100vh - 84px);
            overflow-y: auto;
            .data-container:first-child {
                margin-top: 15px;
            }
            .data-container {
                font-size: 14px;
                color: #333;
                white-space: pre-wrap;
                padding: 0px 15px;
                margin: 0;
            }
        }
        .file-placeholder {
            background: #fff !important;
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
    #text_audit {
        overflow-y: auto;
        height: calc(100vh - 55px);
    }
</style>
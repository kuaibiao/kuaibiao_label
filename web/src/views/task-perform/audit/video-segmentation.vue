<!--审核：视频分割 专用组件-->
<template>
    <div class="" style="position:relative; height: 100%;">
        <div class="task-header">
            <div class="flex-space-between">
		<!--按作业员审核/按数据顺序审核-->
                <audit-by-user :userList="userList" v-show="!isReturnWork"></audit-by-user>
                <!--工序名/作业信息-->
                <!-- <Poptip trigger="hover" placement="bottom">
                    <div class="task-info" style="cursor:pointer;">
                        {{$t('video_s_process_name')}}:{{taskInfo.stepName}} 
                    </div>
                    <task-info slot="content" :taskInfo = "taskInfo" style="z-index:99;" />
                    </Poptip>
                -->
            </div>
            <div class="task-btn-group" v-show="taskListIsNull">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble="tableData.length > 0"
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
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillReset('', true)">{{$t('tool_bulk_reset')}}
                </Button>
                <Button type="warning" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}
                </Button>
                <Button type="error" size="small" @click="exitfirmModal = true">{{$t('tool_clear_and_exit')}}</Button>
            </div>
        </div>
        <!--视频列表-->
        <div class="video-wrapper" data-tips="Video List">
            <Table ref="selection"
                   :columns="columnsConfig"
                   :data="tableData"
                   @on-selection-change="onSelectChange"
            ></Table>
        </div>
        <Spin fix v-if="loading">{{ loadingText }}</Spin>
        <!--查看：弹窗-->
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
                    :needEdit="false" 
                    :canHandleKeyboard = "canHandleKeyboard" 
                    @edit = "showEditModal"
                    @close = "viewModal = false"
                    @task-pass = "taskPass"
                    @task-reject = "taskReject"
                    @task-reset = "taskReset"
                    @task-setDifficult = "taskSetDifficult"
                    ref = "viewModal">
            </video-segmentation-view>
        </Modal>

        <!--修改:弹窗-->
        <Modal v-model="editModal" 
               :class="'edit-modal-wrapper'" 
               width="100" 
               style="min-height:100%" 
               :closable="false" 
               :mask-closable="false" 
               @on-visible-change="editModalVisibleChange">
            <div slot="header" class="edit-modal-header">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble="false"
                ></task-progress>
                <Poptip trigger="hover" placement="bottom">
                    <div class="task-info" style="cursor:pointer;">
                        {{ taskItemInfo }}
                    </div>
                    <task-info slot="content" :taskInfo="taskItemInfoMore"/>
                </Poptip>
                <div class="edit-btn-group">
                    <!--提交-->
                    <Button class="btns-s-1405" type="primary" size="small" @click="submitEditTask()">{{$t('tool_submit')}}</Button>
                    <!--取消-->
                    <Button class="btns-c-1406" @click="editModal = false" size="small">{{$t('tool_cancel')}}</Button>
                </div>
            </div>
            <template-view
                    :config="templateInfo"
                    scene="execute"
                    ref="templateView"
                    v-if="editModal">
            </template-view>
            <Spin fix v-if="editLoading">{{loadingText}}</Spin>
            <span slot="footer"></span>
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
        <Modal v-model="resetModal"
               :title="$t('tool_fillreset_reason')"
               @on-ok="handleResetModalOnOk"
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
    import vsComponent from '@/common/video-segmentation/index.vue'; //视频分割:组件    
    import TaskInfo from '../components/task-info.vue';
    import auditByUser from '../components/audit-by-user.vue';
    import dataIsValid from '../../../common/dataIsValid'; // 数据清洗结果类型 Yes No Unknown
    import commonMixin from '../mixins/commom';
    import cloneDeep from 'lodash.clonedeep';
    let vsCtor = Vue.extend(vsComponent);
    export default {
        name: "audit-video-segmentation",
        mixins: [commonMixin],
        vsObj: null,
        props: {
            templateInfo: {
                type: Array,
                default: [],
            },
            taskList: {
                type: Array,
                required: true,
            },
            userList: {
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
                isReturnWork:false, //是否是'返工作业' 
                exitfirmModal: false,
                currentId: '',
                currentTaskIndex: 0,
                currentFeedback: '',
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
                        title: this.$t('tool_reject'),
                        align: 'center',
                        key: 'data',
                        render: (h, params) => {
                            if (params.row.workResult.feedback) {
                                return h('div', [
                                    h('span', {}, this.$t('tool_rejected')),
                                    h('span', [
                                        h('Poptip', {
                                            props: {
                                                trigger: "hover",
                                                title: this.$t('tool_reject_reason'),
                                                content: params.row.workResult.feedback,
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

                                        ])
                                    ]),
                                ]);
                            }
                        }
                    },
                    {
                        title: this.$t('tool_handle'),
                        align: 'center',
                        width: 320,
                        render: (h, params) => {
                            return h('div', [
                                h('Button', { //按钮：查看
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
                                h('Button', { //按钮：通过
                                    props: {
                                        type: 'success',
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
                                h('Button', { //按钮：驳回
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
                                }, this.$t('tool_reject')),
                                h('Button', { //按钮：重置
                                    props: {
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskWillReset(params.row.data.id);
                                            // this.taskReject(params.row.data.id, this.rejectReason)
                                        }
                                    }
                                }, this.$t('tool_reset')),
                                h('Button', { //按钮：挂起
                                    props: {
                                        size: 'small',
                                        type: 'warning'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskSetDifficult(params.row.data.id);
                                            // this.taskReject(params.row.data.id, this.rejectReason)
                                        }
                                    }
                                }, this.$t('tool_diffcult_job'))
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
                parentWorkResults: [],  // 作业结果
                parentWorks: null,      // 作业人员
                currentUserId: '',
                dataIdsCache: {},       // 提交中或提交过的数据ID缓存 防止针对同一数据ID同时执行驳回通过重置等操作
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
            taskListIsNull () {
                if (!this.taskList.length) {
                    this.loading = false;
                }
                return this.taskList.length;
            },
            canHandleKeyboard () {
                return this.viewModal && !(this.editModal || this.rejectModal || this.resetModal || this.taskIsTimeOut);
            },
        },
        watch: {
            taskList (newV, oldV) {
                this.tableData = cloneDeep(this.taskList);
                this.dataIdsCache = {};
                this.taskIsTimeOut = false;
                if (newV.length === 0) {
                    this.viewModal = false;                    
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
            Vue.nextTick(() => {
                this.loading = false;
            });
            //返工作业:不显示筛选项
            this.isReturnWork = (this.$route.query && Boolean(this.$route.query.isReturnWork)) || false;
        },
        methods: {
            //弹窗：修改
            showEditModal (index) {
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 2
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
                this.viewModal = false;
            },
            checkTaskList () {
                if (this.tableData.length === 0) {                    
                    EventBus.$emit('perform-fetchTask');
                }
            },
            // 按作业员审核
            userIdChange (e) {
                if (e.type === 'workerChange') {
                    this.currentUserId = e.data.cur;
                }
            },
            cloneDeep (a) {
                return cloneDeep(a);
            },
            onSelectChange (selection) {
                this.selectedTask = selection;
            },
            //功能：初始化任务
            initTask () {
                let app = this;
                this.getTaskResource(this.dataId);
                this.parentWorkResults = this.tableData[this.currentTaskIndex].parentWorkResults;
                this.parentWorks = this.tableData[this.currentTaskIndex].parentWorks;
                Vue.nextTick(() => {
                    this.currentId = this.parentWorkResults[this.parentWorkResults.length - 1].work_id;
                });
            },
            //功能：获取资源
            getTaskResource (dataId) {
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: dataId,
                    type: 'ori'
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
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                this.$Message.destroy();
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            //1.获取视频url
                            let video_url = '';
                            video_url = resource && resource[0] && resource[0][1] && resource[0][1]['url'];                                                        
                            //2.获取作业信息
                            let taskData = this.tableData[this.currentTaskIndex].data;
                            this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataName: taskData.name,
                                dataId: taskData.id,
                            };
                            this.executeTask(dataId, video_url);                            
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                        this.editLoading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_failed'),
                            duration: 2,
                        });
                        // 错误处理
                    }
                });
            },
            executeTask (dataId, src) {
                var self = this;
                let data = this.tableData[this.currentTaskIndex];
                let parentWorkResults = data && data.parentWorkResults && data.parentWorkResults[0];                                
                let work_id = parentWorkResults && parentWorkResults.work_id; // 执行人的ID
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        user_id: this.currentUserId,
                        data_id: dataId,
                        work_id,
                        op: 'edit',
                    },
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                            // 错误处理
                        } else {
                            let taskData = this.tableData[this.currentTaskIndex].data;
                            let parentWorks = this.tableData[this.currentTaskIndex].parentWorks;
                            this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataName: taskData.name,
                                dataId: taskData.id,
                                user: (parentWorks && parentWorks[0] && parentWorks[0].user) || {}
                            };
                            
                            let result = res.data.dataResultInfo.result || {};
                            self.videoSegmentationInit(src, result); //初始化:视频分割组件 - 用于修改
                            
                            EventBus.$emit('ready');
                            if (result && result.info) {
                                result.info.forEach((item) => {
                                    EventBus.$emit('setValue', {
                                        ...item,
                                        scope: this.$refs.templateView.$el
                                    });
                                });
                            }
                        }
                    },
                    error: () => {
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_failed'),
                            duration: 2,
                        });
                    }
                });
            },           
            //视频分割组件:初始化 - 用于修改
            videoSegmentationInit(video_url, result){
                var self = this;
                let container = self.$refs.templateView.$el.querySelector('[data-tpl-type="task-file-placeholder"]');                 
                let id = $(container).data('id');
                //1.结果回显处理
                let data=[];
                if(result && result.data){
                    data = result.data;
                }
                //2.获取渲染'视频分割'组件的HTML标签
                if (container) {
                    container = container.firstElementChild;
                }
                if (!self.vsObj) {
                    self.vsObj = new vsCtor({
                        parent: self
                    });
                    self.vsObj.$mount(container);
                }
                self.vsObj.$nextTick(() => { //给'视频分割'组件传值                
                    self.vsObj.init({
                        'video_url':video_url,
                        'id':id,
                        'type':'video-segmentation',
                        'data':data,
                        'is_edit':true
                    });
                });
            },
            //功能：提交保存修改
            submitEditTask () {
                var self = this;
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                if (this.submiting && this.editLoading) {
                    return;
                }
                let info = this.$refs.templateView.getGlobalData();
                let vsData = self.vsObj.getResultDataFun(); //获取'视频分割'组件的结果数据
                let dataArr = [];
                if(vsData){
                    if(vsData.value){
                        dataArr = cloneDeep(vsData.value);
                    }
                }
                let validValue = this.$refs.templateView.getDataIsValid();
                if (validValue) {
                    switch (validValue.value) {
                        case dataIsValid.yes: {
                            let InfoHasEmpty = false;
                            InfoHasEmpty = info.filter(item => {
                                return item.type !== 'data-is-valid' && item.required;
                                // required 属性可能会undefined 其布尔值为false
                            }).some((item) => {
                                return item.value.length === 0; // 表单信息 有为空的 String or Array
                            });
                            if (info.length && InfoHasEmpty) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: this.$t('tool_required_item'),
                                    duration: 2,
                                });
                                return;
                            }
                            break;
                        }
                        case dataIsValid.no: { // 无效的时候 清空标注数据和表单数据 保留数据有效无效的信息
                            info = [validValue];
                            break;
                        }
                        case dataIsValid.unknown : { // 不确定的时候不做任何判断有结果就保存
                            break;
                        }
                    }
                }
                let result = {
                    info,
                    data:dataArr
                };
                let data = this.tableData[this.currentTaskIndex];
                let dataId = data && data.data && data.data.id;                
                let parentWorkResults = data && data.parentWorkResults && data.parentWorkResults[0];                                
                let work_id = parentWorkResults && parentWorkResults.work_id; // 执行人的ID                
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    data_id: dataId,
                    work_id,
                    op: 'edit_submit',
                    data_result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.data_id || !reqData.task_id || !reqData.project_id) {
                    return;
                }
                this.loading = true;
                this.submiting = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        this.submiting = false;
                        if (res.error) {
                            this.submiting = false;
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.taskPass(dataId, work_id);
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                        this.submiting = false;
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
            //功能：审核通过
            taskPass (dataId, currentId) {
                var self = this;
                if (this.taskIsTimeOut && this.viewModal) {
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
                            //检查是否有新数据
                            self.checkTaskList();
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
                            this.rejectModal = false;
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
                            this.resetModal = false;
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
            // 修改结果模态框
            editModalVisibleChange (flag) { //true打开    false关闭
                if (flag) {
                    this.editLoading = true;
                    this.initTask();
                } else {
                    this.editLoading = false;
                    EventBus.$emit('needConfirmLeave', false);
                    $('#text_audit .data-container-wrapper').html(this.$t('tool_data_loading'));
                    // 销毁'视频分割'组件
                    this.vsObj && this.vsObj.$destroy(); //调用子组件中的销毁方法
                    this.vsObj = null;
                }
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
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/index'} : this.$store.state.app.prevPageUrl;
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
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/index'} : this.$store.state.app.prevPageUrl;
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
        },
        components: {
            'video-segmentation-view': () => import("../../../common/components/task-audit-view/video-segmentation-view"), //视频分割
            'template-view': () => import('components/template-produce'),
            'task-progress': () => import('../components/taskprogress.vue'),            
            'task-info': TaskInfo,
            'audit-by-user': auditByUser
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
<!--视频分割-->
<template>
    <div style="position:relative; height: 100%;">
        <div class="task-header">
            <user-stat :userStat = "userStat" />
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore" />
            </Poptip>
            <div class="task-btn-group">
                <task-progress
                        :total="taskList.length"
                        :current="currentTaskIndex"
                        :timeout="ptimeout"
                ></task-progress>
                <!--查看驳回原因-->
                <Button v-if="feedback.trim() !== ''" type="error" size="small" @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <!--挂起-->
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <!--退出-->
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <!--提交并退出-->
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <!--提交(D)-->
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}</Button>
            </div>
        </div>
        
        <template-view style="min-height: 420px"
                    :config="templateInfo"
                    scene="execute"
                    ref="templateView">
        </template-view>

        <Spin fix v-if="loading">{{loadingText}}</Spin>
        <Modal v-model="feedbackModal" :title="$t('tool_reject_reason')">
            <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
        </Modal>
    </div>
</template>

<script>
import Vue from 'vue';
import api from '@/api';
import util from "@/libs/util";
import TemplateView from 'components/template-produce';
import taskProgress from '../components/taskprogress.vue';
import TaskInfo from '../components/task-info.vue';
import UserStat from '../components/user-stat.vue';
import EventBus from '@/common/event-bus';
import dataIsValid from '../../../common/dataIsValid';
import videoSegmentationComponent from '../../../common/video-segmentation/index.vue'; //视频分割
import AssetsLoader from '../../../libs/assetLoader.js';
import commonMixin from '../mixins/commom';
import cloneDeep from 'lodash.clonedeep';
let videoSegmentationCtor = Vue.extend(videoSegmentationComponent);
export default {
    name: 'produce-video-segmentation',
    mixins: [commonMixin],
    videoSegmentation: null,
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
        taskInfo: {
            type: Object,
            required: true,
        },
        serverTime: {
            type: Number,
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
        }
    },
    data () {
        return {
            rotateAngle: 90,
            ptimeout: 600,
            currentTaskIndex: 0,
            loading: false,
            taskItemInfo: '',
            taskItemInfoMore: {},
            loadingText: this.$t('tool_loading'),
            userStat: {},
            feedback: '',
            feedbackModal: false,
            workedTaskId: {},
            imageList: {},
            preloadTimer: [],
            labelList: [],
            assetLoader: null            
        };
    },
    computed: {
        dataId () {
            return this.taskList[this.currentTaskIndex].data.id;
        },
        userId () {
            return this.$store.state.user.userInfo.id;
        }
    },
    watch: {
        taskList (val) {            
            this.getTaskResource(this.dataId);
            this.imageList = {};
        },
        timeout (v) {
            this.ptimeout = v;
        }
    },
    mounted () {                
        Vue.nextTick(() => { // 初始化第一个任务
            this.taskInit(this.taskList[this.currentTaskIndex]);            
        });
        this.workedTaskId = {};
        this.handleKeyUp = this.handleKeyUp.bind(this);
        $(window).on('keyup', this.handleKeyUp);
        this.userStat = this.taskStat;
        EventBus.$on('task-timeout', this.setTaskTimeout);
    },
    methods: {
        //功能：预加载任务资源
        preloadTaskResource () {
            let [first, ...last] = this.taskList;
            if (last && last.length < 1) {
                return;
            }
            let requestList = last.map((task) => {
                return {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: task.data.id,
                    type: 'ori',
                };
            });
            // 作业文件加载器 接口地址, 请求数据参数信息, 一次加载几个 单个成功回调
            this.assetLoader = new AssetsLoader(api.task.resource, requestList, 1, (res, req) => {
                let taskData = res.data;
                let resource = Object.keys(taskData || {});
                let src;
                this.labelList = [];
                resource.forEach((key) => {
                    if ($(`[data-target= ${key}]`).length) {
                        src = taskData[key];
                    } else {
                        this.labelList.push({
                            [key]: taskData[key]
                        });
                    }
                });
                this.imageList[req.data_id] = taskData;                
            }, (res, req) => {});
        },
        handleKeyUp (e) {
            let target = e.target;
            // if (e.keyCode === 68 && e.ctrlKey) { // Ctrl D 提交
            //     e.preventDefault();
            //     this.submit();
            //     return;
            // }
            // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
            if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                return;
            }
            if (target.tagName.toLowerCase() === 'textarea') {
                return;
            }
            // 招行要求去掉 D 提交 待定
            if (e.keyCode === 68) { // D 提交
                this.submit();
            }
        },        
        viewFeedback () {
            this.feedbackModal = true;
        },
        setTaskTimeout () {
            this.loadingText = this.$t('tool_timed_out');
            this.loading = true;
            this.$Modal.remove();
        },
        taskInit (taskData) {
            this.getTaskResource(taskData.data.id);
        },
        //功能：加载资源
        getTaskResource (dataId) {
            if (this.imageList[dataId]) {
                let taskData = this.imageList[dataId];
                let resource = Object.keys(taskData || {});
                let src;
                this.labelList = [];                
                resource.forEach((key) => {                    
                    if (taskData[key] && taskData[key]['url']) {
                        src = taskData[key]['url'];
                    } else {
                        this.labelList.push({
                            [key]: taskData[key]
                        });
                    }
                });                
                this.executeTask(dataId, src);
                return;
            }
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
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let taskData = res.data;
                        let resource = Object.keys(taskData || {});
                        if (resource.length === 0) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                            return;
                        }
                        let src;
                        this.labelList = [];
                        resource.forEach((key) => {
                            if ($(`[data-target= ${key}]`).length) {
                                src = taskData[key];
                            } else {
                                this.labelList.push({
                                    [key]: taskData[key]
                                });
                            }
                        });
                        this.imageList[dataId] = taskData;
                        //2.开始执行任务
                        if(res.data && res.data.video_url && res.data.video_url.url){
                            //res.data.video_url 是视频的key
                            this.executeTask(dataId, res.data.video_url.url);
                        }
                        //3.第一个加载完成开始加载后边的资源
                        if (this.currentTaskIndex === 0) {
                            this.preloadTaskResource();
                        }                       
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        //功能：执行任务
        executeTask (dataId, video_url) {
            var self = this;
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                data_id: dataId,
                op: 'execute',
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                return;
            }
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        EventBus.$emit('needConfirmLeave', false);
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                        // 错误处理
                    } else {                        
                        this.ptimeout = res.data.timeout;
                        let taskData = this.taskList[this.currentTaskIndex];
                        this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskData.data.id;
                        this.taskItemInfoMore = {
                            ...this.taskInfo,
                            dataId: taskData.data.id,
                            dataName: taskData.data.name
                        };
                        let result = [];
                        if(taskData.dataResult && taskData.dataResult.result && taskData.dataResult.result.data){
                            result = taskData.dataResult.result;
                        }                        
                        this.feedback = taskData.workResult.feedback || '';
                        self.videoSegmentationInit(video_url, result.data);//初始化视频分割组件
                        EventBus.$emit('ready');
                        setTimeout(() => {
                            if (result && result.info instanceof Array) {
                                result.info.forEach((item) => {
                                    EventBus.$emit('setValue', {
                                        ...item,
                                        scope: this.$refs.templateView.$el
                                    });
                                });
                            }
                        }, 10);
                        EventBus.$emit('needConfirmLeave', true);
                    }
                },
                error: () => {
                    EventBus.$emit('needConfirmLeave', false);
                    this.loading = false;
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                }
            });
        },
        //功能：保存'视频分割'标注结果
        saveTaskResult (next = true, isDifficult = 0) {
            var self = this;
            if (this.loading) {return;}
            if (this.workedTaskId[this.dataId]) {return;}
            this.loading = true;
            let info = this.$refs.templateView.getGlobalData();            
            let vsData = self.videoSegmentation.getResultDataFun(); //获取'视频分割'组件的结果数据
            let dataArr = []; //标注结果
            if(vsData){
                if(vsData.value){
                    dataArr = cloneDeep(vsData.value);
                }
            }            
            let validValue = this.$refs.templateView.getDataIsValid();
            if (validValue && isDifficult === 0) {
                switch (validValue.value) {
                    case dataIsValid.yes: {
                        // 判断是否有表单信息
                        let InfoHasEmpty = false;
                        InfoHasEmpty = info.filter(item => {
                            return item.type !== 'data-is-valid' && item.required;
                            // required 属性可能会undefined 其布尔值为false
                        }).some((filteredItem) => {
                            return filteredItem.value.length === 0; // 表单信息 有为空的 String or Array
                        });
                        if (info.length && InfoHasEmpty) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: this.$t('tool_required_item'),
                                duration: 2,
                            });
                            this.loading = false;
                            return;
                        }
                        break;
                    }
                    case dataIsValid.no: {
                        info = [validValue];
                        break;
                    }
                    case dataIsValid.unknown : {
                        break;
                    }
                }
            }
            let result = {};
            result[this.dataId] = {
                info,
                data:dataArr,
                is_difficult: isDifficult
            };
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                data_id: this.dataId,
                result: JSON.stringify(result),
                op: 'submit',
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                this.loading = false;
                return;
            }
            this.workedTaskId[this.dataId] = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        // 错误处理
                        delete this.workedTaskId[this.dataId];
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.$Message.destroy();
                        this.$Message.success({
                            content: this.$t('tool_submit_success'),
                            duration: 2,
                        });
                        let stat = res.data[this.dataId];
                        let label_count = Number(this.userStat.label_count) + Number(stat.label_count);
                        let point_count = Number(this.userStat.point_count) + Number(stat.point_count);
                        let work_count = Number(this.userStat.work_count) + Number(stat.work_count);
                        this.userStat = {
                            label_count,
                            point_count,
                            work_count
                        };
                        if (next) {
                            if (this.currentTaskIndex === this.taskList.length - 1) {
                                this.currentTaskIndex = 0;
                                EventBus.$emit('perform-fetchTask');
                            } else {
                                this.currentTaskIndex++;
                                this.getTaskResource(this.dataId);
                            }
                        } else {
                            this.clearTask();
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    delete this.workedTaskId[this.dataId];
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        setDifficult () {
            this.saveTaskResult(true, 1);
        },
        submit () {
            this.saveTaskResult(true, 0);
        },
        submitAndExit () {
            if (this.taskList.length - this.currentTaskIndex - 1) {
                this.$Modal.confirm({
                    title: this.$t('tool_submitexit_confirmation'),
                    content: this.$t('tool_submit_exit_description', {num: this.taskList.length - this.currentTaskIndex}),
                    okText: this.$t('tool_submit_exit'),
                    cancelText: this.$t('tool_cancel'),
                    loading: true,
                    onOk: () => {
                        this.saveTaskResult(false, 0);
                        setTimeout(() => { // 连续点击时 remove会有bug
                            this.$Modal.remove();
                        }, 150);
                    }
                });
            } else {
                this.saveTaskResult(false, 0);
            }
        },
        exit () {
            this.$Modal.confirm({
                title: this.$t('tool_exit_confirmation'),
                content: this.$t('tool_exit_description', {num: this.taskList.length - this.currentTaskIndex}),
                loading: true,
                onOk: () => {
                    this.clearTask();
                }
            });
        },
        clearTask () {
            this.loading = true;
            this.assetLoader && this.assetLoader.abort();
            this.assetLoader = null;
            EventBus.$emit('needConfirmLeave', false);
            $.ajax({
                url: api.task.execute,
                type: 'POST',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    op: 'clear',
                },
                success: () => {
                    this.loading = false;
                    this.$Modal.remove();
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
                    this.loading = false;
                    this.$Modal.remove();
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
        //视频分割组件:初始化
        videoSegmentationInit(video_url, result){            
            var self = this;            
            let container = self.$refs.templateView.$el.querySelector('[data-tpl-type="task-file-placeholder"]');                                   
            //2.获取渲染'视频分割'组件的HTML标签
            if (container) {
                container = container.firstElementChild;
            }
            if (!self.videoSegmentation) {
                self.videoSegmentation = new videoSegmentationCtor({
                    parent: self
                });
                self.videoSegmentation.$mount(container);
            }            
            self.videoSegmentation.$nextTick(() => { //给'视频分割'组件传值
                self.videoSegmentation.init({
                    'video_url':video_url, 
                    'type':'video-segmentation',
                    'data':result, //结果回显
                    'is_edit':true
                });
            });
        }
    },
    beforeDestroy () {
        this.assetLoader && this.assetLoader.abort();
        EventBus.$off('task-timeout', this.setTaskTimeout);
        EventBus.$off('textFilePlaceholderReady', this.initTask);
        $(window).off('keyup', this.handleKeyUp);

        this.videoSegmentation && this.videoSegmentation.$destroy(); //调用子组件中的销毁方法
        this.videoSegmentation = null;
    },
    components: {
        'template-view': TemplateView,
        'task-progress': taskProgress,
        'task-info': TaskInfo,
        'user-stat': UserStat,
        ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue')
    }
};
</script>
<style lang="scss">
    .image-rotate {
        height: calc(100vh - 140px);
    }
</style>
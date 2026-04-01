<!--执行:数据采集-->
<template>
    <div class="data-collection-content" style="">
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
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">{{$t('tool_submit_difficult_job')}}</Button>
                <!--退出-->
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <!--提交并退出-->
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <!--提交(D)-->
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}</Button>
                <!--调试：
                <Button @click.native="showtemplateInfo">测试</Button>
                -->                
            </div>
        </div>

        <!--组件模板-->
        <template-view style="min-height: 420px"
            :config="templateInfo"
            scene="execute"
            ref="templateView">
        </template-view>

        <Spin fix v-if="loading">{{loadingText}}</Spin>

        <!--驳回原因-->
        <Modal v-model="feedbackModal"
            :title="$t('tool_reject_reason')">
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
import '@/libs/viewerjs/viewer.min.css';
import Viewer from '@/libs/viewerjs/viewer.min.js';
import dataIsValid from '../../../common/dataIsValid';
import AssetsLoader from '../../../libs/assetLoader.js';
import commonMixin from '../mixins/commom';
export default {
    name: 'produce-image-transcription',
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
        },
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
            this.taskItemInfo = this.$t('tool_job_id') + ': ' + this.dataId;           
        },
        timeout (v) {
            this.ptimeout = v;
        }        
    },
    mounted () {
        //1.初始化第一个任务
        Vue.nextTick(() => {
            this.taskInit(this.taskList[this.currentTaskIndex]);
        });
        //2.绑定任务超时和监听键盘事件
        this.workedTaskId = {};
        this.handleKeyUp = this.handleKeyUp.bind(this);
        $(window).on('keyup', this.handleKeyUp);
        this.userStat = this.taskStat;
        EventBus.$on('task-timeout', this.setTaskTimeout);
    },
    methods: {
        //功能：初始化任务
        taskInit (taskData) { 
            this.updateDataIdInfo();
            let result = this.prepareResult(taskData);
            this.feedback = taskData.workResult.feedback || '';
            setTimeout(() => {
                if (result && result.info instanceof Array) {
                    result.info.forEach((item) => {
                        EventBus.$emit('setValue', { //数据回显
                            ...item,
                            scope: this.$refs.templateView.$el
                        });
                    });
                }
            }, 10);
        },        
        //测试：调试
        showtemplateInfo(){
            console.log('');
            console.log('templateInfo：',this.templateInfo);
        },
        handleKeyUp (e) {
            let target = e.target;
            if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                return;
            }
            if (target.tagName.toLowerCase() === 'textarea') {
                return;
            }            
            if (e.keyCode === 68) { // D 提交
                this.submit();
            }
        },
        //查看驳回原因
        viewFeedback () {
            this.feedbackModal = true;
        },
        setTaskTimeout () {
            this.loadingText = this.$t('tool_timed_out');
            this.loading = true;
            this.$Modal.remove();
        },               
        //功能：保存作业结果   isDifficult=1代表挂起   isDifficult=0代表提交
        saveTaskResult (next = true, isDifficult = 0) {
            if (this.loading) {
                return;
            }
            if (this.workedTaskId[this.dataId]) {
                return;
            }
            this.loading = true;
            let info = this.$refs.templateView.getGlobalData();
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
                            this.messageShowFun({type:'warning', content:this.$t('tool_required_item'), duration:2});
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
                        this.messageShowFun({type:'error', content:res.message, duration:2});
                    } else {
                        this.messageShowFun({type:'success', content:this.$t('tool_submit_success'), duration:2});
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
                                this.taskInit(this.taskList[this.currentTaskIndex]);
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
        //更新作业id信息
        updateDataIdInfo(){ 
            this.taskItemInfo = this.$t('tool_job_id') + ': ' + this.dataId;
            this.taskItemInfoMore = {
                ...this.taskInfo,
                dataId: this.dataId,
                dataName: ''
            };
        },
        //挂起
        setDifficult () {
            this.saveTaskResult(true, 1);
        },
        //提交(D)
        submit () {
            this.saveTaskResult(true, 0);
        },
        //提交并退出
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
        //退出
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
                    this.loading = false;
                    this.$Modal.remove();
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
        /* 功能：吐丝提示
        this.messageShowFun({type:'', content:'', duration:''});
        */
        messageShowFun(params){
            this.$Message.destroy();
            let _duration = params.duration || 2;
            let _content = params.content || '';
            if(params.type === 'success'){ //1.
                this.$Message.success({content: _content, duration: _duration});
            }else if(params.type === 'warning'){ //2.
                this.$Message.warning({content: _content, duration: _duration});
            }else if(params.type === 'error'){ //3.
                this.$Message.error({content: _content, duration: _duration});
            }else if(params.type === 'info'){ //4.
                this.$Message.info({content: _content, duration: _duration});  
            }else{ //5.
                this.$Message.info({content: _content, duration: _duration});
            }
        }
    },
    beforeDestroy () {
        this.assetLoader && this.assetLoader.abort();
        EventBus.$off('task-timeout', this.setTaskTimeout);
        EventBus.$off('textFilePlaceholderReady', this.initTask);
        $(window).off('keyup', this.handleKeyUp);
    },
    components: {
        'template-view': TemplateView,
        'task-progress': taskProgress,
        'task-info': TaskInfo,
        'user-stat': UserStat,
        ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue'),
    }
};
</script>
<style lang="scss">
    .image-rotate {
        height: calc(100vh - 140px);
    }
</style>
<style>
.data-collection-content{
    position:relative; height: 100%;
}
</style>
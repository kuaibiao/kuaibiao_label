<!--不画框的图片转录 还可用于 单个图片的判断-->
<template>
    <div class="" style="position:relative; height: 100%;">
        <div class="task-header">
            <user-stat :userStat = "userStat" />
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo = "taskItemInfoMore" />
            </Poptip>
            <div class="task-btn-group">
                <task-progress
                        :total="taskList.length"
                        :current="currentTaskIndex"
                        :timeout="ptimeout"
                ></task-progress>
                <Button class="btn-1" v-if="feedback.trim() !== ''" type="error" size="small" @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <!--挂起-->
                <Button class="btn-2" type="warning" size="small" @click.native="setDifficult" :loading="loading">{{$t('tool_submit_difficult_job')}}</Button>
                <!--退出-->
                <Button class="btn-3" type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <!--提交并退出-->
                <Button class="btn-4" type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <!--提交(D)-->
                <Button class="btn-5" type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}</Button>
                <!--
                <Button @click.native="showtemplateInfo">测试</Button>
                -->
            </div>
        </div>
        <div class="button-tools">
            {{$t('tool_rotation_angle')}} <InputNumber v-model="rotateAngle" :min = "0" :max="180" :step = "1"></InputNumber>
            <Button @click="rotateLeft"> {{$t('tool_left_rotate')}}</Button>
            <Button @click="rotateRight"> {{$t('tool_right_rotate')}}</Button>
            <Button @click="rotateOrigin"> {{$t('tool_diaplasis')}} </Button>
            <div class="label-list" style="display: inline-block">
                <Tag color="primary"
                     v-for="(label ,index) in labelList"
                     :key="index"
                >{{Object.entries(label)[0].join(':')}}</Tag>
            </div>
        </div>
        <template-view style="min-height: 420px"
            :config="templateInfo"
            scene="execute"
            ref="templateView">
        </template-view>
        <Spin fix v-if="loading">{{loadingText}}</Spin>
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
            imageList: {},
            preloadTimer: [],
            labelList: [],
            assetLoader: null,
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
        taskList () {
            this.getTaskResource(this.dataId);
            this.imageList = {};
            // this.preloadTaskResource();
        },
        timeout (v) {
            this.ptimeout = v;
        },
    },
    mounted () {        
        Vue.nextTick(() => { // 初始化第一个任务
            this.taskInit(this.taskList[this.currentTaskIndex]);
            // this.preloadTaskResource();
        });
        this.workedTaskId = {};
        this.handleKeyUp = this.handleKeyUp.bind(this);
        $(window).on('keyup', this.handleKeyUp);
        this.userStat = this.taskStat;
        EventBus.$on('task-timeout', this.setTaskTimeout);
    },
    methods: {
        //测试：
        showtemplateInfo(){
            console.log('');
            console.log('templateInfo：',this.templateInfo);
        },
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
                        src = taskData[key].url;
                    } else {
                        this.labelList.push({
                            [key]: taskData[key]
                        });
                    }
                });
                this.imageList[req.data_id] = taskData;
                // cache image
                let image = new Image();
                image.src = src;
            }, (res, req) => {
            });
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
        rotateOrigin () {
            this.viewer && this.viewer.rotateTo(0);
        },
        rotateLeft () {
            this.viewer && this.viewer.rotate(-this.rotateAngle);
        },
        rotateRight () {
            this.viewer && this.viewer.rotate(this.rotateAngle);
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
        getTaskResource (dataId) {
            if (this.imageList[dataId]) {
                let taskData = this.imageList[dataId];
                let resource = Object.keys(taskData || {});
                let src;
                this.labelList = [];
                resource.forEach((key) => {
                    if ($(`[data-target= ${key}]`).length) {
                        src = taskData[key].url;
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
                                src = taskData[key].url;
                            } else {
                                this.labelList.push({
                                    [key]: taskData[key]
                                });
                            }
                        });
                        this.imageList[dataId] = taskData;
                        this.executeTask(dataId, src);
                        // 第一个加载完成开始加载后边的图片资源
                        if (this.currentTaskIndex === 0) {
                            this.preloadTaskResource();
                        }
                        // if (src.slice(0, 4) === 'data') { // base 64
                        //     if (/^data:image/.test(src)) { // 确定是图片
                        //         this.imageList[dataId] = taskData;
                        //         this.executeTask(dataId, src);
                        //     } else {
                        //         this.loading = false;
                        //         this.$Message.destroy();
                        //         this.$Message.error({
                        //             content: '作业资源不是图片',
                        //             duration: 2,
                        //         });
                        //     }
                        // } else { // http(s) 链接
                        //     this.imageList[dataId] = taskData;
                        //     this.executeTask(dataId, src);
                        // }
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        executeTask (dataId, image_url) {
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
                        if (this.viewer) {
                            this.viewer.destroy();
                        }
                        this.ptimeout = res.data.timeout;
                        let taskData = this.taskList[this.currentTaskIndex];
                        this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskData.data.id;
                        this.taskItemInfoMore = {
                            ...this.taskInfo,
                            dataId: taskData.data.id,
                            dataName: taskData.data.name,
                        };
                        let result = this.prepareResult(taskData);
                        this.feedback = taskData.workResult.feedback || '';
                        let height = window.innerHeight - 220;
                        height = height > 420 ? height : 420;
                        $('[data-tpl-type="task-file-placeholder"] .instance-container').height(height).html(
                            `<img class="image-rotate" width=100% src=${image_url}>`
                        );
                        this.viewer = new Viewer(document.getElementsByClassName('image-rotate')[0], {
                            inline: true,
                            button: false,
                            navbar: false,
                            toolbar: false,
                            title: false,
                            transition: false,
                            ready: () => {
                                $('.image-rotate').css('visibility', 'hidden');
                            }
                        });
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
        //功能：保存作业结果
        saveTaskResult (next = true, isDifficult = 0) { //至------520行-结束
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
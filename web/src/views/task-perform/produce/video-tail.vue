<template>
    <div class="" style="position:relative; height: 100%;">
        <div class="task-header" id="task-produce">
            <user-stat :userStat="userStat"/>
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <div class="task-btn-group">
                <task-progress
                        :total="taskList.length"
                        :current="currentTaskIndex"
                        :timeout="timeout"
                ></task-progress>
                <Button v-if="feedback.trim() !== ''" type="error" size="small"
                        @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <Button type="warning" size="small" @click.native="setDifficult">{{$t('tool_submit_difficult_job')}}</Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit">{{$t('tool_submit')}}</Button>
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        <code>{{$t('tool_space')}} </code> {{$t('tool_play_pause')}} <br>
                        <code>N </code> {{$t('tool_add_label')}} <br>
                        <code>ESC </code> {{$t('tool_cancel_label')}} <br>
                        <code>{{$t('tool_right_arrow')}}→</code> {{$t('tool_next_frame')}} <br>
                        <code>{{$t('tool_left_arrow')}}←</code> {{$t('tool_previous_frame')}} <br>
                    </div>
                </Tooltip>
            </div>

        </div>
        <template-view
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
    import 'jquery-ui';
    import '@/libs/jquery-ui/jquery-ui.css';
    import {initVideoAnnotation} from '@/libs/vatic/index.js';
    import commonMixin from '../mixins/commom';
    export default {
        name: 'produce-video-tail',
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
                currentTaskIndex: 0,
                loading: true,
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                feedback: '',
                feedbackModal: false,
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
                this.workedTaskId = [];
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            Vue.nextTick(() => {
                this.getTaskResource(this.dataId);
            });
            this.workedTaskId = [];
            this.userStat = this.taskStat;
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('submitTask', this.saveTaskResult);
            EventBus.$on('setLabel', this.setLabel);
            EventBus.$on('setDefaultLabel', this.setDefaultLabel);
            EventBus.$on('appendLabel', this.appendLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('formElementChange', this.saveAnnotationAttr);
        },
        methods: {
            // 设置标签
            setLabel (labelObj) {
                initVideoAnnotation.setAnn(labelObj);
            },
            // 设置默认标签
            setDefaultLabel (labelObj) {
                initVideoAnnotation.setDefaultLabel(labelObj);
            },
            // 追加标签
            appendLabel (labelObj) {
                initVideoAnnotation.appendLabel(labelObj);
                // console.log(JSON.stringify(labelObj, null, 2));
            },
            deleteLabel (index) {
                initVideoAnnotation.deleteLabel(index);
            },
            // 设置某一个标注对象的信息，
            saveAnnotationAttr () {
                // console.log(JSON.stringify(this.getLocalAttr(), null, 2));
            },
            // 读取模板组件里配置的局部标注数据
            getLocalAttr () {
                return this.$refs.templateView.getData();
            },
            // 读取模板组件里对整体的标注数据， 最终提交标注数据时调用
            getGlobalAttr () {
                return this.$refs.templateView.getGlobalData();
            },
            viewFeedback () {
                this.feedbackModal = true;
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.$Modal.remove();
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
                        if (res.error) {
                            this.loading = false;
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
                            let that = this;
                            let xhr = new XMLHttpRequest();
                            xhr.open('GET', resource[0][1].url, true);
                            xhr.responseType = 'blob';

                            xhr.onload = function (e) {
                                if (this.status === 200) { // this === xhr
                                    let res = e.target.response;
                                    let src = URL.createObjectURL(res);
                                    that.executeTask(dataId, src);
                                    that.loadingText = that.$t('tool_analyze');
                                }
                            };
                            xhr.onprogress = function (e) {
                                if (e.lengthComputable) {
                                    that.loadingText = that.$t('tool_loadings') + ': ' + (e.loaded / e.total * 100).toFixed(1) + ' %';
                                }
                            };
                            xhr.onerror = xhr.onabort = function () { // 错误处理
                                that.loading = false;
                                that.$Message.destroy();
                                that.$Message.error({
                                    content: that.$t('tool_resource_failed'),
                                    duration: 2,
                                });
                            };
                            xhr.send();
                        }
                    },
                    error: () => {
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_request_picture_failed'),
                            duration: 2,
                        });
                        // 错误处理
                    }
                });
            },
            executeTask (dataId, video_url) {
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
                            this.timeout = res.data.timeout;
                            let taskItemData = this.taskList[this.currentTaskIndex].data;
                            this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataId: taskItemData.id,
                                dataName: taskItemData.name,
                            };
                            let result = this.prepareResult(this.taskList[this.currentTaskIndex]);
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
                            let container = $(this.$refs.templateView.$el).find('[data-tpl-type="video-file-placeholder"] .instance-container');
                            this.initTask(
                                container,
                                video_url,
                                result.data || [],
                            );
                            // window.initTask({
                            //     EventBus,
                            //     user_id: this.userId,
                            //     server_time: this.serverTime,
                            //     draw_type: this.categoryInfo.draw_type,
                            //     photo_url: image_url,
                            //     isAudit: false,
                            //     result: result,
                            // });
                            this.loadingText = this.$t('tool_loading');
                            EventBus.$emit('ready');
                            if (result && result.info) {
                                result.info.forEach((item) => {
                                    EventBus.$emit('setValue', {
                                        ...item,
                                        scope: this.$refs.templateView.$el
                                    });
                                });
                            }
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
            setAnnotationLabel (labelObj) {
                // console.log(labelObj);
            },
            initTask (container, video_url, result) {
                initVideoAnnotation(container, video_url, result, EventBus, this.serverTime, this.userId);
            },
            saveTaskResult (next = true, isDifficult = 0) {
                if (this.loading) {
                    return;
                }
                if (~this.workedTaskId.indexOf(this.dataId)) {
                    return;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: this.dataId,
                    op: 'submit',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                    return;
                }
                let data = initVideoAnnotation.getResult();
                // console.log(data);
                let info = this.getGlobalAttr();
                if (typeof data === 'string') {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_play_video'),
                        duration: 3,
                    });
                    return;
                }
                if (data instanceof Array && data.length === 0 && isDifficult === 0) { // 标注数据为空
                    // 判断是否有表单信息
                    let InfoHasEmpty = false;
                    InfoHasEmpty = info.every((item) => {
                        return item.value.length === 0; // 表单信息 有为空的 String or Array
                    });
                    if (info.length && InfoHasEmpty) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: this.$t('tool_result_empty'),
                            duration: 3,
                        });
                        return;
                    }
                }
                let result = {};
                result[this.dataId] = {
                    data,
                    is_difficult: isDifficult
                };
                if (info.length) {
                    result[this.dataId].info = info;
                }
                reqData.result = JSON.stringify(result);
                this.loading = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
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
                            this.$Message.destroy();
                            this.$Message.success({
                                content: this.$t('tool_submit_success'),
                                duration: 1,
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
                            this.workedTaskId.push(this.dataId);
                            initVideoAnnotation.destroy();
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
            EventBus.$off('submitTask', this.saveTaskResult);
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('setLabel', this.setLabel);
            EventBus.$off('setDefaultLabel', this.setDefaultLabel);
            EventBus.$off('appendLabel', this.appendLabel);
            EventBus.$off('deleteLabel', this.deleteLabel);
            EventBus.$off('formElementChange', this.saveAnnotationAttr);
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

    .video-info {
        display: flex;
        justify-content: space-around;
        padding: 5px 2px;
        .play-status {
            color: #2b85e4;
            font-size: 1.2em;
            padding-right: 2em;
        }
    }

    .video-control {
        display: flex;
        justify-content: center;
        padding: 2px;
        .control {
            margin-left: 4px;
        }
    }

    #doodle {
        position: relative;
        z-index: 1;
        margin: 0px auto;
        min-height: 400px;
        width: 100%;
    }

    #canvas {
        z-index: 1;
    }

    #objects .bz-box {
        border: 1px solid #666;
        display: inline-block;
        margin: 0px 5px;
        padding: 4px 5px;
        border-radius: 3px;
        cursor: pointer;
        max-width: 160px;
    }

    #objects .bz-box-cur {
        background-color: #e9c1e4 !important;
        border: 1px solid #e99fdb !important;
    }

    #objects .bz-box input {
        margin: 2px;
    }

    #doodle .bbox {
        border: 1px solid #FF0000;
        position: absolute;
        z-index: 3;
        border-radius: 3px;
        box-sizing: border-box;
    }

    #doodle .bbox-cur {
        border: 2px solid yellow;
        z-index: 4;
    }

    .center-drag {
        width: 11px;
        height: 11px;
        border-radius: 50%;
        border: 1px solid rgba(255, 0, 0, .5);
        background-color: rgba(255, 255, 0, .05);
        position: absolute;
        left: 50%;
        top: 50%;
        transform: translate(-50%, -50%);
        cursor: move;
    }

    .resizable-handle {
        width: 10px;
        height: 10px;
        border: 1px solid rgba(200, 0, 0, 0.8);
        &.resizable-handle-sw {
            bottom: 0 !important;
            left: 0 !important;
        }
        &.resizable-handle-w {
            top: 50% !important;
            margin-top: -5px;
            left: 0 !important;
        }
        &.resizable-handle-nw {
            top: 0 !important;
            left: 0 !important;
        }
        &.resizable-handle-n {
            margin-left: -5px;
            left: 50% !important;
            top: 0 !important;
        }
        &.resizable-handle-ne {
            top: 0 !important;
            right: 0 !important;
        }
        &.resizable-handle-e {
            top: 50% !important;
            margin-top: -5px;
            right: 0 !important;
        }
        &.resizable-handle-se {
            bottom: 0 !important;
            right: 0 !important;
        }
        &.resizable-handle-s {
            left: 50% !important;
            margin-left: -5px;
            bottom: 0 !important;
        }
    }

    .ui-slider {
        position: relative;
        text-align: left;
        height: .8em;
        margin: 10px auto;
    }

    .ui-slider-handle {
        position: absolute;
        z-index: 2;
        width: 1.2em;
        height: 1.2em;
        cursor: default;
        -ms-touch-action: none;
        touch-action: none;
        top: -.3em;
        margin-left: -.6em;
    }

    .ui-widget.ui-widget-content {
        border: 1px solid #d3d3d3;
    }

    .ui-state-default {
        border: 1px solid #d3d3d3;
        background-color: #e6e6e6;
    }

    .ui-state-hover, .ui-state-focus {
        border: 1px solid #999999;
        background-color: #dadada;
    }

    .ui-state-active {
        border: 1px solid #aaaaaa;
        background-color: #ffffff;
    }

    .ui-state-disabled {
        opacity: .35;
    }

    .ui-corner-all {
        border-radius: 4px;
    }

    #canvas2 {
        z-index: 1;
        position: absolute;
        left: 0px;
        top: 0px;
        display: none;
    }

    .guide-box {
        position: absolute;
        left: 0px;
        top: 0px;
        z-index: 4;
        display: none;
        opacity: 0.7;
    }

    .g-x {
        background: rgb(0, 254, 255);
        pointer-events: none;
        width: 100%;
        height: 1px;
    }

    .g-y {
        pointer-events: none;
        background: rgb(0, 254, 255);
        width: 1px;
        height: 100%;
    }
</style>




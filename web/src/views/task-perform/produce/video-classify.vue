<template>
    <div class="" style="position:relative; height: 100%;">
        <div class="task-header" id="task-produce">
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
                        :timeout="timeout"
                ></task-progress>
                <Button v-if="feedback.trim() !== ''" type="error" size="small"
                        @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit')}}
                </Button>
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
    import dataIsValid from '../../../common/dataIsValid';
    import commonMixin from '../mixins/commom';

    export default {
        name: "produce-video-classify",
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
                workedTaskId: {},
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
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            this.userStat = this.taskStat;
            this.workedTaskId = {};
            EventBus.$on('task-timeout', this.setTaskTimeout);
            this.handleKeyUp = this.handleKeyUp.bind(this);
            $(window).on('keyup', this.handleKeyUp);
            this.getTaskResource(this.dataId);
        },
        methods: {
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.$Modal.remove();
            },
            handleKeyUp (e) {
                let target = e.target;
                // if (e.keyCode === 68 && e.ctrlKey) { //  ctrl + D 提交
                //     e.preventDefault();
                //     this.saveTaskResult(true, 0);
                //     return;
                // }
                // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (e.keyCode === 68) { // D 提交
                    this.saveTaskResult(true, 0);
                }
            },
            viewFeedback () {
                this.feedbackModal = true;
            },
            getTaskResource (dataId) {
                let taskItemData = this.taskList[this.currentTaskIndex].data;
                this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                this.taskItemInfoMore = {
                    ...this.taskInfo,
                    dataId: taskItemData.id,
                    dataName: taskItemData.name,
                };
                // if (this.imageList[dataId]) {
                //     this.executeTask(dataId, this.imageList[dataId]);
                //     return;
                // }
                // if (this.currentTaskIndex > 0) {
                //     clearTimeout(this.preloadTimer[this.currentTaskIndex - 1]);
                // }
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
                                this.loading = false;
                                this.$Message.destroy();
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            let file = resource[0][1];
                            this.executeTask(dataId, file.url);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            executeTask (dataId, src) {
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        data_id: dataId,
                        op: 'execute',
                    },
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
                            let result = this.prepareResult(this.taskList[this.currentTaskIndex]);
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
                            let container = $(this.$refs.templateView.$el).find('[data-tpl-type="video-file-placeholder"] .instance-container');
                            // 任务初始化
                            container.html(`
                                <video
                                    src="${src}"
                                     style="max-height: calc(100vh - 180px); margin: 0 auto; display: block; width:100%;"
                                     autoplay="autoplay"
                                     controls
                                     oncontextmenu="return false">
                                     <p>` + this.$t('tool_not_support_video_playback') + `</p>
                                 </video>`);
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
            saveTaskResult (next = true, isDifficult = 0) {
                if (this.loading) {
                    return;
                }
                if (this.workedTaskId[this.dataId]) {
                    return;
                }
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
                    is_difficult: isDifficult,
                };
                this.loading = true;
                this.workedTaskId[this.dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        data_id: this.dataId,
                        result: JSON.stringify(result),
                        op: 'submit',
                    },
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
        destroyed () {
            EventBus.$off('task-timeout', this.setTaskTimeout);
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

<style scoped>

</style>

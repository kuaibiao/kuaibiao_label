// 语音分类
<template>
    <div class="voice-transcription" style="position:relative; height: 100%;" >
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
                <Button v-if="feedback.trim() !== ''" type="error" size="small" @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}
                </Button>
            </div>
        </div>
        <template-view
                :config="templateInfo"
                scene="execute"
                ref="templateView">
        </template-view>
        <Spin fix v-if="loading">
            <Progress style="width: 300px" :percent="Math.floor($store.state.app.getBase64Process)" status="active" />
            <p>{{loadingText}}</p>
        </Spin>
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
    import EventBus from '../../../common/event-bus';
    import UserStat from '../components/user-stat.vue';
    import dataIsValid from '../../../common/dataIsValid';
    import AudioSegmentComponent from '../../../common/audio-segment/audio-segment';
    import commonMixin from '../mixins/commom';

    const AudioSegmentCtor = Vue.extend(AudioSegmentComponent);

    export default {
        name: 'produce-audio-classify',
        mixins: [commonMixin],
        audioSegment: null,
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
                ptimeout: 600,
                currentTaskIndex: 0,
                loading: false,
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                clientTime: Math.floor(new Date().valueOf() / 1000),
                isReady: false,
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
            },
            regionsListArray () {
                return Object.values(this.regionsList);
            },

        },
        watch: {
            timeout (v) {
                this.ptimeout = v;
            },
            taskList () {
                this.getTaskResource(this.dataId);
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            Vue.nextTick(() => {
                this.getTaskResource(this.dataId);
            });
            this.workedTaskId = {};
            this.userStat = this.taskStat;
            EventBus.$on('task-timeout', this.setTaskTimeout);
            this.handleKeyUp = this.handleKeyUp.bind(this);
            this.handleKeyDown = this.handleKeyDown.bind(this);
            $(window).on('keyup', this.handleKeyUp);
            $(window).on('keydown', this.handleKeyDown);
            // EventBus.$on('formElementChange', this.saveRegionInfo);
        },
        methods: {
            handleKeyUp (e) {
                let target = e.target;
                // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (e.keyCode === 68) { // 回车 D 提交
                    this.submit();
                }
            },
            handleKeyDown (e) {
                let target = e.target;
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (e.keyCode === 17) { // Ctrl
                    e.preventDefault();
                    // this.togglePlayPause();
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
            getAttrInfo () {
                return this.$refs.templateView.getData();
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
                            this.$Message.destroy();
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                        } else {
                            // let resource = Object.entries(res.data || {});
                            // if (resource.length === 0) {
                            //     this.$Message.destroy();
                            //     this.$Message.error({
                            //         content: this.$t('tool_request_failed'),
                            //         duration: 2,
                            //     });
                            //     return;
                            // }
                            // this.executeTask(this.dataId, resource[0][1]);
                            let voice = res.data.voice_url;
                            this.executeTask(this.dataId, voice);
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                        // 错误处理
                    }
                });
            },
            initAudioSegment (voice) {
                this.isReady = false;
                let mountNode = this.$refs.templateView.$el.querySelector('[data-tpl-type="audio-file-placeholder"]');
                if (mountNode) {
                    mountNode = mountNode.firstElementChild;
                }
                if (this.audioSegment) {
                    this.audioSegment.pause();
                    this.audioSegment.$destroy();
                }
                this.audioSegment = new AudioSegmentCtor({
                    parent: this
                });
                this.audioSegment.$mount(mountNode);
                this.$nextTick(() => {
                    this.audioSegment.init({
                        userId: this.userId,
                        serverTime: this.serverTime,
                        src: voice.url,
                        waveform: voice.waveform,
                        segments: [],
                        allowEditing: true,
                        isSegmentation: false
                    });
                });
                this.audioSegment.$on('ready', () => {
                    this.$store.commit('changeGetBase64Process', 0);
                    this.loading = false;
                    this.isReady = true;
                });
                this.audioSegment.$on('error', () => {
                    this.$store.commit('changeGetBase64Process', 0);
                    this.loading = false;
                    this.isReady = false;
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                });
                this.audioSegment.$on('loadProgress', (loaded) => {
                    this.$store.commit('changeGetBase64Process', loaded);
                });
            },
            executeTask (dataId, voice) {
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
                        if (res.error) {
                            EventBus.$emit('needConfirmLeave', false);
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.ptimeout = res.data.timeout;
                            let taskItemData = this.taskList[this.currentTaskIndex].data;
                            this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataId: taskItemData.id,
                                dataName: taskItemData.name,
                            };
                            let result = this.prepareResult(this.taskList[this.currentTaskIndex]);
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
                            this.initAudioSegment(voice, []);
                            EventBus.$emit('ready');
                            EventBus.$emit('needConfirmLeave', true);
                            setTimeout(() => {
                                if (result.info && result.info instanceof Array) {
                                    result.info.forEach(item => {
                                        EventBus.$emit('setValue', {
                                            ...item,
                                            scope: this.$refs.templateView.$el
                                        });
                                    });
                                }
                            }, 100);
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
                if (this.workedTaskId[this.dataId]) {
                    return;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: this.dataId,
                    op: 'submit',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
                    return;
                }
                this.audioSegment && this.audioSegment.pause();
                let result = {};
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
                result[this.dataId] = {
                    info,
                    is_difficult: isDifficult
                };
                reqData.result = JSON.stringify(result);
                this.loading = true;
                this.workedTaskId[this.dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        if (res.error) {
                            delete this.workedTaskId[this.dataId];
                            this.loading = false;
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
            },
            getTime () {
                let now = Math.floor(new Date().valueOf() / 1000);
                return +this.serverTime + (now - this.clientTime);
            }
        },
        beforeDestroy () {
            if (this.audioSegment) {
                this.audioSegment.pause();
                this.audioSegment.$destroy();
            }
            EventBus.$off('task-timeout', this.setTaskTimeout);
            // EventBus.$off('formElementChange', this.saveRegionInfo);
            $(window).off('keyup', this.handleKeyUp);
            $(window).off('keydown', this.handleKeyDown);
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
<style lang="scss" >
    .voice-transcription {
        .file-placeholder {
            background: #fff!important;
        }

    }
</style>

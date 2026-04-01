<template>
    <div class="video-audit-view">
        <div class="result-view-header">
            <Button type="primary" size="small" @click="close" icon="md-return-left">{{$t('tool_return')}}</Button>
            <div class="button-tools">
                <ButtonGroup style="margin-left: 15px;">
                    <Button @click="prevTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex > 0)">{{$t('tool_previous')}}(A)
                    </Button>
                    <Button size="small" type="info" v-if="taskListCopy.length">
                        {{ (currentIndex+1) + '/' + taskListCopy.length }}
                    </Button>
                    <Button @click="nextTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex < taskListCopy.length - 1)">
                        {{$t('tool_next')}}(S)
                    </Button>
                </ButtonGroup>
            </div>
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{ taskItemInfo }}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <task-progress
                    :total="0"
                    :current="0"
                    :timeout="timeout"
                    :noticeAble="false"
                    style="height: 48px; padding: 8px"
            ></task-progress>
            <div>
                <Button type="warning" size="small" v-if="isAudit" @click="taskSetDifficult" :loading="loading">
                    {{$t('tool_diffcult_job')}}
                </Button>
                <Button type="error" size="small" v-if="isAudit" @click="errorTask('reset')" :loading="loading">
                    {{$t('tool_q')}}
                </Button>
                <Button type="warning" size="small" @click="errorTask('reject')" :loading="loading">{{$t('tool_w')}}
                </Button>
                <Button type="info" size="small" v-if="needEdit" @click="editResult" :loading="loading">
                    {{$t('tool_modify_space')}}
                </Button>
                <Button type="primary" size="small" @click="taskPass" :loading="loading">{{$t('tool_d')}}</Button>
            </div>
        </div>
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="video"
                 ref="videoContainer"
                 style="max-height: calc(100vh - 180px);
                  margin: 0 auto;
                  display: block;
                  width:100%;
                  background-color: rgba(0,0,0,0.5);">
            </div>
            <div class="text-info">
                <ErrorTaskReasonFill
                        :type="errorTaskType"
                        v-if="errorTaskReasonShow"
                        @cancel="errorTaskReasonShow = false"
                        @confirm="setErrorTask"
                        ref="errorTaskReason"
                ></ErrorTaskReasonFill>
                <div class="scroll-content" :style="scrollContentStyle">
                    <div v-if="feedback" class="info-item-wrapper feedback">
                        <h4 class="info-item-header">{{$t('tool_rejected_quality_inspection')}}</h4>
                        <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                        <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
                    </div>
                    <div v-if="redofeedback" class="info-item-wrapper feedback">
                        <h4 class="info-item-header">{{$t('tool_Reasons_for_rejection_of_rework')}}</h4>
                        <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                        <ErrorTaskReasonShow :reason="redofeedback"></ErrorTaskReasonShow>
                    </div>
                    <text-analysis-result class="info-item-wrapper" :data="(result && result.info) || []" :index="-1"
                                          :user="workUser"></text-analysis-result>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import api from '@/api';
    import textAnalysisResult from '@/views/task-perform/components/text-analysis-result';
    import TaskInfo from '@/views/task-perform/components/task-info.vue';
    import TaskProgress from '@/views/task-perform/components/taskprogress.vue';
    import cloneDeep from 'lodash.clonedeep';
    import ErrorTaskReasonFill from '../error-task-reason-fill';
    import ErrorTaskReasonShow from '../error-task-reason-show';
    export default {
        name: "video-audit-view",
        props: {
            taskList: {
                type: Array,
                require: true,
            },
            index: {
                type: Number,
                require: true
            },
            timeout: {
                type: Number,
                require: true,
            },
            taskInfo: {
                type: Object,
                require: true,
            },
            canHandleKeyboard: {
                type: Boolean,
            },
            isAudit: {
                type: Boolean,
                default: true,
            },
            categoryView: {
                type: String,
                required: true,
            },
            needEdit: {
                type: Boolean,
                default: true,
            },
        },
        data () {
            return {
                taskListCopy: [],
                currentSrc: '',
                loading: false,
                currentIndex: 0,
                isOpen: false,
                errorTaskType: '',
                errorTaskReasonShow: false,
                isAuditAgain: false,
            };
        },
        watch: {
            taskList (newV, oldV) {
                oldV = this.taskListCopy;
                this.taskListCopy = cloneDeep(newV);
                if (!this.isOpen) {
                    return;
                }
                if (newV.length !== oldV.length) {
                    this.errorTaskReasonShow = false;
                }
                if (newV.length < oldV.length) {
                    this.currentIndex--;
                    this.currentIndex = this.currentIndex < 0 ? 0 : this.currentIndex;
                    if (newV.length > 0) {
                        this.getResource(this.currentIndex);
                    }
                } else if (newV.length > oldV.length) {
                    this.currentIndex = 0;
                    this.getResource(this.currentIndex);
                }
                if (newV.length === 0) {
                    this.currentIndex = 0;
                    this.destroyVideo();
                }
            },
        },
        computed: {
            scrollContentStyle () {
                if (this.errorTaskReasonShow) {
                    return {
                        maxHeight: window.innerHeight - 48 - 142 + 'px'
                    };
                } else {
                    return {
                        maxHeight: window.innerHeight - 48 + 'px'
                    };
                }
            },
            isImageLabel () {
                return this.categoryView === 'image_label';
            },
            dataInfo () {
                return this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].data;
            },
            taskItemInfo () {
                return this.$t('tool_job_id') + ': ' + ((this.dataInfo && this.dataInfo.id) || '');
            },
            labelList () {
                return (this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].labelList) || [];
            },
            taskItemInfoMore () {
                return {
                    ...this.taskInfo,
                    dataId: this.dataInfo && this.dataInfo.id,
                    dataName: this.dataInfo && this.dataInfo.name,
                };
            },
            feedback () {
                let ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].workResult;
                if (ret) {
                    ret = ret.feedback;
                } else {
                    ret = '';
                }
                return ret;
            },
            redofeedback () {
                let ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].lastWorkResults[0];
                if (ret) {
                    ret = ret.feedback;
                } else {
                    ret = '';
                }
                return ret;
            },
            result () {
                let ret = {};
                if (this.isAudit) {
                    let parentWorkResults = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorkResults;
                    if (Array.isArray(parentWorkResults)) { // 父分布是否是审核
                        this.isAuditAgain = parentWorkResults.length === 0 || parentWorkResults.some((task) => {
                            let result = JSON.parse(task.result || '{}');
                            return !(result.data || result.info);
                        });
                    }
                    if (this.isAuditAgain) { // 再次审核
                        ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                        if (ret) {
                            ret = ret.result || {};
                        }
                    } else {
                        ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorkResults[0];
                        if (ret) {
                            ret = JSON.parse(ret.result || '{}');
                        }
                    }
                } else {
                    ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                    if (ret) {
                        ret = ret.result || {};
                    }
                }
                return ret;
            },
            workUser () {
                let ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorks[0];
                if (ret) {
                    ret = ret.user;
                } else {
                    ret = {};
                }
                return ret;
            }
        },
        mounted () {
            this.taskListCopy = cloneDeep(this.taskList);
            this.currentIndex = this.index;
        },
        methods: {
            bindEvent () {
                this.unbindEvent();
                $(window).on('keydown', this.handleKeyDown);
                $(window).on('keyup', this.handleKeyUp);
            },
            unbindEvent () {
                $(window).off('keydown', this.handleKeyDown);
                $(window).off('keyup', this.handleKeyUp);
            },
            handleKeyDown (e) {
                let target = e.target;
                // 屏蔽输入框内的键盘事件
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (!this.canHandleKeyboard || this.loading) {
                    return;
                }
                let keyCode = e.which || e.keyCode;
                switch (keyCode) {
                    // 空格 修改
                    case 32 : {
                        e.preventDefault();
                        this.needEdit && this.editResult();
                        break;
                    }
                }
            },
            handleKeyUp (e) {
                let target = e.target;
                // 屏蔽输入框内的键盘事件
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (!this.canHandleKeyboard || this.loading) {
                    return;
                }
                if (!this.canHandleKeyboard || this.loading) { // 模板框打开的时候 才响应键盘事件
                    return;
                }
                let keyCode = e.which || e.keyCode;
                switch (keyCode) {
                    // d 通过
                    case 68 : {
                        this.taskPass();
                        break;
                    }
                    // W 驳回
                    case 87 : {
                        this.errorTask('reject');
                        break;
                    }
                    // Q 重置
                    case 81 : {
                        this.isAudit && this.errorTask('reset');
                        break;
                    }
                    // A 上一个
                    case 65 : {
                        this.prevTask();
                        break;
                    }
                    // S 下一个
                    case 83 : {
                        this.nextTask();
                        break;
                    }
                }
            },
            init (index) {
                this.currentIndex = index;
                this.isOpen = true;
                this.getResource(this.currentIndex);
                this.bindEvent();
            },
            close () {
                this.$emit('close');
                this.errorTaskReasonShow = false;
                this.isOpen = false;
                this.destroyVideo();
            },
            errorTask (type) {
                if (this.loading) return;
                this.errorTaskReasonShow = true;
                this.errorTaskType = type; // 重置
            },
            setErrorTask (type, reason) {
                if (this.loading) return;
                let dataId = this.dataInfo.id;
                switch (type) {
                    case 'reject': {
                        this.loading = true;
                        this.$emit('task-reject', dataId, reason);
                        break;
                    }
                    case 'reset': {
                        this.loading = true;
                        this.$emit('task-reset', dataId, reason);
                        break;
                    }
                }
            },
            taskPass () {
                if (this.loading) return;
                this.loading = true;
                let dataId = this.dataInfo.id;
                let parentWorkResults = this.taskListCopy[this.currentIndex].parentWorkResults;
                this.$emit('task-pass', dataId, parentWorkResults[parentWorkResults.length - 1].work_id);
            },
            taskSetDifficult () {
                if (this.loading) return;
                this.loading = true;
                let dataId = this.dataInfo.id;
                this.$emit('task-setDifficult', dataId);
            },
            prevTask () {
                if (this.taskListCopy.length > 1 && this.currentIndex > 0) {
                    this.currentIndex--;
                    this.getResource(this.currentIndex);
                    this.errorTaskReasonShow = false;
                } else {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_first_one_received_task'),
                        duration: 1,
                    });
                }
            },
            nextTask () {
                if (this.taskListCopy.length > 1 && this.currentIndex < this.taskListCopy.length - 1) {
                    this.currentIndex++;
                    this.getResource(this.currentIndex);
                    this.errorTaskReasonShow = false;
                } else {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_last_one_received_task'),
                        duration: 1,
                    });
                }
            },
            editResult () {
                if (this.loading) return;
                let video = this.$refs.videoContainer.querySelector('video');
                if (video) {
                    video.pause();
                }
                this.$emit('edit', this.currentIndex);
            },
            getResource (index) {
                if (this.taskListCopy[index] && this.taskListCopy[index].videoSrc) {
                    this.currentSrc = this.taskListCopy[index].videoSrc;
                    this.updateVideoViewer();
                    return;
                }
                this.loading = true;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.dataInfo.project_id,
                        data_id: this.dataInfo.id,
                        type: 'ori',
                    },
                    success: (res) => {
                        this.loading = false;
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
                            if (this.taskListCopy[index]) {
                                this.taskListCopy[index].videoSrc = resource[0][1].url;
                            }

                            this.currentSrc = resource[0][1].url;
                            this.updateVideoViewer();
                        }
                    },
                    error: () => {
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_request_failed'),
                            duration: 2,
                        });
                    }
                });
            },
            updateVideoViewer () {
                this.$refs.videoContainer.innerHTML = `<video
                src="${this.currentSrc}"
                style="max-height: calc(100vh - 180px); margin: 0 auto; display: block; width:100%;"
                autoplay="autoplay"
                controls
                oncontextmenu="return false">
                <p>${this.$t('tool_not_support_video_playback')}</p>
               </video>`;
            },
            destroyVideo () {
                if (this.$refs.videoContainer) {
                    let video = this.$refs.videoContainer.querySelector('video');
                    if (video) {
                        video.pause();
                    }
                    this.$refs.videoContainer.innerHTML = '';
                }
            }
        },
        beforeDestroy () {
            this.destroyVideo();
            this.unbindEvent();
        },
        components: {
            textAnalysisResult,
            ErrorTaskReasonFill,
            ErrorTaskReasonShow,
            'task-info': TaskInfo,
            'task-progress': TaskProgress,
        }
    };
</script>

<style lang="scss" scoped>
    .info-item-wrapper {
        border: 1px solid #edf0f6;
        margin-bottom: 12px;

        &.feedback {
            border-color: #f92929;
            border-radius: 8px 8px 0 0;

            p {
                padding: 8px;

            }

            .info-item-header {
                padding: 2px 6px;
                color: #fff;
                border-radius: 7px 7px 0 0;
                background: #f92929;
            }
        }
    }

    .demo-spin-icon-load {
        animation: ani-demo-spin 1s linear infinite;
    }

    @keyframes ani-demo-spin {
        from {
            transform: rotate(0deg);
        }
        50% {
            transform: rotate(180deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .demo-spin-col {
        height: 100px;
        position: relative;
        border: 1px solid #eee;
    }

    .result-view-header {
        border-top: 1px solid #eee;
        height: 48px;
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    .result-view-body {
        min-height: calc(100vh - 52px);
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: space-between;

        .result-info {
            flex-basis: 220px;
            max-width: 220px;
            flex-shrink: 0;
            flex-grow: 0;
            padding: 0 2px;

            &.image-transcription-result {
                flex-basis: 320px;
            }
        }

        .text-info {
            flex-basis: 320px;
            max-width: 320px;
            flex-shrink: 0;
            flex-grow: 0;
            padding: 0 2px;

            &.image-transcription-result {
                flex-basis: 420px;
            }
        }

        .scroll-content {
            max-height: calc(100vh - 52px);
            overflow: auto;
        }

        .image-wrapper {
            background-color: #959595;
            width: 100%;
            min-height: calc(100vh - 52px);
        }

        .image-holder {
            display: none;

        }
    }
</style>

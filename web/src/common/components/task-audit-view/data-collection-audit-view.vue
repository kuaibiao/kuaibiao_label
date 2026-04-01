<!--数据采集：审核-->
<template>
    <div class="data-collection-audit-view">
        <div class="result-view-header">
            <!--返回-->
            <Button type="primary" size="small" @click="close" icon="md-return-left">{{$t('tool_return')}}</Button>
            
            <div class="button-tools">                
                <ButtonGroup style="margin-left: 15px;">
                    <!-- 上一个 -->
                    <Button @click="prevTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex > 0)">{{$t('tool_previous')}}(A)
                    </Button>
                    <!-- 进度 -->
                    <Button size="small" type="info" v-if="taskListCopy.length">
                        {{ (currentIndex+1) + '/' + taskListCopy.length }}
                    </Button>
                    <!-- 下一个 -->
                    <Button @click="nextTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex < taskListCopy.length - 1)">
                        {{$t('tool_next')}}(S)
                    </Button>
                </ButtonGroup>
            </div>

            <Poptip trigger="hover" placement="bottom" v-if="!isImageLabel">
                <div class="task-info" style="cursor:pointer;">
                    {{ taskItemInfo }}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <task-progress v-if="!isImageLabel"
                           :total="0"
                           :current="0"
                           :timeout="timeout"
                           :noticeAble="false"
                           style="height: 48px; padding: 8px"
            ></task-progress>
            <div>
                <!--挂起-->
                <Button type="warning" size="small" v-if="isAudit" @click="taskSetDifficult" :loading="loading">
                    {{$t('tool_diffcult_job')}}
                </Button>
                <!--重置(Q)-->
                <Button type="error" size="small" v-if="isAudit" @click="errorTask('reset')" :loading="loading">
                    {{$t('tool_q')}}
                </Button>
                <!--驳回(W)-->
                <Button type="warning" size="small" @click="errorTask('reject')" :loading="loading">{{$t('tool_w')}}</Button>
                <!--修改(空格) :disabled="taskListCopy[currentIndex].imageLoadError" -->
                <Button class="btn-need-edit" type="info" size="small" v-if="needEdit"  @click="editResult" :loading="loading">
                    {{$t('tool_modify_space')}}
                </Button>
                <!--通过(D)-->
                <Button type="primary" size="small" @click="taskPass" :loading="loading">{{$t('tool_d')}}</Button>
            </div>
        </div>

        <!--审核内容-->
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size="18" class="demo-spin-icon-load"></Icon>
                <div class="load-1">Loading</div>
            </Spin>
            <!--审核内容:图片-->
            <div class="image-wrapper" ref="imageContainer" style="">
                <span class='grey-tips'>{{$t('tool_audio_right_view_results')}}</span>
            </div>
            <div class="result-info" v-if="showResultList" :class="!isImageLabel ? 'image-transcription-result': '' ">
                <ImageLabelResultListView :canDelete="false"></ImageLabelResultListView>
            </div>
            <!--作业:结果-->
            <div class="text-info" :class="!isImageLabel ? 'image-transcription-result': '' ">
                <!--取消 @cancel="errorTaskReasonShow = false"-->
                <!--确定 @confirm="setErrorTask"-->
                <ErrorTaskReasonFill
                        :type="errorTaskType"
                        v-if="errorTaskReasonShow"
                        @cancel="errorTaskReasonShow = false" 
                        @confirm="setErrorTask"  
                        ref="errorTaskReason"
                ></ErrorTaskReasonFill>
                <div class="scroll-content" :style="scrollContentStyle">
                    <task-progress v-if="isImageLabel"
                                   :total="0"
                                   :current="0"
                                   :timeout="timeout"
                                   :noticeAble="false"
                                   class="info-item-wrapper"
                                   style="height: 48px; padding: 8px"
                    ></task-progress>
                    <div v-if="feedback" class="info-item-wrapper feedback">
                        <h4 class="info-item-header">{{$t('tool_rejected_quality_inspection')}}</h4>                        
                        <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
                    </div>
                    <div v-if="redofeedback" class="info-item-wrapper feedback">
                        <h4 class="info-item-header">{{$t('tool_Reasons_for_rejection_of_rework')}}</h4>                        
                        <ErrorTaskReasonShow :reason="redofeedback"></ErrorTaskReasonShow>
                    </div>
                    <div style="display: inline-block" v-if="!isImageLabel && labelList.length">
                        <Tag color="primary"
                             v-for="(label ,index) in labelList"
                             :key="index"
                        >{{Object.entries(label)[0].join(':')}}
                        </Tag>
                    </div>
                    <dataCollectionAnalysisResult class="info-item-wrapper" 
                     :data="(result && result.info) || []" 
                     :index="-1" 
                     :user="workUser" @previewFileEvent="previewFileFun"></dataCollectionAnalysisResult>
                    <task-info class="info-item-wrapper" :taskInfo="taskItemInfoMore" v-if="isImageLabel"/>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import api from '@/api';
    import dataCollectionAnalysisResult from '@/views/task-perform/components/data-collection-analysis-result';
    import TaskInfo from '@/views/task-perform/components/task-info.vue';
    import TaskProgress from '@/views/task-perform/components/taskprogress.vue';
    import cloneDeep from 'lodash.clonedeep';
    import ErrorTaskReasonFill from '../error-task-reason-fill';
    import ErrorTaskReasonShow from '../error-task-reason-show';
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import util from '@/libs/util.js';
    import EventBus from '@/common/event-bus';
    let ImageLabelInstance = null;
    export default {
        name: "data-collection-audit-view",
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
            showResultList: {
                type: Boolean,
                default: true,
            }
        },
        data () {
            return {
                taskListCopy: [],
                type: '',
                currentSrc: '',
                loading: false,
                currentIndex: 0,
                rotateAngle: 90,
                isOpen: false,
                errorTaskType: '',
                errorTaskReasonShow: false,
                isAuditAgain: false,
            };
        },
        watch: {
            taskList (newV, oldV) {
                // 1.初始化提示语
                this.initHtmlTips();
                // 2.更新数据列表
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
                    this.destroyViewer();
                } else if (newV.length > oldV.length) {
                    this.destroyViewer();
                    this.currentIndex = 0;
                }
                if (newV.length === 0) {
                    this.currentIndex = 0;
                    this.destroyViewer();
                }
            },
            currentIndex () {
                this.initHtmlTips(); //初始化提示语
            }
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
            // 功能：预览文件
            previewFileFun (data) {
                var self = this;
                if (data && data.name) {
                    if (util.geFileTypeByExt(data.name) === 'img-file') { // 图片文件
                        if (data.uri) {
                            self.viewImageFun(data.uri);
                        }
                    } else if (util.geFileTypeByExt(data.name) === 'audio-file') { // 音频文件
                        if (data.uri) {
                            var height = $(self.$refs.imageContainer).height();
                            height = parseInt(height) / 2;
                            let result = '';
                            result = result + '<div class="audio-content">';
                            result = result + '<em class="filename">' + data.name + '</em>';
                            result = result + '<audio src="' + data.uri + '" controls="controls" width="100%" height="' + height + '">';
                            // result = result + '您的浏览器不支持 audio 标签。';
                            result = result + this.$t('tool_audio_support_audio');
                            result = result + '</audio>';
                            result = result + '</div>';
                            $(self.$refs.imageContainer).html(result);
                        }
                    } else if (util.geFileTypeByExt(data.name) === 'video-file') { // 视频文件
                        if (data.uri) {
                            var height = $(self.$refs.imageContainer).height();
                            let result = '';
                            result = result + '<video src="' + data.uri + '" controls="controls" width="100%" height="' + height + '">';
                            // result = result + '您的浏览器不支持 video 标签。';
                            result = result + this.$t('tool_audio_support_audio');
                            result = result + '</video>';
                            $(self.$refs.imageContainer).html(result);
                        }
                    } else if (util.geFileTypeByExt(data.name) === 'other-file') { // 其他类型的文件
                        if (data.uri) {
                            var height = $(self.$refs.imageContainer).height();
                            let result = '';
                            result = result + '<div class="other-file-content">';
                            result = result + '<em class="filename">' + data.name + '</em>';
                            result = result + '<a href="' + data.uri + '" target="_blank" class="link-file">';
                            // result = result + '点击查看';
                            result = result + this.$t('tool_audio_click_view');
                            result = result + '</a>';
                            result = result + '</div>';
                            $(self.$refs.imageContainer).html(result);
                        }
                    }
                }
            },
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
                    // c 缩小图片
                    case 67: {
                        e.preventDefault();
                        ImageLabelInstance && ImageLabelInstance.zoom(-0.3);
                        break;
                    }
                    // v 放大图片
                    case 86: {
                        e.preventDefault();
                        ImageLabelInstance && ImageLabelInstance.zoom(0.3);
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
                    // A 上一张
                    case 65 : {
                        this.prevTask();
                        break;
                    }
                    // S 下一张
                    case 83 : {
                        this.nextTask();
                        break;
                    }
                    // B 复位
                    case 66: {
                        ImageLabelInstance && ImageLabelInstance.resetZoom();
                        break;
                    }
                }
            },
            // 初始化
            init (index) {
                var self = this;
                this.currentIndex = index;
                this.isOpen = true;
                this.destroyViewer();
                this.$nextTick(() => {
                    self.initHtmlTips(); // 初始化提示语
                });
                this.bindEvent();
            },
            // 初始化提示语
            initHtmlTips () {
                var self = this;
                this.$nextTick(() => {
                    // 请点击右侧 "预览" 查看采集结果...
                    $(self.$refs.imageContainer).html('<span class="grey-tips">' + this.$t('tool_audio_right_view_results') + '</span>');
                    self.loading = false;
                });
            },
            close () {
                this.$emit('close');
                this.errorTaskReasonShow = false;
                this.destroyViewer();
                this.isOpen = false;
            },
            // 操作   type='reject'驳回(W)    type='reset'重置(Q)
            errorTask (type) {
                if (this.loading) return;
                this.errorTaskReasonShow = true;
                this.errorTaskType = type;
            },
            setErrorTask (type, reason) {
                if (this.loading) return;
                let dataId = this.dataInfo.id;
                switch (type) {
                    case 'reject': { // 驳回(W)
                        this.loading = true;
                        this.$emit('task-reject', dataId, reason);
                        break;
                    }
                    case 'reset': { // 重置(Q)
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
                    this.destroyViewer();
                    this.currentIndex--;                    
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
                    this.destroyViewer();
                    this.currentIndex++;                    
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
                if (this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].imageLoadError) return;
                this.$emit('edit', this.currentIndex);
            },
            setImageWrapperHeight () {
                let mainHeight = window.innerHeight - 56;
                $('.image-wrapper').height(mainHeight);
            },            
            destroyViewer () {
                ImageLabelInstance && ImageLabelInstance.destroy();
                ImageLabelInstance = null;
            },
            // 功能：预览图片
            viewImageFun (url) {
                ImageLabelInstance = new window.ImageLabel({
                    viewMode: true,
                    EventBus,
                    container: this.$refs.imageContainer,
                    photo_url: url,
                    result: this.result
                });
                ImageLabelInstance.setLang(this.$store.state.app.lang);
                ImageLabelInstance.Stage.on('ready', () => {
                    ImageLabelInstance.Stage.off('ready');
                });
                ImageLabelInstance.Stage.on('image.error', () => {
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_resource_failed'),
                        duration: 1
                    });
                    ImageLabelInstance.Stage.off('image.error');
                });
            }
        },
        destroyed () {
            this.unbindEvent();
            this.destroyViewer();
        },
        components: {
            dataCollectionAnalysisResult,
            ErrorTaskReasonFill,
            ErrorTaskReasonShow,
            'task-info': TaskInfo,
            'task-progress': TaskProgress,
            ImageLabelResultListView: () =>
                import('../task-result-view/image-label-result-list-view.vue'),
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
        justify-content: flex-start;
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
        .scroll-content {max-height: calc(100vh - 80px);overflow: auto;}
        .image-wrapper {background-color: #959595;width: 100%;min-height: calc(100vh - 48px);text-align: center; position: relative;}
        .image-holder {display: none;}
    }
</style>
<style>
.image-wrapper .grey-tips {color: #fff;padding-top: 20em;display: block;}
.image-wrapper .audio-content{padding-top: 10em;text-align: center;}
.image-wrapper .audio-content em.filename{display: block;color: #fff;padding-bottom: 5px;}
.image-wrapper .other-file-content{padding-top: 10em;text-align: center;}
.image-wrapper .other-file-content em.filename{display: block;color: #fff;font-style: normal;font-size:14px;}
.image-wrapper .other-file-content a.link-file{color: #ffffff;text-decoration: underline;}
.image-wrapper .other-file-content a.link-file:visited{color: #ffffff;}
.image-wrapper .other-file-content a.link-file:hover{color: #cefefe;}
</style>
<!--图片审核-->
<template>
    <div class="image-audit-view">
        <div class="result-view-header">
            <!--返回-->
            <Button class="btn-1" type="primary" size="small" @click="close" icon="md-return-left">{{$t('tool_return')}}</Button>
            <div>
                <ButtonGroup>
                    <Button v-for="btn in viewType"
                            :type="(type === btn.type ? 'primary' : 'default')"
                            @click="getImage(btn.type, currentIndex)"
                            :key="btn.type"
                            size="small"
                    >{{ btn.text }}
                    </Button>
                </ButtonGroup>
                <Checkbox v-model="soleoMode" 
                    @on-change="changeSoloMode" 
                    ref="soloMode"
                    :disabled='isViewOriginImage'
                    v-if="isImageLabel">{{$t('tool_single_view')}}</Checkbox>
            </div>
            <div class="button-tools">
                <!-- {{$t('tool_rotation_angle')}}
                <InputNumber v-model="rotateAngle"
                             :min="0"
                             :max="180"
                             :step="1"
                             size="small"></InputNumber>
                <ButtonGroup>
                    <Button @click="rotateLeft" size="small"> {{$t('tool_left_rotate')}}</Button>
                    <Button @click="rotateRight" size="small"> {{$t('tool_right_rotate')}}</Button>
                    <Button @click="rotateOrigin" size="small"> {{$t('tool_diaplasis')}}</Button>
                </ButtonGroup> -->
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
                <Button type="warning" size="small" v-if="isAudit" @click="taskSetDifficult" :loading="loading">
                    {{$t('tool_diffcult_job')}}
                </Button>
                <Button type="error" size="small" v-if="isAudit" @click="errorTask('reset')" :loading="loading">
                    {{$t('tool_q')}}
                </Button>
                <Button type="warning" size="small" @click="errorTask('reject')" :loading="loading">{{$t('tool_w')}}
                </Button>
                <Button type="info" size="small" v-if="needEdit" 
                :disabled="taskListCopy[currentIndex] && taskListCopy[currentIndex].imageLoadError" 
                @click="editResult" :loading="loading">
                    {{$t('tool_modify_space')}}
                </Button>

                <Button type="primary" size="small" @click="taskPass" :loading="loading">{{$t('tool_d')}}</Button>
            </div>
        </div>

        <!--审核内容-->
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <!--审核内容:图片-->
            <div class="image-wrapper" ref="imageContainer" style="text-align: center; position: relative; width: 100%">
                <!--<img :src="currentSrc" class="image-holder">-->
            </div>
            <div v-if="showResultList" class="result-info" :class="!isImageLabel ? 'image-transcription-result': '' ">
                <ImageLabelResultListView :canDelete="false"></ImageLabelResultListView>
            </div>
            <!--作业:结果-->
            <div class="text-info" :class="!isImageLabel ? 'image-transcription-result': '' ">
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
                        <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                        <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
                    </div>
                    <div v-if="redofeedback" class="info-item-wrapper feedback">
                        <h4 class="info-item-header">{{$t('tool_Reasons_for_rejection_of_rework')}}</h4>
                        <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                        <ErrorTaskReasonShow :reason="redofeedback"></ErrorTaskReasonShow>
                    </div>
                    <div style="display: inline-block" v-if="!isImageLabel && labelList.length">
                        <Tag color="primary"
                             v-for="(label ,index) in labelList"
                             :key="index"
                        >{{Object.entries(label)[0].join(':')}}
                        </Tag>
                    </div>
                    <textAnalysisResult class="info-item-wrapper" 
                     :data="(result && result.info) || []" 
                     :index="-1" 
                     :user="workUser"></textAnalysisResult>
                    <task-info class="info-item-wrapper" :taskInfo="taskItemInfoMore" v-if="isImageLabel"/>
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
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import EventBus from '@/common/event-bus';
    let ImageLabelInstance = null;
    export default {
        name: "image-audit-view",
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
            },
            viewType: {
                type: Array,
                default () {
                    return [{ // 查看结果图片类型，mask图 mark图 原图
                        type: 'mark',
                        text: this.$t('tool_mark_tabs_m')
                    },
                    {
                        type: 'markWithoutLabel',
                        text: this.$t('tool_mark_no_tabs_l')
                    },
                    {
                        type: 'markIsFilled',
                        text: this.$t('too_mark_fill')
                    },
                    {
                        type: 'mask',
                        text: this.$t('tool_mark_n')
                    },
                    {
                        type: 'raw',
                        text: this.$t('tool_original_image')
                    }];
                }
            }

        },
        data () {
            return {
                taskListCopy: [],
                type: '', // mark mask raw, markWithoutLabel
                currentSrc: '',
                loading: false,
                currentIndex: 0,
                rotateAngle: 90,
                isOpen: false,
                errorTaskType: '',
                errorTaskReasonShow: false,
                isAuditAgain: false,
                soleoMode: false,
                isViewOriginImage: false
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
                    this.destroyViewer();
                    if (newV.length > 0) {
                        this.getImage(this.type, this.currentIndex);
                    }
                } else if (newV.length > oldV.length) {
                    this.destroyViewer();
                    this.currentIndex = 0;
                    this.getImage(this.type, this.currentIndex);
                }
                if (newV.length === 0) {
                    this.currentIndex = 0;
                    this.destroyViewer();
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
            this.type = this.viewType[0].type;
            // this.getResource(this.type, this.currentIndex);
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
                    // E 显示原图
                    case 69 : {
                        if (this.checkSupportedType('raw')) {
                            this.getImage('raw', this.currentIndex);
                        }
                        break;
                    }
                    // M mark 图带标签
                    case 77 : {
                        if (this.checkSupportedType('mark')) {
                            this.getImage('mark', this.currentIndex);
                        }
                        break;
                    }
                    // L mark 图隐藏标签
                    case 76 : {
                        if (this.checkSupportedType('markWithoutLabel')) {
                            this.getImage('markWithoutLabel', this.currentIndex);
                        }
                        break;
                    }
                    // N Mask
                    case 78 : {
                        if (this.checkSupportedType('mask')) {
                            this.getImage('mask', this.currentIndex);
                        }
                        break;
                    }
                    // B 复位
                    case 66: {
                        ImageLabelInstance && ImageLabelInstance.resetZoom();
                        break;
                    }
                }
            },
            checkSupportedType (type) {
                return this.viewType.some(item => item.type === type);
            },
            init (index) {
                this.currentIndex = index;
                this.isOpen = true;
                // this.setImageWrapperHeight();
                this.destroyViewer();
                this.getImage(this.type, this.currentIndex);
                this.bindEvent();
            },
            close () {
                this.$emit('close');
                this.errorTaskReasonShow = false;
                this.destroyViewer();
                this.isOpen = false;
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
                    this.destroyViewer();
                    this.currentIndex--;
                    this.getImage(this.type, this.currentIndex);
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
                    this.getImage(this.type, this.currentIndex);
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
            getResource (type, index) {
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
                            let file = resource[0][1];
                            if (this.taskListCopy[index]) {
                                this.taskListCopy[index].rawImage = file.url;
                            }

                            this.currentSrc = file.url;
                            this.updateImageViewer(type);
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
            getImage (type, index) {                
                this.type = type;                
                if (this.taskListCopy[index] && this.taskListCopy[index].rawImage) {
                    switch (type) {
                        case 'raw' : {
                            this.getRawImage(type);
                            break;
                        }
                        case 'mark' : {
                            this.getMarkImage(type);
                            break;
                        }
                        case 'markIsFilled' : {
                            this.getMarkIsFilledImage(type);
                            break;
                        }
                        case 'markWithoutLabel': {
                            this.getMarkWithoutLabelImage(type);
                            break;
                        }
                        case 'mask' : {
                            this.getMaskImage(type);
                            break;
                        }
                        default: {
                            this.getMarkImage(type);
                        }
                    }
                    this.loading = false;
                } else {
                    this.getResource(type, index);
                }
            },
            getRawImage (type) {
                if (!ImageLabelInstance) {
                    this.updateImageViewer(type);
                } else {
                    ImageLabelInstance.viewOriginImage();
                }
                this.isViewOriginImage = true;
            },
            getMarkWithoutLabelImage (type) {
                if (!ImageLabelInstance) {
                    this.updateImageViewer(type);
                } else {
                    ImageLabelInstance.viewMarkWithoutLabel();
                }
                this.isViewOriginImage = false;
            },
            getMaskImage (type) {
                if (!ImageLabelInstance) {
                    this.updateImageViewer(type);
                } else {
                    ImageLabelInstance.viewMaskWithoutBackgroundImage();
                }
                this.isViewOriginImage = false;
            },
            getMarkImage (type) {
                if (!ImageLabelInstance) {
                    this.updateImageViewer(type);
                } else {
                    ImageLabelInstance.viewMarkWithLabel();
                }
                this.isViewOriginImage = false;
            },
            getMarkIsFilledImage (type) {
                if (!ImageLabelInstance) {
                    this.updateImageViewer(type);
                } else {
                    ImageLabelInstance.viewMaskWithBackgroundImage();
                }
                this.isViewOriginImage = false;
            },
            changeSoloMode (mode) {
                // 主动将多选框失焦 不然会和快捷键响应处理 冲突
                this.$refs.soloMode.$el.querySelector('input').blur();
                if (ImageLabelInstance) {
                    ImageLabelInstance.toggleSoloMode(mode);
                }
            },
            updateImageViewer (type) {
                ImageLabelInstance = new window.ImageLabel({
                    viewMode: true,
                    EventBus,
                    container: this.$refs.imageContainer,
                    photo_url: this.taskListCopy[this.currentIndex].rawImage,
                    result: this.result,
                });
                ImageLabelInstance.setLang(this.$store.state.app.lang);
                ImageLabelInstance.Stage.on('ready', () => {
                    ImageLabelInstance.toggleSoloMode(this.soleoMode);
                    this.getImage(type, this.currentIndex);
                    ImageLabelInstance.Stage.off('ready');
                });
                ImageLabelInstance.Stage.on('image.error', () => {
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_resource_failed'),
                        duration: 1,
                    });
                    ImageLabelInstance.Stage.off('image.error');
                });
            },
            destroyViewer () {
                ImageLabelInstance && ImageLabelInstance.destroy();
                ImageLabelInstance = null;
            }
        },
        destroyed () {
            this.unbindEvent();
            this.destroyViewer();
        },
        components: {
            textAnalysisResult,
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
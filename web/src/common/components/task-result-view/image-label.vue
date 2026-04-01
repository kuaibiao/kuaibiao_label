<template>
    <div>
        <div class="result-view-header" slot="header">
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name">{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
            </div>
            <div>
                <ButtonGroup>
                    <Button v-for="btn in viewType"
                            size="small"
                            :type="(type === btn.type ? 'primary' : 'default')"
                            @click="getImage(btn.type)"
                            :key="btn.type"
                    >{{ btn.text }}
                    </Button>
                </ButtonGroup>
                <Checkbox v-model="soleoMode" 
                    @on-change="changeSoloMode"
                    ref="soloMode"
                    :disabled='isViewOriginImage'
                    v-if="showResultList">{{$t('tool_single_view')}}</Checkbox>
            </div>
            <div></div>
        </div>
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="image-wrapper" style="text-align: center; position: relative;">
                <!--<img :src="currentSrc" class="image-holder">-->
            </div>
            <div v-if="showResultList" class="result-info">
                <ImageLabelResultListView :canDelete="false"></ImageLabelResultListView>
            </div>
            <div class="text-info">
                <div style="display: inline-block">
                    <Tag color="primary"
                         v-for="(label ,index) in labelList"
                         :key="index"
                    >{{Object.entries(label)[0].join(':')}}
                    </Tag>
                </div>
                <text-analysis-result :data="result.info || []" :index="-1" :user="workUser"></text-analysis-result>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import textAnalysisResult from '@/views/task-perform/components/text-analysis-result';
    import '@/libs/tooltips/bootstrap.min.css'; // 只包含 tooltips 和 poppover
    import '@/libs/tooltips/bootstrap.min.js'; // 只包含 tooltips 和 poppover
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import EventBus from '@/common/event-bus';

    let ImageLabelInstance = null;
    export default {
        name: "image-label-result",
        props: {
            result: { // 作业结果
                type: Object,
                required: true,
            },
            projectId: { // 项目Id
                type: [String, Number],
                required: true,
            },
            dataId: { // 资源ID
                type: [String, Number],
                required: true,
            },
            dataInfo: { // 资源信息
                type: Object,
                required: true,
            },
            workUser: {
                type: Object,
                required: false,
            },
            showResultList: { // 是否显示图片标注结果
                type: Boolean,
                default: false
            },
            viewType: { // 查看结果图片类型，mask图 mark图 原图
                type: Array,
                required: false,
                default () {
                    return [{
                        type: 'mark',
                        text: this.$t('tool_mark_tabs')
                    },
                    {
                        type: 'markWithoutLabel',
                        text: this.$t('tool_mark_no_tabs')
                    },
                    {
                        type: 'markIsFilled',
                        text: this.$t('too_mark_fill')
                    },
                    {
                        type: 'mask',
                        text: this.$t('tool_mark')
                    },
                    {
                        type: 'raw',
                        text: this.$t('tool_original')
                    }];
                }
            }
        },
        data () {
            return {
                rawImageSrc: '', // 原图
                type: '', // mark mask raw, markWithoutLabel
                loading: false,
                labelList: [],
                soleoMode: false,
                isViewOriginImage: false,
            };
        },
        mounted () {
            this.type = this.viewType[0].type;
            this.getResource();
            // this.setImageWrapperHeight();
        },
        methods: {
            setImageWrapperHeight () {
                let mainHeight = $('.result-view-body').height();
                let headerHeight = $('.text-info').height();
                $('.image-wrapper').height(mainHeight - headerHeight);
            },
            changeSoloMode (mode) {
                // 主动将多选框失焦 不然会和快捷键响应处理 冲突
                this.$refs.soloMode.$el.querySelector('input').blur();
                if (ImageLabelInstance) {
                    ImageLabelInstance.toggleSoloMode(mode);
                }
            },
            getResource () {
                this.loading = true;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        data_id: this.dataId,
                        type: 'ori',
                    },
                    success: (res) => {
                        if (res.error) {
                            this.loading = false;
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let taskData = res.data;
                            let resource = Object.keys(taskData || {});
                            if (resource.length === 0) {
                                this.loading = false;
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
                                if (key === 'image_url') {
                                    src = taskData[key].url;
                                } else {
                                    this.labelList.push({
                                        [key]: taskData[key]
                                    });
                                }
                            });
                            this.rawImageSrc = src;
                            this.updateImageViewer(this.type);
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
            getImage (type) {
                this.type = type;
                // this.setImageWrapperHeight();
                switch (this.type) {
                    case 'raw' : {
                        this.getRawImage();
                        break;
                    }
                    case 'mark' : {
                        this.getMarkImage();
                        break;
                    }
                    case 'markWithoutLabel': {
                        this.getMarkWithoutLabelImage();
                        break;
                    }
                    case 'markIsFilled': {
                        this.getMarkIsFilledImage();
                        break;
                    }
                    case 'mask' : {
                        this.getMaskImage();
                        break;
                    }
                    default: {
                        this.getMarkImage();
                    }
                }
            },
            getRawImage () {
                ImageLabelInstance.viewOriginImage();
                this.isViewOriginImage = true;
            },
            getMarkWithoutLabelImage () {
                ImageLabelInstance.viewMarkWithoutLabel();
                this.isViewOriginImage = false;
            },
            getMarkIsFilledImage () {
                ImageLabelInstance.viewMaskWithBackgroundImage();
                this.isViewOriginImage = false;
            },
            getMaskImage () {
                ImageLabelInstance.viewMaskWithoutBackgroundImage();
                this.isViewOriginImage = false;
            },
            getMarkImage () {
                ImageLabelInstance.viewMarkWithLabel();
                this.isViewOriginImage = false;
            },
            updateImageViewer (type) {
                if (!ImageLabelInstance) {
                    ImageLabelInstance = new window.ImageLabel({
                        viewMode: true,
                        EventBus,
                        container: document.querySelector('.image-wrapper'),
                        photo_url: this.rawImageSrc,
                        result: this.result,
                    });
                    ImageLabelInstance.setLang(this.$store.state.app.lang);
                    ImageLabelInstance.Stage.on('ready', () => {
                        this.getImage(type);
                        this.loading = false;
                    });
                    ImageLabelInstance.Stage.on('image.error', () => {
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_resource_failed'),
                            duration: 1,
                        });
                        ImageLabelInstance.Stage.off('image.error');
                    });
                } else {
                    this.getImage(type);
                }
            }
        },
        destroyed () {
            if (ImageLabelInstance) {
                ImageLabelInstance.destroy();
                ImageLabelInstance = null;
            }
        },
        components: {
            textAnalysisResult,
            ImageLabelResultListView: () =>
                import('./image-label-result-list-view.vue'),
        }
    };
</script>

<style lang="scss" scoped>
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
        line-height: 40px;
        display: flex;
        justify-content: center;
        align-items: center;

        .data-info {
            margin-left: -30px;
            margin-right: 20px;
        }
    }

    .result-view-body {
        height: calc(100vh - 42px);
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: flex-start;

        .image-wrapper {
            width: 100%;
        }
        .result-info {
            max-height: calc(100vh - 60px);
            flex-basis: 220px;
            flex-shrink: 0;
            flex-grow: 0;
            padding: 10px;
            overflow-y: auto;
        }
        .text-info {
            max-height: calc(100vh - 60px);
            flex-basis: 320px;
            flex-shrink: 0;
            flex-grow: 0;
            padding: 10px;
            overflow-y: auto;
        }

        .image-holder {
            width: 100%;
        }
    }
</style>

<!--查看作业结果：视频分割-->
<template>
    <div>
        <div class="result-view-header" slot="header">
            <div class="data-info padding-rt">
                <!--作业ID-->
                <b>{{$t('operator_job_id')}}：</b> <span>{{dataInfo.id}}</span>
            </div>
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name">{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
            </div>
        </div>
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="image-wrapper" ref="imageWrapper" style="text-align: center; position: relative;">
                <img class="video-holder">
            </div>            
            <div class="text-info">
                <div style="display: inline-block">
                    <Tag color="primary"
                         v-for="(label ,index) in labelList"
                         :key="index"
                    >{{Object.entries(label)[0].join(':')}}
                    </Tag>
                </div>
                <videoSegmentationResult 
                :info="(result && result.info) || []" 
                :data="(result && result.data) || []" 
                :index="-1" 
                :user="workUser"></videoSegmentationResult>                
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import vsComponent from '@/common/video-segmentation/index.vue'; //视频分割:组件      
    import videoSegmentationResult from '@/views/task-perform/components/video-segmentation-result.vue';    
    import EventBus from '@/common/event-bus';
    let vsCtor = Vue.extend(vsComponent);
    let ImageLabelInstance = null;
    export default {
        name: "video-segmentation-result",
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
            showResultList: {
                type: Boolean,
                default: false
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
            this.getResource();
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
                var self = this;
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
                                if (key === 'video_url') {
                                    src = taskData[key] && taskData[key]['url'];
                                } else {
                                    this.labelList.push({
                                        [key]: taskData[key] && taskData[key]['url']
                                    });
                                }
                            });                            
                            //准备加载视频分割组件                           
                            self.videoSegmentationInit(src, this.result); //初始化:视频分割组件
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
            //视频分割组件:初始化 - 用于查看
            videoSegmentationInit(video_url, result){
                var self = this;
                let container = self.$refs.imageWrapper;
                let id = '';
                //1.结果回显处理
                let data=[];
                if(result && result.data){
                    data = result.data;
                }
                //2.获取渲染'视频分割'组件的HTML标签
                if (container) {
                    container = container.firstElementChild;
                }                                
                if (!self.vsObj) {
                    self.vsObj = new vsCtor({
                        parent: self
                    });
                    self.vsObj.$mount(container);
                }
                self.vsObj.$nextTick(() => { //给'视频分割'组件传值
                    self.loading = false;
                    let _height = $(self.$refs.imageWrapper).height();                
                    self.vsObj.init({
                        'video_url':video_url,
                        'id':id,
                        'type':'video-segmentation',
                        'data':data,
                        'audit_view_box_height':_height,
                        'is_edit':false
                    });
                });
            },
        },
        destroyed () {           
            //1.清空'视频分割组件'的相关对象
            this.vsObj && this.vsObj.$destroy(); //调用子组件中的销毁方法
            this.vsObj = null;
        },
        components: {            
           videoSegmentationResult
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
        height: calc(100vh - 52px);
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
    .padding-rt{padding-right:2em;padding-top:1px;}
</style>
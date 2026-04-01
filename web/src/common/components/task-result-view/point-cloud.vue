<template>
    <div>
        <div class="result-view-header" slot="header">
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name">{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
            </div>
        </div>
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>{{loadingText}}</div>
            </Spin>
            <div class="point-cloud" style="height: calc(100vh - 60px); position: relative;">
                <div style="height: 100%;"></div>
            </div>
            <div class="result-info">
                <text-analysis-result :data="result.info || []" :index="-1" :user="workUser"></text-analysis-result>
                <ImageLabelResultListView :canDelete="false"></ImageLabelResultListView>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import PQueue from 'p-queue';
    import textAnalysisResult from '@/views/task-perform/components/text-analysis-result';
    import PointCloudComponent from '../../point-cloud/point-cloud';
    import PointCloudSegmentComponent from '../../point-cloud/pointcloud-segment';
    let PointCloudCtor = null;
    export default {
        name: "point-cloud-result",
        pointCloud: null,
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
            categoryView: {
                type: String,
                required: true,
            }
            // showResultList: {
            //     type: Boolean,
            //     default: false
            // },
        },
        data () {
            return {
                loading: false,
                loadingText: this.$t('tool_loading'),
            };
        },
        mounted () {
            if (this.categoryView) {
                switch(this.categoryView) {
                    case '3d_pointcloud' : {
                        PointCloudCtor = Vue.extend(PointCloudComponent);
                        break;
                    }
                    case 'pointcloud_segment': {
                        PointCloudCtor = Vue.extend(PointCloudSegmentComponent);
                        break;
                    }
                }
            }
            this.getResource();
        },
        methods: {
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
                                this.$Message.destroy();
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            let data = this.result.data || [];
                            if (this.categoryView === 'pointcloud_segment') {
                                // 队列式 加载
                                let queue = new PQueue({
                                    concurrency: 2,
                                });
                                data.forEach((json, index) => {
                                    queue.add(function () {
                                            return new Promise((resolve, reject) => {
                                            $.getJSON(json.indexs).done((res) => {
                                                data[index].indexs = res;
                                                resolve({index: index, indexs: res});
                                            }).fail((res) => {
                                                reject(json.indexs + 'load error');
                                            })
                                        })
                                    })
                                });
                                queue.start();
                                let onIdle = queue.onIdle();
                                onIdle.then(() => {
                                    this.initPointCloud(taskData, data);
                                });
                            } else {
                                this.initPointCloud(taskData, data);
                            }
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

            initPointCloud (urls, result) {
                let mountNode = this.$el.querySelector('.point-cloud');
                if (mountNode) {
                    mountNode = mountNode.firstElementChild;
                }
                if (!this.pointCloud) {
                    this.pointCloud = new PointCloudCtor({
                        parent: this
                    });
                    this.pointCloud.$mount(mountNode);
                }
                let pcdUrl = urls['3d_url'].url;
                let image2dList = [];
                let cameraConfigUrl = urls['camera_config']; // 3d点云对应2d图片的相机参数
                let groundOffset = urls['groundOffset']
                let labelRangeRaidus = +urls['labelRangeRaidus'];
                if (cameraConfigUrl && typeof cameraConfigUrl.url === 'string') {
                    $.getJSON(cameraConfigUrl.url, (cameraConfig) => {
                        Object.keys(urls).map((key) => {
                            if (key.indexOf('3d_img') === 0) {
                                image2dList.push({
                                    ...cameraConfig[key],
                                    url: urls[key].url,
                                });
                            }
                        });
                        this.$nextTick(() => {
                            this.pointCloud.init({
                                allowEditing: false,
                                src: pcdUrl,
                                result,
                                image2dList,
                                groundOffset,
                                labelRangeRaidus
                            });
                        });
                    });
                } else {
                    this.$nextTick(() => {
                        this.pointCloud.init({
                            allowEditing: false,
                            src: pcdUrl,
                            result,
                            image2dList,
                            groundOffset,
                            labelRangeRaidus
                        });
                    });
                }
                this.pointCloud.$on('progress', (e) => {
                    this.loadingText = this.$t('tool_loading') + e.message.toFixed(2) + '%';
                })
                this.pointCloud.$on('ready', () => {
                    this.loading = false;
                    this.isReady = true;
                    this.loadingText = this.$t('tool_loading')
                });
                this.pointCloud.$on('error', () => {
                    this.loading = false;
                    this.isReady = false;
                    this.loadingText = this.$t('tool_loading')
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                });
            },
        },
        destroyed () {
            if (this.pointCloud) {
                this.pointCloud.$destroy();
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
        height: calc(100vh - 52px);
        width: 100%;
        display: flex;
        flex-direction: row;
        justify-content: flex-start;

        .point-cloud {
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
    }
</style>


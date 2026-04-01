<template>
    <div>
        <div class="result-view-header" slot="header">
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name" :width="400">{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
                <!--<span>{{dataInfo.id}} - {{dataInfo.name}}</span>-->
            </div>
        </div>
        <div class="result-view-content">
            <Spin v-if="loading" fix>
                <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="video-container" ref="videoContainer">

            </div>
            <div class="voice-result-wrapper">
                <resultItemAnalysis
                    :data="result.info || []"
                    :index="-1"
                    :user="workUser || {}"
                    />
            </div>
        </div>
    </div>
</template>
<script>
    import api from '@/api';
    import util from '@/libs/util';
    import resultItemAnalysis from '@/views/task-perform/components/text-analysis-result.vue';
    export default {
        name: "video-classify-result",
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
            }
        },
        data () {
            return {
                loading: false,
            };
        },
        mounted () {
            this.getTaskResource();
        },
        methods: {
            getTaskResource () {
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
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                            this.editLoading = false;
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
                            let container = $(this.$refs.videoContainer);
                            container.innerHTML = `
                                <video
                                    src="${file.url}"
                                     style="max-height: calc(100vh - 180px); margin: 0 auto; display: block; width:100%;"
                                     autoplay="autoplay"
                                     controls
                                     oncontextmenu="return false">
                                     <p>` + this.$t('tool_not_support_video_playback') + `</p>
                                 </video>`;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
        },
        components: {
            resultItemAnalysis,
        }
    };
</script>

<style scoped lang="scss">
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
</style>

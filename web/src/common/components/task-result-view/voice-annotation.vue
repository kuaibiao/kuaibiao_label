<template>
    <div>
        <div class="result-view-header" slot="header">
            <div v-if="dataInfo" class="data-info">
                <ellipsis-text :text="dataInfo.id + '--' + dataInfo.name" :width="400">{{dataInfo.id}} - {{dataInfo.name}}
                </ellipsis-text>
            </div>
        </div>
        <div class="audio-result-content" :class="categoryView">
            <Spin v-if="loading" fix>
                <Progress style="width: 300px" :percent="Math.floor($store.state.app.getBase64Process)" status="active" />
                <p>{{loadingText}}</p>
            </Spin>
            <div class="voice-container" ref="voiceContainer" :style="containerStyle">
                <div class="voice-container-placeholder"></div>
            </div>
            <div class="voice-result-wrapper">
                <resultItemAnalysis
                        :data="(result && result.info) || []"
                        :index="-1"
                        :user="workUser"
                        class="result-analysis">
                </resultItemAnalysis>
            </div>
        </div>
    </div>
</template>

<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from '@/libs/util';
    import resultItemAnalysis from '@/views/task-perform/components/text-analysis-result.vue';
    import AudioSegmentComponent from '../../audio-segment/audio-segment';
    const AudioSegmentCtor = Vue.extend(AudioSegmentComponent);
    export default {
        name: "voice-annotation-result",
        audioSegment: null,
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
            },
        },
        data () {
            return {
                loadingText: this.$t('tool_loading'),
                isReady: false,
                loading: false,
            };
        },
        computed: {
            isSegmentation () {
                return this.categoryView === 'voice_transcription';
            },
            containerStyle () {
                let style = {
                    width: 'calc(100vw - 18px)',
                };
                if (this.isSegmentation) {
                    style.width = 'calc(100vw - 340px)';
                }
                return style;
            }
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
                            let voice = res.data.voice_url;
                            this.initAudioSegment(voice, this.result.data || []);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            initAudioSegment (voice, regions) {
                this.isReady = false;
                let mountNode = this.$refs.voiceContainer;
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
                        segments: regions,
                        allowEditing: false,
                        isSegmentation: this.isSegmentation,
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
        },
        destroyed () {
            if (this.audioSegment) {
                this.audioSegment.pause();
                this.audioSegment.$destroy();
            }
        },
        components: {
            resultItemAnalysis,
        }
    };
</script>

<style lang="scss" >
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
    .audio-result-content {
        display: flex;
        flex-direction: row;
        .voice-result-wrapper {
            flex-basis: 320px;
            .result-analysis {
                width: 320px;
                max-height: calc(100vh - 42px);
                overflow: auto;
            }
        }
        &.voice_classify {
            flex-direction: column;
            .voice-result-wrapper {
                .result-analysis {
                    width: 100%;
                    max-height: 50vh;
                }
            }
        }
    }
</style>

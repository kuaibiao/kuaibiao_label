<template>
    <div class="" style="position:relative; height: 100%;">
        <Row>
            <i-col>
                <template-view
                        :config="templateInfo"
                        scene="execute"
                        ref="templateView">
                </template-view>
            </i-col>
        </Row>
        <Modal v-model="showResultModal"
               :width="550"
               :title="$t('tool_job_result')"
               footer-hide>
            <pre style="padding: 0 15px;max-height: 500px;overflow-y: auto">{{JSON.stringify(submitData, null, 2)}}</pre>
        </Modal>
    </div>
</template>
<script>
    import Vue from 'vue';
    import TemplateView from 'components/template-produce';
    import AudioSegmentComponent from '../../../common/audio-segment/audio-segment';
    import EventBus from '@/common/event-bus';
    import {AUDIO} from '../../../common/previewDefaultData';
    const AudioSegmentCtor = Vue.extend(AudioSegmentComponent);
    export default {
        audioSegment: null,
        props: {
            templateInfo: {
                type: Array,
                default: [],
            },
            categoryInfo: {
                type: Object,
                required: true,
            },
        },
        data () {
            return {
                submitData: {},
                showResultModal: false,
            };
        },
        mounted () {
            setTimeout(() => {
                this.initAudioSegment();
            }, 300);
        },
        methods: {
            initAudioSegment () {
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
                        src: AUDIO,
                        segments: [],
                        allowEditing: false,
                        isSegmentation: false
                    });
                });
            },
            getSubmitData () {
                this.submitData = {
                    info: this.$refs.templateView.getGlobalData()
                };
                this.showResultModal = true;
            }
        },
        beforeDestroy () {
        },
        components: {
            'template-view': TemplateView,
        }
    };
</script>




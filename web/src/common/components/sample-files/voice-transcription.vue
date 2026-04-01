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
            EventBus.$on('formElementChange', this.saveRegionInfo);
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
                        allowEditing: true,
                        isSegmentation: true
                    });
                });
                this.audioSegment.$on('ready', () => {
                    this.audioSegment.setDefaultAttr(this.getAttrInfo());
                });
                this.audioSegment.$on('showSegmentAttr', (attr) => {
                    this.showRegionInfo(attr);
                });
            },
            getAttrInfo () {
                return this.$refs.templateView.getData();
            },
            showRegionInfo (attr) {
                attr.forEach(item => {
                    EventBus.$emit('setValue', {
                        ...item,
                        scope: this.$refs.templateView.$el
                    });
                });
            },
            saveRegionInfo () {
                this.audioSegment.saveSegmentAttr(this.getAttrInfo());
            },
            getSubmitData () {
                let notes = this.audioSegment.getSegments().map(item => {
                    return {
                        ...item,
                        type: 'voice_transcription',
                    };
                });
                this.submitData = {
                    data: notes,
                    info: this.$refs.templateView.getGlobalData()
                };
                this.showResultModal = true;
            }
        },
        beforeDestroy () {
            EventBus.$off('formElementChange', this.saveRegionInfo);
            if (this.audioSegment) {
                this.audioSegment.pause();
                this.audioSegment.$destroy();
            }
        },
        components: {
            'template-view': TemplateView,
        }
    };
</script>




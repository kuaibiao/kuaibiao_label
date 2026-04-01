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
    import TemplateView from 'components/template-produce';
    import {VIDEO} from '../../../common/previewDefaultData';
    import EventBus from '@/common/event-bus';

    export default {
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
                this.init();
            }, 300);
        },
        methods: {
            init () {
                let container = $(this.$refs.templateView.$el)
                    .find('[data-tpl-type="video-file-placeholder"] .instance-container');
                // 任务初始化
                container.html(`
                    <video
                        src="${VIDEO}"
                         style="max-height: calc(100vh - 180px); margin: 0 auto; display: block; width:100%;"
                         autoplay="autoplay"
                         controls
                         muted
                         oncontextmenu="return false">
                         <p>` + this.$t('tool_not_support_video_playback') + `</p>
                    </video>`);
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




<template>
    <div class="" style="position:relative; height: 100%;">
        <Row>
            <i-col span="20">
                <template-view
                        :config="cloneDeep(templateInfo)"
                        scene="execute"
                        ref="templateView">
                </template-view>
            </i-col>
            <i-col span="4">
                <ImageLabelResultListView></ImageLabelResultListView>
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
    import cloneDeep from 'lodash.clonedeep';
    import '@/libs/tooltips/bootstrap.min.css'; // 只包含 tooltips 和 poppover
    import '@/libs/tooltips/bootstrap.min.js';
    import TemplateView from 'components/template-produce';
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import EventBus from '@/common/event-bus';
    import commonMixin from '../mixins/commom.js';
    import imageLabelMixin from '../mixins/imageLabelMixin.js';
    import {IMAGE} from '../../../common/previewDefaultData';
    let ImageLabelInstance = null;
    export default {
        name: 'produce-image-label',
        mixins: [commonMixin, imageLabelMixin],
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
                imageToolConfig: {
                    supportShapeType: [],
                    advanceTool: []
                },
                submitData: [],
                showResultModal: false,
            };
        },
        mounted () {
            // this.setImageToolConfig(this.imageToolConfig);
            EventBus.$on('ImageToolConfig', this.setImageToolConfig);
        },
        methods: {
            cloneDeep (target) {
                return cloneDeep(target);
            },
            setImageToolConfig (config) {
                this.imageToolConfig.supportShapeType = config.supportShapeType.toString();

                this.$nextTick(() => {
                    ImageLabelInstance = new window.ImageLabel({
                        viewMode: false,
                        EventBus,
                        container: this.$refs.templateView.$el.querySelector('[data-tpl-type="task-file-placeholder"]'),
                        draw_type: this.imageToolConfig.supportShapeType,
                        photo_url: IMAGE,
                    });
                    ImageLabelInstance.setLang(this.$store.state.app.lang);
                    EventBus.$emit('ready');
                });
            },
            getSubmitData () {
                this.submitData = {
                    data: ImageLabelInstance && ImageLabelInstance.getSubmitData(),
                    info: this.$refs.templateView.getGlobalData()
                };
                this.showResultModal = true;
            }
        },
        beforeDestroy () {
            EventBus.$off('ImageToolConfig', this.setImageToolConfig);
            ImageLabelInstance && ImageLabelInstance.destroy();
            ImageLabelInstance = null;
        },
        components: {
            'template-view': TemplateView,
            ImageLabelResultListView: () =>
                import('@/common/components/task-result-view/image-label-result-list-view.vue'),
        }
    };
</script>




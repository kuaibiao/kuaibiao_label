<template>
    <div class="" style="position:relative; height: 100%;">
        <Row>
            <i-col>
                <template-view
                        :config="cloneDeep(templateInfo)"
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
    import EventBus from '@/common/event-bus';
    import cloneDeep from 'lodash.clonedeep';
    import { TEXT } from '../../../common/previewDefaultData';

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
                submitData: [],
                showResultModal: false,
            }
        },
        mounted () {
            setTimeout(() => {
                this.init();
            }, 300);
        },
        methods: {
            cloneDeep (target) {
                return cloneDeep(target);
            },
            init () {
                let target = this.$refs.templateView.$el.querySelector('[data-tpl-type="text-file-placeholder"] .text-container');
                target.innerHTML = (`<pre style="white-space: pre-wrap;">${TEXT}</pre>`);
                EventBus.$emit('setupMarker');
            },
            getSubmitData () {
                this.submitData = {
                    info: this.$refs.templateView.getGlobalData()
                };
                this.showResultModal = true;
            }

        },
        components: {
            'template-view': TemplateView,
        }
    };
</script>




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
    </div>
</template>
<script>
    import TemplateView from 'components/template-produce';
    import cloneDeep from 'lodash.clonedeep';
    import '@/libs/viewerjs/viewer.min.css';
    import Viewer from '@/libs/viewerjs/viewer.min.js';
    import {IMAGE} from '../../../common/previewDefaultData';
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
        mounted () {
            setTimeout(() => {
                //this.init(IMAGE);
            }, 300);
        },
        methods: {
            cloneDeep (target) {
                return cloneDeep(target);
            },
            /** 
            init (image_url) {
                let height = window.innerHeight - 60 - this.$refs.templateView.$el.getBoundingClientRect().top;
                height = height > 420 ? height : 420;
                $(this.$refs.templateView.$el).find('[data-tpl-type="task-file-placeholder"] .instance-container').height(height).html(
                    `<img class="image-rotate" width=100% src=${image_url}>`
                );
                this.viewer = new Viewer(this.$refs.templateView.$el.querySelector('.image-rotate'), {
                    inline: true,
                    button: false,
                    navbar: false,
                    toolbar: false,
                    title: false,
                    transition: false,
                    ready: () => {
                        $('.image-rotate').css('visibility', 'hidden');
                    }
                });
            }
            */
        },
        beforeDestroy () {
            if (this.viewer) {
                this.viewer.destroy();
            }
        },
        components: {
            'template-view': TemplateView,
        }
    };
</script>
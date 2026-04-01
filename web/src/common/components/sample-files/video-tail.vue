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
    </div>
</template>
<script>
    import TemplateView from 'components/template-produce';
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
            };
        },
        mounted () {

        },
        methods: {
            
        },
        beforeDestroy () {
        },
        components: {
            'template-view': TemplateView,
        }
    };
</script>




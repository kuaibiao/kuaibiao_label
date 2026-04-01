// 本组件只是作为标准工具的配置,不在视觉上有体现
<template>
    <div style="display: none"></div>
</template>

<script>
    import mixin from "../mixins/template-mixin";
    import EventBus from '@/common/event-bus';
    export default {
        mixins: [mixin],
        name: "image-label-tool",
        props: {
            config: {
                type: Object,
                require: true
            },
            path: {
                type: String,
                required: false
            },
            scene: {
                type: String,
                required: true
            }
        },
        data () {
            return {
                mode: 'icon',
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        mounted () {
            this.mode = this.scene;
            EventBus.$emit('ImageToolConfig', this.config);
            EventBus.$once('ready', () => {
                EventBus.$emit('task-default-config', {rectSSize: this.config.advanceTool.rectS});
            });
        },
    };
</script>

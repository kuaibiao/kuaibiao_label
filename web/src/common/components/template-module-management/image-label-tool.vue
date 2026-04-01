<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <Icon type="ios-hammer" size="20"/>
            <span class="template-name">{{$t('template_image_label_tool')}}</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'">
            <h2 class="instance-header">{{$t('template_image_label_tool')}}</h2>
            <div> {{$t('template_shape_enable')}} :<br>
                <Tag v-for="shape in config.supportShapeType"
                     :key="shape"
                     :fade="false"
                     color="primary">
                    {{$t('operator_shape_' + shape )}}
                </Tag>
                <Tag v-if="config.supportShapeType.length === 0"
                     color="red"
                     :fade="false"
                >{{$t('template_need_add_shape')}}
                </Tag>
            </div>
        </div>
    </div>
</template>

<script>
    import mixin from '../mixins/module-mixin';
    import EventBus from '@/common/event-bus';

    export default {
        mixins: [mixin],
        name: 'image-label-tool',
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
                mode: 'icon'
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
        },
    };
</script>

<style scoped>

</style>

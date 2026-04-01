<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode=== 'icon'">
            <span class="bficonfont bf-icon-edit1"></span>
            <span class="template-name">OCR</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'">
            <h2 class="instance-header">{{config.header}}</h2>
            <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
            <div class="ocr-placeholder">
                <Input type="textarea"
                        v-model="value"
                       ref="inputRef"
                       :autosize = "{minRows:1,maxRows:6}"
                       @on-change="handlerEnter"
                       :disabled="mode!== 'execute'"/>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue';
    import EventBus from '../../event-bus';
    import mixin from "../mixins/template-mixin";
    import throttle from 'lodash.throttle';
    export default {
        mixins: [mixin],
        name: 'ocr',
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
                value: ''
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        mounted () {
            this.mode = this.scene;
            this.$nextTick(() => {
                let ref = this.$refs.inputRef;
                if (ref) {
                    ref.$el.querySelector('textarea').setAttribute('wrap', 'off');
                }
            });
            EventBus.$on('showText', this.showText);
            this.handlerEnter = throttle(this.handlerEnter, 400);
        },
        methods: {
            showText (text) {
                this.value = text;
            },
            reset () {
                this.value = '';
            },
            handlerEnter () {
                EventBus.$emit('setText', {
                    text: this.value
                }, false);
            },
        },
        destroyed () {
            EventBus.$off('showText', this.showText);
        }
    };
</script>
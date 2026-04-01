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
            <h5 class="instance-tips">{{config.tips}}</h5>
            <div class="ocr-placeholder">
                <Input v-model="value" @on-enter="handlerEnter"
                       :disabled="mode!== 'execute'"/>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-mixin';

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
        created () {
        },
        mounted () {
            this.mode = this.scene;
        },
        methods: {
            handlerEnter () {
                this.value = '';
            },
        }
    };
</script>



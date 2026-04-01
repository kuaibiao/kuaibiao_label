<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-unfold"></span>
            <span class="template-name">{{$t('template_form_select')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">
                <Icon type="ios-bookmark" size="12" color="red" v-if="config.required"></Icon>
                {{config.header}}
            </h2>
            <h5 class="instance-tips">{{config.tips}}</h5>
            <Select v-model="config.value" :multiple="config.multiple" :disabled="mode !== 'execute'">
                <Option :value="item.text" v-for="(item, index) in config.data" :key="item.text">
                </Option>
            </Select>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-mixin';

    export default {
        mixins: [mixin],
        name: 'form-select',
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
        created () {
        },
        mounted () {
            this.mode = this.scene;
        },
        methods: {}
    };
</script>


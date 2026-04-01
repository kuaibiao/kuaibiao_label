<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-inall"></span>
            <span class="template-name">{{$t('template_multi_input')}}</span>
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
            <Input type="textarea"
                   :placeholder="config.placeholder || '' "
                   v-model="config.value"
                   :disabled="mode !== 'execute'"
                   :autosize="{ minRows: 1, maxRows: config.maxRows || 3}"
                   clearable />
        </div>
    </div>
</template>
<script>
import mixin from '../mixins/module-mixin';
export default {
    name: 'multi-input',
    mixins: [mixin],
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
    created () {},
    mounted () {
        this.mode = this.scene;
    },
    methods: {}
};
</script>

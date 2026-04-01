<template>
    <div class="template-instance" :path="path" :data-id="dConfig.id" :data-tpl-type="dConfig.type">
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
                {{dConfig.header}}
            </h2>
            <h5 class="instance-tips" v-if="config.tips">{{dConfig.tips}}</h5>
            <Select v-model="dConfig.value"
                    :multiple="dConfig.multiple"
                    :clearable="!dConfig.multiple"
                    :disabled="mode !== 'execute'"
                    @on-change="handleChange"
                    @on-clear="handleChange">
                <Option :value="item.text" :label="item.text" v-for="(item) in dConfig.data" :key="item.text">
                </Option>
            </Select>
        </div>
    </div>
</template>
<script>
    import mixin from "../mixins/template-mixin";
    import EventBus from '@/common/event-bus';

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
        data() {
            return {
                mode: 'icon',
                dConfig: {}
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        mounted() {
            this.mode = this.scene;
            this.dConfig = this.config;
            EventBus.$on('ready', this.reset);
            EventBus.$on('setValue', this.set);
        },
        methods: {
            handleChange() {
                let dConfig = this.dConfig;
                let workerInfo = {
                    cBy: dConfig.cBy ? dConfig.cBy : this.userId,
                    cTime: dConfig.cTime ? dConfig.cTime : this.getCurrentTimeStampSec(),
                    mBy: this.userId,
                    mTime: this.getCurrentTimeStampSec(),
                };
                this.updateWorkerInfo(workerInfo); // 该方法在mixin里
                EventBus.$emit('formElementChange');
            },
            reset() {
                let value = '';
                this.config.data.forEach((item) => {
                    if (item.selected) {
                        value = item.text;
                    }
                });
                this.$set(this.dConfig, 'value', value);
                this.updateWorkerInfo({
                    cBy: this.userId,
                    cTime: this.getCurrentTimeStampSec(),
                    mBy: '',
                    mTime: '',
                });
            },
            set(data) {
                if (data.id === this.dConfig.id && data.scope.contains(this.$el)) {
                    this.$set(this.dConfig, 'value', data.value);
                    this.updateWorkerInfo({
                        cBy: data.cBy,
                        cTime: data.cTime,
                        mBy: data.mBy,
                        mTime: data.mTime,
                    });
                }
            }
        },
        destroyed() {
            EventBus.$off('ready', this.reset);
            EventBus.$off('setValue', this.set);
        }
    };
</script>


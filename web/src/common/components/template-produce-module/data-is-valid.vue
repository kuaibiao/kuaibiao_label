<template>
    <div class="template-instance" :path="path" :data-id="dConfig.id" :data-tpl-type="dConfig.type">
        <div class="template-info" v-if="mode=== 'icon'">
            <Icon type="md-checkbox-outline" size="20"></Icon>
            <span class="template-name">{{$t('template_data_is_valid')}}</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'">
            <h2 class="instance-header">{{dConfig.header}}</h2>
            <h5 class="instance-tips" v-if="dConfig.tips">{{dConfig.tips}}</h5>
            <RadioGroup v-model="dConfig.value" @on-change="handleChange">
                <Radio :label="item.text" v-for="(item) in dConfig.data" :key="item.text"
                       :disabled="mode!== 'execute'">
                    <span>{{item.text}} <span v-if="item.keyBoard">({{item.keyBoard | keyMap}})</span> </span>
                </Radio>
            </RadioGroup>
        </div>
    </div>
</template>

<script>
    import mixin from "../mixins/template-mixin";
    import EventBus from '@/common/event-bus';
    import encodeKeyCode from '../../encodeKeyCode';
    export default {
        mixins: [mixin],
        name: 'data-is-valid',
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
                dConfig: {}
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        mounted () {
            this.mode = this.scene;
            this.dConfig = this.config;
            this.handleKeyDown = this.handleKeyDown.bind(this);
            this.setKeyBoardEvent();
            EventBus.$on('ready', this.reset);
            EventBus.$on('setValue', this.set);
        },
        methods: {
            setKeyBoardEvent () {
                $(window).on('keydown', this.handleKeyDown);
            },
            unsetKeyBoardEvent () {
                $(window).off('keydown', this.handleKeyDown);
            },
            handleKeyDown (e) {
                let target = e.target;
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                this.dConfig.data.forEach((item) => {
                    if (item.keyBoard) {
                        // 按同一规则转换
                        let eKeyCode = encodeKeyCode(e);
                        let itemKeyCode = encodeKeyCode(item.keyBoard);
                        if (eKeyCode === itemKeyCode) {
                            e.preventDefault();
                            this.dConfig.value = item.text;
                            this.handleChange();
                        }
                    }
                });
            },
            handleChange () {
                let dConfig = this.dConfig;
                let workerInfo = {
                    cBy: dConfig.cBy ? dConfig.cBy : this.userId,
                    cTime: dConfig.cTime ? dConfig.cTime : this.getCurrentTimeStampSec(),
                    mBy: this.userId,
                    mTime: this.getCurrentTimeStampSec(),
                }
                this.updateWorkerInfo(workerInfo); // 该方法在mixin里
            },
            reset () {
                let value = '';
                this.dConfig.data.forEach((item) => {
                    if (item.checked) {
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
            set (data) {
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
        destroyed () {
            EventBus.$off('ready', this.reset);
            EventBus.$off('setValue', this.set);
            this.unsetKeyBoardEvent();
        }
    };
</script>

<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type"
         style="max-width: 360px;">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-bone"></span>
            <span class="template-name">{{$t('template_key_point_group')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">{{config.header}}</h2>
            <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
            <div class="bone-list" v-if="config.data.length">
                <Button v-for="(item, index) in config.data"
                        :type="currentKeyPointIndex === index ? 'primary' : 'default'"
                        @click.native="changeKeyPoint(index)"
                        size="small"
                        :key="index"
                >
                    {{item.name}}
                </Button>
            </div>
            <div class="bone-container">
                <img :src="currentKeyPointItem.exampleImageSrc | formatUrl" :alt="$t('tool_schematic_key_points')"
                     class="example-img"
                     v-if="currentKeyPointItem.exampleImageSrc">
                <span class="form-btn point-item"
                      v-for="(item,index) in currentKeyPointItem.point"
                      :data-step="index + 1"
                      :key="index"
                      :style="itemStyle(item.position)"
                      :data-text="item.text"
                      :class="activeIndex === index ? 'active' : ''">
                    {{index + 1}}
              </span>
            </div>
            <div class="bp-tools-btn" v-if="mode === 'execute'">
                <Button type="primary" size="small" @click="toggleIsInside">{{$t('tool_out_picture')}}</Button>
                <Button type="primary" size="small" @click="toggleVisibility">{{$t('tool_occluded')}}</Button>
                <Button type="warning" size="small" @click="backPre">{{$t('tool_back_previous_step')}}</Button>
                <Button type="warning" size="small" @click="backFirst">{{$t('tool_back_step_one')}}</Button>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from "../mixins/template-mixin";
    import EventBus from '../../event-bus';
    /* global canvasStage */
    export default {
        name: 'key-point-group',
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
                mode: 'icon',
                currentKeyPointIndex: 0,
                currentKeyPointItem: {},
                activeIndex: 0,
            };
        },
        computed: {
            keyPointTotalPointNumber () {
                return this.currentKeyPointItem.point.length;
            },
            keyPointGroupName () {
                return this.currentKeyPointItem.name;
            },
            keyPointEqualDiversionConfig () {
                return this.currentKeyPointItem.equalDiversionConfig
            }
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
            this.currentKeyPointItem = this.config.data[this.currentKeyPointIndex];
            EventBus.$on('ready', this.handleReady);
            EventBus.$on('keyPointReset', this.handleReset);
            EventBus.$on('keyPointNext', this.handleNext);
        },
        methods: {
            toggleIsInside () {
                EventBus.$emit('toggleIsInside');
            },
            toggleVisibility () {
                EventBus.$emit('togglePointVisivility');
            },
            backPre () {
                if (this.activeIndex !== 0) {
                    EventBus.$emit('backPre', 'point');
                } else {
                    return;
                }
                if (this.activeIndex > 0) {
                    this.activeIndex--;
                } else {
                    this.activeIndex = 0;
                }
                this.setDefaultLabel();
            },
            backFirst () {
                if (this.activeIndex !== 0) {
                    EventBus.$emit('backFirst', 'point', true);
                } else {
                    return;
                }
                this.activeIndex = 0;
                this.setDefaultLabel();
            },
            handleNext () {
                this.activeIndex++;
                if (this.activeIndex > this.keyPointTotalPointNumber - 1) {
                    this.activeIndex = 0;
                }
                this.setDefaultLabel();
            },
            handleReady () {
                EventBus.$emit('task-default-config', {
                    keyPointTotalPointNumber: this.keyPointTotalPointNumber,
                    keyPointGroupName: this.keyPointGroupName,
                    keyPointEqualDiversionConfig: this.keyPointEqualDiversionConfig,
                });
                this.setDefaultLabel();
            },
            handleReset () {
                this.activeIndex = 0;
                this.setDefaultLabel();
            },
            setDefaultLabel () {
                EventBus.$emit('setDefaultLabel', {
                    category: '',
                    label: this.activeIndex + 1 + '',
                    shortValue: this.config.data[this.currentKeyPointIndex].point[this.activeIndex].text,
                    color: this.config.data[this.currentKeyPointIndex].point[this.activeIndex].color || '#ffff00'
                }, true
                );
            },
            changeKeyPoint (index) {
                if (this.currentKeyPointIndex === index) return;
                if (this.activeIndex !== 0) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_complete_key_points_current_group'),
                        duration: 2,
                    });
                    return;
                }
                this.currentKeyPointIndex = index;
                this.currentKeyPointItem = this.config.data[index];
                this.activeIndex = 0;
                this.handleReady();
            },
            itemStyle: (position) => {
                if (!position) {
                    return {
                        top: 0,
                        left: 0,
                        right: 'unset'
                    };
                }
                return {
                    top: position.top * 100 + '%',
                    left: position.left * 100 + '%',
                    right: 'unset'
                };
            },
        },
        destroyed () {
            EventBus.$off('ready', this.handleReady);
            EventBus.$off('keyPointReset', this.handleReset);
            EventBus.$off('keyPointNext', this.handleNext);
        }
    };
</script>
<style scoped lang="scss">
    .bone-list {
        margin: 6px;
        .ivu-btn {
            margin-right: 5px;
        }
    }

    .bone-container {
        position: relative;
        max-width: 80%;
        min-width: 200px;
        .example-img {
            width: 100%;
            display: block;
        }
        .point-item {
            position: absolute;
            height: 16px;
            font-size: 12px;
            color: #fff;
            width: auto;
            min-width: 14px !important;
            max-width: 26px !important;
            border: 1px solid #666;
            border-radius: 4px;
            text-align: center;
            background-color: #4fa76a;
            line-height: 12px;
            padding: 1px;
            cursor: pointer;
            &:hover,
            &.active {
                background-color: #ffff09;
                color: red;
                font-weight: 700;
                opacity: 1;
                z-index: 1;
                &::before {
                    opacity: 1;
                }
            }
            &::before {
                content: attr(data-text);
                position: absolute;
                top: -17px;
                left: 50%;
                transform: translate(-50%, 0);
                max-width: 12em;
                color: red;
                background: #ff0;
                min-width: 4em;
                border-radius: 2px;
                z-index: 2;
                opacity: 0;
                pointer-events: none;
                white-space: nowrap;
                transition: opacity 0.2s;
            }
        }
    }

    .bp-tools-btn {
        margin-top: 15px;
        button {
            margin-bottom: 4px;
        }
    }
</style>

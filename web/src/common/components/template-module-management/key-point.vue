<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type"
         style="max-width: 360px;">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-bone"></span>
            <span class="template-name">{{$t('template_key_point')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">{{config.header}}</h2>
            <h5 class="instance-tips">{{config.tips}}</h5>
            <div class="bone-container">
                <img :src="formatUrl(config.exampleImageSrc)" :alt="$t('template_key_points')"
                     class="example-img">
                <span class="form-btn point-item"
                      v-for="(item,index) in config.data"
                      :data-step="index + 1"
                      :key="index"
                      :style="itemStyle(item.position)"
                      :data-text="item.text">
                    {{index + 1}}
              </span>
            </div>
            <div class="bp-tools-btn" v-if="mode === 'execute'">
                <Button type="primary" size="small" @click="toggleIsInside">{{$t('template_outside_the_graph')}}</Button>
                <Button type="primary" size="small" @click="toggleVisibility">{{$t('template_covered')}}</Button>
                <Button type="default" size="small" @click="backPre">{{$t('template_back_to_previous_step')}}</Button>
                <Button type="default" size="small" @click="backFirst">{{$t('template_back_to_step_one')}}</Button>
            </div>
        </div>
    </div>

</template>
<script>
    import mixin from '../mixins/module-mixin';
    import util from '@/libs/util.js';
    import api from '@/api';

    export default {
        name: 'key-point',
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
        created () {
        },
        mounted () {
            this.mode = this.scene;
        },
        methods: {
            formatUrl (url) {
                return api.staticBase + util.replaceUrl(url);
            },
            toggleIsInside () {
            },
            toggleVisibility () {
            },
            backPre () {
            },
            backFirst () {
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
            }
        }
    };
</script>
<style lang="scss" scoped>
    .bone-container {
        position: relative;
        max-width: 100%;
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
    }
</style>


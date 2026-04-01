<template>
    <div class="template-instance" :path="path" :data-id="config.id" :data-tpl-type="config.type">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-label"></span>
            <span class="template-name">{{$t('template_tag')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">{{config.header}}
                <Tooltip placement="bottom-end" :transfer="true">
                    <Icon type="ios-help-circle-outline" size="16"></Icon>
                    <div slot="content">
                        <Icon type="ios-bookmark" size="12" color="red" ></Icon>
                         {{$t('template_label_least_once')}} <br>
                        <Icon type="ios-medical" size="12" color="red"></Icon>
                        {{$t('template_tag_group_least_once')}}
                    </div>
                </Tooltip>
            </h2>
            <h5 class="instance-tips">{{config.tips}}</h5>
            <div class="label-container layout-vertical">
                <template v-if="config.deepLevel === 1">  <!--非标签组 -->
                    <div class="label-item" v-for="(item, index) in config.data"
                         :key="index"
                         :class="activeIndex === index ? 'active':''">
                        <Tag
                                type="dot"
                                :color="item.color"
                                :data-index="index"
                                :fade="false"
                                :data-label="JSON.stringify(item)"
                                @click.native="handleTagClick"
                                :class="activeIndex === index ? 'active':''"
                        >
                            <Icon type="ios-bookmark" size="12" color="red" v-if="item.isRequired === 1"></Icon>
                            {{conatLabel(item)}}
                        </Tag>
                        <img :src="formatUrl(item.exampleImageSrc)" width="96" v-if="item.exampleImageSrc"
                             class="example-image">
                    </div>
                </template>
                <template v-if="config.deepLevel !== 1 && config.tagLayoutType === 'tab'">
                    <tagLayoutTab
                            :config="config"
                            @click-tag="handleTagClick"/>
                </template>
                <template v-else>
                    <template v-if="config.deepLevel === 2"> <!--标签组  两层-->
                        <Collapse v-for="(item, index) in config.data" :key="index" accordion>
                            <Panel>
                                <div style="display:inline-block">
                                    <Icon type="ios-medical" size="12"
                                          color="red"
                                        v-if="item.tagIsRequired === 1"></Icon>
                                    {{item.text}}
                                </div>
                                <div slot="content">
                                    <div class="label-item" v-for="(subitem, subindex) in config.data[index].subData"
                                         :key="subindex"
                                         :class="( activeIndex === calcFromIndex(index) + subindex ) ? 'active':''">
                                        <Tag
                                                type="dot"
                                                :color="subitem.color"
                                                :fade="false"
                                                :data-label="JSON.stringify(subitem)"
                                                :data-index="calcFromIndex(index) + subindex"
                                                @click.native="handleTagClick"
                                                :class="( activeIndex === calcFromIndex(index) + subindex ) ? 'active':''"
                                        >
                                            <Icon type="ios-bookmark" size="12" color="red"
                                                  v-if="subitem.isRequired === 1"></Icon>
                                            {{conatLabel(subitem)}}
                                        </Tag>
                                        <img :src="formatUrl(subitem.exampleImageSrc)" width="96"
                                             v-if="subitem.exampleImageSrc" class="example-image">
                                    </div>
                                </div>
                            </Panel>
                        </Collapse>
                    </template>
                    <template v-if="config.deepLevel === 3">  <!--标签组 三层-->
                        <Collapse v-for="(item, index) in config.data" :key="index">
                            <Panel>
                                <div style="display:inline-block">{{item.text}}</div>
                                <div slot="content">
                                    <my-tabs :data="item.subData"
                                             @click-tag="handleTagClick"
                                             :fromIndex="calcFromIndex(index)"
                                             :activeIndex="activeIndex">
                                    </my-tabs>
                                </div>
                            </Panel>
                        </Collapse>
                    </template>
                </template>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-mixin';
    import myTabs from 'components/tabs/tabs';
    import util from '@/libs/util.js';
    import api from '@/api';

    export default {
        name: 'tag',
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
                activeIndex: 0
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
            handleTagClick (e) {
                if (this.mode !== 'execute') return;
                let index = e.target && e.target.getAttribute('data-index');
                if (!isNaN(index)) {
                    this.activeIndex = +index;
                }
            },
            conatLabel (item) {
                return item.shortValue ? item.shortValue + '-' + item.text : item.text;
            },
            calcFromIndex (index) {
                let fromIndex = 0;
                let data = this.config.data;
                for (let i = 0, l = data.length; i < l; i++) {
                    if (i < index) {
                        if (this.config.deepLevel === 3) {
                            let subData = data[i].subData;
                            subData.forEach(v => {
                                fromIndex += v.subData.length;
                            });
                        } else {
                            fromIndex += data[i].subData.length;
                        }
                    } else {
                        break;
                    }
                }
                return fromIndex;
            }
        },
        components: {
            'my-tabs': myTabs,
            tagLayoutTab: () => import('../tag-layout-tab.vue'),
        }
    };
</script>
<style lang="scss">
    .label-container {
        .ivu-collapse-item > .ivu-collapse-header {
            padding-left: 16px;
            height: 28px;
            line-height: 28px;
        }
        .ivu-collapse-content {
            padding: 0 8px;
            .ivu-collapse-content-box {
                padding-top: 4px;
                padding-bottom: 4px;
            }
        }
        .label-item {
            display: inline-block;
            // min-width: 32%;
            margin: 2px;
            border-radius: 4px;
            .example-image {
                display: block;
                margin: 5px auto;
            }
        }
        .ivu-tag {
            width: 100%;
            margin: 0;
            background-color: #547399 !important;
            span {
                pointer-events: none;
            }
            &.active {
                background-color: #2196f3 !important;
            }
            .ivu-tag-text {
                color: #fff;
            }
        }
    }
</style>


<template>
    <div class="template-instance" :path="path" :data-id="config.id"
         :data-tpl-type="config.type"
         :data-tpl-subtype="config.subType">
        <div class="template-info" v-if="mode === 'icon'">
            <span class="bficonfont bf-icon-label"></span>
            <span class="template-name">{{$t('template_tag')}}</span>
        </div>
        <div class="template-delete" v-if="mode === 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode !== 'icon'">
            <h2 class="instance-header">{{config.header}}
                <Tooltip placement="bottom-start" :transfer="true">
                    <Icon type="ios-help-circle-outline" size="16"></Icon>
                    <div slot="content">
                        <Icon type="ios-bookmark" size="12" color="red"></Icon>
                        {{$t('tool_all_label_once')}} <br>
                        <Icon type="ios-star" size="12" color="red"></Icon>
                        {{$t('tool_label_group_once')}}
                    </div>
                </Tooltip>
            </h2>
            <h5 class="instance-tips" v-if="config.tips">{{config.tips}}</h5>
            <!--
             <template v-if="config.subType === 'group'">
                 <div class="editor-item">
                     <h4 class="editor-header">开启标签组锁定:
                       <Checkbox v-model="config.tagGroupLock">
                           {{config.tagGroupLock ? '已开启': '已关闭'}}
                       </Checkbox>
                     </h4>
                 </div>
             </template>
             -->
            <template v-if="config.tagIsSearchAble">
                <!--当前选中对象的标签数-->
                <div class="current-shape-label-list">
                    <h4 style="font-size: 12px;color: #aaadb3; margin-bottom:6px;">
                        {{$t('tool_currently_selected_object_tags')}}[{{currentShapeLabels.length}}]</h4>
                    <Tag v-if="currentShapeLabels.length && currentShapeLabels[0].text"
                         v-for="(label , index) in currentShapeLabels"
                         :key="index"
                         type="border"
                         closable
                         :fade="iFalse"
                         @on-close='deleteLabel(label, index)'
                    > {{formatCurrentLabel(label)}}
                    </Tag>
                </div>
                <!--搜索标签-->
                <div class="tag-search-wrapper">
                    <Input
                            :placeholder="$t('tool_search_tag')"
                            clearable
                            size="small"
                            v-model="keyWords"
                            @on-change="searchTag"
                    />
                    <div class="search-result-list">
                        <div class="label-item" v-for="(item, index) in searchResult"
                             :key="index">
                            <Tag
                                    type="dot"
                                    :color="item.color"
                                    :fade="false"
                                    :data-label="JSON.stringify(item)"
                                    :data-category="item.category"
                                    :data-tag-is-unique="item.tagIsUnique"
                                    :data-global-tag-is-unique="config.tagIsUnique"
                                    @click.native="handleTagClick"
                                    :class="{used: checkLabelIsUsed(item.category + '-' + item.text)}"
                            >
                                <Icon type="ios-bookmark" size="12" color="red" v-if="item.isRequired === 1"></Icon>
                                {{config.subType === 'single' ? conatLabel(item) : (item.category + conatLabel(item))}}
                            </Tag>
                            <img :src="item.exampleImageSrc | formatUrl"
                                 width="96" v-if="item.exampleImageSrc" class="example-image"/>
                        </div>
                    </div>
                </div>
            </template>

            <!--标签列表-->
            <div class="label-container layout-vertical">
                <!--非标签组-->
                <template v-if="config.deepLevel === 1">
                    <h5>
                        <Icon type="ios-star" size="12"
                              color="red"
                              v-if="config.tagIsRequired === 1"></Icon>
                        {{$t('tool_tag_list')}}
                    </h5>
                    <div class="label-item" v-for="(item, index) in config.data"
                         :key="index"
                         :class="{ active:activeIndex === index }">
                        <Tag
                                type="dot"
                                :color="item.color"
                                :data-index="index"
                                :fade="false"
                                :data-label="JSON.stringify(item)"
                                :data-category="item.text"
                                :data-tag-is-unique="config.tagIsUnique"
                                :data-global-tag-is-unique="config.tagIsUnique"
                                :data-tag-is-required="config.tagIsRequired"
                                @click.native="handleTagClick"
                                :class="{
                                    active: activeIndex === index,
                                    used: checkLabelIsUsed(item.text + '-' + item.text)
                                }"
                        >
                            <Icon type="ios-bookmark" size="12" color="red" v-if="item.isRequired === 1"></Icon>
                            {{conatLabel(item)}}
                        </Tag>
                        <img :src="item.exampleImageSrc | formatUrl" width="96" v-if="item.exampleImageSrc"
                             class="example-image" />
                    </div>
                </template>
                <template v-if="config.deepLevel !== 1 && config.tagLayoutType === 'tab'">
                    <tagLayoutTab
                            :config="config"
                            @click-tag="handleTagClick"
                            @tag-layout-tab-ready="layoutTabReady"
                            :activeIndex="activeIndex"
                            :usedLabel="usedLabel"
                    />
                </template>
                <template v-else>
                    <template v-if="config.deepLevel === 2"> <!--标签组  两层-->
                        <Collapse v-for="(item, index) in config.data" :key="index" accordion
                                  :value="openTagGroup">
                            <Panel>
                                <div style="display:inline-block">
                                    <Icon type="ios-star" size="12"
                                          color="red"
                                          v-if="item.tagIsRequired === 1"></Icon>
                                    {{item.text}}
                                </div>
                                <div slot="content">
                                    <div class="label-item" v-for="(subitem, subindex) in item.subData"
                                         :key="subindex"
                                         :class="{active: ( activeIndex === calcFromIndex(index) + subindex )}">
                                        <Tag
                                                type="dot"
                                                :color="subitem.color"
                                                :fade="false"
                                                :data-label="JSON.stringify(subitem)"
                                                :data-category="item.text"
                                                :data-index="calcFromIndex(index) + subindex"
                                                :data-tag-is-unique="config.data[index].tagIsUnique"
                                                :data-global-tag-is-unique="config.tagIsUnique"
                                                :data-tag-is-required="item.tagIsRequired"
                                                @click.native="handleTagClick"
                                                :class="{
                                                    active:  ( activeIndex === calcFromIndex(index) + subindex ),
                                                    used: checkLabelIsUsed(item.text + '-' + subitem.text)
                                                 }"

                                        >
                                            <Icon type="ios-bookmark" size="12" color="red"
                                                  v-if="subitem.isRequired === 1">
                                            </Icon>
                                            {{conatLabel(subitem)}}
                                        </Tag>
                                        <img :src="subitem.exampleImageSrc | formatUrl" width="96"
                                             v-if="subitem.exampleImageSrc" class="example-image"/>
                                    </div>
                                </div>
                            </Panel>
                        </Collapse>
                    </template>
                    <template v-if="config.deepLevel === 3">  <!--标签组 三层-->
                        <Collapse v-for="(item, index) in config.data" :key="index" :value="openTagGroup">
                            <Panel>
                                <div style="display:inline-block">{{item.text}}</div>
                                <div slot="content">
                                    <my-tabs :data="item"
                                             @click-tag="handleTagClick"
                                             :fromIndex="calcFromIndex(index)"
                                             :activeIndex="activeIndex"
                                             :globalTagIsUnique="config.tagIsUnique"
                                             :usedLabel="usedLabel">
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
    import mixin from "../mixins/template-mixin";
    import myTabs from "../label-components/tabs";
    import EventBus from '../../event-bus';
    import throttle from 'lodash.throttle';

    export default {
        name: "tag",
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
                mode: "icon",
                activeIndex: 0,
                currentShapeLabels: [],
                iFalse: false,
                keyWords: '',
                searchResult: [],
                originData: {},
                formatData: {},
                currentLabel: null,
                defaultLabel: [],
                usedLabel: [], // 已经使用的标签 有 category 和 label 组成 category-label
            };
        },
        watch: {
            scene: function (scene) {
                this.mode = scene;
            }
        },
        computed: {
            openTagGroup: function () {
                return this.config.tagGroupOpen ? this.config.data.map((t, i) => {
                    return i;
                }) : '';
            },
        },
        mounted () {
            this.mode = this.scene;
            this.$set(this.config, 'tagIsSearchAble',
                typeof this.config.tagIsSearchAble !== 'undefined' ? this.config.tagIsSearchAble : true);
            this.searchTag = throttle(this.searchTag, 200); // 输入框输入值变动节流
            EventBus.$on('ready', this.reset);
            EventBus.$on('renderLabelList', this.setCurrentShapeLabels);
            EventBus.$on('usedLabelChange', this.usedLabelChange);
            this.getOriginData();
            this.getRequireTagForSingleTag();
            this.getRequireTagForGroup();
            this.reset();
        },
        methods: {
            usedLabelChange (payload) {
                this.usedLabel = payload;
            },
            checkLabelIsUsed (labelWithCategory) {
                return ~this.usedLabel.indexOf(labelWithCategory);
            },
            formatCurrentLabel (label) {
                if (this.config.subType === 'single') {
                    return [label.shortValue, label.text].filter(v => v !== '').join('-');
                } else {
                    return [label.categoryText, label.shortValue, label.text].filter(v => v !== '').join('-');
                }
            },
            getDefaultLabel () {
                let defaultLabel = [];
                let deepLevel = this.config.deepLevel;
                let globalTagIsUnique = this.config.tagIsUnique; // 0 至多一个, 1 可多个标签
                let configData = this.config.data;
                // 找出默认标签 单个标签 默认第一个, 标签组 根据标签唯一性设置 取每一组的第一个
                switch (deepLevel) {
                    case 1 : {
                        let firstLabel = configData[0]; // 单标签模式下第一个标签
                        if (firstLabel) {
                            let label = {
                                label: firstLabel.text || '',
                                category: firstLabel.text || '',
                                shortValue: firstLabel.shortValue,
                                color: firstLabel.color,
                                isRequired: firstLabel.isRequired,
                                globalTagIsUnique: this.config.tagIsUnique,
                                localTagIsUnique: this.config.tagIsUnique,
                                minWidth: firstLabel.minWidth,
                                minHeight: firstLabel.minHeight,
                                maxWidth: firstLabel.maxWidth,
                                maxHeight: firstLabel.maxHeight,
                            };
                            defaultLabel.push(label);
                        }
                        break;
                    }
                    case 2 : {
                        for (let i = 0, l = configData.length; i < l; i++) {
                            let subGroup = configData[i];
                            let firstLabel = subGroup.subData[0] || {};
                            let label = {
                                label: firstLabel.text || '',
                                category: subGroup.text || '',
                                shortValue: firstLabel.shortValue,
                                color: firstLabel.color,
                                isRequired: firstLabel.isRequired,
                                globalTagIsUnique: this.config.tagIsUnique,
                                localTagIsUnique: subGroup.tagIsUnique,
                                minWidth: firstLabel.minWidth,
                                minHeight: firstLabel.minHeight,
                                maxWidth: firstLabel.maxWidth,
                                maxHeight: firstLabel.maxHeight,
                            };
                            defaultLabel.push(label);
                            if (!globalTagIsUnique) {
                                break; // 退出for
                            }
                        }
                        break; // 退出switch
                    }
                    case 3 : {
                        for (let i = 0, l = configData.length; i < l; i++) {
                            let group = configData[i];
                            let subGroup = group.subData[0] || {}; // todo
                            let firstLabel = subGroup.subData[0] || {}; // todo
                            let label = {
                                label: firstLabel.text || '', // todo
                                category: group.text + '-' + subGroup.text,
                                shortValue: firstLabel.shortValue,
                                color: firstLabel.color,
                                isRequired: firstLabel.isRequired,
                                globalTagIsUnique: this.config.tagIsUnique,
                                localTagIsUnique: group.tagIsUnique,
                                minWidth: firstLabel.minWidth,
                                minHeight: firstLabel.minHeight,
                                maxWidth: firstLabel.maxWidth,
                                maxHeight: firstLabel.maxHeight,
                            };
                            defaultLabel.push(label);
                            if (!globalTagIsUnique) {
                                break;
                            }
                        }
                        break;
                    }
                }
                this.defaultLabel = defaultLabel;
            },
            //功能：设置当前已选的标签
            setCurrentShapeLabels (e) {
                this.currentShapeLabels = e;
            },
            checkTagGroupLock (label) {
                if (this.config.subType === 'group' && this.config.tagGroupLock && this.currentLabel) {
                    let currentCategory = this.currentLabel.category.split('-')[0];
                    let labelCategory = label.category.split('-')[0];
                    if (currentCategory !== labelCategory) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            // content: `已开启标签组锁定，目前只能使用 ${currentCategory} 分组下的标签`,
                            content: this.$t('tool_tag_group_locking', {num: currentCategory}),
                            duration: 3,
                        });
                        return true;
                    }
                }
                return false;
            },
            searchTag () {
                let originDataIds = Object.keys(this.originData);
                let hitIds = [];
                this.keyWords.split(/\s+/g).forEach((key) => { // 标签搜索bug修复
                    if (key) {
                        hitIds = [];
                        originDataIds.forEach((id) => {
                            let realText = id.split('-&-')[1];
                            if (~realText.indexOf(key)) {
                                hitIds.push(id);
                            }
                        });
                        originDataIds = hitIds;
                    }
                });
                this.searchResult = [];
                hitIds.forEach((id) => {
                    this.searchResult.push(this.originData[id]);
                });
            },
            uniqueKey (key) {
                return Math.random().toString(36).substr(2, 8) + '-&-' + key;
            },
            getRequireTagForGroup () {
                let data = this.config.data.slice();
                let requiredLabels = [];
                if (this.config.subType === 'single') {
                    if (this.config.tagIsRequired) {
                        data.forEach((v) => {
                            requiredLabels.push(v.text);
                        });
                    }
                } else {
                    data.forEach((v1) => {
                        let category = v1.text;
                        if (v1.tagIsRequired) {
                            v1.subData.forEach((v2) => {
                                if (v2.subData && v2.subData.length) {
                                    let category2 = category + '-' + v2.text;
                                    requiredLabels.push(category2);
                                }
                            });
                            requiredLabels.push(category);
                        }
                    });
                }
                if (requiredLabels.length) {
                    EventBus.$emit('requiredTagGroup', requiredLabels, this.config.subType);
                }
            },
            getRequireTagForSingleTag () {
                let data = this.config.data.slice();
                if (this.config.subType === 'single') {
                    this.formatData.subType = this.config.subType;
                    data.forEach((v) => {
                        let item = {
                            ...v,
                            category: v.text,
                        };
                        if (!this.formatData[item.category]) {
                            this.formatData[item.category] = [item];
                        } else {
                            this.formatData[item.category].push(item);
                        }
                    });
                } else {
                    data.forEach((v1) => {
                        let category = v1.text;
                        v1.subData.forEach((v2) => {
                            if (v2.subData && v2.subData.length) {
                                v2.subData.forEach((v3) => {
                                    let item = {
                                        ...v3,
                                        category: category + '-' + v2.text,
                                    };
                                    if (v2.isRequired) {
                                        if (!this.formatData[item.category]) {
                                            this.formatData[item.category] = [item];
                                        } else {
                                            this.formatData[item.category].push(item);
                                        }
                                    }
                                });
                            } else {
                                let item = {
                                    ...v2,
                                    category: category,
                                };
                                if (v2.isRequired) {
                                    if (!this.formatData[item.category]) {
                                        this.formatData[item.category] = [item];
                                    } else {
                                        this.formatData[item.category].push(item);
                                    }
                                }
                            }
                        });
                    });
                }
                this.formatData.subType = this.config.subType;
                EventBus.$emit('requiredTagForSingle', this.formatData);
            },
            getOriginData () {
                let data = this.config.data.slice();
                if (this.config.subType === 'single') {
                    data.forEach((v) => {
                        let item = {
                            ...v,
                            category: v.text,
                            tagIsUnique: this.config.tagIsUnique
                        };
                        let uniqueKey = this.uniqueKey(item.category + item.text + item.shortValue);
                        this.originData[uniqueKey] = item;
                    });
                } else {
                    data.forEach((v1) => {
                        let category = v1.text;
                        v1.subData.forEach((v2) => {
                            if (v2.subData && v2.subData.length) {
                                v2.subData.forEach((v3) => {
                                    let item = {
                                        ...v3,
                                        category: category + '-' + v2.text,
                                        tagIsUnique: v1.tagIsUnique
                                    };
                                    let uniqueKey = this.uniqueKey(item.category + item.text + item.shortValue);
                                    this.originData[uniqueKey] = item;
                                });
                            } else {
                                let item = {
                                    ...v2,
                                    category: category,
                                    tagIsUnique: v1.tagIsUnique
                                };
                                let uniqueKey = this.uniqueKey(item.category + item.text + item.shortValue);
                                this.originData[uniqueKey] = item;
                            }
                        });
                    });
                }
            },
            deleteLabel (label, index) {
                // if(this.currentShapeLabels.length === 1) {
                //   this.$Message.warning({
                //       content: "至少有一个标签",
                //       duration: 2,
                //   });
                //   return;
                // }
                this.currentShapeLabels.splice(index, 1);
                EventBus.$emit('deleteLabel', index);
                // canvasStage && canvasStage.bs_deleteLabel(label.text);
            },
            handleTagClick (e) {
                if (this.mode !== "execute") return;
                let label = this.getLabelInfo(e.target);
                if (this.checkTagGroupLock(label)) {
                    return;
                }
                this.currentLabel = {...label};
                let index = e.target && e.target.getAttribute("data-index");
                if (!isNaN(index)) {
                    this.activeIndex = +index;
                }
                // let tagIsRequired = e.target.getAttribute('data-tag-is-required');
                if (label.globalTagIsUnique === 1) {
                    // canvasStage &&  canvasStage.bs_appendLabel(this.label);
                    EventBus.$emit('appendLabel', label);
                } else {
                    EventBus.$emit('setLabel', label);
                    // canvasStage && canvasStage.bs_setLabel(label);
                }
            },
            layoutTabReady () {
                this.reset();
            },
            getLabelInfo (ele) {
                let l = JSON.parse(ele.getAttribute('data-label'));
                let category = ele.getAttribute('data-category');
                let globalTagIsUnique = +(ele.getAttribute('data-global-tag-is-unique') || 1);
                let localTagIsUnique = +(ele.getAttribute('data-tag-is-unique') || 0);
                return {
                    label: l.text,
                    category: category || '',
                    shortValue: l.shortValue,
                    color: l.color,
                    isRequired: l.isRequired,
                    globalTagIsUnique,
                    localTagIsUnique,
                    minWidth: l.minWidth,
                    minHeight: l.minHeight,
                    maxWidth: l.maxWidth,
                    maxHeight: l.maxHeight,
                };
            },
            setDefaultLabel () {
                if (this.defaultLabel.length === 0) {
                    this.getDefaultLabel();
                }
                if (this.defaultLabel.length) {
                    EventBus.$emit.apply(EventBus, ['setDefaultLabel'].concat(this.defaultLabel));
                }
                // canvasStage && canvasStage.bs_setDefaultLabel(this.defaultLabel);
                EventBus.$emit('task-default-config', {
                    polygonPointDistanceMin: this.config.pointDistanceMin,
                    pointPositionNoLimit: this.config.pointPositionNoLimit || 0,
                    pointTagShapeType: this.config.pointTagShapeType || [],
                    labelList: this.originData || {}
                });
            },
            reset () {
                this.activeIndex = 0;
                this.currentShapeLabels = [];
                this.keyWords = '';
                this.searchResult = [];
                this.setDefaultLabel();
            },
            conatLabel (item) {
                return item.shortValue ? item.shortValue + "-" + item.text : item.text;
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
        destroyed () {
            EventBus.$off('ready', this.reset);
            EventBus.$off('renderLabelList', this.setCurrentShapeLabels);
        },
        components: {
            "my-tabs": myTabs,
            tagLayoutTab: () => import('../label-components/tag-layout-tab.vue'),
        }
    };
</script>
<style lang="scss">
    .current-shape-label-list,
    .tag-search-wrapper {
        margin-bottom: 6px;
        min-height: 32px;
    }

    .label-container,
    .search-result-list {
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
            height: auto;
            line-height: 1;
            padding: 10px;
            min-height: 32px;
            position: relative;
            span {
                pointer-events: none;
            }
            &.used {
                &::before {
                    content: attr(data-used-count) '';
                    position: absolute;
                    left: 0;
                    top: 0;
                    height: 16px;
                    width: 16px;
                    padding: 1px;
                    color: #333;
                    background-image: linear-gradient(135deg, #f90 50%, transparent 50%);
                }
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

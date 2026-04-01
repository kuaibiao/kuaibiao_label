<template>
    <div>
        <h5 style="margin-bottom: 4px;">{{$t('tool_label_group_title')}}:</h5>
        <Button
                v-for="(item, index) in config.data"
                :key="index"
                :type="categoryIndex === index ? 'primary': 'default' "
                size="small"
                @click.native="changeCategory(index)"
                style="margin: 1px;"
        >{{item.text}}</Button>
        <h5 style="margin-bottom: 4px;">{{$t('tool_selected_categories_labels')}}:</h5>
        <template v-if="config.deepLevel === 2">
            <div class="label-item" v-for="(subitem, subindex) in tagList.subData"
                 :key="subitem.text + subindex"
                 :class="tagIndex === subindex ? 'active':''">
                <Tag
                        type="dot"
                        :color="subitem.color"
                        :fade="false"
                        :data-label="JSON.stringify(subitem)"
                        :data-category="config.data[categoryIndex].text"
                        :data-tag-is-unique="config.data[categoryIndex].tagIsUnique"
                        :data-global-tag-is-unique="config.tagIsUnique"
                        :data-tag-is-required="config.data[categoryIndex].tagIsRequired"
                        :class="{
                            active: tagIndex === subindex,
                            used: checkLabelIsUsed(config.data[categoryIndex].text + '-' + subitem.text)
                            }"
                        @click.native="handleTagClick($event, subindex)"
                >
                    <Icon type="ios-star" size = "12" color="red" v-if="subitem.isRequired === 1"></Icon>
                    {{subitem.text}}
                </Tag>
                <img :src="formatUrl(subitem.exampleImageSrc)" width="96" v-if="subitem.exampleImageSrc" class="example-image">
            </div>
        </template>
        <template v-if="config.deepLevel === 3">
            <my-tabs :data="tagList"
                     :fromIndex="0"
                     :activeIndex="activeIndex"
                     @click-tag="handleTagClick"
                     :globalTagIsUnique="config.tagIsUnique"
                     :usedLabel="usedLabel">
            </my-tabs>
        </template>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from '@/libs/util.js';
    import myTabs from './tabs';
    export default {
        name: 'tag-layout-tab',
        props: {
            config: {
                type: Object,
                required: true,
            },
            activeIndex: { // 当前选中的标签索引
                type: Number,
                required: true,
                default: 0
            },
            globalTagIsUnique: {},
            usedLabel: {
                type: Array,
                default: []
            }
        },
        data () {
            return {
                categoryIndex: 0,
                tagIndex: 0,
                tagList: {},
            };
        },
        mounted () {
            this.tagList = this.config.data[this.categoryIndex];
            Vue.nextTick(() => {
                this.$emit('tag-layout-tab-ready');
            });
        },
        watch: {
            config () {
                this.categoryIndex = 0;
                this.tagIndex = 0;
                this.tagList = this.config.data[this.categoryIndex];
            }
        },
        methods: {
            checkLabelIsUsed (labelWithCategory) {
                return ~this.usedLabel.indexOf(labelWithCategory);
            },
            formatUrl (url) {
                return api.staticBase + util.replaceUrl(url);
            },
            handleTagClick (e, index) {
                this.tagIndex = index;
                this.$emit("click-tag", e);
            },
            changeCategory (index) {
                this.categoryIndex = index;
                this.tagIndex = 0;
                this.tagList = this.config.data[index];
            }
        },
        components: {
            'my-tabs': myTabs,
        }
    };
</script>

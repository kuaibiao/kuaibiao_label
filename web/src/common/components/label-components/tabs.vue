<template>
    <div class="tabs-wrapper">
        <div class="tabs-nav-wrapper">
            <div class="tabs-nav" :class="active === index ? 'active': ''"
                 v-for="(item, index) in data.subData"
                 @click="handleTabNavChange(index)"
                 :key="index">
                {{item.text}}
            </div>
        </div>
        <div class="tabs-content-wrapper">
            <div class="tabs-content"
                 v-for="(item, index) in data.subData"
                 :class="active === index ? 'active': ''"
                 :key="index">
                <div class="label-item"
                     v-for="(subitem, subindex) in item.subData"
                     :class="{active: activeIndex === countIndex(index, subindex)}"
                     :key="subindex">
                    <Tag
                            type="dot"
                            :color="subitem.color"
                            :key="subitem.text"
                            :fade="false"
                            :data-index="countIndex(index,subindex)"
                            :data-label="JSON.stringify(subitem)"
                            :data-category="data.text + '-' + item.text"
                            :data-tag-is-unique="item.tagIsUnique"
                            :data-global-tag-is-unique="globalTagIsUnique"
                            :data-tag-is-required="item.tagIsRequired"
                            @click.native="handleTagClick($event)"
                            :class="{
                                active: activeIndex === countIndex(index, subindex),
                                used: checkLabelIsUsed([data.text, item.text,subitem.text].join('-'))
                            }"
                    >
                        <Icon type="ios-star" size="12" color="red" v-if="subitem.isRequired === 1"></Icon>
                        {{conatLabel(subitem)}}
                    </Tag>
                    <img :src="formatUrl(subitem.exampleImageSrc)" width="96" v-if="subitem.exampleImageSrc"
                         class="example-image"/>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import util from '@/libs/util.js';
    import api from '@/api';
    export default {
        name: "myTabs",
        props: {
            data: {
                type: Object,
                required: true
            },
            fromIndex: { // 当前tab渲染的标签起始索引
                type: Number,
                required: true,
                default: 0
            },
            activeIndex: { // 当前选中的标签索引
                type: Number,
                required: true,
                default: 0
            },
            globalTagIsUnique: {},
            usedLabel: {
                type: Array,
                default: [],
            }
        },
        data () {
            return {
                active: 0, // tab处于激活状态
            };
        },
        methods: {
            checkLabelIsUsed (labelWithCategory) {
                return ~this.usedLabel.indexOf(labelWithCategory);
            },
            formatUrl (url) {
                return api.staticBase + util.replaceUrl(url);
            },
            handleTabNavChange (i) {
                this.active = i;
            },
            handleTagClick (e) {
                this.$emit("click-tag", e);
            },
            conatLabel (item) {
                return item.shortValue ? item.shortValue + "-" + item.text : item.text;
            },
            countIndex (index, subindex) {
                let data = this.data.subData;
                let l = data.length;
                let curentIndex = 0;
                for (let i = 0; i < l; i++) {
                    if (i < index) {
                        curentIndex += data[i].subData.length;
                    } else {
                        curentIndex += subindex;
                        break;
                    }
                }
                return this.fromIndex + curentIndex;
            }
        }
    };
</script>
<style lang="scss">
    @import "./style.scss";
</style>



<template>
    <div class="tabs-wrapper">
        <div class="tabs-nav-wrapper">
            <div class="tabs-nav" :class="active === index ? 'active': ''"
                 v-for="(item, index) in data"
                 @click="handleTabNavChange(index)"
                 :key="index">
                {{item.text}}
            </div>
        </div>
        <div class="tabs-content-wrapper">
            <div class="tabs-content"
                 v-for="(item, index) in data"
                 :class="active === index ? 'active': ''"
                 :key="index">
                <div class="label-item"
                     v-for="(subitem, subindex) in item.subData"
                     :class="activeIndex === countIndex(index, subindex) ? 'active' : ''"
                     :key="subindex">
                    <Tag
                            type="dot"
                            :color="subitem.color"
                            :fade="false"
                            :data-index="countIndex(index,subindex)"
                            :data-label="JSON.stringify(subitem)"
                            @click.native="handleTagClick($event)"
                            :class="activeIndex === countIndex(index, subindex) ? 'active' : ''"
                    >
                        <Icon type="ios-medical" size="12" color="red" v-if="subitem.isRequired === 1"></Icon>
                        {{conatLabel(subitem)}}
                    </Tag>
                    <img :src="formatUrl(subitem.exampleImageSrc)" width="96" v-if="subitem.exampleImageSrc"
                         class="example-image">
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import util from '@/libs/util.js';
    import api from '@/api';

    export default {
        name: 'myTabs',
        props: {
            data: {
                type: Array,
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
            }
        },
        data () {
            return {
                active: 0, // tab处于激活状态
            };
        },
        methods: {
            formatUrl (url) {
                return api.staticBase + util.replaceUrl(url);
            },
            handleTabNavChange (i) {
                this.active = i;
            },
            handleTagClick (e) {
                this.$emit('click-tag', e);
            },
            conatLabel (item) {
                return item.shortValue ? item.shortValue + '-' + item.text : item.text;
            },
            countIndex (index, subindex) {
                let data = this.data;
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



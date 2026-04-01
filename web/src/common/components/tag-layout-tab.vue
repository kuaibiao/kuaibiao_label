<template>
    <div>
        <h5>{{$t('template_tag_group_title')}}:</h5>
        <Button
                v-for="(item, index) in config.data"
                :type="categoryIndex === index ? 'primary': 'default' "
                size="small"
                :key="index"
                @click.native="changeCategory(index)"
                style="margin: 1px;"
        >{{item.text}}
        </Button>
        <h5>{{$t('template_select_the_category_tab')}}:</h5>
        <template v-if="config.deepLevel === 2">
            <div class="label-item" v-for="(subitem, subindex) in tagList"
                 :key="subitem.text + subindex">
                <Tag
                        type="dot"
                        :color="subitem.color"
                        :fade="false"
                >
                    <Icon type="ios-medical" size="12" color="red" v-if="subitem.isRequired === 1"></Icon>
                    {{subitem.text}}
                </Tag>
                <img :src="formatUrl(subitem.exampleImageSrc)" width="96" v-if="subitem.exampleImageSrc"
                     class="example-image">
            </div>
        </template>
        <template v-if="config.deepLevel === 3">
            <my-tabs :data="tagList"
                     :fromIndex="0"
                     :activeIndex="0"
                     @click-tag="handleTagClick">
            </my-tabs>
        </template>
    </div>
</template>
<script>
    import api from '@/api';
    import util from '@/libs/util.js';
    import myTabs from 'components/tabs/tabs';

    export default {
        name: 'tag-layout-tab',
        props: {
            config: {
                type: Object,
                required: true,
            }
        },
        data () {
            return {
                categoryIndex: 0,
                tagIndex: 0,
                tagList: [],
            };
        },
        mounted () {
            this.tagList = this.config.data[this.categoryIndex].subData;
        },
        watch: {
            config () {
                this.categoryIndex = 0;
                this.tagIndex = 0;
                this.tagList = this.config.data[this.categoryIndex].subData;
            }
        },
        methods: {
            formatUrl (url) {
                return api.staticBase + util.replaceUrl(url);
            },
            handleTagClick () {
            },
            changeCategory (index) {
                this.categoryIndex = index;
                this.tagList = this.config.data[index].subData;
            }
        },
        components: {
            'my-tabs': myTabs,
        }
    };
</script>

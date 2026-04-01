<template>
    <div ref="template" class="template-instance" :path="path"
         :data-id="config.id"
         :data-tpl-type="config.type"
         :data-target="config.anchor">
        <div class="template-info" v-if="mode=== 'icon'">
            <Icon type="ios-paper" size="20"></Icon>
            <span class="template-name">{{$t('template_text_file_placeholder')}}</span>
        </div>
        <div class="template-delete" v-if="mode=== 'edit'">
            <span class="bficonfont bf-icon-del2" @click="handleDelete"></span>
        </div>
        <div class="instance-container" v-if="mode!== 'icon'"
             :class="(mode === 'execute' || mode === 'audit') ? 'perform' : ''">
            <h2 class="instance-header" v-if="mode!== 'execute'">{{config.header}}</h2>
            <h5 class="instance-tips" v-if="mode!== 'execute'">{{config.tips}}</h5>
            <div class="file-placeholder">
                <div class="search-wrapper" v-if="mode === 'execute' && config.searchEnable" style="text-align:center">
                    <Input style="width: 140px;" v-model="keyword" @on-enter="search" ref="searchInput"/>
                    <span v-if="totalMatch > 0">{{(currentMatch + 1) + '/' + totalMatch}}</span>
                    <Button @click="search" type="primary">
                        <Icon type="ios-search" size="14"></Icon>
                    </Button>
                    <Button @click="preMarker" type="primary">
                        <Icon type="ios-arrow-up" size="14"></Icon>
                    </Button>
                    <Button @click="nextMarker" type="primary">
                        <Icon type="ios-arrow-down" size="14"></Icon>
                    </Button>
                    <Button @click="clearMarker" type="error">✖</Button>
                </div>
                <div class="text-container" ref="textContent" style="margin-top: 0;position: relative;">
                    {{$t('tool_text_shown_deal_operator')}}
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from "../mixins/template-mixin";
    import EventBus from '../../event-bus';
    import Mark from 'mark.js';
    import api from '@/api';

    export default {
        mixins: [mixin],
        name: 'text-file-placeholder',
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
                staticBase: api.staticBase,
                mode: 'icon',
                marker: null,
                markerOption: {
                    element: 'span',
                    className: 'search',
                    done: this.markerDone,
                },
                totalMatch: 0,
                currentMatch: 0,
                keyword: '',
                result: [],
            };
        },
        created () {
        },
        mounted () {
            this.config.searchEnable = typeof this.config.searchEnable === 'undefined'
                ? true : this.config.searchEnable;
            this.mode = this.scene;
            EventBus.$on('setupMarker', this.setupMarker);
            EventBus.$emit('textFilePlaceholderReady');
            if (this.mode === "edit") {
                this.$store.commit("updatePlaceHolderCounter", {
                    type: "text",
                    add: true,
                });
            }
        },
        methods: {
            setupMarker () {
                if (!this.config.searchEnable) {
                    return;
                }
                let target = this.$refs.template.getElementsByClassName('text-container')[0];
                if (this.marker) {
                    this.search();
                } else {
                    this.marker = new Mark(target);
                }
            },
            markerDone (total) {
                this.totalMatch = total;
                this.currentMatch = 0;
                this.result = $(this.$refs.textContent).find('span.search');
                if (this.keyword.length > 0 && this.result.length === 0) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('template_no_match_text'),
                        duration: 2,
                    });
                    return;
                }
                this.jumpCurrent();
            },
            search () {
                this.marker.unmark({
                    className: 'search',
                    done: () => {
                        this.marker.mark(this.keyword, this.markerOption);
                    },
                });
            },
            preMarker () {
                if (this.result.length) {
                    this.currentMatch -= 1;
                    if (this.currentMatch < 0) {
                        this.currentMatch = this.result.length - 1;
                    }
                    if (this.currentMatch > this.result.length - 1) {
                        this.currentMatch = 0;
                    }
                    this.jumpCurrent();
                }
            },
            nextMarker () {
                if (this.result.length) {
                    this.currentMatch += 1;
                    if (this.currentMatch < 0) {
                        this.currentMatch = this.result.length - 1;
                    }
                    if (this.currentMatch > this.result.length - 1) {
                        this.currentMatch = 0;
                    }
                    this.jumpCurrent();
                }
            },
            jumpCurrent () {
                let current;
                if (this.result.length) {
                    current = this.result.eq(this.currentMatch);
                    this.result.removeClass('current');
                    let parent = this.$refs.textContent;
                    if (current.length) {
                        current.addClass('current');
                        let top = current.offset().top;
                        let position = current.position();
                        let pTop = $(parent).offset().top;
                        // 不在视区范围内 才滚动
                        if (position.top < 0 || position.top + current.height() > parent.clientHeight) {
                            parent.scrollTo(0, parent.scrollTop + top - pTop);
                        }
                    }
                }
            },
            clearMarker () {
                this.marker.unmark({
                    className: 'search',
                    done: () => {
                        this.keyword = '';
                        this.totalMatch = 0;
                        this.currentMatch = 0;
                    }
                });
            }
        },
        destroyed () {
            if (this.mode === "edit") {
                this.$store.commit("updatePlaceHolderCounter", {
                    type: "text",
                    add: false
                });
            }
            EventBus.$off('setupMarker', this.setupMarker);
            this.marker = null;
        }
    };
</script>
<style lang="scss">
    [data-tpl-type="text-file-placeholder"]:first-child {
        .instance-container.perform {
            margin: 10px 5px 0;
            padding: 0 50px;
        }
    }

    [data-tpl-type="text-file-placeholder"]:last-child {
        .instance-container.perform {
            padding: 0 50px 30px;
        }
    }

    [data-tpl-type="text-file-placeholder"] {
        .instance-container.perform {
            margin: 0 5px;
            padding: 0 50px;
        }
    }

    .search-wrapper {
        margin-bottom: 5px;
    }

    .instance-container.perform {
        .file-placeholder {
            background: #fff !important;
            padding: 0;
            min-height: 20px;
        }
    }

    .text-container {
        display: inline-block;
        line-height: 1.5;
        font-size: 14px;
        max-height: 320px;
        overflow: auto;
        text-align: left;
        padding: 0 10px;

        .search {
            background: greenyellow;
            color: black;

            &.current {
                background: orange;
            }
        }

        .highlight {
            background: yellow;
            color: black;
            /*border-bottom: 1px solid #6a6c6f;*/
            &.current {
                background: lightcoral;
            }
        }

        pre {
            margin: 0;
            white-space: pre-wrap;
            word-break: break-word; // 长英文单词处理
        }
    }

    .file-placeholder {
        min-height: 180px;
        text-align: center;
        padding-top: 35%;
        padding-bottom: 15px;
        background-repeat: no-repeat;
        background-position: center 20px;
        background-color: #edf0f5;
        background-size: 30%;
        font-size: 14px;
    }
</style>

<template>
    <div class="audit-wrapper">
        <div class="audit-content">

            <div class="task-list-1954">
                <fieldset>
                    <legend class="legend-1954">{{$t('tool_top_jobs',{num: beforeNums})}}</legend>
                    <div class="task-box-1954 task-next">
                        <div class="a-img-box" v-for="(task, index) in nearbyList.before" :key="task.id">
                            <Spin size="small" class="image-loading" v-show="!task[imageType + 'Image']">
                                <Icon type="ios-loading" size=18 class="spin-icon-load"></Icon>
                                <div>Loading</div>
                            </Spin>
                            <img
                                    :src="task[imageType + 'Image']"
                                    class="audit-result-image"
                                    @click="imageViewer(task.id, index, $event ,'before')"
                            >
                            <span style="position:relative;top:130px;color:black">ID: {{task.id}}</span>
                        </div>
                        <div style="text-align: center;padding: 20px 0;" v-if="!nearbyList.before.length">
                            <img :src="defalutImage" alt="">
                            <p style="font-size: 14px;letter-spacing: 2px;position: relative;right: -13px;">{{$t('operator_nearby_no_image')}}</p>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </fieldset>
            </div>

            <div class="task-list-1954">
                <fieldset>
                    <legend class="legend-1954">{{$t('tool_last_jobs', {num: afterNums})}}</legend>
                    <div class="task-box-1954 task-next">
                        <div class="a-img-box" v-for="(task, index) in nearbyList.after" :key="task.id">
                            <Spin size="small" class="image-loading" v-show="!task[imageType + 'Image']">
                                <Icon type="ios-loading" size=18 class="spin-icon-load"></Icon>
                                <div>Loading</div>
                            </Spin>
                            <img
                                    :src="task[imageType + 'Image']"
                                    class="audit-result-image"
                                    @click="imageViewer(task.id, index, $event, 'after')"
                            >
                            <span style="position:relative;top:130px;color:black">ID: {{task.id}}</span>
                        </div>
                        <div style="text-align: center;padding: 20px 0;" v-if="!nearbyList.after.length">
                            <img :src="defalutImage" alt="">
                            <p style="font-size: 14px;letter-spacing: 2px;position: relative;right: -13px;">{{$t('operator_nearby_no_image')}}</p>
                        </div>
                        <div style="clear:both;"></div>
                    </div>
                </fieldset>
            </div>
        </div>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from "../../../libs/util";
    import Viewer from '../../../libs/viewerjs/viewer.min.js';
    import '@/libs/viewerjs/viewer.min.css';
    import AssetsLoader from '../../../libs/assetLoader.js';
    import defalutImage from '../../../images/default-image/nonearbyImage.png';
    export default {
        props: {
            nearbyList: {
                type: Object,
                default: {
                    after: [],
                    before: [],
                },
            }
        },
        data () {
            return {
                imageType: 'mark',
                viewer: null,
                imageViewerIsOpen: false,
                beforeNums: 0,
                afterNums: 0,
                imageList: {},
                assetLoader: null,
                nearByIdList: [],
                defalutImage
            };
        },
        watch: {
            nearbyList: function () {
                if (this.nearbyList) {
                    this.updateNearByIdList();
                    this.getTaskImage();
                }
                // 前n张图片
                if (this.nearbyList.before) {
                    this.beforeNums = this.nearbyList.before.length;
                }
                // 后n张图片
                if (this.nearbyList.after) {
                    this.afterNums = this.nearbyList.after.length;
                }
            }
        },
        mounted () {
            if (this.nearbyList) {
                this.updateNearByIdList();
                this.getTaskImage();
            }
            $('.audit-wrapper').on('click', '.viewer-fullscreen', function (e) {
                e.stopPropagation();
                this.viewer && this.viewer.destroy();
                this.viewer = null;
                this.imageViewerIsOpen = false;
            }.bind(this));
        },
        methods: {
            updateNearByIdList () {
                this.nearByIdList = [];
                if (this.nearbyList.before) {
                    this.nearbyList.before.forEach((item) => {
                        this.nearByIdList.push(item.id);
                    });
                }
                if (this.nearbyList.after) {
                    this.nearbyList.after.forEach((item) => {
                        this.nearByIdList.push(item.id);
                    });
                }
            },
            checkCache () {
                if (this.nearbyList.before) {
                    this.nearbyList.before.forEach((item) => {
                        if (this.imageList[item.id]) {
                            item['markImage'] = this.imageList[item.id];
                        }
                    });
                }
                if (this.nearbyList.after) {
                    this.nearbyList.after.forEach((item) => {
                        if (this.imageList[item.id]) {
                            item['markImage'] = this.imageList[item.id];
                        }
                    });
                }
                this.removeCache();
            },
            removeCache () {
                for (let id in this.imageList) {
                    if (this.imageList.hasOwnProperty(id)) {
                        if (this.nearByIdList.indexOf(id) === -1) {
                            this.imageList[id] = null;
                            delete this.imageList[id];
                        }
                    }
                }
            },
            getTaskImage () {
                this.checkCache();
                let imageList = [];
                if (this.nearbyList.before) {
                    imageList = imageList.concat(this.nearbyList.before);
                }
                if (this.nearbyList.after) {
                    imageList = imageList.concat(this.nearbyList.after);
                }
                let requestList = imageList.map((task) => {
                    return {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: task.project_id,
                        data_id: task.id,
                        type: 'ori',
                    };
                });
                requestList = requestList.filter((req) => {
                    return !this.imageList[req.data_id];
                });
                this.assetLoader = new AssetsLoader(api.task.mark, requestList, 2, (res, req) => {
                    let resource = Object.entries(res.data || {});
                    if (resource.length === 0) {
                        return;
                    }
                    let src = resource[0][1];
                    let image = new Image();
                    image.src = src;
                    image.onload = () => {
                        this.imageList[req.data_id] = src;
                        this.imageLoaded(req.data_id, src);
                    };
                    image.onerror = () => {
                        this.imageList[req.data_id] = '';
                    };
                }, (res, req) => {
                    this.imageList[req.data_id] = '';
                });
            },
            imageLoaded (dataId, src) {
                if (this.nearbyList.before) {
                    let index = this.nearbyList.before.findIndex((t) => {
                        return t.id === dataId;
                    });
                    if (~index) {
                        let item = this.nearbyList.before[index];
                        item['markImage'] = src;
                        this.nearbyList.before.splice(index, 1, item);
                    }
                }
                if (this.nearbyList.after) {
                    let index = this.nearbyList.after.findIndex((t) => {
                        return t.id === dataId;
                    });
                    if (~index) {
                        let item = this.nearbyList.after[index];
                        item['markImage'] = src;
                        this.nearbyList.after.splice(index, 1, item);
                    }
                }
            },
            getTaskMarkImage (dataId, type, index, ba) {
                $.ajax({
                    url: api.task.mark,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        data_id: dataId,
                        type: type,
                    },
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let task = this.nearbyList[ba][index];
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            task[type + 'Image'] = resource[0][1] || '';
                            this.nearbyList[ba].splice(index, 1, task);
                            Vue.nextTick(() => {
                                this.viewer && this.viewer.update();
                            });
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            let task = this.nearbyList[ba][index];
                            task[type + 'Image'] = '_-_';
                            this.nearbyList[ba].splice(index, 1, task);
                        });
                    }
                });
            },
            imageViewer (dataId, index, e, ba) {
                let num;
                if (ba === 'after') {
                    num = index + this.afterNums;
                } else {
                    num = index;
                }
                if (!this.viewer) {
                    this.initImageView();
                }
                this.viewer.hide(true);
                this.viewer.show(true);
                this.viewer.view(num);
            },
            initImageView () {
                this.viewer = new Viewer(document.getElementsByClassName('audit-content')[0], {
                    filter(image) {
                        return ~image.className.indexOf('audit-result-image');
                    },
                    toolbar: false,
                    transition: false,
                    url: (img) => {
                        return img.src;
                    },
                    hidden: () => {
                        this.imageViewerIsOpen = false;
                    },
                    view: (e) => {
                        this.imageViewerIsOpen = true;
                        this.currentIndex = e.detail.index;
                    },
                });
            },
            abort () {
                this.assetLoader && this.assetLoader.abort();
            }
        },
        destroyed () {
            this.abort();
        }
    };
</script>
<style lang="scss">
    /*查看前后作业css*/
    .task-list-1954 {
        margin-bottom: 5px;
    }

    .task-list-1954 .legend-1954 {
        margin-left: 10px;
        margin-right: 10px;
        padding-left: 3px;
        padding-right: 3px;
    }

    .task-list-1954 .task-box-1954 {
        padding: 10px;
    }

    .task-list-1954 .task-box-1954 .a-img-box {
        width: 150px;
        height: 150px;
        display: block;
        float: left;
        background-color: #e2e2e2;
        margin-right: 5px;
        margin-bottom: 5px;
        overflow: hidden;
        position: relative;
        text-align: center
    }

    .task-list-1954 .task-box-1954 .a-img-box img {
        width: auto;
        height: 85%;
        display: block;
        border: 0px;
        margin: 0 auto;
        position: absolute;
        top: 0px;
    }

    .task-list-1954 .task-box-1954 .a-img-box .image-loading {
        margin-top: 30px;
    }
    .modal-view-task-nums .ivu-modal-footer {
        display: none;
    }
</style>

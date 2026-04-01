<template>
    <div class="module-editor">
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_title_optional')}}</h4>
            <Input v-model="module.header" :placeholder="$t('template_enter_title')"
                   @on-change="saveChange"/>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_remarks_optional')}}</h4>
            <Input v-model="module.tips" :placeholder="$t('template_enter_comments')"
                   @on-change="saveChange"/>
        </div>
        <div class="example-img-preview editor-item">
            <img :src="formatUrl(module.exampleImageSrc)"
                 style="height: 120px; margin: 5px auto; display: block; border:1px solid #eee;">
            <div style="text-align:center">
                <Button shape="circle" style="margin-right:10px;" @click.native="showModal">{{$t('template_edit_key_point_location')}}</Button>
                <Upload
                        :data="exampleImageConf.data"
                        :action="exampleImageConf.url"
                        :accept="exampleImageConf.accept"
                        :show-upload-list="false"
                        name="image"
                        :on-success="imageUploadSuccess"
                        :on-error="imageUploadError"
                        style="display:inline-block;">
                    <Button shape="circle">{{$t('template_replace_picture')}}</Button>
                </Upload>
            </div>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">
                <span style="vertical-align:4px;">{{$t('template_keyPoint_color_set_all')}}</span>
                <color-picker
                        style="display: inline-block"
                        :color="defaultColor"
                        :width="100"
                        @input="defaultColorChange">
                </color-picker>
            </h4>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_edit_key_points')}}</h4>
            <h6 class="editor-subheader">{{$t('template_adjust_order_key_points')}}</h6>
            <div class="sortable-list">
                <draggable v-model="listData"
                           tag="div"
                           v-bind="sortOptions"
                           @end="handleSortEnd">
                    <div class="item-header-flex" v-for="(item ,index) in listData" :key="index">
                        <Icon type="ios-menu" class="drag-handle" style="cursor:move; margin-right: 4px;"></Icon>
                        <span style="font-size: 14px;display:inline-block; min-width:24px; text-align: center"> {{index  +1}} </span>
                        <Input :value="item.text" style="margin:2px 8px" size="small" @on-change="handleItemChange(index, $event)"> </Input>
                        <color-picker :color="item.color || defaultColor"
                                      :width="100"
                                      @input="itemColorChange(item, index, $event)"
                        >
                        </color-picker>
                        <Icon type="ios-trash" class="del-handler" size="18"
                              @click.native="handleItemDel(index, $event)"></Icon>
                    </div>
                </draggable>
            </div>
            <Button type="primary" icon="md-add" @click.native="addItem">{{$t('template_add_key_points')}}</Button>
            <Button type="primary" icon="md-add" @click.native="addMoreItem">{{$t('template_batch_add_option')}}</Button>
            <div class="excel-import">
                <Button type="primary" icon="md-add" @click.native="handleImportClick">{{$t('template_excel_import')}}</Button>
                <a class="demo-file-link" :href="staticBase + '/template/keypoint-import-template.xlsx'"
                   :download="$t('template_key_components_excel_import_templates')">{{$t('template_download_template')}}</a>
                <input type="file" class="js-filepicker"
                       ref="excel-file-picker" style="display:none;"
                       accept=".xls,.xlsx,.csv"
                       @change="handleExcelFile"
                >
            </div>
        </div>
        <Modal
                :title="$t('template_edit_location_key_points_sample_diagram')"
                v-model="editExampleImage"
                :scrollable="true"
                :mask-closable="false"
                :ok-text="$t('template_save')"
                :cancel-text="$t('template_close')"
                @on-ok="savePointPosition"
                @on-visible-change="modalToggle"
                class-name="vertical-center-modal">
            <h4 class="header-tips">{{$t('template_light_position_enable_drag')}}</h4>
            <Row class="setting-wrapper">
                <i-col span="17" class="example-img-wrapper">
                    <div class="example-img-container" ref="exampleImgContainer">
                        <img :src="formatUrl(module.exampleImageSrc)"
                             style="display: block; max-width: 100%; max-height: calc(100vh - 200px); min-height: 400px;"
                             @click="addPositionItem">
                        <span class="bone-point" v-for="(item, index) in listData" v-show="item.position"
                              :key="index"
                              :data-step="index + 1"
                              :data-text="item.text"
                              :style="itemStyle(item.position)"
                        >{{index + 1}}</span>
                    </div>
                </i-col>
                <i-col span="7">
                    <div class="equal-diversion-config" v-show="!unPositionedList.length">
                        <div class="equal-config-item" 
                            v-for= "(item, index) in equalDiversionConfig"
                            :key="index">
                            <template v-if="item.type === equalPointType.ordered">
                                <div>
                                    <label for="">{{$t('template-start')}}</label>
                                    <InputNumber size="small" v-model="item.start" 
                                        :min="1"
                                        :max="listData.length"
                                    />
                                </div>
                                <div>
                                    <label for="">{{$t('template-end')}}</label>
                                    <InputNumber size="small" v-model="item.end"
                                        :min="1"
                                        :max="listData.length"
                                    />
                                </div>
                            </template>
                            <template v-else>
                                <Input type="textarea" v-model="item.equalPoints"  :autosize="{minRows:1, maxRows: 3}"/>
                            </template>
                            <Icon type="md-trash" size="20" @click="removeEqualConfig(index)"/>
                        </div>
                        <ButtonGroup>
                            <Button type="primary" size="small"
                                 @click="addEqualConfig(equalPointType.ordered)" 
                                icon="md-add-circle">{{$t('template-order-uniform')}}</Button>
                            <Button type="info" size="small" 
                                @click="addEqualConfig(equalPointType.custom)" 
                                icon="md-add-circle">{{$t('template-custom-bisecting')}}</Button>
                        </ButtonGroup>
                    </div>
                    <div class="unpositioned-point-list"> 
                        <span class="unpositioned-point"
                              v-for="(item, index) in unPositionedList"
                              :data-index="item.index"
                              :key="index"
                              :class="index === 0 ? 'active': ''"
                        > {{item.text || (item.index)}}</span>
                    </div>
                </i-col>
            </Row>
        </Modal>
        <BatchAddOption
                ref="batchAdd"
                :optionList="optionList"
                @update="handleAddMoreItem"
        >
        </BatchAddOption>
    </div>
</template>
<script>
    import Vue from 'vue';
    import draggable from 'vuedraggable';
    import Util from '@/libs/util';
    import api from '@/api';
    import 'jquery-ui';
    import colorPicker from '../color-picker';

    export default {
        name: 'key-point-editor',
        props: {
            config: {
                type: Object,
                required: true,
            },
            path: {
                type: String,
                required: true,
            }
        },
        data () {
            return {
                staticBase: api.staticBase,
                editExampleImage: false,
                exampleImageConf: {
                    url: api.upload.image,
                    accept: '.jpg, .png, .jpeg',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    }
                },
                module: {
                    data: [],
                    exampleImageSrc: ''
                },
                sortOptions: {
                    animation: 200,
                    scrollSensitivity: 15,
                    scrollSpeed: 20,
                    sort: true,
                    handle: '.drag-handle',
                    ghostClass: 'ghost'
                },
                defaultColor: '#ffff00',
                optionList: [],
                equalPointType: {
                    ordered: 'ordered',
                    custom: 'custom',
                }
            };
        },
        mounted () {
            this.module = this.config;
            this.defaultColor = this.config.defaultColor || this.defaultColor;
        },
        computed: {
            listData: {
                get: function () {
                    return this.module.data;
                },
                set: function (newValue) {
                    this.module.data = newValue;
                }
            },
            equalDiversionConfig: {
                get: function () {
                    let value = this.module.equalDiversionConfig || [];
                    this.module = {
                        ...this.module,
                        equalDiversionConfig: value,
                    };
                    return value;
                },
                set: function (newValue) {
                    this.module = {
                        ...this.module,
                        equalDiversionConfig: newValue,
                    };
                }
            },
            unPositionedList: {
                get: function () {
                    let data = [];
                    this.listData.map((item, index) => {
                        if (!item.position) {
                            data.push({
                                text: item.text,
                                index: index
                            });
                        }
                    });
                    return data;
                },
            },
        },
        watch: {
            config: {
                handler: function (config) {
                    this.module = config;
                    this.defaultColor = config.defaultColor || this.defaultColor;
                },
                deep: true,
            }
        },
        methods: {
            defaultColorChange (color) {
                this.defaultColor = color;
                this.listData = this.listData.map((item) => {
                    item.color = color;
                    return item;
                });
                this.module.defaultColor = color;
                this.saveChange();
            },
            itemColorChange (item, index, color) {
                item.color = color;
                this.listData.splice(index, 1, item);
                this.saveChange();
            },
            formatUrl (url) {
                return api.staticBase + Util.replaceUrl(url);
            },
            addPositionItem (e) {
                if (this.unPositionedList.length < 1) {
                    return;
                }
                let item = {};
                let container = $(this.$refs.exampleImgContainer);
                let containerWidth = container.width();
                let containerHeight = container.height();
                item.text = this.unPositionedList.shift().text;
                let x = e.offsetX - 8;
                let y = e.offsetY - 10;// 鼠标点击位置
                item.position = {
                    top: y / containerHeight,
                    left: x / containerWidth
                };
                let index = +$('.unpositioned-point.active').attr('data-index');
                this.listData.splice(index, 1, item);
                Vue.nextTick(() => {
                    $('.example-img-container .bone-point').draggable({
                        containment: 'parent'
                    });
                });
            },
            addEqualConfig (type) {
                this.equalDiversionConfig = this.equalDiversionConfig || [];
                // 有起始点的
                if (type === this.equalPointType.ordered) {
                    this.equalDiversionConfig.push({
                        start: 1,
                        end: 1,
                        type,
                    });
                } else { // 自定义无序的
                    this.equalDiversionConfig.push({
                        equalPoints: '',
                        type,
                    });
                }
            },
            removeEqualConfig (index) {
                this.equalDiversionConfig.splice(index, 1);
            },
            modalToggle (isShow) {
                if (isShow) {
                    $('.example-img-container .bone-point').draggable({
                        containment: 'parent'
                    });
                }
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
            savePointPosition () {
                let container = $(this.$refs.exampleImgContainer);
                let containerWidth = container.width();
                let containerHeight = container.height();
                let data = [];
                container.find('.bone-point').each((index, ele) => {
                    let item = {};
                    let top = parseFloat($(ele).css('top'));
                    let left = parseFloat($(ele).css('left'));
                    item.text = $(ele).attr('data-text');
                    item.position = {
                        top: top / containerHeight,
                        left: left / containerWidth
                    };
                    data.push(item);
                });
                this.module.data = data;
                this.equalDiversionConfig = this.equalDiversionConfig.filter(item => {
                    // valid 为true  表示 满足条件
                    let valid = true;
                    if (item.type === this.equalPointType.ordered) {
                        let start = +item.start;
                        let end = +item.end;
                        if ((start === 0 && end === 0) || start >= end || end - start < 2) {
                            valid = false;
                        }
                        item.start = start;
                        item.end = end;
                    } else {
                        let equalPointsStr = item.equalPoints;
                        // 先替换到所有非数字 字符 为',' 然后按 ',' 分割
                        let equalPoints = equalPointsStr.replace(/(\D)+/g, ',').split(',');
                        equalPoints = equalPoints.filter(n => {
                            n = parseInt(n);
                            // 非数字 或者 小于等于0  大于 最大点总数时 过滤掉
                            return !Number.isNaN(n) && (n > 0 && n <= data.length);
                        });
                        // 少于2个时 过滤掉 因为少于两个等分无意义
                        if (equalPoints.length > 2) {
                            item.equalPoints = Array.from(new Set(equalPoints)).join();
                        } else {
                            valid = false;
                        }
                    }
                    return valid;
                });
                this.saveChange();
            },
            showModal () {
                this.editExampleImage = true;
            },
            imageUploadSuccess (res) {
                if (res.error) {
                    this.$Message.error({
                        content: this.$t('template_upload_failed_try_again'),
                        duration: 2,
                    });
                } else {
                    this.module.exampleImageSrc = res.data.url;
                    this.saveChange();
                }
            },
            imageUploadError () {
                this.$Message.error({
                    content: this.$t('template_upload_failed_try_again'),
                    duration: 2,
                });
            },
            saveChange () {
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
            handleItemDel (index) {
                this.listData.splice(index, 1);
                this.saveChange();
            },
            handleItemChange (index, e) {
                let item = this.listData[index];
                item.text = e.target.value;
                this.listData.splice(index, 1, item);
                this.saveChange();
            },
            handleSortEnd () {
                this.saveChange();
            },
            handleAddMoreItem (list) {
                // 保留的选项
                let retainData = this.listData.filter(v => {
                    let index = list.indexOf(v.text);
                    if (~index) {
                        // 如果是保留的选项 在追加列表中删掉
                        list.splice(index, 1);
                        return true;
                    } else {
                        return false;
                    }
                });
                this.listData = retainData.concat(list.map((v) => {
                    return {
                        text: v,
                        color: this.defaultColor
                    };
                }));
                this.saveChange();
            },
            addMoreItem () {
                this.optionList = this.listData.map(v => {
                    return v.text;
                });
                this.$refs.batchAdd.show();
            },
            addItem () {
                this.listData.push({
                    text: this.listData.length + 1,
                    color: this.defaultColor,
                });
                this.saveChange();
            },
            handleImportClick () {
                this.$refs['excel-file-picker'].click(); // 打开文件选择框
            },
            handleExcelFile (e) {
                Util.xlsxToJson(e.target.files[0], (data) => {
                    if (data.length === 0) {
                        this.$Message.warning({
                            content: this.$t('template_data_empty')
                        });
                        // 清空input元素的值，避免两次选择相同的文件不触发change事件
                        this.$refs['excel-file-picker'].value = '';                        
                        return;
                    }
                    let result = this.parseJsonToBonePointData(data);
                    if (result.length) {
                        this.listData = result;
                        this.saveChange();
                        this.$Message.success({
                            content: this.$t('project_upload_success')
                        });
                    }
                    // 清空input元素的值，避免两次选择相同的文件不触发change事件
                    this.$refs['excel-file-picker'].value = '';
                }, this);
            },
            parseJsonToBonePointData (resultJson) {
                var data = [];
                if (Array.isArray(resultJson) && resultJson.length > 0) {
                    if (!resultJson[0].text) {
                        this.$Message.error({
                            content: this.$t('template_data_invalid'),
                            duration: 2,
                        });
                        return data;
                    }
                    resultJson.forEach(function (v) {
                        data.push({
                            text: v.text
                        });
                    });
                }
                return data;
            }
        },
        components: {
            draggable,
            colorPicker,
            BatchAddOption: () => import('../batch-add-option.vue'),
        }
    };
</script>
<style lang="scss">
    @import './style';

    .vertical-center-modal {
        display: flex;
        align-items: center;
        justify-content: center;
        .header-tips {
            text-align: center;
            margin: 10px auto;
        }
        .ivu-modal {
            top: 0;
            width: auto !important;
        }
        .ivu-modal-content {
            min-width: 768px !important;
        }
    }

    .setting-wrapper {
        text-align: center;
        min-width: 960px;

        .unpositioned-point-list {
            max-height: calc(100vh - 200px);
            overflow-y: auto;
        }
        .example-img-container {
            position: relative;
            padding: 0;
            user-select: none;
            display: inline-block;
            outline: 1px solid #ccc;
        }
        .bone-point {
            position: absolute;
            height: 16px;
            top: 0;
            left: 0;
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
            cursor: move;
        }
        .unpositioned-point {
            display: block;
            min-width: 20px;
            &.active {
                background: #ff0;
                color: red;
            }
        }
    }
    .equal-diversion-config {
        .equal-config-item {
            display: flex;
            justify-content: space-around;
            padding: 5px;
            &+.equal-config-item {
                border-top: 1px dashed #666;
            }
        }
    }
    
</style>

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
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_set_label_type')}}</h4>
            <Select v-model="subType"
                    @on-change="handleTypeChange">
                <Option value="single">{{$t('template_single_tag')}}</Option>
                <Option value="group">{{$t('template_label_group')}}</Option>
            </Select>
        </div>
        <Collapse class="editor-advanced-config">
            <Panel name="1">
                {{$t('template_advanced_settings')}}
                <div slot="content">
                    <div class="editor-item">
                        <h4 class="editor-header">{{$t('template_batch_change_label_color')}} :
                            <color-picker :color="defaultColor"
                                          @input="defaultColorChange"
                            >
                                <span class="editor-subheader" slot="tips"
                                      style="margin-left: 4px;">{{$t('template_system_default_value')}} #ffff00 </span>
                            </color-picker>
                        </h4>
                    </div>
                    <div class="editor-item">
                        <h4 class="editor-header">{{$t('template_polygon_point_distance_setting')}}：
                            <InputNumber :min="0" v-model="module.pointDistanceMin"
                                         @on-change="saveChange" size="small"></InputNumber>
                            <span class="editor-subheader">0 {{$t('template_no_limit')}}</span>
                        </h4>
                    </div>
                    <div class="editor-item">
                        <h4 class="editor-header">{{$t('template_whether_enable_tag_echo_search')}}：
                            <Checkbox v-model="module.tagIsSearchAble"
                                      @on-change="saveChange">{{module.tagIsSearchAble ? $t('template_open'): $t('template_close')}}
                            </Checkbox>
                        </h4>
                    </div>
                    <div class="editor-item">
                        <Tooltip :content="$t('template_client_unSupport')"
                                 :transfer="true"
                                 placement="left">
                            <h4 class="editor-header">{{$t('template_whether_drawn_outside_picture')}}：
                                <Checkbox v-model="module.pointPositionNoLimit"
                                          @on-change="saveChange">{{module.pointPositionNoLimit ?
                                    $t('template_can'): $t('template_can_not')}}
                                </Checkbox>
                            </h4>
                        </Tooltip>
                    </div>
                    <div class="editor-item" v-if="module.pointTagShapeType">
                        <h4 class="editor-header">{{$t('template_label_coordinate_points_following_graph')}}：
                            <CheckboxGroup v-model="module.pointTagShapeType"
                                           @on-change="saveChange">
                                <Checkbox label="trapezoid">
                                    <span>{{$t('template_trapezoidal')}}</span>
                                </Checkbox>
                                <Checkbox label="quadrangle">
                                    <span>{{$t('template_quadrilateral')}}</span>
                                </Checkbox>
                                <Checkbox label="cuboid">
                                    <span>{{$t('template_cuboid')}}</span>
                                </Checkbox>
                                <Checkbox label="line">
                                    <span>{{$t('template_line')}}</span>
                                </Checkbox>
                                <Checkbox label="triangle">
                                    <span>{{$t('template_triangle')}}</span>
                                </Checkbox>
                            </CheckboxGroup>
                        </h4>
                    </div>
                    <template v-if="module.subType === 'group'">
                        <div class="editor-item">
                            <h4 class="editor-header">{{$t('template_open_tag_group_lock')}}:
                                <Checkbox v-model="module.tagGroupLock"
                                          @on-change="saveChange">{{module.tagGroupLock ? $t('template_opened'): $t('template_off')}}
                                </Checkbox>
                            </h4>
                        </div>
                        <div class="editor-item">
                            <h4 class="editor-header">{{$t('template_whether_tag_group_expanded_default')}}:
                                <Checkbox v-model="module.tagGroupOpen"
                                          @on-change="saveChange">{{module.tagGroupOpen ? $t('template_unfold'): $t('template_closed')}}
                                </Checkbox>
                            </h4>
                        </div>
                        <div class="editor-item">
                            <h4 class="editor-header">{{$t('template_label_group_presentation')}}:
                                <RadioGroup v-model="module.tagLayoutType" @on-change="saveChange">
                                    <Radio label="list">{{$t('template_list_type')}}</Radio>
                                    <Radio label="tab">{{$t('template_card_type')}}</Radio>
                                </RadioGroup>
                            </h4>
                        </div>
                    </template>
                </div>
            </Panel>
        </Collapse>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_whether_global_label_is_unique')}}
                <Poptip trigger="hover">
                    <Icon type="md-help-circle" style="cursor:help;"></Icon>
                    <div class="tag-help-tip" slot="content">
                        {{$t('template_sets_whether_label_is_unique')}}<br/>
                        {{$t('template_indicates_label_has_at_most_one_label')}} <br/>
                        {{$t('template_indicates_label_may_have_multiple_labels')}} <br/>
                        <span style="color: #000;">({{$t('template_setting_highest_priority')}})</span>
                    </div>
                </Poptip>
            </h4>
            <Select v-model="module.tagIsUnique"
                    @on-change="saveChange">
                <Option :value="0">{{$t('template_label_only_have_one_label')}}</Option>
                <Option :value="1">{{$t('template_labels_can_have_multiple_labels')}}</Option>
            </Select>
        </div>
        <template v-if="module.subType === 'single'">
            <div class="editor-item">
                <h4 class="editor-header">{{$t('template_whether_the_label_must_have')}}</h4>
                <Select v-model="module.tagIsRequired"
                        @on-change="saveChange">
                    <Option :value="1">{{$t('template_the_label_must_have_label')}}</Option>
                    <Option :value="0">{{$t('template_labels_may_not_have_labels')}}</Option>
                </Select>
            </div>
        </template>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_edit_label')}}:
                <Poptip trigger="hover" word-wrap  width="200">
                    <Icon type="md-help-circle" style="cursor:help;"></Icon>
                    <div class="tag-help-tip" slot="content">{{$t('template_editable_label_properties')}}</div>
                </Poptip>
            </h4>
            <template v-if="subType === 'single'">  <!-- 单个标签 -->
                <div class="sortable-list">
                    <draggable v-model="listData"
                               tag="div"
                               v-bind="sortOptions"
                               @end="handleSortEnd">
                        <div class="item-header-flex" v-for="(item ,index) in listData" :key="index">
                            <Icon type="ios-menu" class="drag-handle" style="cursor:move"></Icon>
                            <Input :value="item.text" class="dragable-ignore" style="margin: 2px 8px"
                                   @on-change="handleItemChange(index, $event)"/>
                            <labelAttr :item="item" @saveChange="saveChange"/>
                            <Icon type="ios-trash" class="dragable-ignore del-handler" size="18"
                                  @click.native="handleItemDel(index, $event)">
                            </Icon>
                        </div>
                    </draggable>
                </div>
                <Button type="primary" icon="md-add"
                        @click.native="addSingleItem"
                        style="margin-top:5px;">{{$t('template_add_tags')}}
                </Button>
            </template>
            <template v-if="subType === 'group'">
                <div class="sortable-list">
                    <!-- 标签组 -->
                    <draggable v-model="listData"
                               tag="div"
                               v-bind="sortOptions"
                               @end="handleSortEnd">
                        <div class="list-item-flex" v-for="(item ,index) in listData" :key="index">
                            <div class="item-header-flex"> <!-- 一级标签 -->
                                <Icon
                                        :type="collapseInfo[index] && collapseInfo[index].collapsed ? 'md-arrow-dropdown': 'md-arrow-dropright'"
                                        class="dragable-ignore"
                                        style="cursor:pointer; flex-basis: 18px"
                                        size="18"
                                        @click.native="toggleCollapse(index)"
                                ></Icon>
                                <Icon type="ios-menu" class="drag-handle" style="cursor:move"></Icon>
                                <Input :value="item.text" style="margin: 2px 8px"
                                       @on-change="handleItemChange(index, $event)"/>
                                <Icon type="ios-trash"
                                      class="del-handler" size="18"
                                      style="flex-basis: 32px"
                                      @click.native="handleItemDel(index)"></Icon>
                            </div>
                            <transition>
                                <div class="collapse-content dragable-ignore"
                                     v-show="collapseInfo[index] && collapseInfo[index].collapsed">
                                    <div class="editor-item" v-if="module.tagIsUnique">
                                        <h4 class="editor-header">{{$t('template_whether_tags_group_unique')}}
                                            <Poptip trigger="hover">
                                                <Icon type="md-help-circle" style="cursor:help;"></Icon>
                                                <div class="tag-help-tip" slot="content">
                                                    {{$t('template_sets_whether_label_in_the_group_is_unique')}}<br/>
                                                    {{$t('template_indicates_tag_one_label_within_group')}} <br/>
                                                    {{$t('template_indicates_nnotator_multiple_tags_within_group')}}
                                                </div>
                                            </Poptip>
                                        </h4>
                                        <Select v-model="item.tagIsUnique"
                                                @on-change="saveChange">
                                            <Option :value="0">{{$t('template_tags_may_use_most_one_label_within_group')}}</Option>
                                            <Option :value="1">{{$t('template_tags_can_use_multiple_tags_within_group')}}</Option>
                                        </Select>
                                    </div>
                                    <div class="editor-item">
                                        <h4 class="editor-header">{{$t('template_whether_there_must_be_tags_within_group')}}</h4>
                                        <Select v-model="item.tagIsRequired"
                                                @on-change="saveChange">
                                            <Option :value="1">{{$t('template_tags_must_have_set_tags')}}</Option>
                                            <Option :value="0">{{$t('template_tags_may_not_have_set_tags')}}</Option>
                                        </Select>
                                    </div>
                                    <div class="editor-item" v-for="(subItem ,subIndex) in item.subData"
                                         :key="subIndex">
                                        <div class="item-header-flex"> <!-- 二级标签 -->
                                            <Input :value="subItem.text" class="dragable-ignore"
                                                   style="margin: 2px 8px"
                                                   @on-change="handleSubItemChange(index, subIndex,  $event)"/>
                                            <labelAttr
                                                    :item="subItem"
                                                    @saveChange="saveChange"
                                                    v-if="subItem.subData && !subItem.subData.length > 0"
                                            />
                                            <Icon type="ios-trash"
                                                  class="dragable-ignore del-handler"
                                                  size="18"
                                                  @click.native="handleSubItemDel(index, subIndex)"></Icon>
                                        </div>
                                        <div class="subsubItemList" v-if="subItem.subData && subItem.subData.length">
                                            <!-- 三级标签 -->
                                            <div class="item-header-flex"
                                                 v-for="(subSubItem ,subSubIndex) in subItem.subData"
                                                 :key="subSubIndex">
                                                <Input :value="subSubItem.text"
                                                       class="dragable-ignore"
                                                       style="margin: 2px 8px"
                                                       @on-change="handleSubSubItemChange(index, subIndex, subSubIndex , $event)"/>
                                                <labelAttr :item="subSubItem" @saveChange="saveChange"/>
                                                <Icon type="ios-trash"
                                                      class="dragable-ignore del-handler"
                                                      size="18"
                                                      @click.native="handleSubSubItemDel(index, subIndex , subSubIndex)">
                                                </Icon>
                                            </div>
                                        </div>
                                        <Button type="primary" icon="md-add"
                                                @click.native="addSubSubItem(index, subIndex)"
                                                style="margin:5px 5px 5px 28px;">{{$t('template_add_three_level_label')}}
                                        </Button>
                                    </div>
                                    <Button type="primary" icon="md-add" @click.native="addSubItem(index)"
                                            style="margin:5px;">{{$t('template_add_secondary_tags')}}
                                    </Button>
                                </div>
                            </transition>
                        </div>
                    </draggable>
                </div>
                <Button type="primary" icon="md-add" @click.native="addGroupItem" style="margin:5px;">{{$t('template_add_tag_group')}}</Button>
            </template>
            <div class="excel-import">
                <Button type="primary" icon="md-add" @click.native="handleImportClick">{{$t('template_excel_import')}}</Button>
                <a class="demo-file-link" :href="staticBase + '/template/tag-import-template.xlsx'"
                   :download="$t('template_key_components_excel_import_templates')">{{$t('template_download_template')}}</a>
                <input type="file" class="js-filepicker"
                       ref="excel-file-picker" style="display:none;"
                       accept=".xls,.xlsx,.csv"
                       @change="handleExcelFile"
                >
            </div>
        </div>
    </div>
</template>
<script>
    import draggable from 'vuedraggable';
    import labelAttr from './label-attr';
    import colorPicker from '../color-picker';
    import Util from '@/libs/util';
    import api from '@/api';

    export default {
        name: 'tag-editor',
        props: {
            config: {
                type: Object,
                required: true
            },
            path: {
                type: String,
                required: true
            }
        },
        data () {
            return {
                staticBase: api.staticBase,
                module: {},
                sortOptions: {
                    animation: 200,
                    scrollSensitivity: 15,
                    scrollSpeed: 20,
                    sort: true,
                    handle: '.drag-handle',
                    preventOnFilter: false,
                    ghostClass: 'ghost'
                },
                collapseInfo: [],
                visible: false,
                subType: '',
                defaultColor: '#ffff00',
            };
        },
        mounted () {
            this.module = this.config;
            this.defaultColor = this.config.defaultColor || this.defaultColor;
            // 考虑单个和分组
            if (this.module.subType === 'single') {
                this.$set(this.module, 'tagIsUnique',
                    typeof this.module.tagIsUnique !== 'undefined' ? +this.module.tagIsUnique : 0);
            } else {
                this.$set(this.module, 'tagIsUnique',
                    typeof this.module.tagIsUnique !== 'undefined' ? +this.module.tagIsUnique : 1);
            }
            this.$set(this.module, 'tagIsSearchAble',
                typeof this.module.tagIsSearchAble !== 'undefined' ? this.module.tagIsSearchAble : true);
            this.$set(this.module, 'pointPositionNoLimit', Boolean(this.module.pointPositionNoLimit));
            this.$set(this.module, 'pointTagShapeType', this.module.pointTagShapeType || []);
            this.$set(this.module, 'tagGroupOpen', this.module.tagGroupOpen || false);
            this.$set(this.module, 'tagLayoutType', this.module.tagLayoutType || 'list');
            this.subType = this.config.subType;
            this.collapseInfo = this.module.data.map(() => {
                return {
                    collapsed: false
                };
            });
        },
        watch: {
            config: {
                handler: function (config) {
                    this.module = config;
                    this.subType = this.config.subType;
                    this.defaultColor = config.defaultColor || this.defaultColor;
                    // 考虑单个和分组
                    if (this.module.subType === 'single') {
                        this.$set(this.module, 'tagIsUnique',
                            typeof this.module.tagIsUnique !== 'undefined' ? +this.module.tagIsUnique : 0);
                    } else {
                        this.$set(this.module, 'tagIsUnique',
                            typeof this.module.tagIsUnique !== 'undefined' ? +this.module.tagIsUnique : 1);
                    }
                },
                deep: true,
            }
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
        },
        methods: {
            defaultColorChange (color) {
                this.defaultColor = color;
                let listData = [];
                if (this.module.subType === 'single') {
                    listData = this.listData.map((item) => {
                        item.color = color;
                        return item;
                    });
                } else {
                    listData = this.listData.map((item) => {
                        item.subData = item.subData.map(subItem => {
                            if (subItem.subData.length) {
                                subItem.subData = subItem.subData.map(subSubItem => {
                                    subSubItem.color = color;
                                    return subSubItem;
                                });
                            } else {
                                subItem.color = color;
                            }
                            return subItem;
                        });
                        return item;
                    });
                }
                this.listData = listData;
                this.module.defaultColor = color;
                this.saveChange();
            },
            handleTypeChange (subType) {
                if (subType === this.module.subType) {
                    return;
                }
                this.module.subType = subType;
                if (subType === 'single') {
                    this.module.deepLevel = 1;
                    this.$set(this.module, 'tagIsRequired', 0);
                    // this.$set(this.module, 'tagIsUnique', 0);
                    this.listData = this.listData.map(item => {
                        return {
                            text: item.text,
                            shortValue: '',
                            color: this.defaultColor,
                            minWidth: 0,
                            minHeight: 0,
                            maxWidth: 0,
                            maxHeight: 0,
                            exampleImageSrc: '',
                        };
                    });
                } else {
                    this.module.deepLevel = 2;
                    delete this.module.tagIsRequired;
                    this.$set(this.module, 'tagGroupOpen', false);
                    this.$set(this.module, 'tagLayoutType', this.module.tagLayoutType || 'list');
                    this.listData = this.listData.map((item) => {
                        return {
                            text: item.text,
                            tagIsRequired: 0,
                            tagIsUnique: 0,
                            subData: []
                        };
                    });
                }
                this.saveChange();
            },
            handleImportClick () {
                this.$refs['excel-file-picker'].click(); // 打开文件选择框
            },
            handleExcelFile (e) {
                Util.xlsxToJson(e.target.files[0], (data) => {
                    let result = this.parseJsonToTagData(data);
                    if (result) {
                        this.listData = result.data;
                        this.module.subType = this.subType = result.subType;
                        if (result.subType === 'group') {
                            this.collapseInfo = this.listData.map((item, index) => {
                                let collapsed = this.collapseInfo[index] && this.collapseInfo[index].collapsed;
                                return {
                                    collapsed: typeof collapsed === 'undefined' ? true : collapsed
                                };
                            });
                        } else {
                            this.module.deepLevel = 1;
                        }
                        // 清空input元素的值，避免两次选择相同的文件不触发change事件
                        this.$refs['excel-file-picker'].value = '';
                        this.saveChange();
                    }
                }, this);
            },
            pickItemValue (v) {
                let shortValue = (v.shortValue || '').trim();
                let color = (v.color || this.defaultColor).trim();
                let minWidth = Number(v.minWidth || '');
                let minHeight = Number(v.minHeight || '');
                let maxWidth = Number(v.maxWidth || '');
                let maxHeight = Number(v.maxHeight || '');
                let exampleImageSrc = (v.exampleImageSrc || '').trim();
                minWidth = isNaN(minWidth) ? 0 : minWidth;
                minHeight = isNaN(minHeight) ? 0 : minHeight;
                maxWidth = isNaN(maxWidth) ? 0 : maxWidth;
                maxHeight = isNaN(maxHeight) ? 0 : maxHeight;
                return {
                    shortValue,
                    color,
                    minWidth,
                    minHeight,
                    maxWidth,
                    maxHeight,
                    exampleImageSrc,
                };
            },
            parseJsonToTagData (resultJson) {
                let data = [];
                let type = 'single';
                if (Array.isArray(resultJson) && resultJson.length > 0) {
                    let firstItem = resultJson[0];
                    let deepLevel = 2;
                    // 根据label_1  label_2  label_3  来确定标签的类型，单个或分组，两层或三层
                    if (firstItem.label_1) {
                        if (firstItem.label_2) {
                            type = 'group';
                            firstItem.label_3 && (deepLevel = 3);
                        }
                    } else {
                        this.$Message.error({
                            content: this.$t('template_table_data_format_incorrect'),
                            duration: 2
                        });
                        return false;
                    }
                    if (type === 'single') {
                        // 单个便签类型
                        resultJson.forEach((v) => {
                            let item = this.pickItemValue(v);
                            item.text = v.label_1;
                            data.push(item);
                        });
                    } else {
                        // 标签组类型
                        // 两层的标签组
                        if (deepLevel === 2) {
                            let root = {};
                            resultJson.forEach(function (v) {
                                if (root[v.label_1]) {
                                    root[v.label_1].push(v);
                                } else {
                                    root[v.label_1] = [v];
                                }
                            });
                            for (let key in root) {
                                if (root.hasOwnProperty(key)) {
                                    let item = {
                                        text: key,
                                        subData: [],
                                        tagIsRequired: 0,
                                        tagIsUnique: 0,
                                    };
                                    root[key].forEach((v) => {
                                        let subItem = this.pickItemValue(v);
                                        subItem.subData = [];
                                        subItem.text = v.label_2;
                                        subItem.text && item.subData.push(subItem);
                                    });
                                    data.push(item);
                                }
                            }
                        } else {
                            // 三层标签组
                            let root = {};
                            // 遍历一级标签
                            resultJson.forEach(function (v) {
                                if (root[v.label_1]) {
                                    root[v.label_1].push(v);
                                } else {
                                    root[v.label_1] = [v];
                                }
                            });
                            // 在属于同一一级标签中 遍历二级标签分类
                            for (let key in root) {
                                if (root.hasOwnProperty(key)) {
                                    let subRoot = {};
                                    root[key].forEach(function (v) {
                                        if (subRoot[v.label_2]) {
                                            subRoot[v.label_2].push(v);
                                        } else {
                                            subRoot[v.label_2] = [v];
                                        }
                                    });
                                    root[key] = subRoot;
                                }
                            }
                            // 处理遍历后的数据
                            for (let key in root) {
                                if (root.hasOwnProperty(key)) {
                                    let item = {
                                        text: key,
                                        subData: [],
                                        tagIsRequired: 0,
                                        tagIsUnique: 0,
                                    };
                                    for (let subKey in root[key]) {
                                        if (root[key].hasOwnProperty(subKey)) {
                                            let subItem = {
                                                text: subKey,
                                                subData: []
                                            };
                                            root[key][subKey].forEach((v) => {
                                                let subSubItem = this.pickItemValue(v);
                                                subSubItem.text = v.label_3;
                                                subItem.subData.push(subSubItem);
                                            });
                                            item.subData.push(subItem);
                                        }
                                    }
                                    data.push(item);
                                }
                            }
                        }
                    }
                }
                return {
                    data: data,
                    subType: type,
                };
            },
            toggleCollapse (index) {
                let collapsed = this.collapseInfo[index].collapsed;
                this.collapseInfo[index].collapsed = !collapsed;
            },
            handleItemChange (index, e) {
                let item = this.listData[index];
                item.text = e.target.value;
                this.listData.splice(index, 1, item);
                this.saveChange();
            },
            handleItemDel (index) {
                this.listData.splice(index, 1);
                this.collapseInfo.splice(index, 1);
                this.saveChange();
            },
            addSingleItem () {
                this.listData.push({
                    text: '',
                    shortValue: '',
                    color: this.defaultColor,
                    minWidth: 0,
                    minHeight: 0,
                    maxWidth: 0,
                    maxHeight: 0,
                    exampleImageSrc: '',
                });
                this.collapseInfo.push({collapsed: false});
            },
            handleSubItemDel (index, subIndex) {
                this.listData[index].subData.splice(subIndex, 1);
                this.saveChange();
            },
            handleSubItemChange (index, subIndex, e) {
                let subItem = this.listData[index].subData[subIndex];
                subItem.text = e.target.value;
                this.listData[index].subData.splice(subIndex, 1, subItem);
                this.saveChange();
            },
            addSubItem (index) {
                this.listData[index].subData.push({
                    text: '',
                    shortValue: '',
                    color: this.defaultColor,
                    minWidth: 0,
                    minHeight: 0,
                    maxWidth: 0,
                    maxHeight: 0,
                    exampleImageSrc: '',
                    subData: [],
                });
            },
            handleSubSubItemDel (index, subIndex, subSubIndex) {
                this.listData[index].subData[subIndex].subData.splice(subSubIndex, 1);
                this.saveChange();
            },
            handleSubSubItemChange (index, subIndex, subSubIndex, e) {
                let subItem = this.listData[index].subData[subIndex].subData[subSubIndex];
                subItem.text = e.target.value;
                this.listData[index].subData[subIndex].subData.splice(subSubIndex, 1, subItem);
                this.saveChange();
            },
            addSubSubItem (index, subIndex) {
                this.listData[index].subData[subIndex].subData.push({
                    text: '',
                    shortValue: '',
                    color: this.defaultColor,
                    minWidth: 0,
                    minHeight: 0,
                    maxWidth: 0,
                    maxHeight: 0,
                    exampleImageSrc: '',
                });
            },
            saveChange () {
                this.module.subType = this.subType;
                if (this.subType === 'group') {
                    let deepLevelIs3 = this.listData.some((item) => {
                        return item.subData.some((subItem) => {
                            return subItem.subData.length > 0;
                        });
                    });
                    this.module.deepLevel = deepLevelIs3 ? 3 : 2;
                } else {
                    this.module.deepLevel = 1;
                }
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
            handleSortEnd () {
                this.saveChange();
            },
            addGroupItem () {
                this.listData.push({
                    text: '',
                    tagIsRequired: 0,
                    tagIsUnique: 0,
                    subData: []
                });
                this.collapseInfo.push({
                    collapsed: false
                });
            }
        },
        components: {
            draggable,
            labelAttr,
            colorPicker,
        }
    };
</script>
<style lang="scss">
    @import "./style";

    .editor-advanced-config {
        margin: 0 15px;
        .ivu-collapse-content {
            padding: 12px 0;
            .ivu-collapse-content-box {
                padding: 0;
            }
        }
    }

    .tag-info-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin: 5px auto;
        flex-wrap: wrap;
    }

    .subsubItemList {
        margin-left: 20px;
    }
</style>

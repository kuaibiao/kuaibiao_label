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
                   @on-change="saveChange"></Input>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_editing_options')}}</h4>
            <h6 class="editor-subheader">{{$t('template_set_option_content_sort')}}</h6>
            <div class="sortable-list">
                <draggable v-model="listData"
                           tag="div"
                           v-bind="sortOptions"
                           @end="handleSortEnd">
                    <div class="item-header-flex" v-for="(item ,index) in listData" :key="item.index">
                        <Icon type="ios-menu" class="drag-handle" style="cursor:move; margin-right: 4px;"></Icon>
                        <Checkbox v-model="item.checked"
                                  @on-change="handleCheckboxChange(item, index)"
                                  style="margin-right: 4px;margin-left: 4px;"></Checkbox>
                        <Input :value="item.text" style=" margin:2px 4px"
                               @on-change="handleItemChange(index, $event)"></Input>
                        <div style="display: inline-block;"
                             @keydown="setKeyCode(index, $event)">
                            <Button icon="ios-keypad" style="margin-right: 4px;">{{ item.keyBoard | keyMap }}</Button>
                        </div>

                        <Icon type="ios-trash" class="del-handler" size="18"
                              @click.native="handleItemDel(index)"></Icon>
                    </div>
                </draggable>
            </div>
            <Button type="primary" icon="md-add" @click.native="addItem">{{$t('template_add_option')}}</Button>
            <Button type="primary" icon="md-add" @click.native="addMoreItem">{{$t('template_batch_add_option')}}</Button>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_sets_option_layout')}}</h4>
            <RadioGroup v-model="vertical" @on-change="saveChange">
                <Radio label="h">{{$t('template_horizontal_array')}}</Radio>
                <Radio label="v">{{$t('template_vertical_array')}}</Radio>
            </RadioGroup>
        </div>
        <div class="editor-item" style="margin-top:15px;">
            <Checkbox v-model="module.required"
                      @on-change="saveChange"> {{$t('template_whether_must')}}
            </Checkbox>
        </div>
        <BatchAddOption
                ref="batchAdd"
                :optionList="optionList"
                @update="handleAddMoreItem"
        ></BatchAddOption>
    </div>
</template>
<script>
    import draggable from 'vuedraggable';
    import mixin from '../mixins/module-editor-mixin';

    export default {
        name: 'form-checkbox-editor',
        mixins: [mixin],
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
                module: {},
                vertical: 'h',
                sortOptions: {
                    animation: 200,
                    scrollSensitivity: 15,
                    scrollSpeed: 20,
                    sort: true,
                    handle: '.drag-handle',
                    ghostClass: 'ghost'
                },
                optionList: [],
            };
        },
        mounted () {
            this.module = this.config;
            this.vertical = this.module.vertical ? 'v' : 'h';
        },
        computed: {
            listData: {
                get: function () {
                    return this.module.data;
                },
                set: function (newValue) {
                    this.module.data = newValue;
                }
            }
        },
        watch: {
            config: {
                handler: function (config) {
                    this.module = config;
                    this.vertical = this.module.vertical ? 'v' : 'h';
                },
                deep: true,
            }
        },
        methods: {
            handleCheckboxChange (item) {
                let values = this.module.value;
                let index = values.indexOf(item.text);
                if (index > -1) {
                    !item.checked && values.splice(index, 1);
                } else {
                    item.checked && values.push(item.text);
                }
                this.module.value = values;
                this.saveChange();
            },
            handleItemChange (index, e) {
                let item = this.listData[index];
                item.text = e.target.value;
                this.listData.splice(index, 1, item);
                if (item.checked) { // 设置默认选择的值更改时需要同步默认值
                    let values = [];
                    this.listData.forEach(item => {
                        if (item.checked) {
                            values.push(item.text);
                        }
                    });
                    this.module.value = values;
                }
                this.saveChange();
            },
            saveChange () {
                this.module.vertical = this.vertical === 'v';
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
            handleSortEnd () {
                this.saveChange();
            },
            handleAddMoreItem (list) {
                // 删除的选项
                let delData = this.listData.filter(v => {
                    return list.indexOf(v.text) === -1;
                });
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
                // 清除删除选项设置的快捷键记录
                this.clearKeyCode(delData);
                this.listData = retainData.concat(list.map((v) => {
                    return {
                        text: v,
                        checked: false
                    };
                }));
                this.module.value = retainData.filter(v => {
                    return v.checked;
                }).map(v => v.text);
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
                    text: '',
                    checked: false
                });
            }
        },
        components: {
            draggable,
            BatchAddOption: () => import('../batch-add-option.vue'),
        }
    };
</script>
<style lang="scss">
    @import "./style";
</style>

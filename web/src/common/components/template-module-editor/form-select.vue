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
                   @on-change="saveChange"> </Input>
        </div>
        <div class="editor-item">
            <h4 class="editor-header">{{$t('template_editing_options')}}</h4>
            <h6 class="editor-subheader">{{$t('template_set_option_content_sort')}}</h6>
            <div class="sortable-list">
                <draggable v-model="listData"
                           tag="div"
                           v-bind="sortOptions"
                           @end="handleSortEnd">
                    <div class="item-header-flex" v-for="(item ,index) in listData" :key="index">
                        <Icon type="ios-menu" class="drag-handle" style="cursor:move; margin-right: 4px;"></Icon>
                        <Checkbox v-model="item.selected"
                                  @on-change="handleCheckboxChange(item, index)"
                                  style="margin-right: 4px;margin-left: 4px;"
                        ></Checkbox>
                        <Input :value="item.text" style="margin:2px 8px" @on-change="handleItemChange(index, $event)"></Input>
                        <Icon type="ios-trash" class="del-handler" size="18" @click.native="handleItemDel(index)"></Icon>
                    </div>
                </draggable>
            </div>
            <Button type="primary" icon="md-add" @click.native="addItem">{{$t('template_add_option')}}</Button>
            <Button type="primary" icon="md-add" @click.native="addMoreItem">{{$t('template_batch_add_option')}}</Button>
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

    export default {
        name: 'form-select-editor',
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
                },
                deep: true,
            }
        },
        methods: {
            handleItemDel (index) {
                this.listData.splice(index, 1);
                this.saveChange();
            },
            handleCheckboxChange (item, cIndex) {
                // iview Select 组件 multiple 动态切换存在bug, 单选模式切到多选没问题，多选到单选有问题
                // let values = this.module.value;
                // if(Array.isArray(values) ) {
                //     let index = values.indexOf(item.text);
                //     if(index > -1) {
                //       !item.selected && values.splice(index, 1);
                //     } else {
                //       item.selected && values.push(item.text);
                //     }
                //     if(values.length > 1) {
                //       this.module.multiple = true;
                //     } else {
                //       values = values.toString();
                //       this.module.multiple = false;
                //     }
                // } else {
                //   if(values && item.text !== values) {
                //     values = [values, item.text];
                //     this.module.multiple = true;
                //   } else {
                //     values = item.text;
                //     this.module.multiple = false;
                //   }
                // }
                // 限制单选
                if (item.selected) {
                    this.module.value = item.text;
                    this.listData = this.listData.map((item, index) => {
                        item.selected = index === cIndex;
                        return item;
                    });
                }
                this.saveChange();
            },
            handleItemChange (index, e) {
                let item = this.listData[index];
                item.text = e.target.value;
                if (item.selected) { // 设置默认选择的值更改时需要同步默认值
                    this.module.value = item.text;
                }
                this.listData.splice(index, 1, item);
                this.saveChange();
            },
            saveChange () {
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
            handleSortEnd () {
                this.saveChange();
            },
            handleAddMoreItem (list) {
                // // 删除的选项
                // let delData = this.listData.filter(v => {
                //     return list.indexOf(v.text) === -1;
                // });
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
                // this.clearKeyCode(delData);
                this.listData = retainData.concat(list.map((v) => {
                    return {
                        text: v,
                        selected: false
                    };
                }));
                let [item] = retainData.filter(v => {
                    return v.selected;
                });
                this.module.value = (item && item.text) || '';
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
                    selected: false
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

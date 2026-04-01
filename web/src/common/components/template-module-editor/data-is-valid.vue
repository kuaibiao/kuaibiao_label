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
            <h4 class="editor-header">{{$t('template_editing_options')}}</h4>
            <div class="sortable-list">
                <div class="item-header-flex" v-for="(item ,index) in listData" :key="index">
                    <Checkbox v-model="item.checked"
                              :disabled="item.checked"
                              @on-change="handleRadioChange(item, index)"
                              style="margin-right: 4px;margin-left: 4px;"></Checkbox>
                    <Input :value="item.text" style="margin:2px 4px"
                           disabled/>
                    <div style="display: inline-block;"
                         @keydown="setKeyCode(index, $event)">
                        <Button icon="ios-keypad" style="margin-right: 4px;">{{ item.keyBoard | keyMap }}</Button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
<script>
    import mixin from '../mixins/module-editor-mixin';
    export default {
        name: 'data-is-valid-editor',
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
            handleRadioChange (item, cIndex) {
                if (item.checked) {
                    this.module.value = item.text;
                    this.listData = this.listData.map((item, index) => {
                        item.checked = index === cIndex;
                        return item;
                    });
                }
                // 与多选框编辑不同 数据清洗 必须有默认值
                // else {
                //     this.listData = this.listData.map((item, index) => {
                //         index === cIndex && (item.checked = false);
                //         return item;
                //     });
                //     this.module.value = '';
                // }
                this.saveChange();
            },
            handleItemChange (index, e) {
                let item = this.listData[index];
                item.text = e.target.value;
                if (item.checked) { // 设置默认选择的值更改时需要同步默认值
                    this.module.value = item.text;
                }
                this.listData.splice(index, 1, item);
                this.saveChange();
            },
            saveChange () {
                this.module.vertical = this.vertical === 'v';
                this.$store.commit('saveModule', {
                    path: this.path,
                    moduleData: this.module
                });
            },
        },
    };
</script>
<style lang="scss">
    @import "./style";
    .ivu-input[disabled], fieldset[disabled] .ivu-input {
        color: #333;
    }
</style>

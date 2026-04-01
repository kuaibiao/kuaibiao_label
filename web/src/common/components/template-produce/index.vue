<template>
    <div class="template-preview" ref="templateView">
        <component :is="component.type"
                   v-for="(component, index) in config" :key="index"
                   :config="component"
                   :scene="scene"
                   :path="index + ','">
        </component>
    </div>
</template>
<script>
    import {
        SingleInput,
        MultiInput,
        FormRadio,
        AudioFilePlaceholder,
        ExternalLink,
        FormCheckbox,
        FormSelect,
        FormUpload,
        TaskFilePlaceholder,
        KeyPoint,
        KeyPointGroup,
        Layout,
        Ocr,
        ShowImg,
        ShowText,
        Tag,
        VideoFilePlaceholder,
        TextFilePlaceHolder,
        DataIsValid,
        ImageLabelTool,
        FormGroup
    } from '../template-produce-module/index';
    export default {
        name: 'template-produce',
        props: {
            config: {
                type: Array,
                required: true
            },
            scene: {
                type: String,
                required: true
            }
        },
        data () {
            return {
                formType: [
                    'single-input',
                    'multi-input',
                    'form-radio',
                    'form-checkbox',
                    'form-select',
                    'data-is-valid',
                    // "form-upload"
                ]
            };
        },
        mounted () {
            setTimeout(() => {
                // 设置容器的最大高度
                let ele = this.$refs.templateView;
                let top = ele.getBoundingClientRect().top;
                // querySelector 会返回符合规则的第一个 Element,  querySelectorAll 会返回符合规则的所有元素集合 nodeList
                let d = ele.querySelector('[data-tpl-type="layout"] > .layout .children-con:last-child');
                let height = window.innerHeight - top;
                height = height > 520 ? height : 520;
                // ele.style.minHeight = height + 'px';
                if (d) {
                    d.style.maxHeight = height + 'px';
                    d.style.overflowY = 'auto';
                    d.style.overflowX = 'visible';
                    d.style.zIndex = 1;
                }
            }, 200);
        },
        methods: {
            /**
             * 区分 layout 或者 form-group
             * form-group 内的数据 统计为局部
             * form-group 外的 统计为整体
             */
            getLayoutChildrenData (layout) {
                let column0 = this.getFormComponentData(layout.column0.children);
                let column1 = this.getFormComponentData(layout.column1.children);
                return [...column0, ...column1];
            },
            getFormComponentData (source) { // 获取source里全部表单元素信息
                let result = [];
                source.forEach((item) => {
                    let itemData = [];
                    if (item.type === 'layout') { // 只处理 layout 内部的表单 会过滤掉form-group内的
                        itemData = itemData.concat(this.getLayoutChildrenData(item));
                    } else if (~this.formType.indexOf(item.type)) {
                        itemData.push({
                            id: item.id,
                            type: item.type,
                            value: item.value || '',
                            header: item.header,
                            required: item.required,
                            cBy: item.cBy,
                            cTime: item.cTime,
                            mBy: item.mBy,
                            mTime: item.mTime,
                        });
                        // todo 上传组件单独处理
                    }
                    [].push.apply(result, itemData);
                });
                return result;
            },

            getGlobalData () {
                return this.getFormComponentData(this.config);
            },
            getDataIsValid () {
                let data = this.getGlobalData();
                for (let i = 0, l = data.length; i < l; i++) {
                    let item = data[i];
                    if (data[i].type === 'data-is-valid') {
                        return {
                            id: item.id,
                            type: item.type,
                            value: item.value || '',
                            header: item.header,
                            required: item.required,
                            cBy: item.cBy,
                            cTime: item.cTime,
                            mBy: item.mBy,
                            mTime: item.mTime,
                        };
                    }
                }
                return null;
            },
            // 获取form-group 内的表单数据，用于局部标注
            getLayoutChildrenLocalData (layout) {
                let column0 = this.getFormLocalData(layout.column0.children);
                let column1 = this.getFormLocalData(layout.column1.children);
                return [...column0, ...column1];
            },
            getFormLocalData (source) {
                let result = [];
                source.forEach((item) => {
                    let itemData = [];
                    if (item.type === 'form-group') {
                        itemData = this.getFormGroupData(item);
                    } else if (item.type === 'layout') {
                        itemData = this.getLayoutChildrenLocalData(item);
                    }
                    [].push.apply(result, itemData);
                });
                return result;
            },

            getFormGroupData (formfroup) {
                let result = [];
                formfroup.children.forEach((item) => {
                    let itemData = [];
                    if (~this.formType.indexOf(item.type)) {
                        itemData.push({
                            id: item.id,
                            type: item.type,
                            value: item.value || '',
                            header: item.header,
                            required: item.required,
                            cBy: item.cBy,
                            cTime: item.cTime,
                            mBy: item.mBy,
                            mTime: item.mTime,
                        });
                    } else if (item.type === 'form-group') {
                        itemData = this.getFormGroupData(item);
                    } else if (item.type === 'layout') {
                        itemData = this.getLayoutChildrenLocalData(item);
                    }
                    [].push.apply(result, itemData);
                });
                return [...result];
            },
            getData () {
                return this.getFormLocalData(this.config);
            },
        },
        components: {
            // 引入所有模板组件 异步加载
            'form-group': FormGroup,
            'single-input': SingleInput,
            'multi-input': MultiInput,
            'form-radio': FormRadio,
            'audio-file-placeholder': AudioFilePlaceholder,
            'external-link': ExternalLink,
            'form-checkbox': FormCheckbox,
            'form-select': FormSelect,
            'form-upload': FormUpload,
            'task-file-placeholder': TaskFilePlaceholder,
            'key-point': KeyPoint,
            'key-point-group': KeyPointGroup,
            'show-img': ShowImg,
            'show-text': ShowText,
            'video-file-placeholder': VideoFilePlaceholder,
            'layout': Layout,
            'ocr': Ocr,
            'tag': Tag,
            'text-file-placeholder': TextFilePlaceHolder,
            'data-is-valid': DataIsValid,
            'image-label-tool': ImageLabelTool,
        }
    };
</script>
<style lang="scss">
    @import "@/styles/template-common.scss";
</style>


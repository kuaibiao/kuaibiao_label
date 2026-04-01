<template>
    <div>
        <Row class="template-edit-header">
            <i-col span="8">
                <i-input v-model="templateName">
                    <span slot="prepend">{{$t('project_template_name')}}:</span>
                </i-input>
            </i-col>
            <i-col span="8" class="template_type">
                {{$t('project_type')}}：
                <RadioGroup v-model="template_type" type="button">
                    <Radio v-for="(item,index) in templateTypeList" :label="index" :key="index">{{item}}</Radio>
                </RadioGroup>
            </i-col>
            <i-col span="8" class="text-right">
                <Button type="primary" @click="saveTemplate"> {{$t('project_save')}}</Button>
                <Button @click="cancelEdit"> {{$t('project_cancel')}}</Button>
            </i-col>
        </Row>
        <Row class="template-setting-con margin-top-10" :gutter="6" type="flex">
            <i-col span="4" class="template-setting-left backclip-content" ref="settingLeft">
                <Collapse simple v-model="currentOpenPanel">
                    <Panel name="filePlaceholderModule">
                        {{$t('project_data_container')}}
                        <div class="list-draggable" slot="content">
                            <draggable v-model="filePlaceholderModule"
                                       tag="div"
                                       v-bind="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in filePlaceholderModule" :key="index"
                                           :config="component"
                                           :scene="'icon'">
                                </component>
                            </draggable>
                        </div>
                    </Panel>
                    <Panel name="descriptionModule">
                        {{$t('project_operating_instruction')}}
                        <div class="list-draggable" slot="content">
                            <draggable v-model="descriptionModule"
                                       tag="div"
                                       v-bind="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in descriptionModule" :key="index"
                                           :config="component"
                                           :scene="'icon'">
                                </component>
                            </draggable>
                        </div>
                    </Panel>
                    <Panel name="layoutModule">
                        {{$t('project_layout')}}
                        <div class="list-draggable" slot="content">
                            <draggable v-model="layoutModule"
                                       tag="div"
                                       v-bind="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in layoutModule" :key="index"
                                           :config="component"
                                           :scene="'icon'">
                                </component>
                            </draggable>
                        </div>
                    </Panel>
                    <Panel name="workerModule">
                        {{$t('project_operational_components')}}
                        <div class="list-draggable" slot="content">
                            <draggable v-model="workerModule"
                                       tag="div"
                                       v-bind="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in workerModule" :key="index"
                                           :config="component"
                                           :scene="'icon'">
                                </component>
                            </draggable>
                        </div>
                    </Panel>
                </Collapse>
            </i-col>
            <i-col span="14" class="template-setting-main backclip-content">
                <h3 class="template-setting-header">{{$t('project_template_design')}}</h3>
                <div class="list-sortable">
                    <draggable v-model="editModuleList"
                               tag="div"
                               v-bind="dropOptions"
                               @choose="handleChoose"
                               style="min-height: 420px;"
                               @change="handleChange"
                               @end="handleEnd"
                    >
                        <component :is="component.type"
                                   v-for="(component, index) in editModuleList" :key="index" :path="index + ','"
                                   :config="component"
                                   :scene="'edit'">
                        </component>
                    </draggable>
                </div>
            </i-col>
            <i-col span="6" class="template-setting-edit backclip-content">
                <h3 class="template-setting-header">{{$t('project_attribute_edit')}}</h3>
                <component :is="moduleEditor[currentModule.data && currentModule.data.type]"
                           :config="currentModule.data"
                           :path="currentModule.path"
                ></component>
            </i-col>
        </Row>
    </div>

</template>
<script>
    import api from '@/api';
    import uuid from 'uuid/v4';
    import util from '@/libs/util';
    import cloneDeep from 'lodash.clonedeep';
    import Vue from 'vue';
    import getDefaultConfig from 'components/template-default-config';
    import getBasicTemplate from '../../../common/basicTemplateConfig';
    import CategoryTemplateConfig from '../../../common/categoryTemplateConfig';

    const descriptionModuleList = [
        'key-point',
        'key-point-group',
        'show-img',
        'show-text'
    ];
    const filePlaceholderList = [
        'task-file-placeholder',
        'video-file-placeholder',
        'audio-file-placeholder',
        'text-file-placeholder'
    ];
    const layoutModuleList = [
        'layout',
        'form-group'
    ];
    const workerModuleList = [
        'data-is-valid',
        'image-label-tool',
        'tag',
        'single-input',
        'multi-input',
        'form-radio',
        'form-checkbox',
        'form-select',
        'ocr',
        // 'form-upload',
    ];
    export default {
        name: 'template-edit',
        props: {
            tempId: {
                type: String
            },
            categoryId: {
                type: String
            },
        },
        data () {
            return {
                currentOpenPanel: ['filePlaceholderModule', 'descriptionModule', 'workerModule', 'layoutModule'],
                template_type: 1,
                templateTypeList: [],
                templateId: '',
                templateName: '',
                templateConfig: [],
                categoryTemplateConfig: {
                    list: [],
                    required: []
                },
                // 左侧展示的组件
                filePlaceholderModule: [],
                descriptionModule: [],
                workerModule: [],
                layoutModule: [],
                dragOptions: {
                    animation: 200,
                    group: {
                        name: 'description',
                        pull: 'clone',
                        put: false
                    },
                    sort: false
                },
                dropOptions: {
                    animation: 200,
                    group: {
                        name: 'description',
                        pull: true,
                        put: true
                    },
                    scrollSensitivity: 15,
                    scrollSpeed: 20,
                    sort: true,
                    filter: '.template-delete',
                    ghostClass: 'ghost'
                },
                moduleEditor: (() => {
                    let moduleEditor = {};
                    [
                        'image-label-tool',
                        'data-is-valid',
                        'form-group',
                        'single-input',
                        'multi-input',
                        'form-radio',
                        'form-checkbox',
                        'form-select',
                        'ocr',
                        'form-upload',
                        'tag',
                        'task-file-placeholder',
                        'video-file-placeholder',
                        'audio-file-placeholder',
                        'text-file-placeholder',
                        'external-link',
                        'key-point',
                        'key-point-group',
                        'show-img',
                        'show-text',
                        'layout'
                    ].map(k => {
                        let v = k + '-editor';
                        moduleEditor[k] = v;
                    });
                    return moduleEditor;
                })(),
                withAnchorType: [
                    'task-file-placeholder',
                    'video-file-placeholder',
                    'audio-file-placeholder',
                    'text-file-placeholder'
                ]
            };
        },
        computed: {
            editModuleList: {
                get () {
                    return this.$store.state.template.editModuleList;
                },
                set (value) {
                    this.$store.commit('updateList', value);
                }
            },
            currentId: function () {
                return this.$store.state.template.currentModuleData.data.id;
            },
            currentModule: {
                get () {
                    return this.$store.state.template.currentModuleData;
                },
                set (value) {
                    this.$store.commit('setCurrentModule', value);
                }
            }
        },
        watch: {
            currentId: function (id) {
                $('.selected-module').removeClass('selected-module');
                $('[data-id=' + id + ']').addClass('selected-module');
            },
        },
        mounted () {
            document.body.ondrop = function (event) {
                event.preventDefault();
                event.stopPropagation();
            };
            this.$store.commit('clearUserKeyMap');
            this.templateId = this.tempId || '';
            if (this.templateId === 'new') {
                this.editModuleList = getBasicTemplate();
            } else {
                this.getTemplateDetail();
            }
            this.currentModule = {
                data: {},
                path: ''
            };
            this.getCategoryList();
        },
        methods: {
            categoryChange (id, override = true) {
                let category = {};
                for (let i = 0, l = this.categoryList.length; i < l; i++) {
                    let item = this.categoryList[i];
                    if (item.id === id) {
                        category = item;
                        break;
                    }
                }
                if (override) {
                    this.editModuleList = getBasicTemplate(category.view);
                }
                let categoryTemplateConfig = this.categoryTemplateConfig = CategoryTemplateConfig[category.view] || {
                    list: [],
                    required: []
                };
                if (categoryTemplateConfig.list.length === 0) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('project_category_no_component'),
                    });
                    return;
                }
                // 左侧展示的组件
                this.filePlaceholderModule = filePlaceholderList
                    .filter(item => ~categoryTemplateConfig.list.indexOf(item))
                    .map(v => {
                        return getDefaultConfig(v);
                    });
                this.descriptionModule = descriptionModuleList
                    .filter(item => ~categoryTemplateConfig.list.indexOf(item))
                    .map(v => {
                        return getDefaultConfig(v);
                    });
                this.workerModule = workerModuleList
                    .filter(item => ~categoryTemplateConfig.list.indexOf(item)).map(v => {
                        return getDefaultConfig(v);
                    });
                this.layoutModule = layoutModuleList
                    .filter(item => ~categoryTemplateConfig.list.indexOf(item)).map(v => {
                        return getDefaultConfig(v);
                    });
            },
            saveTemplate () {
                if (this.templateName.trim().length === 0) {
                    this.$Message.warning({
                        content: this.$t('project_template_name_null'),
                        duration: 3
                    });
                    return;
                }
                if (this.editModuleList.length === 0) {
                    this.$Message.warning({
                        content: this.$t('project_template_content_null'),
                        duration: 3
                    });
                    return;
                }
                if (this.categoryTemplateConfig.required.length) {
                    // 检查是否添加了必要模块
                    let missModule = this.categoryTemplateConfig.required.filter((item) => {
                        return $(`.template-setting-main [data-tpl-type=${item}]`).length === 0;
                    });
                    // todo 在视觉上给出提示
                    if (missModule.length) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: this.$t('project_lack') + missModule.map(item => {
                                return this.$t('template-' + item);
                            }).join() + this.$t('project_modules'),
                            duration: 3
                        });
                        return;
                    }
                }
                let url =
                    this.tempId === 'new' ? api.template.create : api.template.update;
                let data = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    template_id: this.tempId,
                    config: JSON.stringify(this.editModuleList),
                    name: this.templateName,
                    type: this.template_type,
                    category_id: this.categoryId
                };
                this.tempId === 'new' && delete data.template_id;
                $.ajax({
                    url: url,
                    type: 'post',
                    data: data,
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.success({
                                content: this.$t('project_save_success'),
                                duration: 3
                            });
                            this.$emit('model-close', this.templateName);
                        }
                    },
                    error: () => {
                        this.$Message.error({
                            content: this.$t('project_save_error'),
                            duration: 3
                        });
                    }
                });
            },
            cancelEdit () {
                this.$emit('model-close');
            },
            getTemplateDetail () {
                $.ajax({
                    url: api.template.detail,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        template_id: this.tempId
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.templateName = res.data.template.name;
                            this.editModuleList = res.data.template.config || [];
                            this.template_type = res.data.template.type;
                            // if (res.data.projects.length) {
                            //     this.$emit('have-project-doing', res.data.projects);
                            // } else {
                            //     this.$emit('open-templateModal');
                            // }
                        }
                    },
                    error: () => {
                        this.$Message.warning({
                            content: this.$t('project_require_template_data_error'),
                            duration: 3
                        });
                    }
                });
            },
            // 选择某个组件
            handleChoose (e) {
                let path = e.item.getAttribute('path');
                this.$store.commit('chooseModule', path);
                Vue.nextTick(() => {
                    $('.selected-module').removeClass('selected-module');
                    $('[data-id=' + this.currentId + ']').addClass('selected-module');
                });
            },
            // 深度复制配置 https://github.com/SortableJS/Vue.Draggable/blob/master/README.md#clone
            handleClone (ori) {
                return cloneDeep(ori);
            },
            handleEnd () {
                $('.selected-module').removeClass('selected-module');
                let target = $('[data-id=' + this.currentId + ']');
                target.addClass('selected-module');
                let curPath = target.attr('path');
                curPath && this.$store.commit('chooseModule', curPath);
            },
            // https://github.com/SortableJS/Vue.Draggable/blob/master/README.md#events
            handleChange (e) {
                // 左侧拖入编辑预览区，编辑预览区内拖动会触发的事件
                // 拖入编辑区 或者拖入可容纳其它元素的容器内,例如 layout form-group
                if (e.added) {
                    e.added.element.scene = 'edit';
                    if (e.added.element.id === '') {
                        e.added.element.id = uuid();
                    }
                    if (~this.withAnchorType.indexOf(e.added.element.type)) {
                        let type = '';
                        if (e.added.element.anchor === '') {
                            switch (e.added.element.type) {
                                case 'task-file-placeholder':
                                    type = 'image';
                                    break;
                                case 'audio-file-placeholder':
                                    type = 'audio';
                                    break;
                                case 'video-file-placeholder':
                                    type = 'video';
                                    break;
                                case 'text-file-placeholder':
                                    type = 'text';
                                    break;
                            }
                            let counter = this.$store.state.template.placeholderCounter[type];
                            e.added.element.anchor =
                                type + '_url' + (counter > 1 ? counter - 1 : '');
                        }
                    }
                    setTimeout(() => {
                        $('.selected-module').removeClass('selected-module');
                        let id = e.added.element.id;
                        let target = $('[data-id=' + id + ']');
                        target.addClass('selected-module');
                        let curPath = target.attr('path');
                        curPath && this.$store.commit('chooseModule', curPath);
                    }, 100);
                }
            },
            getCategoryList () {
                $.ajax({
                    url: api.template.form,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.categoryList = res.data.categories || [];
                            this.templateTypeList = res.data.types || [];
                            this.categoryChange(this.categoryId, this.templateId === 'new');
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            }
        },
        beforeDestroy () {
            this.$store.commit('clearUserKeyMap');
        },
        components: {
            draggable: () => import('vuedraggable'),
            // 引入所有模板组件
            'form-group': () => import('components/template-module-management/form-group.vue'),
            'single-input': () => import('components/template-module-management/single-input.vue'),
            'multi-input': () => import('components/template-module-management/multi-input.vue'),
            'form-radio': () => import('components/template-module-management/form-radio.vue'),
            'audio-file-placeholder': () =>
                import('components/template-module-management/audio-placeholder.vue'),
            'external-link': () =>
                import('components/template-module-management/external-link.vue'),
            'form-checkbox': () =>
                import('components/template-module-management/form-checkbox.vue'),
            'form-select': () => import('components/template-module-management/form-select.vue'),
            'form-upload': () => import('components/template-module-management/form-upload.vue'),
            'task-file-placeholder': () =>
                import('components/template-module-management/image-placeholder.vue'),
            'key-point': () => import('components/template-module-management/key-point.vue'),
            'key-point-group': () => import('components/template-module-management/key-point-group.vue'),
            'show-img': () => import('components/template-module-management/show-img.vue'),
            'show-text': () => import('components/template-module-management/show-text.vue'),
            'video-file-placeholder': () =>
                import('components/template-module-management/video-placeholder.vue'),
            'text-file-placeholder': () =>
                import('components/template-module-management/text-placeholder.vue'),
            layout: () => import('components/template-module-management/layout.vue'),
            ocr: () => import('components/template-module-management/ocr.vue'),
            tag: () => import('components/template-module-management/tag.vue'),
            'data-is-valid': () => import('components/template-module-management/data-is-valid.vue'),
            'image-label-tool': () => import('components/template-module-management/image-label-tool.vue'),

            // 引入所有模板组件的编辑组件
            'form-group-editor': () =>
                import('components/template-module-editor/form-group.vue'),
            'single-input-editor': () =>
                import('components/template-module-editor/single-input.vue'),
            'multi-input-editor': () =>
                import('components/template-module-editor/multi-input.vue'),
            'form-radio-editor': () =>
                import('components/template-module-editor/form-radio.vue'),
            'audio-file-placeholder-editor': () =>
                import('components/template-module-editor/audio-placeholder.vue'),
            'external-link-editor': () =>
                import('components/template-module-editor/external-link.vue'),
            'form-checkbox-editor': () =>
                import('components/template-module-editor/form-checkbox.vue'),
            'form-select-editor': () =>
                import('components/template-module-editor/form-select.vue'),
            'form-upload-editor': () =>
                import('components/template-module-editor/form-upload.vue'),
            'task-file-placeholder-editor': () =>
                import('components/template-module-editor/image-placeholder.vue'),
            'key-point-editor': () =>
                import('components/template-module-editor/key-point.vue'),
            'key-point-group-editor': () =>
                import('components/template-module-editor/key-point-group.vue'),
            'show-img-editor': () =>
                import('components/template-module-editor/show-img.vue'),
            'show-text-editor': () =>
                import('components/template-module-editor/show-text.vue'),
            'video-file-placeholder-editor': () =>
                import('components/template-module-editor/video-placeholder.vue'),
            'text-file-placeholder-editor': () =>
                import('components/template-module-editor/text-placeholder.vue'),
            'layout-editor': () =>
                import('components/template-module-editor/layout.vue'),
            'ocr-editor': () => import('components/template-module-editor/ocr.vue'),
            'tag-editor': () => import('components/template-module-editor/tag.vue'),
            'data-is-valid-editor': () => import('components/template-module-editor/data-is-valid.vue'),
            'image-label-tool-editor': () => import('components/template-module-editor/image-label-tool.vue'),
        }
    };
</script>

<style lang="scss">
    @import "@/styles/template-common.scss";
    .backclip-content {
        background-clip: content-box;
    }
    .template-setting-con {
        border-radius: 6px;
        .template-setting-header {
            color: #3b5998;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding-bottom: 10px;
            border-bottom: 1px solid #c2c6cc;
            margin: 8px 8px 0;
        }
    }

    .template-setting-left {
        background-color: #fff;
        min-height: 510px;
        .template-header {
            color: #3b5998;
            font-size: 14px;
            font-weight: bold;
            text-align: center;
            padding-bottom: 14px;
            border-bottom: 1px solid #c2c6cc;
            margin-top: 5px;
            .template-header-desc {
                display: block;
                padding-top: 8px;
                font-size: 12px;
                color: #aaadb3;
                font-weight: 300;
            }
        }
        .list-draggable {
            margin-top: 15px;
            margin-bottom: 15px;
            cursor: pointer;
        }
    }

    .template-setting-main {
        background-color: #fff;
        min-height: 510px;
        .list-sortable {
            cursor: pointer;
            padding-bottom: 100px;
        }
    }

    .template-setting-edit {
        background-color: #fff;
        min-height: 510px;
    }

    .template-edit-header {
        padding: 5px;
    }
    .template_type {
        padding-left: 10px;
    }
</style>



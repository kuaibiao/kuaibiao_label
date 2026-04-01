<template>
    <div>
        <Row class="template-edit-header">
            <i-col span="20">
                <Form ref="formValidate" :model="templateForm" :label-width="80" inline :rules="ruleCustom" @submit.native.prevent id="templateForm">
                    <FormItem :label="$t('project_template_name') + ':'" prop="templateName" :show-message="false">
                        <i-input v-model="templateForm.templateName" :readonly="!canEditTemplate" style="width: 250px"></i-input>
                    </FormItem>
                    <FormItem :label="$t('project_type') + ':'" prop="template_type" :show-message="false">
                        <RadioGroup v-model="templateForm.template_type" type="button">
                            <Radio v-for="(item,index) in templateTypeList"
                                :label="index"
                                :key="index"
                                :disabled="!canEditTemplate"
                            >{{item}}
                            </Radio>
                        </RadioGroup>
                    </FormItem>
                    <FormItem :label="$t('project_template_category_name')" prop="category_id" :show-message="false">
                        <Select v-model="templateForm.category_id" :disabled="!canEditTemplate"
                                @on-change="categoryChange" style="width: auto;"
                        >
                            <Option v-for="item in categoryList"
                                    :value="item.id"
                                    :label="item.desc.name"
                                    :key="item.id">
                            </Option>
                        </Select>
                        <span style="color: red">{{$t('project_modifying_classification_tip')}}</span>
                    </FormItem>
                </Form>
            </i-col>
            <i-col span="4" class="text-right">
                <Button v-if="supportedPrevireCategory.indexOf(curCategory.view) !== -1" type="primary" @click="templatePreview"> {{$t('project_preview')}}</Button>
                <Button type="primary" @click="saveTemplate" v-if="canEditTemplate" :loading="saveTemplateLoading"> {{$t('project_save')}}</Button>
            </i-col>
        </Row>
        <Row class="template-setting-con margin-top-10" :gutter="6" type="flex">
            <i-col span="4" class="template-setting-left backclip-content" ref="settingLeft">
                <Collapse simple v-model="currentOpenPanel">
                    <Panel name="filePlaceholderModule">
                        {{$t('project_data_container')}}
                        <div class="list-draggable" slot="content">
                            <draggable v-model="filePlaceholderModule"
                                       element="div"
                                       :options="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in filePlaceholderModule"
                                           :key="index"
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
                                       element="div"
                                       :options="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in descriptionModule"
                                           :key="index"
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
                                       element="div"
                                       :options="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in layoutModule"
                                           :key="index"
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
                                       element="div"
                                       :options="dragOptions"
                                       :clone="handleClone"
                            >
                                <component :is="component.type"
                                           v-for="(component, index) in workerModule"
                                           :key="index"
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
                               element="div"
                               :options="dropOptions"
                               @choose="handleChoose"
                               style="min-height: 100%"
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
                <div class="sticky-position editor">
                    <component :is="moduleEditor[currentModule.data && currentModule.data.type]"
                               :config="currentModule.data"
                               :path="currentModule.path"
                    ></component>
                </div>
            </i-col>
        </Row>
        <Modal
                :width="500"
                :closable="false"
                :mask-closable="false"
                v-model="doingProjectsModal"
                :title="$t('project_operation_tip')">
            <p style="font-size: 16px; color: #202637; padding: 0 14px; margin-bottom: 10px" >{{$t('project_edit_template_warning')}}</p>
            <p style="font-size: 14px; color: #5c667d; margin: 4px 0; padding: 0 14px;" v-for="(item, index) in doingProjects" :key="index">{{item}}</p>
            <span slot="footer">
                <Button type="default" @click="$router.replace({name: 'template-management'})">{{$t('project_exit')}}</Button>
                <Button type="primary" @click="doingProjectsModal = false">{{$t('project_continue')}}</Button>
            </span>
        </Modal>
        <Modal v-model="viewModal"
               fullscreen
               :title="$t('project_preview')"
               footer-hide>
            <div slot="header">
                <Button type="default" @click="viewModal = false">{{$t('project_back')}}</Button>
                <Button type="primary" @click="viewResult">{{$t('tool_view_job_result')}}</Button>
            </div>
            <projectSample ref="projectSample" v-if="viewModal" :templateInfo="editModuleList" :categoryInfo="curCategory"></projectSample>
        </Modal>
    </div>

</template>
<script>
    import api from '@/api';
    import uuid from 'uuid/v4';
    import util from '@/libs/util';
    import cloneDeep from 'lodash.clonedeep';
    import Vue from 'vue';
    import getDefaultConfig from 'components/template-default-config';
    import getBasicTemplate from '../../common/basicTemplateConfig';
    import CategoryTemplateConfig from '../../common/categoryTemplateConfig';
    import projectSample from 'components/sample-files/index.vue';

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
        //'ocr',
        'form-upload'
    ];
    export default {
        name: 'template-edit',
        data () {
            return {
                viewModal: false,
                supportedPrevireCategory: ['image_label', 'image_transcription', 'text_analysis', 'text_annotation', 'voice_classify', 'voice_transcription', 'video_classify'],
                currentOpenPanel: ['filePlaceholderModule', 'descriptionModule', 'workerModule', 'layoutModule'],
                categoryList: [],
                doingProjects: [],
                doingProjectsModal: false,
                saveTemplateLoading: false,
                categoryTemplateConfig: {
                    list: [],
                    required: []
                },
                templateForm: {
                    templateName: '',
                    template_type: '1',
                    category_id: '',
                },
                // template_type: 1,
                templateTypeList: {},
                templateId: '',
                // templateName: '',
                templateConfig: [],
                oldEditModuleList: '',
                hiddenLabel: false,
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
                        //'ocr',
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
                        moduleEditor[k] = k + '-editor';
                    });
                    return moduleEditor;
                })(),
                withAnchorType: [
                    'task-file-placeholder',
                    'video-file-placeholder',
                    'audio-file-placeholder',
                    'text-file-placeholder'
                ],
                ruleCustom: {
                    templateName: [
                        {required: true, message: this.$t('project_template_name_null'), trigger: 'blur'}
                    ],
                    category_id: [
                        {required: true}
                    ]
                }
            };
        },
        beforeRouteLeave (to, from, next) { // 离开前提示
            if (!this.canEditTemplate) { // "不可编辑"的离开 不提示保存
                next();
                return;
            }
            if (this.oldEditModuleList === JSON.stringify(this.editModuleList)) {
                next();
                return;
            }
            if ((this.templateId === 'new') && this.editModuleList.length) {
                const answer = this.hiddenLabel || window.confirm(this.$t('project_sure_this_edit'));
                if (answer) {
                    next();
                } else {
                    // util.openNewPage(this, this.$route.name, this.$route.params, this.$route.query);
                    next(false);
                }
            } else if (this.templateId !== 'new') {
                const answer = this.hiddenLabel || window.confirm(this.$t('project_sure_this_edit'));
                if (answer) {
                    next();
                } else {
                    // util.openNewPage(this, this.$route.name, this.$route.params, this.$route.query);
                    next(false);
                }
            } else {
                next();
            }
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
            },
            curCategory () {
                let category = {};
                $.each(this.categoryList, (k, v) => {
                    if(v.id == this.templateForm.category_id) {
                        category = v;
                    }
                })
                return category;
            },
            canEditTemplate () {
                return this.$store.state.app.settings.open_template_diy === '1';
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
            this.templateId = this.$route.params.id;
            if (this.templateId === 'new') {
                this.editModuleList = getBasicTemplate();
            } else {
                this.getTemplateDetail();
            }
            this.templateForm.category_id = this.$route.params.categoryId || '';

            this.currentModule = {
                data: {},
                path: ''
            };
            this.getCategoryList();
        },
        methods: {
            templatePreview () {
                this.viewModal = true;
            },
            viewResult () {
                this.$refs.projectSample.getSubmitResult()
            },
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
                this.currentModule = {
                    data: {},
                    path: ''
                };
            },
            saveTemplate () {
                if (this.editModuleList.length === 0) {
                    this.$Message.destroy();
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
                            content: this.$t('project_lack') + ' ' + missModule.map(item => {
                                return this.$t('template_' + item.replace(/-/g, '_'));
                            }).join() + ' ' + this.$t('project_modules'),
                            duration: 3
                        });
                        return;
                    }
                }
                this.$refs.formValidate.validate((valid) => {
                    if (valid) {
                        this.saveTemplateLoading = true;
                        this.hiddenLabel = true;
                        let url =
                            this.templateId === 'new' ? api.template.create : api.template.update;
                        let data = {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            template_id: this.templateId,
                            config: JSON.stringify(this.editModuleList),
                            name: this.templateForm.templateName,
                            type: this.templateForm.template_type,
                            category_id: this.templateForm.category_id
                        };
                        this.templateId === 'new' && delete data.template_id;
                        $.ajax({
                            url: url,
                            type: 'post',
                            data: data,
                            success: res => {
                                this.saveTemplateLoading = false;
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
                                    this.$router.go(-1);
                                    // this.$store.commit('removeTag', 'template-edit');
                                }
                            },
                            error: () => {
                                this.saveTemplateLoading = false;
                                this.$Message.error({
                                    content: this.$t('project_save_error'),
                                    duration: 3
                                });
                            }
                        });
                    } else {
                        if (this.templateForm.templateName.trim().length === 0) {
                            this.$Message.warning({
                                content: this.$t('project_template_name_null'),
                                duration: 3
                            });
                        }
                    }
                });
            },
            getCategoryList () {
                $.ajax({
                    url: api.template.form,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.categoryList = res.data.categories || [];
                            this.templateTypeList = {...res.data.types} || {};
                            this.categoryChange(this.templateForm.category_id, this.templateId === 'new');
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            },
            getTemplateDetail () {
                $.ajax({
                    url: api.template.detail,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        template_id: this.templateId
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.editModuleList = res.data.template.config || [];
                            this.oldEditModuleList = JSON.stringify(res.data.template.config || []);
                            if (res.data.projects.length) {
                                this.doingProjects = res.data.projects;
                                this.doingProjectsModal = true;
                            }
                            this.templateForm = {
                                templateName: res.data.template.name,
                                template_type: res.data.template.type,
                                category_id: (res.data.template.category_id || '') + ''
                            };
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            },
            handleChoose (e) {
                let path = e.item.getAttribute('path');
                this.$store.commit('chooseModule', path);
                Vue.nextTick(() => {
                    $('.selected-module').removeClass('selected-module');
                    $('[data-id=' + this.currentId + ']').addClass('selected-module');
                });
            },
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
            handleChange (e) {
                // 左侧拖入编辑预览区，编辑预览区内拖动会触发的事件
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
            projectSample,
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
            line-height: 34px;
            font-weight: bold;
            text-align: center;
            // padding-bottom: 10px;
            border-bottom: 1px solid #c2c6cc;
            margin: 0 0 8px 0;
        }
    }

    .template-setting-left {
        background-color: #fff;
        min-height: 510px;
        /*.template-header {*/
        /*color: #3b5998;*/
        /*font-size: 14px;*/
        /*font-weight: bold;*/
        /*text-align: center;*/
        /*padding-bottom: 14px;*/
        /*border-bottom: 1px solid #c2c6cc;*/
        /*margin-top: 5px;*/
        /*.template-header-desc {*/
        /*display: block;*/
        /*padding-top: 8px;*/
        /*font-size: 12px;*/
        /*color: #aaadb3;*/
        /*font-weight: 300;*/
        /*}*/
        /*}*/
        .list-draggable {
            /*margin-top: 10px;*/
            /*margin-bottom: 10px;*/
            cursor: pointer;
        }
    }

    .template-setting-main {
        background-color: #fff;
        min-height: 510px;
        .list-sortable {
            cursor: pointer;
            padding-bottom: 100px;
            height: 100%;
        }
    }

    .template-setting-edit {
        background-color: #fff;
        min-height: 510px;
    }

    .template_type {
        padding-left: 10px;
    }
</style>
<style>
#templateForm .ivu-form-item {
    margin-bottom: 0;
    vertical-align: top;
    zoom: 1;
}
</style>




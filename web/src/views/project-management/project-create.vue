<template>
    <Row class="project-setting-con" id="projectSetting">
        <div style="background:#fff;border-radius: 3px">
            <h1 class="page-title">{{$t('project_project_settings')}}</h1>
            <Row class="nextBtn">
                <Button type="primary" @click="nextStep()" style="float:right;margin-right:20px">
                    {{$t('project_next_allocation_person')}}
                </Button>
                <Button @click="saveSetting('project-management')" style="float:right;margin-right:20px">
                    {{$t('project_save_project')}}
                </Button>
            </Row>
        </div>
        <Card>
            <p slot="title">{{$t('project_projectAudit_info')}}</p>
            <Form ref="formValidate"
                :model="formData"
                :rules="ruleValidate"
                :label-width="150"
                inline
                label-position="right"
                @submit.native.prevent
                style="background-color:#fff; width:100%;">  <!-- 阻止默认提交行为 -->
                <Row>
                    <FormItem :label="$t('project_project_id') +  ' : '">
                        <div style="display:inline-block;width:200px;font-size: 14px">
                            {{projectId}}
                        </div>
                    </FormItem>
                    <FormItem :label="$t('project_projecy_type') +  ' : '" prop="type">
                        <div style="display:inline-block;font-size: 14px">
                            {{ formData.type }}
                        </div>
                    </FormItem>
                </Row>
                <Row>
                    <FormItem :label="$t('project_project_name') +  ' : '" prop="name">
                        <div style="display:inline-block;width:200px;">
                            <Input v-model="formData.name" :placeholder="$t('project_input_project_name')"/>
                        </div>
                    </FormItem>
                    <FormItem
                        :label="$t('project_project_start_end_time') + ' : '"
                        prop="time">
                        <div style="display:inline-block; width:280px;">
                            <DatePicker
                            type="daterange"
                            :placeholder="$t('project_sel_project_start_end_time')"
                            :editable="false"
                            :clearable="false"
                            :options="dateOptions"
                            v-model="formData.time"
                            style="width: 220px"
                            ></DatePicker>
                        </div>
                    </FormItem>
                </Row>
                <Row>
                    <FormItem
                        :label="$t('project_project_template') +  ' : '"
                        prop="template_id"
                        :show-message="showerrorMessage">
                        <div style="display:inline-block;min-width:200px;">
                            <p style="font-size: 14px">{{formData.template_name}}</p>
                            <!--按钮:选择-->
                            <Button
                                type="primary"
                                size="small"
                                v-if="!formData.id"
                                @click="selTemplate(formData.category_id);"
                                >{{$t('project_projectAudit_choose')}}</Button>
                            <!--按钮:编辑-->
                            <Button
                            type="default"
                            size="small"
                            v-if="formData.template_id"
                            @click="tempEdit(formData.template_id, formData.category_id)"
                            >{{$t('project_projectAudit_edit')}}</Button>
                        </div>
                    </FormItem>

                    <FormItem :label="$t('project_project_assign_type') +  ' : '" prop="assign_type">
                      <RadioGroup v-model="formData.assign_type" >
                        <Radio :label="index + ''" v-for="(item,index) in assign_types" :key="item" :disabled="project.status > 1">{{ item }}</Radio>
                      </RadioGroup>
                      <Tooltip>
                        <Icon type="ios-help-circle-outline" :size="18" />
                        <div slot="content"  >
                          分配模式创建后不可更改
                        </div>
                      </Tooltip>
                    </FormItem>
                </Row>
            </Form>
        </Card>
        <Card style="padding-bottom:20px;" v-if="project.status == '0' || project.status == '1'">
            <p slot="title">{{$t('project_upload_data')}}</p>
            <Row>
                <i-col span="11" offset="1">
                    <Upload
                        type="drag"
                        :action="serverUrl"
                        :data="uploadData"
                        :max-size="512000"
                        :format="uploadfileExts"
                        :before-upload="beforeUpload"
                        :default-file-list="uploadedTaskFiles"
                        :on-success="handleUploadSuccess"
                        :on-format-error="handleFileFormatError"
                        :on-error="handleUploadError"
                        :on-exceeded-size="handleMaxSize"
                        :on-remove = "handleRemoveFile"
                        :on-progress = "handleUploading"
                        style="maxWidth: 550px"
                        >
                        <div style="padding: 30px 0">
                            <Icon type="ios-cloud-upload" size="52" style="color: #3399ff"></Icon>
                            <p class="supports">{{$t('project_drag_file_tip')}}</p>
                            <p class="supports">
                                {{$t('project_upload_file_extensions')}}:
                                <span>{{uploadfileExts.toString()}}</span>
                                <Tooltip placement="top" :max-width="200" :transfer="true" :content="$t('project_upload_tip2')">
                                    <Icon style="font-size: 16px" type="ios-help-circle-outline"/>
                                </Tooltip>
                            </p>
                            <p class="supports">
                                {{$t('project_work_file_extensions')}}:
                                <span>{{file_extensions}}</span>
                                <Tooltip placement="top" :max-width="200" :transfer="true" :content="$t('project_upload_tip1')">
                                    <Icon style="font-size: 16px" type="ios-help-circle-outline"/>
                                </Tooltip>
                            </p>
                        </div>
                    </Upload>
                </i-col>
            </Row>
        </Card>
        <Card style="padding-bottom:20px;">
            <p slot="title">{{$t('project_work_process')}}</p>
            <Row>
                <i-col span="16" offset="1" class="margin-top-20">
                    <div class="flow">
                        <div class="work-flow execute">{{$t('project_execute')}}</div>
                        <div class="process-line"></div>
                        <Icon class="icon" type="md-arrow-dropright" size="25"/>
                    </div>
                    <div class="flow">
                        <div class="work-flow audit">{{$t('project_audit')}}</div>
                        <div class="process-line"></div>
                        <Icon class="icon" type="md-arrow-dropright" size="25"/>
                    </div>
                    <div class="flow">
                        <div class="work-flow acceptance">{{$t('project_acceptance_check')}}</div>
                    </div>
                </i-col>
            </Row>
        </Card>
        <Modal
            v-model="templateModal"
            fullscreen
            footer-hide
            scrollable
            :closable="false"
            :mask-closable="false">
            <templateEdit
                :tempId="curTemplate"
                :categoryId="categoryId"
                :proCategory="proCategory"
                v-on:model-close="modelClose"
                v-if="templateModal"
            ></templateEdit>
        </Modal>
        <Modal
               :width="850"
                v-model="selModal"
                :title="$t('project_choose_template')">
           <templateSel
                :loadData="loadData"
                :categoryId="categoryId"
                :proCategory="proCategory"
                v-on:set-template="setTemplate"
                v-on:create-template="tempEdit ('new', categoryId)"
                v-on:sel-updated="selUpdated"
            ></templateSel>
            <span slot="footer"></span>
        </Modal>
    </Row>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import uuid from 'uuid/v4';
import templateSel from './configuration/template-list.vue';
import templateEdit from './configuration/template-edit.vue';
export default {
    name: 'project-create',
    data () {
        const validateTime = (rule, value, callback) => {
            let startTime = new Date(value[0]).getTime();
            let endTime = new Date(value[1]).getTime();

            if (endTime - startTime <= 0) {
                callback(new Error(this.$t('project_project_start_end_time_hint')))
            } else {
                callback();
            }
        };
        return {
            projectId: this.$route.params.id,
            project: {}, // 项目总体信息
            uploadedTaskFiles: [], // 已上传的文件列表
            file_extensions: '',
            template: [], // 项目模板配置
            formData: {
                template_id: '',
                template_name: '',
                time: [],
                assign_type:'0'
            },
            uploadfileTypes: {},
            uploading: false,
            ruleValidate: {
                name: [
                    { required: true, message: this.$t('project_project_name_null'), trigger: 'blur' },
                    {
                        type: 'string',
                        min: 2,
                        max: 30,
                        message: this.$t('project_project_name_format'),
                        trigger: 'blur'
                    }
                ],
                template_id: [
                    { required: true, message: this.$t('project_please_choose_template'), trigger: 'change' },
                ],
                time: [
                    { required: true, type: 'array', message: this.$t('project_sel_project_start_end_time'), trigger: 'change' },
                    { validator: validateTime, trigger: 'change' },
                ],
            },
            dateOptions: {
                disabledDate (date) {
                    return date && date.valueOf() < Date.now() - 86400000;
                }
            },
            serverUrl: api.upload.projectFiles,
            uploadfileExts: [],
            selModal: false,
            loadData: false,
            categoryId: '',
            proCategory: {},
            templateModal: false,
            curTemplate: '',
            stepForm: [],
            assign_types:[],
        };
    },
    mounted () {
        document.body.ondrop = function (event) {
            event.preventDefault();
            event.stopPropagation();
        };
        this.getProjectData();
    },
    computed: {
        uploadData: function () {
            return {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.projectId,
                type: 'uploadfile'
            };
        },
        showerrorMessage () {
            return !this.formData.template_id;
        }
    },
    methods: {
        beforeUpload (file) {
            if (this.uploading) {
                this.$Message.warning({
                    content: this.$t('project_uploading_wait_complete'),
                    duration: 2
                });
                return false;
            }
            let lock = false;
            $.each(this.uploadedTaskFiles, (k, v) => {
                if (file.name == v.name) {
                    lock = true;
                }
            });
            if (lock) {
                this.$Message.warning({
                    content: this.$t('user_upload_refile'),
                    duration: 3
                });
                return false;
            }
        },
        handleUploading () {
            this.uploading = true;
        },
        handleUploadSuccess (res, file, fileList) {
            this.uploading = false;
            if (res.error) {
                this.$Message.warning({
                    content: res.message,
                    duration: 3
                });
                $.each(fileList, (k,v) => {
                    if (v.response.error) {
                        fileList.splice(k, 1);
                    }
                })
            } else {
                this.uploadedTaskFiles = fileList;
                this.uploadedTaskFiles[this.uploadedTaskFiles.length - 1].key = res.data.key;
            }
        },
        handleFileFormatError (file) {
            this.$Message.warning({
                content: file.name + this.$t('project_format_incorrect'),
                duration: 3
            });
        },
        handleUploadError (res) {
            this.uploading = false;
        },
        handleMaxSize (file) {
            this.$Notice.warning({
                title: this.$t('project_upload_file_limit'),
                desc: file.name + this.$t('project_file_size_lt_500')
            });
        },
        handleRemoveFile (file, fileList) {
            this.uploadedTaskFiles = fileList;
            $.ajax({
                url: api.upload.delProjectFiles,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id,
                    file: file.key
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        $.each(this.uploadedTaskFiles, (k, v) => {
                            if (v.key == file.key) {
                                let index = this.uploadedTaskFiles.indexOf(v);
                                this.uploadedTaskFiles.splice(index, 1);
                            }
                        });
                        this.$Message.success({
                            content: this.$t('project_deleted'),
                            duration: 2
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        getProjectData () {
            this.projectId = this.$route.params.id;
            $.ajax({
                url: api.project.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.projectId
                },
                success: res => {
                    let data = res.data;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.project = data.project; // 项目总体信息
                        this.userftp = data.userftp; // ftp信息
                        // this.template = JSON.parse((this.project.template && this.project.template.config.trim()) || '[]'); // 任务模板配置
                        // this.$store.commit('updateList', this.template);
                        this.uploadedTaskFiles = data.uploadfiles;
                        this.uploadfileTypes = data.uploadfileTypes;
                        this.uploadfileExts = data.project.category.upload_file_extensions ? data.project.category.upload_file_extensions.split(',') : [];
                        this.file_extensions = data.project.category.file_extensions || '';
                        this.proCategory = data.project.category;
                        this.curTemplate = data.project.template && data.project.template.id.toString();
                        // this.uploadfileExts = Array.from(new Set(this.uploadfileExts.concat(this.file_extensions.split(',')))); // 需要加上单文件非压缩包支持的类型

                        this.stepForm = [];
                        $.each(res.data.stepGroups.stepGroupList, (k, v) => {
                            let arr = [];
                            $.each(v.steps, (j, k) => {
                                arr.push({
                                    id: k.id,
                                    type: k.type
                                });
                            });
                            this.stepForm.push({
                                tempid: uuid(),
                                id: v.id,
                                steps: arr,
                                sort: '',
                                parent_id: ''
                            });
                        });
                        this.assign_types = data.assign_types;
                        this.formData = {
                            name: this.project.name || '',
                            type: this.project.category.name,
                            time: [new Date(+data.project.start_time * 1000), new Date(+data.project.end_time * 1000)],
                            category_id: data.project.category.id,
                            template_id: data.project.template && data.project.template.id.toString(),
                            template_name: data.project.template && data.project.template.name,
                            assign_type:data.project.assign_type || '0',
                        };
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        nextStep () {
            if (this.uploading) {
                this.$Message.warning({
                    content: this.$t('project_uploading_wait_complete'),
                    duration: 2
                });
                return;
            }
            this.$refs.formValidate.validate(validate => {
                if (validate) {
                    for (let i = 0; i < this.stepForm.length; i++) {
                        if (i == 0) {
                            this.stepForm[i].sort = i;
                            this.stepForm[i].parent_id = '';
                        } else {
                            this.stepForm[i].sort = i;
                            this.stepForm[i].parent_id = this.stepForm[i - 1].tempid;
                        }
                    }
                    let postData = {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        template_id: this.formData.template_id,
                        name: this.formData.name,
                        assign_type:this.formData.assign_type,
                        uploadfile_type: 'web',
                        start_time: util.DateToSeconds(this.formData.time[0]),
                        end_time: util.DateToSeconds(this.formData.time[1]),
                        stepGroups: this.stepForm,
                        op: 'next'
                    };
                    $.ajax({
                        url: api.project.submit,
                        type: 'post',
                        data: postData,
                        success: res => {
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.success({
                                    content: this.$t('project_save_success'),
                                    duration: 2
                                });
                                if(name) {
                                    this.$router.push({
                                        name: name
                                    });
                                } else {
                                    this.$router.push({
                                        name: 'project-configuration',
                                        params: {
                                            id: this.$route.params.id
                                        }
                                    });
                                }
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText);
                        }
                    });
                }
            });
        },
        saveSetting (name) {
            for (let i = 0; i < this.stepForm.length; i++) {
                if (i == 0) {
                    this.stepForm[i].sort = i;
                    this.stepForm[i].parent_id = '';
                } else {
                    this.stepForm[i].sort = i;
                    this.stepForm[i].parent_id = this.stepForm[i - 1].tempid;
                }
            }
            let postData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.projectId,
                template_id: this.formData.template_id,
                name: this.formData.name,
                assign_type:this.formData.assign_type,
                uploadfile_type: 'web',
                start_time: util.DateToSeconds(this.formData.time[0]),
                end_time: util.DateToSeconds(this.formData.time[1]),
                stepGroups: this.stepForm,
                op:'save'
            };
            $.ajax({
                url: api.project.submit,
                type: 'post',
                data: postData,
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_save_success'),
                            duration: 2
                        });
                        if(name) {
                            this.$router.push({
                                name: name
                            });
                        } else {
                            this.$router.push({
                                name: 'project-configuration',
                                params: {
                                    id: this.$route.params.id
                                }
                            });
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        selTemplate (categoryId) {
            if (!categoryId) {
                this.$Message.warning({
                    content: this.$t('project_projectAudit_choose_type'),
                    duration: 3
                });
            } else {
                this.categoryId = categoryId;
                this.loadData = true;
                this.selModal = true;
            }
        },
        modelClose (templateName) {
            this.templateModal = false;
            if (this.selModal) {
                this.loadData = true;
            }
            if (templateName) {
                this.formData.template_name = templateName;
            }
        },
         //按钮:编辑模板
        tempEdit (id, categoryId) {
            if (id) {
                this.curTemplate = id.toString();
                this.categoryId = categoryId;
            } else {
                this.$Message.warning({
                    content: this.$t('project_not_choose_template'),
                    duration: 2
                });
                return;
            }
            this.templateModal = true;
        },
        setTemplate (info) {
            this.formData.template_id = info.id.toString();
            this.formData.template_name = info.name;
            this.selModal = false;
        },
        selUpdated () {
            this.loadData = false;
        }
    },
    components: {
        templateEdit,
        templateSel
    }
};
</script>
<style scoped>
    .date-picker {
        max-width: 606px;
    }
    .project-setting-con {
        /* background: #ffffff; */
        background: #eee;
        padding: 0 0 50px
    }
    .page-title {
        font-size: 18px;
        color: #464c5b;
        line-height: 56px;
        padding-left: 20px;
        font-weight: 400;
        border-bottom: 1px solid #e5e5e5;
    }
    .nextBtn {
        position: absolute;
        width: 100%;
        top: 12px;
    }
    .demo-spin-icon-load{
        animation: ani-demo-spin 1s linear infinite;
    }
    .supports {
        font-size: 14px
    }
    .flow{
        display: inline-block;
    }
    .work-flow{
        min-width: 150px;
        height: 60px;
        border-radius: 6px;
        font-size: 16px;
        display: inline-block;
        text-align: center;
        line-height: 60px;
    }
    .execute{
        background:rgb(229, 255, 254);
        border: 1px solid rgb(3, 187, 51);
        color: rgb(3, 187, 51);
    }
    .audit{
        background: rgb(255, 247, 233);
        border: 1px solid rgb(241, 130, 19);
        color: rgb(241, 130, 19);
        margin-left: -13px;
    }
    .acceptance{
        background: rgb(252, 245, 252);
        color: rgb(246, 15, 47);
        border: 1px solid rgb(246, 15, 47);
        margin-left: -13px;
    }
    .process-line{
        width: 60px;
        height: 1px;
        background:#999;
        display: inline-block;
        margin-left: -4px;
        position: relative;
        top: -5px
    }
    .icon{
        margin-left: -14px;
        margin-bottom: 3px;
        color:#999;
    }
</style>
<style>
    #projectSetting .ivu-form .ivu-form-item-label {
        font-size: 14px;
        font-weight: bold;
    }
    #projectSetting .ivu-upload-list-remove {
        font-size: 20px;
    }
</style>


<style lang="less">
    @import './own-space.less';
</style>

<template>
    <div>
        <Card>
            <p slot="title">
                <Icon type="md-person"></Icon>
                {{$t('user_ownspace')}}
            </p>
            <div id="detailForm">
                <Form ref="editForm" :model="userForm" :label-width="100" label-position="right" :rules="inforValidate">
                    <Form-item label="ID">
                        <div style="display:inline-block;width:300px;">
                            {{user_id}}
                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_avatar')">
                        <div class="demo-upload-list" v-show="isShow">
                            <img :src="staticBase + userForm.imgUrl" v-if="userForm.imgUrl">
                        </div>
                        <Upload
                            ref="upload"
                            :show-upload-list="false"
                            :on-success="handleSuccess"
                            :format="['jpg','jpeg','png']"
                            :on-format-error="handleFormatError"
                            :max-size="2048"
                            :before-upload="beforeUpload"
                            :on-exceeded-size="handleMaxSize"
                            :action="upload_config.url" 
                            :name="upload_config.name"
                            :data="upload_config.data"
                            style="display: inline-block;width:58px;">
                            <Button type="primary" style="float:left">{{$t('user_avatar_settings')}}</Button>
                        </Upload>
                    </Form-item>
                    <Form-item :label="$t('user_name')" prop="name">
                        <div style="display:inline-block;width:300px;">
                            <Input v-model="userForm.name" icon="ios-person" :placeholder="$t('user_input_name')"/>
                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_email')" prop="email">
                        <div style="display:inline-block;width:300px;">
                            <Input v-model="userForm.email" icon="ios-mail" :placeholder="$t('user_input_email')"/>
                            <!-- <span>{{userForm.email}}</span><Icon type="md-color-filter" @click="editEmailClick" style="font-size: 16px;margin-left: 10px;cursor:pointer"/> -->
                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_mobile')" prop="mobile">
                        <div style="display:inline-block;width:300px;">
                            <Input v-model="userForm.mobile" icon="ios-call" :placeholder="$t('user_input_phone')"/>                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_phone')" prop="phone">
                        <div style="display:inline-block;width:300px;">
                            <Input v-model="userForm.phone" icon="ios-call" :placeholder="$t('user_input_phone_number')"/>
                            <!-- <span>{{userForm.mobile}}</span><Icon type="md-color-filter" @click="editMobileClick" style="font-size: 16px;margin-left: 10px;cursor:pointer"/> -->
                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_company')" prop="company" >
                        <div style="display:inline-block;width:300px;">
                            <Input v-model="userForm.company_name" icon="ios-paper-plane"
                                   :placeholder="$t('user_input_text_company')"/>
                        </div>
                    </Form-item>
                    <Form-item :label="$t('user_language')">
                        <RadioGroup v-model="userForm.language">
                            <Radio label="zh-CN">
                                <span>中文简体</span>
                            </Radio>
                            <Radio label="en-US">
                                <span>English</span>
                            </Radio>
                        </RadioGroup>
                    </Form-item>
                    <Form-item :label="$t('user_login_password')">
                        <!-- <Button type="text" size="small" @click="showEditPassword">{{$t('user_change_password')}}</Button> -->
                        <span style="font-size: 14px">*********</span><Icon @click="showEditPassword" type="md-color-filter" style="font-size: 16px;margin-left: 10px;cursor:pointer"/>
                    </Form-item>
                </Form>
                <div>
                    <Button type="text" style="width: 100px;margin-left:100px" @click="goPage('/home')">{{$t('common_cancel')}}</Button>
                    <Button type="primary" style="width: 100px;margin-left:20px" @click="saveEdit">{{$t('common_save')}}</Button>
                </div>
            </div>
        </Card>
        <Modal v-model="editPasswordModal" :closable='false' :mask-closable=false :width="500">
            <h3 slot="header" style="color:#2D8CF0">{{$t('user_change_password')}}</h3>
            <Form ref="editPasswordForm" :model="editPasswordForm" :label-width="100" label-position="right" :rules="passwordValidate">
                <FormItem :label="$t('user_old_password')" prop="oldPass">
                    <Input v-model="editPasswordForm.oldPass" :placeholder="$t('user_input_oldpassword')"/>
                </FormItem>
                <FormItem :label="$t('user_new_password')" prop="newPass">
                    <Input v-model="editPasswordForm.newPass" :placeholder="$t('user_input_newpassword')"/>
                </FormItem>
                <FormItem :label="$t('user_confirm_password')" prop="rePass">
                    <Input v-model="editPasswordForm.rePass" :placeholder="$t('user_input_confirmpassword')"/>
                </FormItem>
            </Form>
            <div slot="footer">
                <Button type="text" @click="cancelEditPass">{{$t('common_cancel')}}</Button>
                <Button type="primary" @click="saveEditPass" :loading="save_loading">{{$t('common_save')}}</Button>
            </div>
        </Modal>
        <Modal v-model="validateModal" :mask-closable="false" :width="500">
            <h3 slot="header" v-if="!isValidate" style="line-height: 22px">{{$t('user_authenticate')}}</h3>
            <h3 slot="header" v-if="isValidate" style="line-height: 22px">{{$t('user_modify_email')}}</h3>
            <updateEmail 
            :userForm="userForm"
            :loadData="loadData" 
            :isValidate="isValidate"
            v-on:modalUpdated="modalUpdated"
            v-on:modalSaved="modalSaved"
            v-on:updataIsValidate="updataIsValidate"
            ></updateEmail>
            <div slot="footer"></div>
        </Modal>
        <Modal v-model="validateModal2" :mask-closable="false" :width="500">
            <h3 slot="header" v-if="!isValidate2" style="line-height: 22px">{{$t('user_authenticate')}}</h3>
            <h3 slot="header" v-if="isValidate2" style="line-height: 22px">{{$t('user_modify_bind_phone')}}</h3>
            <updateMobile 
            :userForm="userForm"
            :loadData="loadData2" 
            :isValidate="isValidate2"
            v-on:modalUpdated="modalUpdated2"
            v-on:modalSaved="modalSaved2"
            v-on:updataIsValidate="updataIsValidate2"
            ></updateMobile>
            <div slot="footer"></div>
        </Modal>
    </div>
</template>

<script>
import api from "@/api";
import util from "@/libs/util.js";
import updateEmail from './components/updateEmail.vue';
import updateMobile from './components/updateMobile.vue';
export default {
    name: 'ownspace_index',
    data () {
        const validePhone = (rule, value, callback) => {
            var re = /^[0-9\-+\s]{5,20}$/;
            if (!value) {
                callback();
            } else if (!re.test(value)) {
                callback(new Error(this.$t('admin_valide_telephone')));
            } else {
                callback();
            }
        };
        const valideNewPassword = (rule, value, callback) => {
            if (value === this.editPasswordForm.oldPass) {
                callback(new Error(this.$t('user_entered_passwords_cannot_match')));
            }
            var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/;
            if (!re.test(value)) {
                callback(new Error(this.$t('user_form_password_format')));
            } else {
                callback();
            }
        };
        const valideRePassword = (rule, value, callback) => {
            if (value !== this.editPasswordForm.newPass) {
                callback(new Error(this.$t('user_form_password_mach')));
            } else {
                callback();
            }
        };
        const valideName = (rule, value, callback) => {
            let re = /^[\u4e00-\u9fa5\w\.]{2,16}$/;
            if (!re.test(value)) {
                callback(new Error(this.$t('user_validate_name')));
            } else {
                callback();
            }
        };
        const valideMobile = (rule, value, callback) => {
            var re = /^1[3456789]{1}\d{9}$/;
            if (!value) {
                callback();
            } else if (!re.test(value)) {
                callback(new Error(this.$t('user_incorrect_phone_format')));
            } else {
                callback();
            }
        };
        return {
            isShow: false,
            loadData: false,
            loadData2: false,
            isValidate: false,
            isValidate2: false,
            validateModal: false,
            validateModal2: false,
            editPasswordModal: false,
            save_loading: false,
            user_id: '',
            staticBase: api.staticBase,
            userForm: {
                id: '',
                name: '',
                email: '',
                mobile: '',
                phone: '',
                language: '',
                company_name: '',
                imgUrl: '',
            },
            upload_config: {
                url: api.upload.image,
                name: 'image',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                }
            },
            editPasswordForm: {
                oldPass: '',
                newPass: '',
                rePass: ''
            },
            uploadList: [],
            inforValidate: {
                name: [
                    { required: true, message: this.$t('user_input_text_name'), trigger: 'blur' },
                    { validator: valideName, trigger: 'blur' }
                ],
                email: [
                    { required: true, message: this.$t('user_input_email'), trigger: 'blur' },
                    {type: 'email', message: this.$t('user_form_email_format'), trigger: 'blur'}
                ],
                phone: [
                    // { required: true, message: this.$t('user_input_text_phone') },
                    { validator: validePhone }
                ],
                mobile: [
                    // { required: true, message: this.$t('user_input_phone'), trigger: 'blur' },
                    { validator: valideMobile, trigger: 'blur' }
                ],
            },
            passwordValidate: {
                oldPass: [
                    { required: true, message: this.$t('user_input_oldpassword'), trigger: 'blur' }
                ],
                newPass: [
                    { required: true, message: this.$t('user_input_newpassword'), trigger: 'blur' },
                    { min: 6, message: this.$t('user_form_password_min'), trigger: 'blur' },
                    { max: 16, message: this.$t('user_form_password_max'), trigger: 'blur' },
                    { validator: valideNewPassword, trigger: 'blur' }
                ],
                rePass: [
                    { required: true, message: this.$t('user_input_confirmpassword'), trigger: 'blur' },
                    { validator: valideRePassword, trigger: 'blur' }
                ]
            },
        };
    },
    components: {
        updateEmail,
        updateMobile
    },
    methods: {
        updataIsValidate (valid) {
            this.isValidate = valid;
        },
        updataIsValidate2 (valid) {
            this.isValidate2 = valid;
        },
        modalUpdated () {
            this.loadData = false;
        },
        modalUpdated2 () {
            this.loadData2 = false;
        },
        modalSaved () {
            this.validateModal = false;
            this.initForm();
        },
        modalSaved2 () {
            this.validateModal2 = false;
            this.initForm();
        },
        editEmailClick () {
            this.isValidate = false;
            this.loadData = true;
            this.validateModal = true;
        },
        editMobileClick () {
            this.isValidate2 = false;
            this.loadData2 = true;
            this.validateModal2 = true;
        },
        formatUrl (url) {
            return api.staticBase + util.replaceUrl(url);
        },
        handleSwitch (lang) {
            // this.lang = lang;
            localStorage.lang = lang;
            this.$store.commit('switchLang', lang); // 如果你要自己实现多语言切换，那么只需要执行这行代码即可，修改语言类型
            util.title(util.handleTitle(this, this.$route.meta));
        },
        handleRemove (file) {
            const fileList = this.$refs.upload.fileList;
            this.$refs.upload.fileList.splice(fileList.indexOf(file), 1);
        },
        beforeUpload (file) {
            if (file.size < 1) {
                this.$Message.warning({
                    content: this.$t('project_file_size_mt_1'),
                    duration: 2
                });
                return false;
            }
        },
        handleMaxSize (file) {
            this.$Notice.warning({
                title: this.$t('admin_upload_filesize_limit'),
                desc: file.name + this.$t('admin_upload_filesize_limit_con')
            });
        },
        handleSuccess (res, file) {
            file.url = res.data.url;
            this.userForm.imgUrl = res.data.url;
            this.isShow = true;
        },
        handleFormatError (file) {
            this.$Notice.warning({
                title: this.$t('user_form_file_format'),
                desc: this.$t('user_form_file_valid')
            });
        },
        showEditPassword () {
            this.editPasswordModal = true;
        },
        cancelEditPass () {
            this.editPasswordModal = false;
        },
        goPage (url) {
            this.$store.commit('removeTag', 'ownspace_index');
            this.$router.push(url);
        },
        saveEditPass () {
            this.$refs['editPasswordForm'].validate((valid) => {
                if (valid) {
                    this.save_loading = true;
                    $.ajax({
                        url: api.user.updatePassword,
                        type: "post",
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            'op': 'verifyPassword',
                            'password': this.editPasswordForm.oldPass,
                        },
                        success: (res) => {
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                                this.save_loading = false;
                            } else {
                                let key = res.data.key;
                                $.ajax({
                                    url: api.user.updatePasswordNew,
                                    type: "post",
                                    data: {
                                        access_token: this.$store.state.user.userInfo.accessToken,
                                        key: key,
                                        'newpassword': this.editPasswordForm.newPass,
                                        'repassword': this.editPasswordForm.rePass,
                                    },
                                    success: (res) => {
                                        this.save_loading = false;
                                        if (res.error) {
                                            this.$Message.destroy();
                                            this.$Message.warning({
                                                content: res.message,
                                                duration: 3
                                            });
                                        } else {
                                            this.editPasswordModal = false;
                                            this.$Message.destroy();
                                            this.$Message.success({
                                                content: this.$t('user_form_revised'),
                                                duration: 3
                                            });
                                        }
                                    },
                                    error: (res, textStatus, responseText) => {
                                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                                            this.save_loading = false;
                                        });
                                    }
                                });
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.save_loading = false;
                            });
                        }
                    });
                }
            });
        },
        initForm () {
            $.ajax({
                url: api.user.detail,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$store.state.user.userInfo.id,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        const userDetail = res.data.user;
                        // userDetail.avatar = api.staticBase + userDetail.avatar;
                        // if (userDetail.team && userDetail.team.logo) {
                        //     userDetail.team.logo = api.staticBase + userDetail.team.logo;
                        // }
                        if (this.uploadList[0]) {
                            this.handleRemove(this.uploadList[0]);
                        }
                        this.user_id = userDetail.id;
                        this.userForm.imgUrl = userDetail.avatar || '';
                        if (this.userForm.imgUrl !== '') {
                            this.isShow = true;
                        }
                        this.userForm.id = userDetail.id;
                        this.userForm.name = userDetail.nickname;
                        this.userForm.email = userDetail.email;
                        this.userForm.mobile = userDetail.mobile;
                        this.userForm.phone = userDetail.phone;
                        this.userForm.language = userDetail.language === '0' ? 'zh-CN' : 'en-US';
                        this.userForm.company_name = userDetail.company_name;

                        if (userDetail) {
                            this.$store.state.user.userInfo = {
                                ...this.$store.state.user.userInfo,
                                ...userDetail
                            };
                            this.$store.commit('login', this.$store.state.user.userInfo);
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        saveEdit () {
            this.$refs['editForm'].validate((valid) => {
                if (valid) {
                    $.ajax({
                        url: api.user.update,
                        type: "post",
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            user_id: this.$store.state.user.userInfo.id,
                            nickname: this.userForm.name,
                            email: this.userForm.email,
                            mobile: this.userForm.mobile,
                            phone: this.userForm.phone,
                            language: this.userForm.language === 'zh-CN' ? '0' : '1',
                            company_name: this.userForm.company_name || '',
                            avatar: this.userForm.imgUrl,
                        },
                        success: (res) => {
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                let lang = this.userForm.language;
                                this.$store.commit('switchLang', lang); // 如果你要自己实现多语言切换，那么只需要执行这行代码即可，修改语言类型
                                util.title(util.handleTitle(this, this.$route.meta));
                                this.$Message.destroy();
                                this.$Message.success(this.$t('user_form_saved'));
                                this.initForm();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText);
                        }
                    });
                }
            });
        }
    },
    mounted () {
        this.initForm();
    }
};
</script>
<style scoped>
    .demo-upload-list{
        display: inline-block;
        width: 60px;
        height: 60px;
        text-align: center;
        line-height: 60px;
        border: 1px solid transparent;
        border-radius: 4px;
        overflow: hidden;
        background: #fff;
        position: relative;
        box-shadow: 0 1px 1px rgba(0,0,0,.2);
        margin-right: 4px;
    }
    .demo-upload-list img{
        width: 100%;
        /* height: 100%; */
    }
    .demo-upload-list-cover{
        display: none;
        position: absolute;
        top: 0;
        bottom: 0;
        left: 0;
        right: 0;
        background: rgba(0,0,0,.6);
    }
    .demo-upload-list:hover .demo-upload-list-cover{
        display: block;
    }
    .demo-upload-list-cover i{
        color: #fff;
        font-size: 20px;
        cursor: pointer;
        margin: 0 2px;
    }
    #avatar{
        display: inline-block;
        width: 100px;
        border-radius: 50%
    }
</style>
<style>
    #detailForm .ivu-form-item-content {
        position: relative;
        line-height: 32px;
        font-size: 12px;
        margin-left: 130px !important
    }
</style>


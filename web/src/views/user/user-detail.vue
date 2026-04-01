<template>
    <div>
        <div class="layout-content">
            <Row>
                <i-col :xxl="2" :xl='2' :lg='2' :md='2' :sm='2' style="text-align:center;width:100px;margin-right:10px;">
                    <div style="width: 100px;height: 100px;overflow: hidden;position: relative;margin: 0 auto;">
                        <Spin fix v-if="spinShow"></Spin>
                        <img :src="getUserAvatar(avatarPath)" style="width: 100%;height: 100%;max-width:100px;max-height:100px;"/>
                    </div>
                </i-col>
                <i-col :xxl='22' :xl='21' :lg='20' :md='18' :sm='18' style="font-size:14px">
                    <div>
                        <strong style="font-size:18px">{{nickname}} ( {{$route.params.id}} )</strong>
                        <Button v-for="(item,index) in userRoles" :key="index" size="small" :style="{marginLeft:'10px',backgroundColor:'#19be6b',color: '#ffffff'}">{{item}}</Button>
                    </div>
                    <div style="margin: 15px 0">
                        <span>{{$t('user_email')}}:   </span>
                        <span style="width:200px;margin-right:100px;">{{user_email}}</span>
                        <span>{{$t('admin_contact_number')}}:   </span>
                        <span style="width:200px;margin-right:80px;">{{user_phone}}</span>
                        <span>{{$t('user_join_time')}}:   </span>
                        <span style="width:200px;margin-right:80px;">{{created_at}}</span>
                        <!-- <div style="position:absolute;right:0;top:0">
                            <Button v-if="$store.state.user.userInfo.id && $route.params.id !== $store.state.user.userInfo.id" type="primary" @click="getUserForm">{{$t('user_edit')}}</Button>
                            <Button v-if="$store.state.user.userInfo.id && $route.params.id !== $store.state.user.userInfo.id" @click="delModel = true">{{$t('user_remove')}}</Button>
                        </div> -->
                    </div>
                    <div style="margin: 15px 0">
                            <span>{{$t('user_create_by')}}:  </span>
                            <span v-if="createdByUser.nickname">{{createdByUser.nickname}}  ({{ createdByUser.id }})</span>
                            <span style="margin-left: 100px;">{{$t('user_belong_group')}}:   </span>
                            <span style="width:200px;margin-right:100px;">{{user_group}}</span>
                    </div>
                </i-col>
            </Row>
            <ul class="tabpane">
                <li :class="currentTab == 'record' ? 'active' : ''" @click="tabClick('record')">{{$t('user_operation_records')}}</li>
                <li :class="currentTab == 'userdevice' ? 'active' : ''" @click="tabClick('userdevice')">{{$t('user_user_equipment')}}</li>
                <li :class="currentTab == 'relatetask' ? 'active' : ''" @click="tabClick('relatetask')">{{$t('admin_mission')}}</li>
                <!-- <li :class="currentTab == 'userftp' ? 'active' : ''" @click="tabClick('userftp')">{{$t('user_ftp')}}</li> -->
            </ul>
        </div>
        <div class="sub-containter">
            <component 
            :is="currentView"
            :ftp="ftp"
            ></component>
        </div>
        <Modal v-model="editModal">
            <p slot="header">
                <span>{{$t('admin_edit_employee')}}</span>
            </p>
            <div style="text-align:left">
                <Form ref="formValidate" :model="updateForm" :label-width="100" :rules="ruleValidate">
                    <FormItem :label="$t('user_avatar')">
                        <div class="upload-list" v-if="updateForm.avatar">
                            <img :src="formatUrl(updateForm.avatar)">
                        </div>
                        <Upload ref="upload" 
                            :show-upload-list="false" 
                            :on-success="CreatehandleSuccessIcon" 
                            :format="['jpg','jpeg','png']" 
                            :on-format-error="handleFormatError" 
                            :max-size="2048" 
                            :action="upload_config.url" 
                            :name="upload_config.name"
                            :data="upload_config.data"
                            style="display: inline-block;width:58px;">
                            <Button type="primary" style="float:left">{{$t('user_avatar_settings')}}</Button>
                        </Upload>
                    </FormItem>
                    <Form-item :label="$t('admin_name')" prop="nickname">
                        <Row>
                            <i-col span="20">
                                <Input v-model="updateForm.nickname" :placeholder="$t('admin_enter_user_name')"
                                       icon="md-person"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_password')" prop="password">
                        <Row>
                            <i-col span="20">
                                <Input v-model="updateForm.password" :placeholder="$t('admin_enter_password')"
                                       icon="md-lock"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_email')" prop="email">
                        <Row>
                            <i-col span="20">
                                <Input v-model="updateForm.email" :placeholder="$t('admin_enter_email')"
                                       icon="ios-mail"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_contact_number')" prop="phone">
                        <Row>
                            <i-col span="20">
                                <Input v-model="updateForm.phone" :placeholder="$t('admin_enter_phone')"
                                       icon="ios-call"/>
                            </i-col>
                        </Row>
                    </Form-item>
                    <Form-item :label="$t('admin_role')" prop="roles">
                        <Checkbox-group v-model="updateForm.roles">
                            <Checkbox v-for="(role,index) in roles" :label="index" :key="index"> {{role}}</Checkbox>
                        </Checkbox-group>
                    </Form-item>
                    <Form-item :label="$t('admin_group')" prop="group_id">
                        <Row>
                            <i-col span="20">
                                <Select v-model="updateForm.group_id" :placeholder="$t('admin_unselected')" clearable>
                                    <Option
                                    v-for="(item, key) in groups"
                                    :value="item.id"
                                    :key="key"
                                    >{{item.name}}</Option>
                                </Select>
                            </i-col>
                        </Row>
                    </Form-item>
                </Form>
            </div>
            <div slot="footer">
                <Button type="success" size="large" long @click.native="saveUser">
                {{$t('admin_save')}}
                </Button>
            </div>
        </Modal>
        <Modal
            v-model="delModel"
            :title="$t('user_operation_tip')">
            <p>{{$t('user_sure_delete_current_user')}} </p>
            <div slot="footer">
                <Button type="text" @click="delModel = false">{{$t('user_cancel')}}</Button>
                <Button type="error" @click="remove" :loading="removeloading">{{$t('user_delete')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
import api from '@/api';
import Vue from 'vue';
import util from '@/libs/util';
import record from './components/operating-record';
import userdevice from './components/use-device';
import relatetask from './components/relate-task';
// import userftp from './components/user-ftp';
import defaultImg from '../../images/default.jpg';
export default {
    name: 'user-detail',
    components: {
        record,
        userdevice,
        relatetask,
        // userftp,
    },
    data () {
        const validatePhone = (rule, value, callback) => {
            var re = /^[0-9\-+\s]{5,20}$/;
            if (value == '') {
                callback();
            } else if (!re.test(value)) {
                callback(new Error(this.$t('admin_valide_phone')));
            } else {
                callback();
            }
        };
        const valideNewPassword = (rule, value, callback) => {
            var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/;
            if(!value) {
                callback()
            }
            if (!re.test(value)) {
                callback(new Error(this.$t('admin_valide_password')));
            } else {
                callback();
            }
        };
        const valideName = (rule, value, callback) => {
            let re = /(^[\u4e00-\u9fa5\w\.\-*][\u4e00-\u9fa5\w\.\-\s*]{0,19}$)/u;
            let reg = /[^\-\.\w\s\u4e00-\u9fa5]/g;
            if (reg.test(value)) {
                callback(new Error(this.$t('user_name_cannot_special_characters'))); // 不能输入特殊字符
            }
            if(value[0] == ' ') {
                callback(new Error(this.$t('user_name_begin_cannot_empty')));
            } else if (!re.test(value)) {
                callback(new Error(this.$t('user_nickname_valide')));
            } else {
                callback();
            }
        };
        return {
            spinShow: true,
            editModal: false,
            avatarPath: '',
            user_email: '',
            nickname: '',
            user_phone: '',
            created_at: '',
            user_group: '',
            userRoles: [],
            createdByUser: {},
            staticBase: api.staticBase,
            currentTab: this.$route.params.tab || 'record',
            ViewMap: {
                record,
                userdevice,
                relatetask,
                // userftp
            },
            ftp: {},
            delModel: false,
            editLoading: false,
            removeloading: false,
            userDetail: {},
            serverUrl: '',
            updateForm: {
                avatar: '',
                nickname: '',
                password: '',
                email: '',
                phone: '',
                roles: [],
                group_id: ''
            },
            roles: {},
            groups: [],
            ruleValidate: {
                nickname: [
                    {required: true, message: this.$t('admin_valide_name'), trigger: 'blur'},
                    { validator: valideName, trigger: 'blur' }
                ],
                email: [
                    {required: true, message: this.$t('admin_valide_email'), trigger: 'blur'},
                    {type: 'email', message: this.$t('admin_email_format_error'), trigger: 'blur'}
                ],
                password: [
                    {type: 'string', validator: valideNewPassword, trigger: 'blur'}
                ],
                phone: [
                    { validator: validatePhone, trigger: 'blur' }
                ],
                roles: [
                    {required: true, type: 'array', min: 1, message: this.$t('admin_choose_one_role'), trigger: 'change'}
                ],
            },
            // 上传图片
            upload_config: {
                url: api.upload.image,
                name: 'image',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                }
            },
        };
    },
    watch: {
        '$route.params' () {
            this.currentTab = this.$route.params.tab || 'record';
        }
    },
    computed: {
        currentView () {
            return this.ViewMap[this.currentTab];
        }
    },
    methods: {
        getUserAvatar (url) {
            if (!url) {
                return defaultImg;
            }
            if (url.indexOf('http') > -1) {
                return url;
            } else {
                return api.staticBase + url;
            }
        },
        formatUrl (url) {
            if (url.indexOf('http') > -1) {
                return url;
            } else {
                return api.staticBase + url;
            }
        },
        CreatehandleSuccessIcon (res, file) {
            file.url = res.data.url;
            this.updateForm.avatar = res.data.url;
        },
        handleFormatError (file) {
            this.$Notice.warning({
                title: this.$t('admin_upload_file_format_incorrect'),
                desc: this.$t('admin_file_format')
            });
        },
        handleMaxSize (file) {
            this.$Notice.warning({
                title: this.$t('admin_upload_filesize_limit'),
                desc: this.$t('admin_upload_filesize_limit_con')
            });
        },
        tabClick (tab) {
            this.currentTab = tab;
            this.$router.push({
                name: 'user-detail',
                params: {
                    id: this.$route.params.id,
                    tab: tab,
                }
            });
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.user.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.spinShow = false;
                        this.userDetail = res.data.user;
                        this.user_email = res.data.user.email;
                        this.nickname = res.data.user.nickname;
                        this.avatarPath = res.data.user.avatar;
                        this.user_phone = res.data.user.phone;
                        this.user_group = res.data.user.group.name;
                        this.created_at = util.timeFormatter(
                            new Date(res.data.user.created_at * 1000),
                            'yyyy-MM-dd'
                        );
                        this.ftp = res.data.user.ftp;

                        let arr = [];
                        this.userRoles = [];
                        $.each(res.data.user.roles, (k, v) => {
                            arr.push(v.item_name);
                            this.userRoles.push(res.data.roles[v.item_name]);
                        });
                        this.createdByUser = res.data.user.createdByUser;
                        this.updateForm = {
                            avatar: res.data.user.avatar,
                            nickname: res.data.user.nickname,
                            password: '',
                            email: res.data.user.email,
                            phone: res.data.user.phone,
                            roles: arr,
                            group_id: res.data.user.group.id
                        };
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        saveUser () {
            this.$refs.formValidate.validate((valid) => {
                if (valid) {
                    $.ajax({
                        url: api.user.update,
                        type: 'post',
                        data: {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            user_id: this.$route.params.id,
                            avatar: this.updateForm.avatar,
                            nickname: this.updateForm.nickname,
                            email: this.updateForm.email,
                            password: this.updateForm.password,
                            phone: this.updateForm.phone,
                            roles: this.updateForm.roles.toString(),
                            group_id: this.updateForm.group_id,
                        },
                        success: res => {
                            this.editModal = false;
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.destroy();
                                this.$Message.success({
                                    content: this.$t('user_form_revised'),
                                    duration: 3
                                });
                                this.getData();
                                this.updateForm = {
                                    avatar: '',
                                    nickname: '',
                                    password: '',
                                    email: '',
                                    phone: '',
                                    roles: [],
                                    group_id: ''
                                };
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            this.editModal = false;
                            util.handleAjaxError(this, res, textStatus, responseText);
                        }
                    });
                }
            });
        },
        remove () {
            this.removeloading = true;
            $.ajax({
                url: api.user.delete,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                },
                success: (res) => {
                    this.removeloading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.delModel = false;
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 2
                        });
                        this.$router.push({
                            name: 'user-list'
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.removeloading = false;
                    });
                }
            });
        },
        getUserForm () {
            $.ajax({
                url: api.user.form,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.statuses = {
                            ...res.data.statuses
                        };
                        this.groups = res.data.groups;
                        this.roles = res.data.roles;
                        this.editModal = true;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
    },
    mounted () {
        this.getData();
    }
};
</script>

<style scoped>
.layout-content{
    position: relative;
    background: #ffffff;
    min-height: 200px;
    padding: 20px
}
.tabpane{
    height: 37px;
    list-style: none;
    position: absolute;
    bottom: 0;
    left: 40px;
}
.tabpane li{
    font-size: 14px;
    float: left;
    height: 37px;
    padding: 8px 16px;
    margin-right: 16px;
    line-height: 21px;
    color: #999999;
    text-align: center;
    cursor: pointer;
}
.tabpane li:hover{
    color: #333333
}
.tabpane li.active{
    color: #2d8cf0;
    border-bottom: 2px solid #2d8cf0
}
.sub-containter {
  background: #ffffff;
  padding: 10px;
  margin-top: 10px;
}
.upload-list {
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
  box-shadow: 0 1px 1px rgba(0, 0, 0, 0.2);
  margin-right: 4px;
}
.upload-list img {
  width: 100%;
  height: 100%;
}
 .demo-spin-container{
    display: inline-block;
    width: 200px;
    height: 100px;
    position: relative;
    border: 1px solid #eee;
}
</style>



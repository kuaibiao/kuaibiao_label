<template>
  <div>
    <Modal v-model="medianValue" width="500">
        <p slot="header">
            <span>{{$t('user_edit_account_information')}}</span>
        </p>
        <div style="text-align:left">
            <Form ref="formValidate" :model="currData" :label-width="100" :rules="ruleValidate" @submit.native.prevent>
                <Row>
                    <!-- <i-col span="12">
                        <Form-item :label="$t('user_avatar')" prop="avatar">
                            <div class="upload-list">
                            <span v-if="!currData.avatar">
                                <div style="width: 58px; height: 58px; line-height: 58px;">
                                    <img :src="staticBase + '/images/avatar.png'">
                                </div>
                            </span>
                            <span v-if="currData.avatar">
                                <img v-if="currData.avatar" :src="staticBase + currData.avatar">
                            </span>
                            </div>
                            <Upload
                                ref="upload"
                                :show-upload-list="false"
                                :on-success="CreatehandleSuccessIcon"
                                :format="['jpg', 'jpeg', 'png']"
                                :on-format-error="handleFileFormatError"
                                :max-size="2048"
                                :action="upload_config.url" :name="upload_config.name"
                                :data="upload_config.data"
                                style="display: inline-block;width:58px;">
                                <Button type="primary" style="float:left" icon="ios-cloud-upload">{{$t('user_upload_avatar')}}</Button>
                            </Upload>
                        </Form-item>
                        <Form-item :label="$t('user_name')" prop="nickname">
                            <Row>
                                <i-col span="20">
                                    <Input v-model="currData.nickname" :placeholder="$t('user_input_user_name')" icon="md-person"/>
                                </i-col>
                            </Row>
                        </Form-item>
                        <Form-item :label="$t('user_password')" prop="password">
                            <Row>
                                <i-col span="20">
                                    <Input type="password" v-model="currData.password" :placeholder="$t('user_input_password')" icon="md-lock"/>
                                  </i-col>
                            </Row>
                        </Form-item>
                    </i-col> -->
                    <i-col span="24">
                        <!-- <Form-item :label="$t('user_account_status')" prop="status">
                            <Radio-group v-model="currData.status">
                                <Radio v-for="(status,index) in statuses" :label="index" :key="index">{{status}}</Radio>
                            </Radio-group>
                        </Form-item> -->
                        <Form-item :label="$t('user_user_type') + '：'" prop="type">
                            <Radio-group v-model="currData.type" @on-change="handleChange">
                                <Radio v-for="(type,index) in types" :label="index" :key="index" v-show="index != '2'">{{type}}</Radio>
                            </Radio-group>
                        </Form-item>
                        <Form-item :label="$t('user_role') + '：'" prop="role">
                            <Checkbox-group v-model="currData.role">
                                <Checkbox v-for="(role,index) in roles[currData.type]" :label="index" :key="index"> {{role}}</Checkbox>
                            </Checkbox-group>
                        </Form-item>
                        <Form-item :label="$t('user_company') + '：'" v-show="currData.type == 0" prop="company">
                            <Row>
                                <i-col span="20">
                                    <Input v-model="currData.company" :placeholder="$t('user_input_company')" icon="ios-paper-plane-outline"/>
                                </i-col>
                            </Row>
                        </Form-item>
                        <Form-item :label="$t('user_belong_team') + '：'" v-if="currData.type == 2" prop="team">
                            <!-- <Radio-group v-model="currData.team">
                                <Radio v-for="(item,index) in teams" :label="item.id" :key="index">{{item.name}}</Radio>
                            </Radio-group> -->
                            <Select v-model="currData.team" style="width:200px">
                                <Option v-for="item in teams" :value="item.id" :key="item.id">{{ item.name }}</Option>
                            </Select>
                        </Form-item>
                    </i-col>
                </Row>
            </Form>
        </div>
        <div slot="footer">
            <Button type="success" size="large" long @click.native="saveBatch" :loading="editLoading">
                {{$t('user_save')}}
            </Button>
        </div>
    </Modal>
  </div>
</template>

<script>
   import api from '@/api';
   import util from '@/libs/util';
   import Vue from 'vue';
   export default {
       name: 'editModal',
       props: {
           editModal: {
               type: Boolean,
           },
           types: {
               type: [Array, Object]
           },
           statuses: {
               type: [Array, Object]
           },
           roles: {
               type: Object
           },
           teams: {
               type: [Array, Object]
           },
           crows: {
               type: Array
           },
           currData: {
               type: Object
           },
           editUserId: {
               type: [Number, String]
           }
       },
       data () {
           const validateMobile = (rule, value, callback) => {
               var re = /^[0-9\-\+\s]{5,20}$/;
               if (!value) {
                   callback();
               } else if (!re.test(value)) {
                   callback(new Error(this.$t('user_input_telephone_correct')));
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
           const validePassword = (rule, value, callback) => {
               if (!value) {
                   callback();
               } else {
                   var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,16}$/;
                   if (!re.test(value)) {
                       callback(new Error(this.$t('user_combination_numbers_letters')));
                   } else {
                       callback();
                   }
               }
           };
           return {
               // 上传图片
               medianValue: false,
               upload_config: {
                   url: api.upload.image,
                   name: 'image',
                   data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                    }
               },
               editLoading: false,
               uploading: false,
               staticBase: api.staticBase,
               ruleValidate: {
                   nickname: [
                       { required: true, message: this.$t('user_input_text_name'), trigger: 'blur' },
                       { validator: valideName, trigger: 'blur' }
                   ],
                   password: [
                       { type: 'string', validator: validePassword, trigger: 'blur' }
                   ],
                   email: [
                       {required: true, message: this.$t('user_input_text_email'), trigger: 'blur'},
                       {type: 'email', message: this.$t('user_form_email_format'), trigger: 'blur'}
                   ],
                   phone: [
                       { validator: validateMobile, trigger: 'blur' }
                   ],
                   role: [
                       {required: true, type: 'array', min: 1, message: this.$t('user_less_one_role'), trigger: 'change'}
                   ],
                   type: [
                       {required: true, message: this.$t('user_select_user_type'), trigger: 'change'}
                   ],
                   status: [
                       {required: true, message: this.$t('user_select_account_status'), trigger: 'change'}
                   ],
                   team: [
                       {required: true, message: this.$t('user_please_select_team'), trigger: 'change'}
                   ],
               }
           };
       },
       mounted () {
           this.medianValue = this.editModal;
       },
       watch: {
           medianValue () {
               this.$emit('medianValue', this.medianValue);
           },
       },
       methods: {
           handleRemoveFile (file) {
               this.uploadedTaskFiles.splice(file, 1);
           },
           handleUpload () {
               if (this.uploadedTaskFiles.length > 0) {
                   this.$Message.warning(this.$t('user_upload_maximum_file', {num: 1}));
                   return false;
               }
           },
           handleUploading () {
               this.uploading = true;
           },
           handleFileFormatError (file) {
               this.$Message.warning({
                   content: file.name + this.$t('user_malformat'),
                   duration: 3
               });
           },
           handleUploadSuccess (response, file, fileList) {
               this.uploadedTaskFiles.push(file);
               this.uploading = false;
           },
           handleChange () {
               this.currData.role = [];
           },
           // 上传头像验证
           CreatehandleSuccessIcon (res, file) {
               file.url = res.data.url;
               this.currData.avatar = res.data.url;
           },
           saveBatch () {
               let teamId;
               if (this.currData.team) {
                   teamId = this.currData.team;
               } else {
                   teamId = '';
               }
               this.editLoading = true;
               let opt = {
                   access_token: this.$store.state.user.userInfo.accessToken,
                   user_id: this.editUserId,
                   //    nickname: this.currData.nickname,
                   //    password: this.currData.password,
                   //    email: this.currData.email,
                   //    phone: this.currData.phone,
                   roles: this.currData.role.toString(),
                   company: this.currData.company || '',
                   team_id: teamId,
                   type: this.currData.type,
                   //    status: this.currData.status,
                   //    avatar: this.currData.avatar
               };
               this.$refs.formValidate.validate((valid) => {
                   if (valid) {
                       $.ajax({
                           url: api.user.userUpdate,
                           type: 'post',
                           data: opt,
                           success: (res) => {
                               this.editLoading = false;
                               if (res.error) {
                                   this.$Message.destroy();
                                   this.$Message.warning({
                                       content: res.message,
                                       duration: 3
                                   });
                               } else {
                                   this.$Message.success(this.$t('user_save_successfully'));
                                   this.medianValue = false;
                                   this.$emit('handleData');
                               }
                           },
                           error: (res, textStatus, responseText) => {
                               util.handleAjaxError(this, res, textStatus, responseText, () => {
                                   this.editLoading = false;
                               });
                           }
                       });
                   } else {
                       this.editLoading = false;
                   }
               });
           },
       }
   };
</script>
<style scoped>
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
        border: 1px dashed #999;
    }
    .upload-list img {
        width: 100%;
        height: 100%;
    }
</style>

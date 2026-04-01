<style lang="less">
    @import './notlogin.less';
</style>

<template>
    <div class="forget-password" @keydown.enter="handleSubmit">
        <Row type="flex" justify="left" align="bottom" class="logo-row">
            <i-col span="4" offset="2" class="logo-con">
                <span class="logo">
                    <img src="../images/logo.png" style="width: 90%" alt="">
                </span>
            </i-col>
            <!-- <i-col span="5" class="lang-con">
                <span class="lang">
                    <span @click="changeLang('zh-CN')" :class="lang == 'zh-CN' ? 'lang-item selected' : 'lang-item'">中文简体</span>
                    <Divider type="vertical" />
                    <span @click="changeLang('en-US')" :class="lang == 'en-US' ? 'lang-item selected' : 'lang-item'">English</span>
                </span>
            </i-col> -->
        </Row>
        <div class="forget-password-con">
            <Row>
                <!-- <i-col span="12" >
                    <div style="margin-top: 45px">
                        <img src="../images/login_left_img.png" style="width: 90%" alt="">
                    </div>
                </i-col> -->
                <i-col span="10" push="6">
                    <div class="form-con">
                        <p class="form-title-style">{{$t('forgetPassword_forgot_password')}}</p>

                      <Form ref="forgetPasswordForm" :model="form" :rules="rules">
                          <FormItem prop="username">
                              <i-input v-model="form.username" size="large" :placeholder="$t('forgetPassword_username')">
                                  <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                        <Icon type="ios-mail" />
                                    </span>
                              </i-input>
                          </FormItem>
                          <FormItem prop="captchaCode">
                              <Row>
                                  <i-col span="15">
                                      <i-input v-model="form.captchaCode" size="large" :maxlength="4" :placeholder="$t('forgetPassword_captcha')">
                                          <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                              <Icon type="logo-linkedin" />
                                          </span>
                                      </i-input>
                                  </i-col>
                                  <i-col span="9">
                                      <div @click="getCaptcha" style="height: 36px;text-align:center;border-radius: 4px;cursor:pointer;;">
                                          <img :src="captchaUrl" alt="" style="display:inline-block;height: 36px">
                                      </div>
                                  </i-col>
                              </Row>
                          </FormItem>
                          <FormItem prop="verify_code">
                              <Row>
                                  <i-col span="15">
                                      <i-input v-model="form.verify_code" :maxlength="4" size="large" :placeholder="$t('forgetPassword_verification_code')">
                                          <!-- <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                              <img src="../images/register/password.svg" alt="">
                                          </span> -->
                                      </i-input>
                                  </i-col>
                                  <i-col span="8" offset="1">
                                      <Button @click="getCode" style="width: 100%;color:#157afb;border:#157afb 1px solid;border-radius:6px" size="large" :class="isDisabled? '': 'submit-btn'" :disabled="isDisabled">{{ getBtnText() }}</Button>
                                  </i-col>
                              </Row>
                          </FormItem>
                          
                          <FormItem prop="newpassword">
                              <i-input v-model="form.newpassword" :maxlength="32" size="large" :placeholder="$t('forgetPassword_new_password')">
                                  <span slot="prefix">
                                      <Icon type="md-key" />
                                  </span>
                              </i-input>
                          </FormItem>
                          <FormItem prop="read" class="read-tip">
                                <Row type="flex" justify="space-between">
                                    <Col>
                                        <span style="color:#2d8cf0;cursor:pointer" @click.prevent="toRegister">{{$t('forgetPassword_to_register')}}</span>
                                    </Col>
                                    <Col>
                                        <span>{{$t('register_has_account')}}</span>
                                        <span style="color:#2d8cf0;cursor:pointer" @click="toLogin">{{$t('forgetPassword_to_login')}}</span>
                                    </Col>
                                </Row>
                            </FormItem>
                            <div style="margin-bottom: 10px; margin-top: 30px">
                              <Button @click="handleSubmit" type="primary" :loading="loading" style="height:50px;" size="large" long>{{$t('forgetPassword_submit')}}</Button>
                          </div>
                      </Form>
                    </div>
                </i-col>
            </Row>
            <div style="width:100%;">
                <p style="width:100%;text-align:center;margin-bottom:25px;color:#7a90a7">{{$t('common_copyright')}}</p>
                <div class="use-chrome" v-if="userAgent.indexOf('Chrome') < 0">
                    {{$t('common_use_chrome') }}
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import Cookies from "js-cookie";
import util from "@/libs/util";
import api from "@/api";
import { getDomain } from "@/common/commonvar.js";
import { mapGetters } from "vuex";

export default {
  data() {

    const validateUsername = (rule, value, callback) => {

        if (!value) {
            return callback(new Error(this.$t('register_input_email_empty')));
        }
        if (value.length > 32) {
            return callback(new Error(this.$t('register_input_email_long_error')));
        }
        
        var emailre = /^[a-z0-9A-Z]+[- | a-z0-9A-Z . _]+@(?:[A-z0-9\-]+\.)+[A-z0-9]+$/;
        if (!emailre.test(value)) {
            return callback(new Error(this.$t('register_input_email_format_error')));
        }
        
        callback();
    };

    const validateCaptchaCode = (rule, value, callback) => {
        var re = /^\w{4}$/;
        if (!value) {
            return callback(new Error(this.$t('register_input_captcha_empty')));
        } 
        if (!re.test(value)) {
              return callback(new Error(this.$t('register_input_captcha_format_error')));
        }
        if (this.getCaptchaHash(value) != this.captchaHash)
        {
            console.log(this.captchaHash)
            return callback(new Error(this.$t('register_input_captcha_error')));
        }
        callback();
    };

    const valideEmailCode = (rule, value, callback) => {
      var re = /^\d{4}$/;
      if (!value) {
        callback(new Error(this.$t("forgetPassword_verification_code_empty")));
      } else if (!re.test(value)) {
        callback(new Error(this.$t("forgetPassword_verification_code_format_error")));
      }else if (this.getCaptchaHash(value) != this.emailCodeHash){
          return callback(new Error(this.$t('register_input_captcha_error')));
      } else {
        callback();
      }
    };
    const validateNewPassword = (rule, value, callback) => {
      var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,32}$/;
      if (!value) {
        callback(new Error(this.$t("forgetPassword_newpassword_format_error")));
      }else if (!re.test(value)) {
        callback(new Error(this.$t("forgetPassword_newpassword_format_error")));
      } else {
        callback();
      }
    };
    return {
      showVerify: true,
      showTips: false,
      userAgent: navigator.userAgent,
      loading: false,
      setLoading: false,
      captchaLoading: false,
      loginType: "password",
      form: {
        username: "",
        password: "",
        verify_code: "",
        captchaCode: "",
        newpassword: "",
      },
      captchaUrl: '',
      captchaKey: '',
      captchaHash: '',
      emailCodeHash: '',
      lang: '',
      captchaCodeKey: "",
      setPasswordKey: "",
      rules: {
        username: [
          { required: true, validator: validateUsername, trigger: "blur" }
        ],
        captchaCode: [
          { required: true, validator: validateCaptchaCode, trigger: 'blur' }
        ],
        verify_code: [
          { required: true, validator: valideEmailCode, trigger: "blur" }
        ],
        newpassword: [
          { required: true, validator: validateNewPassword, trigger: "blur" },
        ],
      },
      pwdType: "password",
      nameError: false,
      nameErrorMess: "",
      isDisabled: false,
      getCodeLock: false,
      codeSending: false,
      timeLast: 60,
      isSend: false,
      vrifyAccountLoading: false,
    };
  },
  computed: {
    userNameTypes() {
      const emailTxt = this.$t("register_login_email");
      const phoneNumTxt = this.$t("register_login_phone");
      const TxtArr = [];
      if (this.open_signin_email) {
        TxtArr.push(emailTxt);
      }
      if (this.open_signin_mobile) {
        TxtArr.push(phoneNumTxt);
      }
      return TxtArr;
    },
    userNameEmptyError() {
      return (
        this.$t("register_login_prefix_please") + this.userNameTypes.join("/")
      );
    },
    userNameTooLong() {
      return (
        this.userNameTypes.join("/") +
        this.$t("register_login_max_length", { count: 60 })
      );
    },
  },
  mounted() {
    this.getCaptcha();

    this.lang = Cookies.get("lang") || "zh-CN";
  },
  methods: {
    changeLang (lang) {
        this.lang = lang;
        this.$store.commit('switchLang', lang);
    },
    toLogin(){
        this.$router.push({name: 'login'});
    },
    toRegister(){
        this.$router.push({name: 'register'});
    },
    getCaptchaHash(v){
        for (var i = v.length - 1, h = 0; i >= 0; --i) {
            h += v.charCodeAt(i);
        }
        return h;
    },
    getBtnText() {
      if (this.isDisabled) {
        // 发送中或倒计时
        if (this.codeSending) {
          return this.$t("forgetPassword_sending");
        } else {
          return this.$t("forgetPassword_retry_time", { num: this.timeLast });
        }
      } else {
        if (this.isSend) {
          return this.$t("forgetPassword_resend");
        } else {
          return this.$t("forgetPassword_get_verification_code");
        }
      }
    },
    getCaptcha() {
      this.captchaLoading = true;
      $.ajax({
        url: api.site.captcha,
        method: "POST",
        data: {
          language: this.lang == "zh-CN" ? "0" : "1",
        },
        success: (res) => {
          if (res.error) {
            this.$Message.destroy();
            this.$Message.warning({
              content: res.message,
              duration: 3,
            });
          } else {
            this.captchaLoading = false;
            this.captchaUrl = res.data.bin;
            this.captchaCodeKey = res.data.key;
            this.captchaHash = res.data.hash;
          }
        },
        error: (res, textStatus, responseText) => {
          util.handleAjaxError(this, res, textStatus, responseText);
        },
      });
    },
    getCode() {

        if (this.getCaptchaHash(this.form.captchaCode) != this.captchaHash)
        {
            this.$Message.destroy();
            this.$Message.warning({content: this.$t('register_input_captcha_error'), duration: 5});
            return ;
        }
        
        let data = {
          op: "sendEmailCode",
          email: this.form.username,
          language: this.lang == "zh-CN" ? "0" : "1",
          captchaCodeKey: this.captchaCodeKey,
          captchaCode: this.form.captchaCode,
        };
        this.getCodeLock = true;
        this.isDisabled = true;
        this.codeSending = true;
        $.ajax({
          url: api.site.forgetPassword,
          method: "POST",
          data: data,
          success: (res) => {
            // this.getCodeLock = false;
            if (res.error) {
              this.isDisabled = false;
              this.codeSending = false;
              this.$Message.destroy();
              this.$Message.warning({
                content: res.message,
                duration: 3,
              });
              // if (res.error == "user_account_not_exist") {
              //   this.nameError = true;
              //   this.nameErrorMess = res.error;
              // }
              // if (res.error == "user_captcha_incorrect") {
              //   this.$refs.captchaForm.validate((valid) => {});
              //   this.form.captchaCode = "";
              //   this.getCaptcha();
              // }
            } else {
              this.$Message.success({
                content: this.$t("forgetPassword_send_successfully"),
                duration: 3,
              });

              this.emailCodeHash = res.data.emailCodeHash;
              this.timeLast = 60;

              this.isDisabled = true;
              this.codeSending = false;
              let timer = setInterval(() => {
                if (this.timeLast > 0) {
                  this.timeLast -= 1;
                } else {
                  clearInterval(timer);
                  this.isSend = true;
                  this.isDisabled = false;
                }
              }, 1000);
            }
          },
          error: (res, textStatus, responseText) => {
            util.handleAjaxError(this, res, textStatus, responseText, () => {
              this.getCodeLock = false;
              this.isDisabled = false;
              this.codeSending = false;
            });
          },
        });
    },
    handleSubmit() {
      this.$refs.forgetPasswordForm.validate((valid) => {
        if (valid) {
          let device_number = Cookies.get("device_number") || new Date().getTime();
          let data = {
            op: 'submit',
            email: this.form.username,
            captchaCode: this.form.captchaCode,
            captchaCodeKey: this.captchaCodeKey,
            verify_code: this.form.verify_code,
            newpassword: window.btoa(this.form.newpassword),
            device_name: navigator.platform || "",
            device_number: device_number,
            app_key: "pc-passport",
            app_version: "1.0.0",
          };
          this.setLoading = true;
          $.ajax({
            url: api.site.forgetPassword,
            method: "POST",
            data: data,
            success: (res) => {
              this.setLoading = false;
              if (res.error) {
                this.$Message.destroy();
                this.$Message.warning({
                  content: res.message,
                  duration: 3,
                });
              } else {
                Cookies.set('liteLoginAccount', this.form.username, { domain: getDomain(), expires: 7 });
                this.toLogin()
              }
            },
            error: (res, textStatus, responseText) => {
              util.handleAjaxError(this, res, textStatus, responseText, () => {
                this.setLoading = false;
              });
            },
          });
        }
      });
    },
  }
};
</script>
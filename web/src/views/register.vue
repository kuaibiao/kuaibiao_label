<style lang="less">
    @import './notlogin.less';
</style>

<template>
    <div class="register" @keydown.enter="handleSubmit">
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
        <div class="register-con">
            <Row>
                <!-- <i-col span="12" >
                    <div style="margin-top: 45px">
                        <img src="../images/login_left_img1.png" style="width: 90%" alt="">
                    </div>
                </i-col> -->
                <i-col span="10" push="6">
                    <div class="form-con">
                        <p class="form-title-style">{{$t('register_welcome')}}</p>
                        <Form ref="registerForm" :model="form" :rules="rules" style="margin-top: 25px">
                            <FormItem prop="company">
                                <i-input v-model="form.company" :maxlength="32" size="large" :placeholder="$t('register_input_company')">
                                    <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                        <Icon type="md-contacts" />
                                    </span>
                                </i-input>
                                <!-- <div class="show-error" v-if="companyError">
                                    <span v-if="companyErrorMess == 'company_already_exists'">
                                        {{$t('user_company_exist')}}
                                        <Tooltip transfer placement="top" style="display:inline-block">
                                            <span style="color: #2d8cf0;cursor:pointer">{{$t('user_contact_service')}}</span>
                                            <div slot="content" style="text-align: right">
                                                <p>{{$t('user_customer_service_number')}}</p>
                                            </div>
                                        </Tooltip>
                                    </span>
                                    <span v-else-if="companyErrorMess">{{companyErrorMess}}</span>
                                </div> -->
                            </FormItem>
                            <FormItem prop="email">
                                <i-input v-model="form.email" :maxlength="32" size="large" :placeholder="$t('register_input_email')">
                                    <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                        <Icon type="ios-mail" />
                                    </span>
                                </i-input>
                            </FormItem>
                            <FormItem prop="mobile">
                                <i-input v-model="form.mobile" :maxlength="11" size="large" :placeholder="$t('register_input_mobile')">
                                    <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                        <Icon type="ios-mail" />
                                    </span>
                                </i-input>
                            </FormItem>
                            <FormItem prop="password">
                                <i-input type="password" v-model="form.password" :maxlength="32" size="large" autocomplete="off" :placeholder="$t('register_input_password')">
                                    <span slot="prefix" style="height:100%;display:flex;align-items:center;justify-content:center">
                                        <Icon type="ios-key" />
                                    </span>
                                </i-input>
                            </FormItem>
                            
                            <FormItem prop="captcha">
                                <Row>
                                    <i-col span="12">
                                        <i-input clearable v-model="form.captcha" :maxlength="4" size="large" :placeholder="$t('register_input_captcha')">
                                            <!-- <span slot="prefix">
                                                <Icon custom="iconfont icon-yanzhengma" style="font-size: 14px"/>
                                            </span> -->
                                        </i-input>
                                    </i-col>
                                    <i-col span="12">
                                        <div @click="getCaptcha" style="height: 36px;text-align:center;border-radius: 4px;cursor:pointer;;">
                                            <img :src="captchaUrl" alt="" style="display:inline-block;height: 36px">
                                        </div>
                                    </i-col>
                                </Row>
                            </FormItem>
                            
                            <FormItem prop="read" class="read-tip">
                                <Row type="flex" justify="space-between">
                                    <Col>
                                        <Checkbox v-model="form.read" style="line-height: 16px">
                                            <span style="color:#2d8cf0;cursor:pointer" @click.prevent="toAgreement">{{$t('register_input_agreement')}}</span>
                                        </Checkbox>
                                    </Col>
                                    <Col>
                                        <span>{{$t('register_has_account')}}</span>
                                        <span style="color:#2d8cf0;cursor:pointer" @click="toLogin">{{$t('register_to_login')}}</span>
                                    </Col>
                                </Row>
                            </FormItem>
                            <Button @click="handleSubmit" style="height:50px;font-size:18px" type="primary" size="large" :loading="loading" long>{{$t('register_input_submit')}}</Button>
                        </Form>
                        
                        
                    </div>
                </i-col>
            </Row>
            <!-- <div style="width:100%;">
                <p style="width:100%;text-align:center;margin-bottom:25px;color:#7a90a7">{{$t('common_copyright')}}</p>
                <div class="use-chrome" v-if="userAgent.indexOf('Chrome') < 0">
                    {{$t('common_use_chrome') }}
                </div>
            </div> -->
        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie';
import util from '@/libs/util';
import api from '@/api';
import {getDomain} from '@/common/commonvar.js';
import {mapGetters} from 'vuex';

export default {
    data () {
        const validateCompany = (rule, value, callback) => {
            console.log('validateCompany: ' + value)
            if (!value) {
                return callback(new Error(this.$t('register_input_company_empty')));
            }
            if (value.length > 32) {
                return callback(new Error(this.$t('register_input_company_limit_max')));
            }
            if (value.length < 2) {
                return callback(new Error(this.$t('register_input_company_limit_min')));
            }
            // this.vrifyCompanyLoading = true;
            // $.ajax(
            //     {
            //         url: api.site.register,
            //         method: 'POST',
            //         data: {
            //             language: (this.lang == 'zh-CN') ? '0' : '1',
            //             op: 'validateName',
            //             company: this.form.company,
            //         },
            //         success: (res) => {
            //             this.vrifyCompanyLoading = false;
            //             if (res.error) {
            //                 this.companyError = true;
            //                 this.companyErrorMess = res.error;
            //             } else {
            //                 this.companyError = false;
            //             }
            //         },
            //         error: (res, textStatus, responseText) => {
            //             this.vrifyCompanyLoading = false;
            //             util.handleAjaxError(this, res, textStatus, responseText);
            //         }
            //     }
            // );
            callback();
        };

        const validateEmail = (rule, value, callback) => {

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
            
            // this.vrifyAccountLoading = true;
            // $.ajax(
            //     {
            //         url: api.site.register,
            //         method: 'POST',
            //         data: {
            //             language: (this.lang == 'zh-CN') ? '0' : '1',
            //             op: 'validateAccountNumber',
            //             email: this.form.email,
            //         },
            //         success: (res) => {
            //             this.vrifyAccountLoading = false;
            //             if (res.error) {
            //                 this.emailError = true;
            //                 this.emailErrorMess = res.error;
            //             } else {
            //                 this.emailError = false;
            //             }
            //         },
            //         error: (res, textStatus, responseText) => {
            //             this.vrifyAccountLoading = false;
            //             util.handleAjaxError(this, res, textStatus, responseText);
            //         }
            //     }
            // );
            callback();
        };

        const validateMobile = (rule, value, callback) => {
            if (!value) {
                return callback(new Error(this.$t('register_input_mobile_empty')));
            }
            if (value.length > 11) {
                return callback(new Error(this.$t('register_input_mobile_limit_max')));
            }
            if (value.length < 11) {
                return callback(new Error(this.$t('register_input_mobile_limit_min')));
            }
            callback();
        };

        const validatePassword = (rule, value, callback) => {
            if (!value) {
                return callback(new Error(this.$t('register_input_password_empty')));
            } 
            var re = /^(?![0-9]+$)(?![a-zA-Z]+$)[0-9A-Za-z]{6,32}$/;
            if (!re.test(value)) {
                return callback(new Error(this.$t('register_input_password_format_error')));
            } 
            callback();
        };

        const validateCaptcha = (rule, value, callback) => {
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

        const validateRead = (rule, value, callback) => {
            if (!value) {
                return callback(new Error(this.$t('register_input_agreement')));
            } 
            callback();
        };

        return {
            captchaUrl: '',
            captchaKey: '',
            captchaHash: '',
            isProduction: api.isProduction,
            lang: Cookies.get('lang') || 'zh-CN',
            userAgent: navigator.userAgent,
            loading: false,
            vrifyCompanyLoading: false,
            vrifyAccountLoading: false,
            // getCodeLock: false,
            invitationSucc: false,
            form: {
                company: '',
                email: '',
                mobile:'',
                password: '',
                captcha: '',
                read: true,
            },
            rules: {
                company: [
                    { validator: validateCompany, trigger: 'blur' }
                ],
                email: [
                    { validator: validateEmail, trigger: 'blur' }
                ],
                mobile: [
                    { validator: validateMobile, trigger: 'blur' }
                ],
                password: [
                    { validator: validatePassword, trigger: 'blur' }
                ],
                captcha: [
                    { validator: validateCaptcha, trigger: 'blur' }
                ],
                read: [
                    { validator: validateRead, trigger: 'blur' }
                ],
            },
            isChrome: true,
        };
    },
    computed: {
        // ...mapGetters([
        //     'open_register_email',
        //     'open_register_mobile',
        // ]),
        
    },
    created() {
        this.fromYourDomainWeb = this.$route.query.from === 'register_from_yourdomain_com';
        if (this.fromYourDomainWeb) {
            let _paq = window._paq = window._paq || [];
            _paq.push(['trackEvent', 'Register', 'visit', 'register_from_yourdomain_com']);
        }
    },
    mounted () {

        //自动加载验证码
        this.getCaptcha();

        //校验是否chrome浏览器
        let nav = window.navigator.userAgent;
        if (nav.includes('Chrome') || nav.includes('chrome')){
            this.isChrome = true
        } else {
            this.isChrome = false
        }
    },
    methods: {
        //切换语言
        changeLang (lang) {
            this.lang = lang;
            this.$store.commit('switchLang', lang);
        },
        toLogin(){
            this.$router.push({name: 'login'});
        },
        getCaptcha () {
            this.captchaLoading = true;
            $.ajax(
                {
                    url: api.site.captcha,
                    method: 'POST',
                    data: {},
                    success: (res) => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.captchaLoading = false;
                            this.captchaUrl = res.data.bin;
                            this.captchaKey = res.data.key;
                            this.captchaHash = res.data.hash;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                }
            );
        },
        getCaptchaHash(v){
            for (var i = v.length - 1, h = 0; i >= 0; --i) {
                h += v.charCodeAt(i);
            }
            return h;
        },
        handleSubmit () {
            if (this.loading) {
                return;
            }

            this.$refs.registerForm.validate((valid) => {
                if (!valid)
                {
                    this.loading = false;
                    return;
                }
                this.loading = true;
                $.ajax(
                    {
                        url: api.site.register,
                        method: 'POST',
                        data: {
                            language: (this.lang == 'zh-CN') ? '0' : '1',
                            device_name: navigator.platform || '',
                            device_number: Cookies.get('device_number') || new Date().getTime(),
                            app_key: 'pc-passport',
                            app_version: '1.0.0',
                            company: this.form.company,
                            email: this.form.email,
                            mobile: this.form.mobile,
                            password: window.btoa(this.form.password),
                            verifyCode: this.form.captcha,
                            verifyCodeKey: this.captchaKey,
                            site_invitation_code: this.$route.query.invitationCode || '',
                        },
                        success: (res) => {
                            this.loading = false;
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                
                                Cookies.set('labelToolLoginAccount', this.form.email, { domain: getDomain(), expires: 7 });
                                //Cookies.set('labelToolAccessToken', res.data.access_token, { domain: getDomain(), expires: 7 });

                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: this.$t('register_finish_to_login'),
                                    duration: 3
                                });

                                this.$router.push({
                                    path: '/login'
                                });
                                
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.loading = false;
                            });
                        }
                    }
                );
            });
        },
    }
};
</script>
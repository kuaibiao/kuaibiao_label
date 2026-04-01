<style lang="less">
    @import './notlogin.less';
</style>

<template>
    <div class="login" @keydown.enter="handleSubmit">
        <Row type="flex" align="bottom" class="logo-row">
            <i-col span="4" offset="2" class="logo-con">
                <span class="logo">
                  <img :src="settings.site_logo ? getUserAvatar(settings.site_logo) : '../images/logo.png'" style="width: 90%;height: auto" alt="">
<!--                    <img v-else src="../images/logo.png" style="width: 90%" alt="">-->
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
        <div class="login-con">
            <Row>
                <!-- <i-col span="12" >
                    <div style="margin-top: 45px">
                        <img src="../images/login_left_img2.png" style="width: 90%" alt="">
                    </div>
                </i-col> -->
                <i-col span="10" push="6">
                    <div class="login-form-con">
                        <p class="form-title-style">{{$t('login_welcome')}}</p>
                        <Form ref="loginForm" :model="form" :rules="rules">
                            <FormItem prop="username">
                                <i-input v-model="form.username" size="large" :placeholder="$t('login_enter_name')">
                                    <span slot="prepend">
                                        <Icon :size="18" type="ios-person-outline"></Icon>
                                    </span>
                                </i-input>
                            </FormItem>
                            <FormItem prop="password">
                                <i-input type="password" :maxlength="32" size="large" v-model="form.password" :placeholder="$t('login_enter_password')">
                                    <span slot="prepend">
                                        <Icon :size="18" type="ios-lock-outline"></Icon>
                                    </span>
                                </i-input>
                            </FormItem>
                            <Row type="flex" justify="space-between">
                                <Col>
                                    <span style="color: #2d8cf0;cursor: pointer" @click="toRegisterPage">{{$t('login_register')}}</span>
                                </Col>
                                <Col>
                                    <span style="color: #2d8cf0;cursor: pointer" @click="toForgetPasswordPage">{{$t('login_forget_password')}}</span>
                                </Col>
                            </Row>

                            <FormItem>
                                <Button @click="handleSubmit" style="margin-top: 15px" type="primary" :loading="loading" long>{{$t('login_login')}}</Button>
                            </FormItem>
                        </Form>
                    </div>
                </i-col>
            </Row>

        </div>
    </div>
</template>

<script>
import Cookies from 'js-cookie';
import Vue from 'vue';
import util from '@/libs/util';
import api from '@/api';
export default {
    data () {
        const validateName = (rule, value, callback) => {
            if (!value) {
                return callback(new Error(this.$t('login_fill_account')));
            }
            callback();
        };
        const validatePassword = (rule, value, callback) => {
            if (!value || value.length < 6) {
                return callback(new Error(this.$t('login_validate_password')));
            }
            callback();
        };
        return {
            loading: false,
            form: {
                username: Cookies.get('liteLoginAccount') || '',
                password: '',
                device_name: '',
                device_number: '',
                app_key: '',
                app_version: ''
            },
            lang: Cookies.get('lang') || 'zh-CN',
            access_token: '',
            rules: {
                username: [
                    { validator: validateName, trigger: 'blur' }
                ],
                password: [
                    { validator: validatePassword, trigger: 'blur' }
                ]
            },
            staticBase: api.staticBase,
            settings:{},
        };
    },
    mounted () {
        this.form.username = Cookies.get('liteLoginAccount') || '';
        this.form.password = '';
        this.getInit();
    },
    methods: {
        getInit(){
          $.ajax({
            url: api.site.init,
            method: 'POST',
            success: (res) => {
              this.settings = res.data.settings;
            },
            error: (res, textStatus, responseText) => {
              util.handleAjaxError(this, res, textStatus, responseText, () => {
                this.loading = false;
              });
            }
          });
        },
        getUserAvatar (url) {
          if (url.indexOf('http') > -1) {
            return url;
          } else {
            return this.staticBase + url;
          }
        },
        changeLang (lang) {
            this.lang = lang;
            this.$store.commit('switchLang', lang);
        },
        toRegisterPage(){
            this.$router.push({
                name: 'register',
                query: {
                    _fm: 'ln'
                }
            });
        },
        toForgetPasswordPage () {
            this.$router.push({
                name: 'forget-password',
                query: {
                    _fm: 'ln'
                }
            });
        },
        handleSubmit () {
            if (this.loading) {
                return;
            }
            this.$refs.loginForm.validate((valid) => {
                if (valid) {
                    this.loading = true;
                    let data = {
                        username: this.form.username,
                        password: window.btoa(this.form.password),
                        language: (this.lang == 'zh-CN') ? '0' : '1',
                        device_name: navigator.platform || '',
                        device_number: '111',
                        app_key: 'pc',
                        app_version: '1.0.0'
                    };
                    $.ajax({
                        url: api.site.login,
                        method: 'POST',
                        data: data,
                        success: (res) => {
                            this.loading = false;
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$store.commit('login', {
                                    accessToken: res.data.access_token,
                                    userName: this.form.username
                                });
                                this.access_token = res.data.access_token;

                                //是否登录的标志
                                Cookies.set('labelToolAccessToken', res.data.access_token);
                                util.setCookie('labelToolLoginAccount', this.form.username, 30);

                                this.$store.commit('setLoginStatus', true);
                                this.getUserInfo();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.loading = false;
                            });
                        }
                    });
                }
            });
        },
        getUserInfo () {
            // 获取用户信息
            const userInfo = this.$store.state.user.userInfo;

            console.log('login.vue.getUserInfo.userInfo: ')
            console.log(userInfo)

            let userInfoRequest =
                $.ajax({
                    url: api.user.detail,
                    method: 'POST',
                    data: {
                        access_token: userInfo.accessToken
                    },
                    success: (res) => {
                        console.log('res: ')
                        console.log(res)

                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            const userDetail = res.data.user;
                            Cookies.set('liteMenuShrink', false);
                            this.$store.state.app.settings = {
                                ...res.data.settings
                            };
                            if (userDetail) {
                                this.$store.state.user.userInfo = {
                                    ...this.$store.state.user.userInfo,
                                    ...userDetail
                                };
                                this.$store.commit('login', this.$store.state.user.userInfo);

                                console.log('this.$store.state.user: ')
                                console.log(this.$store.state.user)

                                let lang = userDetail.language === '0' ? 'zh-CN' : 'en-US';
                                // this.$store.commit('switchLang', lang);
                                // 这里的直接修改store信息
                                // 因为用上一行的方式切换语音是个异步操作 会导致下边的提示信息用的语言和用户看到的不一样
                                localStorage.lang = lang;
                                this.$store.state.app.lang = lang;
                                this.$i18n.locale = lang;
                                this.$store.commit('setPermissions', userDetail.permisstions);
                                this.$store.commit('updateMenulist');
                            }
                            this.$Message.destroy();
                            this.$Message.success(this.$t('login_success_tips'));
                            this.$store.commit('clearAllTags');
                            this.$router.push({
                                path: '/home'
                            });
                        }
                    }
                }
            );

            console.log('userInfoRequest: ')
            console.log(userInfoRequest)

            this.$store.commit('requestUserInfo', userInfoRequest);
        },
    }
};
</script>

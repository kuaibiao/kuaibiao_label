<template>
    <Modal v-model="loginModal"
           class-name="login-modal"
           width="420"
           :closable="false"
           :mask-closable="false">
        <div class="login-modal">
            <Card :bordered="false" dis-hover>
                <p slot="title">
                    <Icon type="log-in"></Icon>
                    {{$t('login_login')}}
                </p>
                <div class="form-con">
                    <Form ref="loginForm" :model="form" :rules="rules">
                        <FormItem prop="username">
                            <i-input v-model="form.username" :placeholder="$t('login_enter_name')">
                                <span slot="prepend">
                                    <Icon :size="16" type="ios-person"></Icon>
                                </span>
                            </i-input >
                        </FormItem>
                        <FormItem prop="password">
                            <i-input  type="password" v-model="form.password" :placeholder="$t('login_enter_password')">
                                <span slot="prepend">
                                    <Icon :size="14" type="ios-lock"></Icon>
                                </span>
                            </i-input >
                        </FormItem>
                        <FormItem>
                            <Button @click="handleSubmit" type="primary" :loading="loading" long>{{$t('login_login')}}</Button>
                        </FormItem>
                    </Form>
                    <p class="login-tip">{{$t('login_enter_tips')}}</p>
                </div>
            </Card>
        </div>
    </Modal>
</template>
<script>
    import Cookies from 'js-cookie';
    import Vue from 'vue';
    import api from '@/api';
    export default {
        name: "login",
        props: {
            userName: {
                type: String,
                default: ''
            },
        },
        data () {
            const validateName = (rule, value, callback = function () {
            }) => {
                if (!value) {
                    return callback(new Error(this.$t('login_fill_account')));
                }
                callback();
            };
            const validatePassword = (rule, value, callback = function () {
            }) => {
                if (!value || value.length < 6) {
                    return callback(new Error(this.$t('login_validate_password')));
                }
                callback();
            };
            return {
                loading: false,
                form: {
                    username: Cookies.get('labelToolLoginAccount') || this.userName || '',
                    password: '',
                    device_name: '',
                    device_number: '',
                    app_key: '',
                    app_version: ''
                },
                access_token: '',
                rules: {
                    username: [
                        { validator: validateName, trigger: 'blur' }
                    ],
                    password: [
                        { validator: validatePassword, trigger: 'blur' }
                    ]
                }
            };
        },
        computed: {
            loginModal () {
                return !this.$store.state.app.hasLogin;
            },
        },
        methods: {
            handleSubmit () {
                if (this.loading) {
                    return;
                }
                this.$refs.loginForm.validate((valid) => {
                    if (valid) {
                        this.loading = true;
                        this.form.device_name = navigator.platform || '';
                        this.form.device_number = '111';
                        this.form.app_key = 'pc';
                        this.form.app_version = '1.0.0';
                        this.loading = true;
                        $.ajax(
                            {
                                url: api.login,
                                method: 'POST',
                                data: this.form,
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
                                        Cookies.set('labelToolAccessToken', res.data.access_token);
                                        this.$store.commit('setLoginStatus', true);
                                        this.getUserInfo();
                                    }
                                },
                                error: () => {
                                    this.loading = false;
                                    this.$Message.destroy();
                                    this.$Message.error(this.$t('login_error_tips'));
                                }
                            }
                        );
                    }
                });
            },
            getUserInfo () {
                // 获取用户信息
                let app = this;
                const access_token = app.$store.state.user.userInfo.accessToken;
                let userInfoRequest =
                    $.ajax({
                        url: api.userDetail,
                        method: 'POST',
                        data: {
                            access_token: access_token
                        },
                        success: function (res) {
                            if (res.error) {
                                app.$Message.warning({
                                    content: this.$t('login_failed_user_information'),
                                    duration: 3
                                });
                            } else {
                                const userDetail = res.data.user;
                                // userDetail.avatar = api.staticBase + userDetail.avatar;
                                // if (userDetail.team && userDetail.team.logo) {
                                //     userDetail.team.logo = api.staticBase + userDetail.team.logo;
                                // }
                                // let access = 1;
                                // $.each(userDetail.roles, function (k, v) {
                                //     if (v.item_name === 'team_manager') {
                                //         access = 0;
                                //     }
                                // });
                                // Cookies.set('access', access);
                                Cookies.set('labelToolMenuShrink', false);
                                app.$store.state.app.settings = {
                                    ...res.data.settings
                                };
                                if (userDetail !== '' || userDetail !== null) {
                                    app.$store.state.user.userInfo = {
                                        ...app.$store.state.user.userInfo,
                                        ...userDetail
                                    };
                                    app.$store.commit('login', app.$store.state.user.userInfo);
                                    app.$store.commit('setPermissions', userDetail.permisstions);
                                    app.$store.commit('updateMenulist');
                                    let lang = userDetail.language === '0' ? 'zh-CN' : 'en-US';
                                    // app.$store.commit('switchLang', lang);
                                    // 这里的直接修改store信息
                                    // 因为用上一行的方式切换语音是个异步操作 会导致下边的提示信息用的语言和用户看到的不一样
                                    localStorage.lang = lang;
                                    app.$store.state.app.lang = lang;
                                    app.$i18n.locale = lang;
                                    // Cookies.set('team_id', res.data.team_id);
                                    // Cookies.set('logo', res.data.team.logo);
                                }
                                app.$Message.success(this.$t('login_success_tips'));
                                // app.$router.push({
                                //     path: '/home'
                                // });
                            }
                        }
                    }
                    );
                app.$store.commit('requestUserInfo', userInfoRequest);
            },
        }
    };
</script>

<style lang="scss" scoped>
    .login-modal {
        .login-tip {
            font-size: 10px;
            text-align: center;
            color: #c3c3c3;
        }
    }
</style>

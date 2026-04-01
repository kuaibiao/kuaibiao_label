import Vue from 'vue';
import iView from 'iview';
import 'element-scroll-polyfill';
import { router } from './router/index';
import { appRouter } from './router/router';
import store from './store';
import App from './App.vue';
import { i18n } from '@/locale';
import 'iview/dist/styles/iview.css';
import '@/styles/common.less';
import api from '@/api/index';
import {favicon_ico} from '@/api/init';
import Cookies from 'js-cookie';
import env from '../build/env';
import keyCodes from './common/keyCodes';
import mixin from './common/globalMixin';
import ellipsisText from './common/components/ellipsis-text.vue';
import util from "@/libs/util";
const version = require('../build/version').version;
window._version = version;
Vue.config.productionTip = false;
Vue.config.devtools = env !== 'production';

// 解决当 button 处于 focus 状态是按空格 触发 button 的 click; 目前是Windows下的Firefox浏览器有这个问题
// 利用事件流的捕获 改变button元素的focus状态
// window.addEventListener('focus', () => {
//     if (document.activeElement && document.activeElement.tagName.toLowerCase() === 'button') {
//         document.activeElement.blur();
//     }
// }, true);
Vue.use(iView, {
    i18n: (key, value) => i18n.t(key, value)
});

Vue.filter('keyMap', function (keyBoard) {
    let text = '';
    if (keyBoard) {
        text += keyBoard.altKey ? 'Alt ' : '';
        text += keyBoard.shiftKey ? 'Shift ' : '';
        text += keyBoard.ctrlKey ? 'Ctrl ' : '';
        text += keyBoard.metaKey ? 'Meta ' : '';
        text += (keyCodes[keyBoard.keyCode] || '').toUpperCase();
    }
    return !text ? i18n.t('common_set_shortcut') : text;
});
Vue.filter('formatUrl', function (url) {
    // 如果url 包含域名信息,则原样保留,如果只是路径则添加主机域名信息
    if (url) {
        let result = new URL(url, api.staticBase);
        return result.href;
    } else {
        return url;
    }
});
Vue.mixin(mixin);
Vue.component('ellipsis-text', ellipsisText);
new Vue({
    el: '#app',
    router: router,
    i18n,
    store: store,
    render: h => h(App),
    data () {
        return {
            currentPageName: ''
        };
    },
    mounted () {
        document.querySelector('#favicon_ico').setAttribute('href', favicon_ico);
        $.ajaxSetup({timeout: 60 * 1000});
        $(document).ajaxComplete((event, xhr, settings) => {
            switch (xhr.status) {
                case 500: {
                    this.$router.push({
                        name: 'error-500'
                    });
                    break;
                }
                case 401: {
                    if (this.$store.state.app.hasLogin) {
                        this.$Message.warning({
                            content: xhr.responseJSON.message,
                            duration: 3
                        });
                        this.$store.commit('setLoginStatus', false);
                    } else {
                        Cookies.remove('labelToolAccessToken');
                        this.$router.push({
                            name: 'login'
                        });
                    }
                    break;
                }
                case 200: {
                    if (xhr.responseJSON && xhr.responseJSON.error === 'site_auth_fail') {
                        if (this.$store.state.app.hasLogin) {
                            this.$Message.warning({
                                content: xhr.responseJSON.message,
                                duration: 3
                            });
                            this.$store.commit('setLoginStatus', false);
                        } else {
                            Cookies.remove('labelToolAccessToken');
                            this.$router.push({
                                name: 'login'
                            });
                        }
                        break;
                    }
                }
            }
        });
        this.currentPageName = this.$route.name;
        // 显示打开的页面的列表
        this.$store.commit('setOpenedList');
        this.$store.commit('initCachepage');
        // 权限菜单过滤相关
        this.$store.commit('switchLang', this.$store.state.app.lang || Cookies.get('lang') || 'zh-CN');

        //if (this.$router.name && (this.$router.name !== 'activate') && (this.$router.name !== 'forget-password')) {
        let notLoginRouters = ['register', 'forget-password', 'login','thirdpartylogin'];
        if (this.$router.name && notLoginRouters.indexOf(this.$router.name) < 0) {
            console.log('main.js.this.$router.name: ')
            console.log(this.$router.name)
            this.chechAuth();
        }
    },
    watch: {
        $route (to, from) {
            console.log('main.js.this.$router.from: ')
            console.log(from)

            console.log('main.js.this.$router.to: ')
            console.log(to)

            //if ((to.name && (to.name !== 'activate') && (to.name !== 'forget-password') && (from.path === '/'))) {
            let notLoginRouters = ['register', 'forget-password', 'login','thirdpartylogin'];
            if ((to.name && notLoginRouters.indexOf(to.name) < 0 && (from.path === '/'))) {
                console.log('main.js.this.$router.name1: ')
                console.log(to.name)
                this.chechAuth();
            }
        }
    },
    created () {
        let tagsList = [];
        appRouter.map((item) => {
            if (item.children.length <= 1) {
                tagsList.push(item.children[0]);
            } else {
                tagsList.push(...item.children);
            }
        });
        this.$store.commit('setTagsList', tagsList);
    },
    methods: {

        getMessageCount () {

            let accessToken = Cookies.get('labelToolAccessToken');
            console.log('main.js.chechAuth.accessToken: ')
            console.log(accessToken)

            if (!accessToken)
            {
                console.log('main.js.chechAuth no accessToken')
                return false;
            }

            $.ajax({
                url: api.user.stat,
                method: 'POST',
                data: {
                    access_token: accessToken
                },
                success: (res) => {
                    this.$store.commit('changeUserStat', false);
                    if (res.error) {
                        // 轮询的 不做错误处理 静默失败
                    } else {
                        const messageCount = res.data.new_message_count;
                        this.$store.commit('setMessageCount', messageCount);
                    }
                }
            });
        },
        chechAuth () {
            // 获取用户信息
            let accessToken = Cookies.get('labelToolAccessToken');
            console.log('main.js.chechAuth.accessToken: ')
            console.log(accessToken)

            if (accessToken) {
                let userInfoRequest =
                    $.ajax({
                        url: api.user.detail,
                        method: 'POST',
                        data: {
                            access_token: accessToken
                        },
                        success: (res) => {
                            if (res.error) {
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                                this.$store.commit('logout', this);
                            } else {
                                const userDetail = res.data.user;
                                // if (userDetail.type === '1') {
                                //     window.open(api.adminBase + '/home', '_self');
                                // };
                                this.$store.state.app.settings = {
                                    ...res.data.settings,
                                };
                                if (userDetail) {
                                    this.$store.state.user.userInfo = {
                                        ...this.$store.state.user.userInfo,
                                        ...userDetail
                                    };
                                    // this.$store.state.user.userInfo = userDetail;
                                    this.$store.commit('login', this.$store.state.user.userInfo);
                                    this.$store.commit('setPermissions', userDetail.permisstions);
                                    this.$store.commit('updateMenulist');
                                }
                                let lang = userDetail.language === '0' ? 'zh-CN' : 'en-US';
                                this.$store.commit('switchLang', lang);
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                            });
                        }
                    }
                );
                this.$store.commit('requestUserInfo', userInfoRequest);
            } else {
                this.$router.push({
                    name: 'login'
                });
            }
        },
    }
});

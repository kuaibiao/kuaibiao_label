import Vue from 'vue';
import iView from 'iview';
import Util from '../libs/util';
import VueRouter from 'vue-router';
import Cookies from 'js-cookie';
import api from '@/api';
import {routers, otherRouter, appRouter} from './router';
import store from '@/store';
const originalPush = VueRouter.prototype.push;
const originalReplace = VueRouter.prototype.replace;
VueRouter.prototype.push = function push (location, onResolve, onReject) {
    if (onResolve || onReject) return originalPush.call(this, location, onResolve, onReject);
    return originalPush.call(this, location).catch(err => err);
};
VueRouter.prototype.replace = function replace (location, onResolve, onReject) {
    if (onResolve || onReject) return originalReplace.call(this, location, onResolve, onReject);
    return originalReplace.call(this, location).catch(err => err);
};
Vue.use(VueRouter);

// 路由配置
const RouterConfig = {
    mode: 'history',
    routes: routers
};
export const router = new VueRouter(RouterConfig);

// 路由守卫，判断是否登录的位置
router.beforeEach((to, from, next) => {

    iView.LoadingBar.start();
    console.log('index.js.to.name : ' + to.name)

    //读取用户的登录标志
    let accessToken = Cookies.get('labelToolAccessToken');

    //用户是否登录
    let isLogin = true;
    if (!accessToken)
    {
        isLogin = false;
    }
    console.log('index.js.isLogin: ' + isLogin)

    //非登录路由
    let notLoginRouters = ['register', 'forget-password', 'login','thirdpartylogin'];

    if (Cookies.get('locking') === '1' && to.name !== 'locking') { // 判断当前是否是锁定状态
        console.log('index.js.locking ')
        next({
            replace: true,
            name: 'locking'
        });
    } else if (Cookies.get('locking') === '0' && to.name === 'locking') {
        console.log('index.js.tolocking ')
        next(false);
    } else if (isLogin && notLoginRouters.indexOf(to.name) >= 0) {
        console.log('index.js.isLogin,is notLoginRouters ')
        next({
            name: 'home_index'
        });
    } else if (!isLogin && notLoginRouters.indexOf(to.name) >= 0) {
        console.log('index.js.notlogin,is notLoginRouters ')
        next();
    } else if (!isLogin && from.name === 'home_index' && to.name === 'quicklogin') { // 判断是否已经登录且前往的是快速登录页
        console.log('index.js.home_index && quicklogin ')
        Util.title();
        next({
            name: 'home_index'
        });
        iView.LoadingBar.finish();
    } else if (!isLogin) {
        console.log('index.js.notlogin1 ')
        next({
            name: 'login'
        });
    } else {
        console.log('index.js.other ')

        const curRouterObj = Util.getRouterObjByName([otherRouter, ...appRouter], to.name);
        if (curRouterObj && curRouterObj.access !== undefined) { // 需要判断权限的路由
            if (curRouterObj.access === parseInt(Cookies.get('access'))) {
                Util.toDefaultPage([otherRouter, ...appRouter], to.name, router, next); // 如果在地址栏输入的是一级菜单则默认打开其第一个二级菜单的页面
            } else {
                next({
                    replace: true,
                    name: 'error-403'
                });
            }
        } else { // 没有配置权限的路由, 直接通过
            Util.toDefaultPage([...routers], to.name, router, next);
        }
    }
});

router.afterEach((to, from) => {
    Util.openNewPage(router.app, to.name, to.params, to.query);
    iView.LoadingBar.finish();
    store.commit('changePrevUrl', from);
    window.scrollTo(0, 0);
});

router.onError((error) => {
    iView.LoadingBar.finish();
});

<style lang="less">
@import "./main.less";
</style>
<template>
        <div class="main" :class="{'main-hide-text': shrink}">
            <div class="sidebar-menu-con" :style="{width: shrink?'60px':'220px', overflow: shrink ? 'visible' : 'auto'}">
                <scroll-bar ref="scrollBar">
                    <shrinkable-menu
                        :shrink="shrink"
                        @on-change="handleSubmenuChange"
                        :theme="menuTheme"
                        :before-push="beforePush"
                        :open-names="openedSubmenuArr"
                        @shrinkHandle="shrinkHandle"
                        :menu-list="menuList">
                    </shrinkable-menu>
                </scroll-bar>
            </div>
            <div class="main-header-con">
                <div class="main-header">
                    <div slot="top" class="logo-con" v-if="settings.site_logo">
                        <template>
                            
                            <img
                            :src="getUserAvatar(userInfo.site.logo ? userInfo.site.logo : settings.site_logo)"
                            key="min-logo"
                            @error="settings.site_logo = ''"/>

                            <!-- <div v-else class="logo-con-team">{{(settings.site_name || '').substr(0,1).toUpperCase()}}</div>
                            <div class="logo-team-name">{{settings.site_name || ''}}</div> -->
                        </template>
                    </div>
                    <div class="header-middle-con">
                        <notice-board></notice-board>
                    </div>
                    <div class="header-avator-con">
                        <div class="user-dropdown-menu-con" style="float:right;margin-left:30px;">
                            <Row type="flex" justify="end" align="middle" class="user-dropdown-innercon">
                                <Dropdown transfer trigger="click" @on-click="handleClickUserDropdown" placement="bottom-end">
                                    <a href="javascript:void(0)" class="name_id">
                                        <span class="main-user-name">{{ userInfo.nickname }} </span><span>({{userInfo.id}})</span>
                                        <Icon type="md-arrow-dropdown" />
                                    </a>
                                    <DropdownMenu slot="list">
                                        <DropdownItem name="ownSpace">{{ $t('common_user_ownspace') }}</DropdownItem>
                                        <DropdownItem name="loginout" divided>{{ $t('common_user_quit') }}</DropdownItem>
                                    </DropdownMenu>
                                </Dropdown>
                                <Avatar
                                    :src="userInfo.avatar ? getUserAvatar(userInfo.avatar) : ''"
                                    :class="!userInfo.avatar ? 'no-avatar': ''"
                                    style="margin-left: 6px;">
                                    {{userInfo.avatar ? '' :(userInfo.email && userInfo.email[0].toUpperCase())}}
                                </Avatar>
                            </Row>
                        </div>
                        <!-- <a class="use-document" :href="helpBase" style="float:right" target="_blank">
                            <Tooltip :transfer="true" :content="$t('user_guide')" placement="bottom">
                                <Icon type="ios-help-circle-outline"></Icon>
                            </Tooltip>
                        </a> -->
                        <message-tip v-model="mesCount" style="float:right"></message-tip>
                        <full-screen v-model="isFullScreen" @on-change="fullscreenChange" style="float:right"></full-screen>
                    </div>
                </div>
            </div>
            <div class="single-page-con" :style="{left: shrink?'60px':'220px'}">
                <div class="single-page">
                    <keep-alive :include="cachePage">
                        <router-view v-if="$route.meta.keepAlive"></router-view>
                    </keep-alive>
                    <router-view v-if="!$route.meta.keepAlive"></router-view>
                </div>
            </div>
            <login :userName="userInfo.email"></login>

          <Modal
              v-model="logoutModal"
              title="退出"
          >
            <p>请选择登录路径</p>

            <div slot="footer">
              <Button @click="logoutModal = false">取消退出</Button>
              <Button type="primary" @click="goSaas">教学平台登录</Button>
              <Button type="primary" @click="goLogin">账号密码登录</Button>
            </div>
          </Modal>
        </div>

</template>
<script>
import shrinkableMenu from './main-components/shrinkable-menu/shrinkable-menu.vue';
// import tagsPageOpened from './main-components/tags-page-opened.vue';
import breadcrumbNav from './main-components/breadcrumb-nav.vue';
import fullScreen from './main-components/fullscreen.vue';
import lockScreen from './main-components/lockscreen/lockscreen.vue';
import messageTip from './main-components/message-tip.vue';
import themeSwitch from './main-components/theme-switch/theme-switch.vue';
import noticeBoard from './main-components/notice-board.vue';
import Cookies from 'js-cookie';
import $ from 'jquery';
import api from '@/api';
import util from '@/libs/util.js';
import scrollBar from '@/views/my-components/scroll-bar/vue-scroller-bars';
import Login from 'components/login';
import {favicon_ico} from '@/api/init';
import Vue from 'vue';

const navLang = navigator.language;
const localLang = (navLang === 'zh-CN' || navLang === 'en-US') ? navLang : false;
export default {
    components: {
        shrinkableMenu,
        // tagsPageOpened,
        breadcrumbNav,
        fullScreen,
        lockScreen,
        messageTip,
        themeSwitch,
        scrollBar,
        Login,
        noticeBoard
    },
    data () {
        return {
            staticBase: api.staticBase,
            helpBase: api.helpBase,
            shrink: Cookies.get('liteMenuShrink') === 'true',
            isFullScreen: false,
            openedSubmenuArr: this.$store.state.app.openedSubmenuArr,
          logoutModal:false,
        };
    },
    computed: {
        menuList () {
            return this.$store.state.app.menuList;
        },
        settings () {
            return this.$store.state.app.settings;
        },
        userInfo () {
            return this.$store.state.user.userInfo;
        },
        // currentUserName () {
        //     return this.$store.state.user.userInfo.email;
        // },
        pageTagsList () {
            return this.$store.state.app.pageOpenedList; // 打开的页面的页面对象
        },
        currentPath () {
            return this.$store.state.app.currentPath; // 当前面包屑数组
        },
        cachePage () {
            return this.$store.state.app.cachePage;
        },
        menuTheme () {
            return this.$store.state.app.menuTheme;
        },
        mesCount () {
            return parseInt(this.$store.state.app.messageCount);
        },
        $lang () {
            return this.$store.state.app.lang;
        },
    },
    methods: {
        getUserAvatar (url) {
            if (url.indexOf('http') > -1) {
                return url;
            } else {
                return this.staticBase + url;
            }
        },
        init () {
            let pathArr = util.setCurrentPath(this, this.$route.name, this.$route.meta);
            this.$store.commit('updateMenulist');
            if (pathArr.length >= 2) {
                this.$store.commit('addOpenSubmenu', pathArr[1].name);
            }
            this.checkTag(this.$route.name);
        },
        getMessageCount () {
            const userInfo = this.$store.state.user.userInfo;
            console.log('main.vue.userInfo: ')
            console.log(userInfo)

            if (!userInfo)
            {
                console.log('main.vue.userInfo no userInfo')
                return false;
            }

            $.ajax({
                url: api.user.stat,
                method: 'POST',
                data: {
                    access_token: userInfo.accessToken
                },
                success: (res) => {
                    if (res.error) {
                        // 轮询的 不做错误处理 静默失败
                    } else {
                        const messageCount = res.data.new_message_count;
                        const taskNewCount = res.data.task_new_count;
                        this.$store.commit('setMessageCount', messageCount);
                    }
                }
            });
        },
        // toggleClick () {
        //     this.shrink = !this.shrink;
        // },
        shrinkHandle (shrink) {
            this.shrink = shrink;
            this.$store.state.app.shrink = shrink;
        },
        handleClickUserDropdown (name) {

            //用户中心操作
            if (name === 'ownSpace') {
                this.$router.push({
                    name: 'ownspace_index'
                });

            //退出登录操作
            } else if (name === 'loginout') {
              this.logoutModal = true;
            }
        },
        goSaas(){
          this.logoutModal = false;
          //删除cookie
          Cookies.remove('labelToolAccessToken');

          //删除store
          this.$store.commit('logout', this);
          this.$store.commit('clearOpenedSubmenu');

          window.location.href = 'https://saas.edu360.cn/';

        },
        goLogin(){
          this.logoutModal = false;
          //删除cookie
          Cookies.remove('labelToolAccessToken');

          //删除store
          this.$store.commit('logout', this);
          this.$store.commit('clearOpenedSubmenu');

          //跳转到登录页
          this.$router.push({
            name: 'login'
          });
        },
        checkTag (name) {
            let openpageHasTag = this.pageTagsList.some(item => {
                if (item.name === name) {
                    return true;
                }
            });
            if (!openpageHasTag) {
                //  解决关闭当前标签后再点击回退按钮会退到当前页时没有标签的问题
                util.openNewPage(
                    this,
                    name,
                    this.$route.params || {},
                    this.$route.query || {}
                );
            }
        },
        handleSubmenuChange (val) {
            // console.log(val)
        },
        beforePush (name) {
            // if (name === 'accesstest_index') {
            //     return false;
            // } else {
            //     return true;
            // }
            return true;
        },
        fullscreenChange (isFullScreen) {
            // console.log(isFullScreen);
        },
        scrollBarResize () {
            this.$refs.scrollBar && this.$refs.scrollBar.resize();
        }
    },
    watch: {
        $route (to) {
            this.$store.commit('setCurrentPageName', to.name);
            let pathArr = util.setCurrentPath(this, to.name, to.meta);
            if (pathArr.length > 2) {
                this.$store.commit('addOpenSubmenu', pathArr[1].name);
            }
            this.checkTag(to.name);
            localStorage.currentPageName = to.name;
            util.title(util.handleTitle(this, this.$route.meta));
        },
        $lang () {
            util.setCurrentPath(this, this.$route.name, this.$route.meta); // 在切换语言时用于刷新面包屑
        },
        openedSubmenuArr () {
            setTimeout(() => {
                this.scrollBarResize();
            }, 300);
        }
    },
    mounted () {
        this.init();
        window.addEventListener('resize', this.scrollBarResize);
        util.title(util.handleTitle(this, this.$route.meta));

        //轮询调用用户统计信息
        //this.getMessageCount();
        const notifyListInterval = window.setInterval(() => {
            this.getMessageCount();
        }, 50000);
    },
    created () {
        // 显示打开的页面的列表
        this.lang = localStorage.lang || 'zh-CN';
        this.$store.commit('setOpenedList');
    },
    dispatch () {
        window.removeEventListener('resize', this.scrollBarResize);
    }
};
</script>

<style scoped>
    .use-document {
        display: inline-block;
        width: 30px;
        padding: 12px 0 13px;
        text-align: center;
        cursor: pointer;
        color: #495060;
        font-size: 21px;
        line-height: 1;
    }
</style>

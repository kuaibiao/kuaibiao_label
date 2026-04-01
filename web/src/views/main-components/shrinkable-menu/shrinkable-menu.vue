<style lang="less">
    @import './menu.less';
</style>

<template>
    <div :style="{background: bgColor}" class="ivu-shrinkable-menu">
        <slot name="top"></slot>
        <div class="navicon-con transMenu" @click="toggleClick" :style="{width: this.shrink ? '60px' : '220px'}">
            <Button :style="{transform: 'rotateZ(' + (this.shrink ? '-90' : '0') + 'deg)'}" type="text" ghost>
                <Icon type="ios-menu" size="20"></Icon>
            </Button>
        </div>
        <!-- <sidebar-menu
            :menu-theme="theme"
            :menu-list="menuList"
            :open-names="openNames"
            @on-change="handleChange"
            :shrink="shrink"
        ></sidebar-menu> -->
        <Menu ref="sideMenu" v-if="menuList.length > 0" :class="!shrink? 'menu-main': ''" :active-name="currName" :open-names="openNames" :theme="menuTheme" width="auto" @on-select="changeMenu">
            <!-- 首页 -->
            <Menu-item name="home_index" key="menuitemhome_index" :class="{sideSmall : shrink}" style="min-height: 48px" :to="'/home'">
                <!-- <Icon type="ios-speedometer-outline" key="menuiconhome_index" size="iconSize"></Icon> -->
                <span v-if="userSitename.length > 0" class="layout-text" key="titlehome_index"><strong>{{ userSitename }}</strong></span>
                <span v-else class="layout-text" key="titlehome_index">{{ $t('router_home') }}</span>
            </Menu-item>
            <!-- root -->
            <MenuGroup :title="$t('common_root')" v-if="access > 2">
                <template v-for="item in menuListRoot">
                    <Menu-item v-if="item.children.length<=1"
                            :name="item.children[0].name"
                            :key="'menuitem' + item.name"
                            :class="{sideSmall : shrink}"
                            :to="item.path + '/' + item.children[0].path">
                        <Icon :type="item.children[0].icon || item.icon" :size="iconSize" :key="'menuicon' + item.name"></Icon>
                        <span class="layout-text" :key="'title' + item.name">{{ itemTitle(item.children[0]) }}</span>
                    </Menu-item>

                    <Submenu v-if="item.children.length > 1" :name="item.name" :key="item.name" :class="{sideSmall : shrink}">
                        <template slot="title">
                            <Icon :type="item.icon" :size="iconSize"></Icon>
                            <span class="layout-text">{{ itemTitle(item) }}</span>
                        </template>
                        <template v-for="child in item.children">
                            <Menu-item :name="child.name"
                                    :key="'menuitem' + child.name"
                                    :to="item.path + '/' + child.path">
                                <Icon :type="child.icon" :size="iconSize" :key="'icon' + child.name"></Icon>
                                <span class="layout-text" :key="'title' + child.name">{{ itemTitle(child) }}</span>
                            </Menu-item>
                        </template>
                    </Submenu>
                </template>
            </MenuGroup>
            <!-- admin -->
            <MenuGroup :title="$t('common_administrater')" v-if="access > 1">
                <template v-for="item in menuListAdmin">
                    <Menu-item v-if="item.children.length<=1"
                            :name="item.children[0].name"
                            :key="'menuitem' + item.name"
                            :class="{sideSmall : shrink}"
                            :to="item.path + '/' + item.children[0].path">
                        <Icon :type="item.children[0].icon || item.icon" :size="iconSize" :key="'menuicon' + item.name"></Icon>
                        <span class="layout-text" :key="'title' + item.name">{{ itemTitle(item.children[0]) }}</span>
                    </Menu-item>

                    <Submenu v-if="item.children.length > 1" :name="item.name" :key="item.name" :class="{sideSmall : shrink}">
                        <template slot="title">
                            <Icon :type="item.icon" :size="iconSize"></Icon>
                            <span class="layout-text">{{ itemTitle(item) }}</span>
                        </template>
                        <template v-for="child in item.children">
                            <Menu-item :name="child.name"
                                    :key="'menuitem' + child.name"
                                    :to="item.path + '/' + child.path">
                                <Icon :type="child.icon" :size="iconSize" :key="'icon' + child.name"></Icon>
                                <span class="layout-text" :key="'title' + child.name">{{ itemTitle(child) }}</span>
                            </Menu-item>
                        </template>
                    </Submenu>
                </template>
            </MenuGroup>
            <!-- team -->
            <MenuGroup :title="$t('common_operator')" v-if="access > 0">
                <template v-for="item in menuListTeam">
                    <Menu-item v-if="item.children.length<=1"
                            :name="item.children[0].name"
                            :key="'menuitem' + item.name"
                            :class="{sideSmall : shrink}"
                            :to="item.path + '/' + item.children[0].path">
                        <Icon :type="item.children[0].icon || item.icon" :size="iconSize" :key="'menuicon' + item.name"></Icon>
                        <span class="layout-text" :key="'title' + item.name">
                            {{ itemTitle(item.children[0]) }}
                            <!-- <Badge status="warning" /> -->
                        </span>
                    </Menu-item>

                    <Submenu v-if="item.children.length > 1" :name="item.name" :key="item.name" :class="{sideSmall : shrink}">
                        <template slot="title">
                            <Icon :type="item.icon" :size="iconSize"></Icon>
                            <span class="layout-text">{{ itemTitle(item) }}</span>
                        </template>
                        <template v-for="child in item.children">
                            <Menu-item :name="child.name"
                                    :key="'menuitem' + child.name"
                                    :to="item.path + '/' + child.path">
                                <Icon :type="child.icon" :size="iconSize" :key="'icon' + child.name"></Icon>
                                <span class="layout-text" :key="'title' + child.name">{{ itemTitle(child) }}</span>
                            </Menu-item>
                        </template>
                    </Submenu>
                </template>
            </MenuGroup>
        </Menu>
        <p class="copyRight" v-if="shrink">&copy; {{ new Date().getFullYear() }}</p>
        <p class="copyRight" v-if="!shrink">&copy; {{ new Date().getFullYear() }} {{site_name}}
            <br>
            <span>{{version}}</span>
        </p>
    </div>
</template>

<script>
import Cookies from 'js-cookie';
import util from '@/libs/util';
export default {
    name: 'shrinkableMenu',
    data () {
        return {
            shrink: Cookies.get('liteMenuShrink') === 'true',
            shrinkCur: true,
            version: window._version,
            lock: 0,
        };
    },
    props: {
        menuList: {
            type: Array,
            required: true
        },
        menuTheme: {
            type: String,
            default: 'dark',
            validator (val) {
                return util.oneOf(val, ['dark', 'light']);
            }
        },
        iconSize: Number,
        openNames: {
            type: Array
        },
        // shrink: {
        //     type: Boolean,
        // },
        beforePush: {
            type: Function
        },
    },
    components: {
    },
    computed: {
        bgColor () {
            return this.menuTheme === 'dark' ? '#495060' : '#fff';
        },
        shrinkIconColor () {
            return this.menuTheme === 'dark' ? '#fff' : '#495060';
        },
        site_name () {
            return this.$store.state.app.settings.site_name;
        },
        userSitename(){

            if (!this.$store.state.user.userInfo.site)
            {
                return '';
            }

            // console.log('this.$store.state.user.userInfo')
            // console.log(this.$store.state.user.userInfo.site.name)

            return this.$store.state.user.userInfo.site.name;
        },
        menuListRoot () {
            return this.menuList.slice(0, 2);
        },
        menuListAdmin () {
            return this.menuList.slice(2, 5);
        },
        menuListTeam () {
            return this.menuList.slice(5);
        },
        currName () {
            if (this.$route.meta.parent2 && this.$route.params.tab && (this.$route.params.tab !== 'work-list')) {
                return this.$route.meta.parent2;
            }
            if (this.$route.meta.parent) {
                return this.$route.meta.parent;
            } else {
                return this.$route.name;
            }
        },
        access: function () {
            let access = 1;
            let roles = this.$store.state.user.userInfo.roles ? this.$store.state.user.userInfo.roles : []
            if (roles.length < 1) {
                return 0
            } else {
                $.each(roles, function (k, v) {
                    if (v.item_name === 'root') {
                        access = 3;
                        return;
                    }
                    if (v.item_name === 'manager') {
                        access = 2;
                        return;
                    }
                });
                return access;
            }

            // let userPermissions = this.$store.state.app.userPermissions;
            // let permission = ['team/users'];
            // return util.is_array_contain(userPermissions, permission) ? 0 : 1;
        },
    },
    methods: {
        handleChange (name) {
            let willpush = true;
            if (this.beforePush !== undefined) {
                if (!this.beforePush(name)) {
                    willpush = false;
                }
            }
            // if (willpush) {
            //     this.$router.push({
            //         name: name,
            //         query: {
            //             "_t": Date.now()
            //         }
            //     });
            // }
            this.$emit('on-change', name);
        },
        toggleClick () {
            this.shrink = !this.shrink;
            Cookies.remove('liteMenuShrink');
            Cookies.set('liteMenuShrink', this.shrink);
            this.$emit('shrinkHandle', this.shrink);
        },
        changeMenu (active) {
            this.$emit('on-change', active);
                if (this.$refs.sideMenu) {
                    this.$refs.sideMenu.currentActiveName = this.currName;
                }
        },
        itemTitle (item) {
            if (typeof item.title === 'object') {
                return this.$t(item.title.i18n);
            } else {
                return item.title;
            }
        }
    },
    updated () {
        if (this.lock < 2) {
            this.$nextTick(() => {
                if (this.$refs.sideMenu) {
                    this.$refs.sideMenu.updateOpened();
                    this.$refs.sideMenu.updateActiveName();
                    this.lock++;
                }
            });
        }
    },
    created () {
        this.$nextTick(() => {
            if (this.$refs.sideMenu) {
                this.$refs.sideMenu.updateOpened();
                this.$refs.sideMenu.updateActiveName();
            }
        });
    }
};
</script>



// 利用组件内路由守卫修改页面标题 全局混入
import api from '@/api/index';
import { mapState } from 'vuex';
let privateBase =  api.download.file;
let platformName = 'LabelTool v1.2.0';
export default {
    beforeRouteEnter (to, from, next) {
        next(vm => {
            vm._setTitle();
        });
    },
    computed: {
        ...mapState({
            gmixin_accessToken: state => state.user.userInfo.accessToken,
            gmixin_userInfo: state => state.user.userInfo,
        })
    },
    methods: {
        gmixin_getPrivateFileUrl (key, base = privateBase) {
            return base + '?file=' + key + '&access_token=' + this.gmixin_accessToken
        },
        gmixin_checkPrivateUrlAndAddToken (url) {
            let tokenStr = 'access_token=' + this.gmixin_accessToken;
            if (~url.indexOf(privateBase)) {
                if (~url.indexOf('?')) {
                    return url + '&' + tokenStr;
                } else {
                    return url + '?' + tokenStr
                }
            } else {
                return url;
            }
        },
        _setTitle () {
            let title = '';
            let item = this.$route.meta;
            if (typeof item.title === 'object') {
                title = this.$t(item.title.i18n);
            } else {
                title = item.title;
            }
            let name = '';
            if (typeof platformName === 'object') {
                name = this.$t(platformName.i18n);
            } else {
                name = platformName;
            }
            document.title = name + '-' + title;
        }
    }
};

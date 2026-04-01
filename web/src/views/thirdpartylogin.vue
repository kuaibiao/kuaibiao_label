<template>
    <div>

    </div>
</template>
<script>
import Cookies from 'js-cookie';
import api from '@/api';
export default {
    name: 'thirdpartylogin',
    data () {
        return {
            access_token:'',
            loading:false,
            openid:'',
          name:'',
        };
    },
    created() {
        if(this.$route.query.openid){
            this.openid = this.$route.query.openid;
        }
      if(this.$route.query.name){
        this.name = this.$route.query.name;
      }
    },
    mounted () {
        this.getToken();
    },
    methods: {
        getToken () {
            if (this.loading) {
                return;
            }
            this.loading = true;
            let data = {
              access_token: this.access_token,
              openid:this.openid,
              appid:'edu360_saas',
              sign:'base64_encode',
              timestamp:new Date().getTime(),
              nonce:'D3rmO7+C5',
              nickname:this.name,
            };
            $.ajax({
              url: api.site.thirdpartylogin,
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
                    userName: ''
                  });
                  this.access_token = res.data.access_token;

                  //是否登录的标志
                  Cookies.set('labelToolAccessToken', res.data.access_token);
                  // util.setCookie('labelToolLoginAccount', '', 30);

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
        }
    },
}
</script>


<template>
  <div class="file-transfer">
      <div style="text-align: right;margin-bottom: 10px">
          <Button @click="openFtp()" type="primary">{{$t('user_create_detect_ftp')}}</Button>
      </div>
      <Table
        size="large"
        highlight-row ref="transferTable"
        :columns="tableOption"
        :data="tableData"
        :loading="loading"
        stripe
        show-header>
    </Table>
  </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
export default {
    name: 'file-transfer',
    data () {
        return {
            loading: false,
            ftp: {},
            tableOption: [
                {
                    title: this.$t('user_address'),
                    key: 'ftp_host',
                    align: 'center',
                    render: (h, params) => {
                        if (this.ftp == '') {
                            return h();
                        } else {
                            return h('Input', {
                                props: {
                                    value: this.ftp.ftp_host,
                                    readonly: true
                                },
                                style: {
                                    maxWidth: '300px'
                                }
                            });
                        }
                    }
                },
                {
                    title: this.$t('user_user_name'),
                    key: 'ftp_username',
                    align: 'center',
                    render: (h, params) => {
                        if (this.ftp == '') {
                            return h('div', [
                                h('span', this.$t('user_not_opened') + ' '),
                                h('a', {
                                    attrs: {
                                        herf: 'javascript:;'
                                    },
                                    on: {
                                        click: this.openFtp
                                    }
                                }, this.$t('user_immediately_opened'))]);
                        } else {
                            return h('Input', {
                                props: {
                                    value: this.ftp.ftp_username,
                                    readonly: true
                                },
                                style: {
                                    maxWidth: '300px'
                                }
                            });
                        }
                    }
                },
                {
                    title: this.$t('user_password'),
                    key: 'ftp_password',
                    align: 'center',
                    render: (h, params) => {
                        if (this.ftp == '') {
                            return h();
                        } else {
                            return h('Input', {
                                props: {
                                    value: this.ftp.ftp_password,
                                    readonly: true
                                },
                                style: {
                                    maxWidth: '300px'
                                }
                            });
                        }
                    }
                }
            ],
            // tableData: [
            //     {
            //         ftp_host: this.ftp.ftp_host,
            //         ftp_username: this.ftp.ftp_username,
            //         ftp_password: this.ftp.ftp_password
            //     }
            // ]
        };
    },
    computed: {
        tableData () {
            if (this.ftp == '') {
                return [
                    {
                        ftp_host: '',
                        ftp_username: '',
                        ftp_password: ''
                    }
                ];
            } else {
                return [{
                    ftp_host: this.ftp.ftp_host,
                    ftp_username: this.ftp.ftp_username,
                    ftp_password: this.ftp.ftp_password
                }];
            }
        }
    },
    mounted () {
        this.getData();
    },
    methods: {
        // 开通
        openFtp () {
            this.loading = true;
            $.ajax({
                url: api.user.openFtp,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getData();
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 3
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.user.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.ftp = res.data.user.ftp;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
    }
};
</script>

<style scoped>
  .file-transfer {
    min-height: 194px;
    /* padding-top: 32px; */
    background-color: #ffffff;
  }
</style>

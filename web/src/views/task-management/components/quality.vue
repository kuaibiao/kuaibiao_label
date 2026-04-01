<template>
    <div>
        <Table
            border
            size="large"
            highlight-row
            :columns="columns"
            :data="tableData"
            :loading="loading"
            @on-sort-change="sortChange"
            stripe
            show-header>
        </Table>
        <div style="margin: 10px;overflow: hidden">
            <div style="float: right;">
                <Page
                        :total="count"
                        :current="page"
                        :page-size="limit"
                        :page-size-opts="[5,10,15,25]"
                        show-total
                        show-elevator
                        show-sizer
                        placement="top"
                        @on-change="changePage"
                        transfer
                        @on-page-size-change="changePageSize">
                    </Page>
            </div>
        </div>
        <Modal
                :width="90"
                v-model="dailyModal"
                :title="$t('admin_daily_performance')"
                @on-cancel="dailyModal = false">
            <Table
                    size="large"
                    highlight-row
                    border
                    :columns="dailyOption"
                    :data="dailyData"
                    stripe
                    show-header>
            </Table>
        </Modal>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
export default {
    data () {
        return {
            loading: false,
            dailyModal: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: +(localStorage.getItem('workListLimit') || 10),
            orderby: '',
            sort: 'desc',
            tableData: [],
            user_id: this.$store.state.user.userInfo.id,
            columns: [
                {
                    title: this.$t('admin_user'),
                    key: 'user_name',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('Tooltip', {
                                props: {
                                    content: this.$t('admin_email') + ': ' + para.row.user.email,
                                    placement: 'right',
                                    transfer: true
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + para.row.user.id),
                                            h('div', this.$t('admin_email') + ': ' + para.row.user.email),
                                            h('div', this.$t('admin_role') + ': ' + this.getRoles(para.row.user.roles)),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', para.row.user.nickname)
                            ]),
                        ]);
                    }
                },
                {
                    title: this.$t('operator_valid_work'),
                    key: 'work_count',
                    align: 'center',
                },
                {
                    title: this.$t('operator_submitted_work'),
                    key: 'submit_count',
                    align: 'center',
                },
                {
                    title: this.$t('operator_valid_data'),
                    align: 'center',
                    children: [
                        {
                            title: this.$t('operator_counting'),
                            key: 'allow_count',
                            align: 'center',
                            sortable: 'custom',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('admin_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('admin_qc_pass_frame')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('admin_counting')),
                                        h('Icon', {
                                            style: {
                                                marginLeft: '3px',
                                                marginTop: '1px',
                                                verticalAlign: 'top'
                                            },
                                            props: {
                                                type: 'ios-help-circle-outline',
                                                size: 16
                                            },
                                        }),

                                    ])
                                ]);
                            }
                        },
                        {
                            title: this.$t('operator_rejecting'),
                            key: 'refuse_count',
                            align: 'center',
                            sortable: 'custom',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('admin_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('admin_qc_reject_frame')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('admin_rejecting')),
                                        h('Icon', {
                                            style: {
                                                marginLeft: '3px',
                                                marginTop: '1px',
                                                verticalAlign: 'top'
                                            },
                                            props: {
                                                type: 'ios-help-circle-outline',
                                                size: 16
                                            },
                                        }),

                                    ])
                                ]);
                            }
                        },
                        {
                            title: this.$t('operator_reseting'),
                            key: 'reset_count',
                            align: 'center',
                            sortable: 'custom',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('admin_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('admin_qc_reset_frame')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('admin_reseting')),
                                        h('Icon', {
                                            style: {
                                                marginLeft: '3px',
                                                marginTop: '1px',
                                                verticalAlign: 'top'
                                            },
                                            props: {
                                                type: 'ios-help-circle-outline',
                                                size: 16
                                            },
                                        }),

                                    ])
                                ]);
                            }
                        },
                    ]
                },
                {
                    title: this.$t('operator_cumulative'),
                    key: 'work_time',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h('span', para.row.work_time + '(s)');
                    },
                },
                {
                    title: this.$t('admin_create_time'),
                    key: 'created_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
                {
                    title: this.$t('admin_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span', util.timeFormatter(
                                new Date(+para.row.updated_at * 1000),
                                'MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
                {
                    title: this.$t('admin_check'),
                    align: "center",
                    render: (h, para) => {
                        return h('Button', {
                            props: {
                                size: 'small'
                            },
                            on: {
                                click: () => {
                                    this.getDailyData(para.row.user.id);
                                }
                            }
                        }, this.$t('admin_daily_performance'));
                    }
                }
            ],
            dailyOption: [
                {
                    title: this.$t('admin_user'),
                    key: 'user_name',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('Tooltip', {
                                props: {
                                    content: this.$t('admin_email') + ': ' + para.row.user.email,
                                    placement: 'top',
                                    transfer: true
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + para.row.user.id),
                                            h('div', this.$t('admin_email') + ': ' + para.row.user.email),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', para.row.user.nickname)
                            ]),
                        ]);
                    }
                },
                {
                    title: this.$t('admin_date'),
                    key: 'date',
                    width: 120,
                    align: 'center'
                },
                {
                    title: this.$t('operator_valid_work'),
                    key: 'work_count',
                    align: 'center',
                },
                {
                    title: this.$t('operator_submitted_work'),
                    key: 'submit_count',
                    align: 'center',
                },
                {
                    title: this.$t('admin_qc'),
                    key: 'work_count',
                    align: 'center',
                    children: [
                        {
                            title: this.$t('admin_counting'),
                            key: 'allow_count',
                            align: 'center'
                        },
                        {
                            title: this.$t('admin_rejecting'),
                            key: 'refuse_count',
                            align: 'center'
                        },
                        {
                            title: this.$t('admin_reseting'),
                            key: 'reset_count',
                            align: 'center'
                        },
                    ]
                },
                {
                    title: this.$t('operator_cumulative'),
                    key: 'work_time',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', para.row.work_time + '(s)');
                    }
                },
            ],
            dailyData: [],
        };
    },
    mounted () {
        this.$store.state.app.userInfoRequest.then(res => {
            this.user_id = res.data.user.id;
            this.getData();
        });
    },
    methods: {
        getDailyData (user_id) {
            $.ajax({
                url: api.stat.statByDay,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    user_id: user_id,
                    limit: '1000',
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
                        this.dailyData = res.data.list;
                        this.dailyModal = true;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                    });
                }
            });
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            localStorage.setItem('workListLimit', size);
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        getRoles (roles) {
            let arrStr = '';
            if (roles) {
                roles.forEach((k, v) => {
                    arrStr += this.roles[k.item_name] + '   ';
                });
                return arrStr;
            } else {
                return '';
            }
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.task.assignedUsers,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                },
                success: res => {
                    let data = res.data;
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.roles = res.data.roles;
                        this.getTableData(res.data);
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

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
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
export default {
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: 'desc',
            tableData: [],
            user_id: this.$store.state.user.userInfo.id,
            columns: [
                {
                    title: this.$t('operator_time'),
                    key: 'date',
                    align: 'center',
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
                    title: this.$t('operator_passed'),
                    key: 'allowed_count',
                    sortable: 'custom',
                    align: 'center',
                },
                {
                    title: this.$t('operator_rejected'),
                    key: 'refused_count',
                    sortable: 'custom',
                    align: 'center',
                },
                {
                    title: this.$t('operator_reseted'),
                    key: 'reseted_count',
                    sortable: 'custom',
                    align: 'center',
                },
                {
                    title: this.$t('operator_valid_data'),
                    align: 'center',
                    children: [

                        // {
                        //     title: this.$t('operator_mark_number'),
                        //     key: 'label_count',
                        //     sortable: 'custom',
                        //     align: 'center',
                        //     renderHeader: (h, params) => {
                        //         return h('span', [
                        //             h('Poptip', {
                        //                 'class': {
                        //                     tablePop: true,
                        //                 },
                        //                 props: {
                        //                     trigger: "hover",
                        //                     title: this.$t('operator_fields_description'),
                        //                     content: "",
                        //                     transfer: true,
                        //                     placement: 'right-start',
                        //                 },
                        //                 scopedSlots: {
                        //                     content: () => {
                        //                         return h('span', {
                        //                         }, [
                        //                             h('div', this.$t('operator_mark_number_amount')),
                        //                         ]);
                        //                     }
                        //                 }
                        //             }, [
                        //                 h('span', this.$t('operator_mark_number')),
                        //                 h('Icon', {
                        //                     style: {
                        //                         marginLeft: '3px',
                        //                         marginTop: '1px',
                        //                         verticalAlign: 'top'
                        //                     },
                        //                     props: {
                        //                         type: 'ios-help-circle-outline',
                        //                         size: 16
                        //                     },
                        //                 }),

                        //             ])
                        //         ]);
                        //     }
                        // },
                        {
                            title: this.$t('tool_pass'),
                            key: 'allow_count',
                            sortable: 'custom',
                            align: 'center',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('operator_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('operator_execute_passed')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('operator_counting')),
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
                            title: this.$t('admin_reject'),
                            key: 'refuse_count',
                            sortable: 'custom',
                            align: 'center',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('operator_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('operator_execute_rejected')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('operator_rejecting')),
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
                            title: this.$t('admin_reset'),
                            key: 'reset_count',
                            sortable: 'custom',
                            align: 'center',
                            renderHeader: (h, params) => {
                                return h('span', [
                                    h('Poptip', {
                                        'class': {
                                            tablePop: true,
                                        },
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('operator_fields_description'),
                                            content: "",
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                        scopedSlots: {
                                            content: () => {
                                                return h('span', {
                                                }, [
                                                    h('div', this.$t('operator_execute_reseted')),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', this.$t('operator_reseting')),
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
                    render: (h, para) => {
                        return h('span', para.row.work_time + '(s)');
                    },
                },
                {
                    title: this.$t('operator_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.updated_at * 1000),
                                'MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
            ],
        };
    },
    mounted () {
        this.$store.state.app.userInfoRequest.then(res => {
            this.user_id = res.data.user.id;
            this.getData();
        });
    },
    methods: {
        changePage (page) {
            this.page = page;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            localStorage.setItem('workListLimit', size);
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; 
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        // 获取数据总数
        getData (id) {
            this.loading = true;
            $.ajax({
                url: api.stat.statByDay,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    user_id: this.user_id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    sort: this.sort,
                    orderby: this.orderby,
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
                        this.getTableData(res.data);
                        this.count = res.data.count * 1;
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

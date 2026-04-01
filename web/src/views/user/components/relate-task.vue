<template>
    <div>
        <Row>
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('operator_input_project_info')"
                    clearable
                    search
                    :enter-button="true"/>
            </div>
        </Row>
        <div style="margin-top:10px">
            <Table
                size="large"
                highlight-row ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                stripe
                @on-sort-change="sortChange"
                show-header>
            </Table>
        </div>
        <div style="margin: 10px;overflow: hidden">
            <div style="float: right;">
                <Page
                    :total="count"
                    :current="page"
                    :page-size="limit"
                    :page-size-opts="[5,10,15,25,50]"
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
            sort: '',
            tableOption: [
                {
                    title: this.$t('admin_task'),
                    key: 'name',
                    align: 'left',
                    ellipsis: true,
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                // content: params.row.name,
                                placement: 'top-start',
                                transfer: true
                            },
                            'class': 'tool_tip',
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('admin_item_id') + ': ' + params.row.project_id),
                                        h('div', this.$t('admin_batch_id') + ': ' + params.row.batch_id),
                                        h('div', this.$t('admin_task_id') + ': ' + params.row.task.id),
                                        h('div', this.$t('admin_task_name') + ': ' + params.row.task.name),
                                    ]);
                                }
                            },
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', params.row.task.name)
                        ]);
                    }
                },
                {
                    title: this.$t('operator_cumulative'),
                    key: "work_time",
                    align: "right",
                    maxWidth: 130,
                    render: (h, params) => {
                        return h('span', params.row.work_time + '（s）');
                    }
                },
                {
                    title: this.$t('admin_amount'),
                    key: "work_count",
                    sortable: 'custom',
                    maxWidth: 110,
                    align: "right",
                },
                {
                    title: this.$t('admin_mark_number'),
                    key: "label_count",
                    sortable: 'custom',
                    maxWidth: 150,
                    align: "right",
                    renderHeader: (h, params) => {
                        return h('span', [
                            h('Poptip', {
                                'class': {
                                    tablePop: true,
                                },
                                props: {
                                    trigger: "hover",
                                    title: this.$t('admin_fields_description'),
                                    transfer: true,
                                    placement: 'right-start',
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', this.$t('admin_picture_description')),
                                            h('div', this.$t('admin_text_description')),
                                            h('div', this.$t('admin_voice_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('admin_mark_number')),
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
                    },
                },
                {
                    title: this.$t('admin_passed'),
                    key: "allowed_count",
                    sortable: 'custom',
                    maxWidth: 120,
                    align: "right"
                },
                {
                    title: this.$t('admin_rejected'),
                    key: "refused_count",
                    sortable: 'custom',
                    maxWidth: 120,
                    align: "right"
                },
                {
                    title: this.$t('admin_reseted'),
                    key: "reseted_count",
                    maxWidth: 120,
                    sortable: 'custom',
                    align: "right"
                }
            ],
            tableData: [],
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        },
    },
    methods: {
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
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
        getData () {
            this.loading = true;
            $.ajax({
                url: api.stat.workStatList,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    limit: this.limit,
                    orderby: this.orderby,
                    sort: this.sort,
                    user_id: this.$route.params.id
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
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
    },
    mounted () {
        this.getData();
    }
};
</script>



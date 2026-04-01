<template>
    <div>
        <Row style="margin-bottom:10px">
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
        <Row>
        <Table size="large"
            highlight-row
            ref="userTable"
            :columns="tableOption"
            :data="tableData"
            :loading="loading"
            @on-sort-change="sortChange"
            stripe show-header></Table>
        <div style="margin: 10px;overflow: hidden">
            <div style="float: right;">
            <Page :total="count" :current="page" :page-size="limit" :page-size-opts="[10,15,20,25,30,50]" show-total show-elevator show-sizer placement="top" transfer @on-change="changePage" @on-page-size-change="changePageSize"></Page>
            </div>
        </div>
        </Row>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
export default {
    data () {
        return {
            loading: false,
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            status: [],
            category_id: [],
            tableOption: [
                {
                    title: this.$t('operator_task'),
                    key: 'name',
                    align: 'left',
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                placement: 'top-start',
                                transfer: true,
                            },
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('operator_project_id') + ': ' + params.row.project_id),
                                        h('div', this.$t('operator_batch_id') + ': ' + params.row.batch_id),
                                        h('div', this.$t('operator_step_id') + ': ' + params.row.step_id),
                                        h('div', this.$t('operator_task_id') + ': ' + params.row.task.id),
                                        h('div', this.$t('operator_task_name') + ': ' + params.row.task.name),
                                    ]);
                                }
                            },
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('router-link', {
                                attrs: {
                                    to: {
                                        name: 'my-task-detail',
                                        params: {
                                            id: params.row.task.id,
                                            tab: 'stat-list',
                                            index: 'index'
                                        },
                                    }
                                }
                            }, params.row.task.name)
                        ]);
                    }
                },
                {
                    title: this.$t('operator_cumulative'),
                    key: "work_time",
                    maxWidth: 130,
                    align: "center",
                    render: (h, params) => {
                        return h('span', params.row.work_time + '（s）');
                    }
                },
                {
                    title: this.$t('operator_amount'),
                    key: "work_count",
                    sortable: 'custom',
                    maxWidth: 120,
                    align: "center",
                },
                {
                    title: this.$t('admin_mark_number'),
                    key: "label_count",
                    sortable: 'custom',
                    align: "center",
                    maxWidth: 120,
                    renderHeader: (h, params) => {
                        return h('span', [
                            h('Poptip', {
                                'class': {
                                    tablePop: true,
                                },
                                props: {
                                    trigger: "hover",
                                    title: this.$t('operator_fields_description'),
                                    transfer: true,
                                    placement: 'right-start',
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', this.$t('operator_picture_description')),
                                            h('div', this.$t('operator_text_description')),
                                            h('div', this.$t('operator_voice_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('operator_mark_number')),
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
                    title: this.$t('operator_passed'),
                    key: "allowed_count",
                    sortable: 'custom',
                    align: "center",
                    maxWidth: 120,
                },
                {
                    title: this.$t('operator_rejected'),
                    key: "refused_count",
                    sortable: 'custom',
                    align: "center",
                    maxWidth: 120,
                },
                {
                    title: this.$t('operator_reseted'),
                    key: "reseted_count",
                    sortable: 'custom',
                    align: "center",
                    maxWidth: 120,
                },
                {
                    title: this.$t('operator_check'),
                    align: "center",
                    maxWidth: 100,
                    render: (h, para) => {
                        return h("div", [
                            h("Button", {
                                props: {
                                    type: "primary",
                                    size: "small"
                                },
                                on: {
                                    click: () => {
                                        this.toTaskRecond(para.row.task.id);
                                    }
                                }
                            }, this.$t('operator_check'))
                        ]);
                    }
                },
            ],
            tableData: []
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        }
    },
    mounted () {
        this.getData();
    },
    methods: {
        toTaskRecond (id) {
            this.$router.push({
                name: 'my-task-detail',
                params: {
                    id: id,
                    tab: 'stat-list',
                    index: 'index'
                }
            });
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
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
            this.$store.state.app.userInfoRequest.then(res => {
                this.requestData(res.data.user.id);
            });
        },
        requestData (id) {
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
                    user_id: id
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
        }
    }
};
</script>

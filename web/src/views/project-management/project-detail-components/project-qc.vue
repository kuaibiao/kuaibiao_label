<template>
    <div class="subcontent" id="project_index">
        <div class="main-con">
            <Card>
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_quality_control')}}</div>
                <Table
                    size="large"
                    highlight-row
                    ref="qcTable"
                    :columns="tableOption"
                    :data="tableData"
                    :loading="loading"
                    stripe
                    show-header
                    @on-filter-change = "filterChange"
                    @on-sort-change = "sortChange"
                ></Table>
                <div style="margin: 10px;overflow: hidden;padding: 1px;">
                    <div style="float: right;">
                        <Page
                            :total="count"
                            :current="page"
                            :page-size ="limit"
                            :page-size-opts="[5,10,15,20,25]"
                            show-total
                            show-elevator
                            show-sizer
                            placement = "top"
                            transfer
                            @on-change="changePage"
                            @on-page-size-change = "changePageSize"
                            ></Page>
                    </div>
                </div>
            </Card>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
import progressOp from '../components/qc-progress.vue';
import {categoryDefaultIcon} from '@/common/categoryDefaultIcon';
export default {
    name: 'project-qc',
    data () {
        return {
            loading: false,
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            status: '',
            orderby: '',
            sort: '',
            statuses: [],
            tableOption: [
                {
                    title: 'ID',
                    key: 'id',
                    align: 'center',
                    width: 110,
                },
                {
                    title: this.$t('operator_task'),
                    key: 'name',
                    align: 'left',
                    minWidth: 150,
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
                                        h('div', this.$t('operator_batch_id') + ': ' + params.row.batch.id),
                                        h('div', this.$t('operator_step_id') + ': ' + params.row.step_id),
                                        h('div', this.$t('operator_task_name') + ': ' + params.row.name),
                                    ]);
                                }
                            },
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('router-link', {
                                props: {
                                    to: {
                                        name: 'qc-work-list',
                                        params: {
                                            projectId: params.row.project_id,
                                            id: params.row.id,
                                            index: 'all'
                                        }
                                    }
                                }
                            }, params.row.name)
                        ]);
                    }
                },
                {
                    title: this.$t('operator_working_out'),
                    key: 'amount',
                    align: 'center',
                    maxWidth: 160,
                    render: (h, para) => {
                        if (para.row.stat) {
                            return h('span', (para.row.stat.work_count ? para.row.stat.work_count : '0') + ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0));
                        } else {
                            return h('span', '-- / ' + '-- / ' + (para.row.batch.amount ? para.row.batch.amount : 0));
                        }
                    },
                    renderHeader: (h, params) => {
                        return h('div', [
                            h('div', [
                                h('Poptip', {
                                    'class': {
                                        tablePop: true,
                                    },
                                    props: {
                                        trigger: 'hover',
                                        title: this.$t('operator_fields_description'),
                                        transfer: true,
                                        placement: 'right-start',
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {
                                            }, [
                                                h('div', this.$t('operator_execute_explain')),
                                                h('div', this.$t('operator_symbol_explain')),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', this.$t('operator_working_out')),
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
                            ]),
                        ]);
                    },
                },
                {
                    title: this.$t('operator_schedule'),
                    key: 'statistics',
                    maxWidth: 250,
                    minWidth: 50,
                    align: 'left',
                    render: (h, para) => {
                        return h('div', [
                            h('div', [
                                h('Tooltip', {
                                    props: {
                                        content: (para.row.stat.work_count ? para.row.stat.work_count : '0') + ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0),
                                        placement: 'top-start',
                                        transfer: true,
                                    },
                                    'class': 'tool_tip',
                                    style: {
                                        display: 'inline'
                                    }
                                }, [
                                    h('div', [
                                        h(progressOp, {
                                            props: {
                                                row: para.row,
                                            }
                                        })
                                    ])

                                ]),
                            ]),
                        ]);
                    },
                    renderHeader: (h, params) => {
                        return h('div', [
                            h('div', [
                                h('Poptip', {
                                    props: {
                                        trigger: 'hover',
                                        title: this.$t('operator_fields_description'),
                                        // content: "已执行张数 / 已执行框(条)数 / 已执行点数",
                                        transfer: true,
                                        placement: 'right-start',
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {
                                            }, [
                                                h('div', this.$t('operator_execute_description')),
                                                h('div', this.$t('operator_audit_description')),
                                                h('div', this.$t('operator_qc_description')),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', this.$t('operator_schedule')),
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
                            ]),
                        ]);
                    }
                },
                // {
                //     title: this.$t('operator_end_time'),
                //     key: 'end_time',
                //     align: 'center',
                //     maxWidth: 120,
                //     render: (h, params) => {
                //         return h('Tooltip', {
                //             props: {
                //                 placement: 'top-start'
                //             },
                //             scopedSlots: {
                //                 content: () => {
                //                     return h('span', {
                //                     }, [
                //                         h('div', this.$t('operator_starting_time') + ': ' + util.timeFormatter(new Date(+params.row.start_time * 1000), 'MM-dd hh:mm')),
                //                         h('div', this.$t('operator_end_time') + ': ' + util.timeFormatter(new Date(+params.row.end_time * 1000), 'MM-dd hh:mm')),
                //                     ]);
                //                 }
                //             },
                //             style: {
                //                 display: 'inline'
                //             }
                //         }, [
                //             h('span', util.timeFormatter(new Date(+params.row.end_time * 1000), 'MM-dd hh:mm'))
                //         ]);
                //     },
                //     sortable: 'custom',
                // },
                {
                    title: this.$t('operator_handle'),
                    key: 'updated_at',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('Button', {
                                props: {
                                    type: 'primary',
                                    size: 'small',
                                },
                                style: {
                                    margin: '3px'
                                },
                                nativeOn: {
                                    click: () => {
                                        this.$router.push({
                                            name: 'perform-task',
                                            query: {
                                                project_id: para.row.project_id,
                                                task_id: para.row.id
                                            }
                                        });
                                    }
                                }
                            }, this.$t('project_acceptance_check')),
                            // h('Button', {
                            //     props: {
                            //         type: 'warning',
                            //         size: 'small',
                            //         disabled: true,
                            //     },
                            //     style: {
                            //         margin: '3px'
                            //     },
                            //     nativeOn: {
                            //         click: () => {

                            //         }
                            //     }
                            // }, '驳回'),
                            h('Button', {
                                props: {
                                    type: 'warning',
                                    size: 'small',
                                    disabled: para.row.refuse_revised * 1 < 1,
                                },
                                style: {
                                    margin: '3px'
                                },
                                nativeOn: {
                                    click: () => {
                                        this.$router.push({
                                            name: 'qc-work-list',
                                            params: {
                                                projectId: para.row.project_id,
                                                id: para.row.id,
                                                index: 'revised'
                                            }
                                        });
                                    }
                                }
                            }, para.row.refuse_revised * 1 < 1 ? this.$t('admin_rework_work') : this.$t('admin_rework_work') + '(' + para.row.refuse_revised + ')'),
                        ]);
                    }
                }
            ],
            tableData: []
        };
    },
    methods: {
        getData () {
            this.loading = true;
            $.ajax({
                url: api.task.tasks,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id,
                    limit: this.limit,
                    page: this.page,
                    sort: this.sort,
                    orderby: this.orderby
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.tableData = res.data.list;
                        this.count = +res.data.count;
                    }
                },
                error: (res, textStatus, responseText) => {
                    this.loading = false;
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
    },
    mounted () {
        this.getData();
    },
    components: {
        progressOp
    }
};
</script>
<style scoped>
    .subcontent{
        background: #efefef;
    }
    .main-con{
        background:#efefef;
        padding: 20px 25px 10px;
    }
    .blue-icon{
        display: inline-block;
        width: 3px;
        height: 18px;
        background: #2d8cf0;
        position: relative;
        top: 3px;
        margin-right:15px;
    }
</style>
<style>
    #project_index .item_title {
        margin-left: 8px;
        font-size: 15px;
        font-weight: 700;
    }
</style>
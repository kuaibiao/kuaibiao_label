<template>
    <div>
        <Row style="margin-bottom:10px">
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('admin_input_project_info')"
                    clearable
                    search
                    :enter-button="true"/>
            </div>
        </Row>
        <Row>
        <Table
            size="large"
            highlight-row
            ref="userTable"
            :columns="tableOption"
            :data="tableData"
            :loading="loading"
            stripe
            @on-sort-change="sortChange"
            @on-selection-change="batchSelectChange"
            show-header>
            <div slot="footer">
                <Button type="primary"  style="margin-left:10px" @click="batchSettingMember" :disabled="selItem.length == 0">
                    {{$t('admin_batch_set_member')}}
                </Button>
                <div style="float:right;margin-right:10px;font-size:12px">
                    <Page
                        :total="count"
                        :current="page"
                        :page-size="limit"
                        :page-size-opts="[10,15,20,25,30,50]"
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
        </Table>
        </Row>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
import taskOp from './components/task-op.vue';
import progressOp from '../my-task/components/progress.vue';
export default {
    name: "management-index",
    data () {
        return {
            team_name: "",
            loading: false,
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            categories: [],
            category_id: [],
            step_types: {},
            status: [],
            tableOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center'
                },
                {
                    title: this.$t('operator_project_id'),
                    key: 'project_id',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
                },
                {
                    title: this.$t('admin_task'),
                    key: 'name',
                    align: 'left',
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                placement: 'top-start',
                                transfer: true
                            },
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('admin_batch_id') + ': ' + params.row.batch.id),
                                        h('div', this.$t('admin_process_id') + ': ' + params.row.step_id),
                                        h('div', this.$t('admin_task_id') + ': ' + params.row.id),
                                        h('div', this.$t('admin_task_name') + ': ' + params.row.name),
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
                                        name: 'task-management-detail',
                                        params: {
                                            id: params.row.id,
                                            tab: 'stat-list',
                                            index: 'index'
                                        }
                                    }
                                }
                            }, params.row.name)
                        ]);
                    }
                },
                {
                    title: this.$t('admin_task_type'),
                    key: "category_id",
                    align: "center",
                    maxWidth: 100,
                    render: (h, para) => {
                        return h("span", this.categories[para.row.project.category_id]);
                    },
                    // filters: [],
                    // filterMultiple: false,
                    // filterMethod: () => true
                },
                {
                    title: this.$t('operator_step'),
                    key: "step_type",
                    align: "center",
                    render: (h, para) => {
                        return h("span", this.step_types[para.row.step.type]);
                    },
                },
                {
                    title: this.$t('operator_working_out'),
                    key: "amount",
                    align: "center",
                    maxWidth: 170,
                    render: (h, para) => {
                        if (para.row.stat) {
                            return h("span", (para.row.stat.work_count ? para.row.stat.work_count : '0') + ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0));
                        } else {
                            return h("span", '-- / ' + '-- / ' + (para.row.batch.amount ? para.row.batch.amount : 0));
                        }
                    },
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
                                            h('div', this.$t('admin_execute_description')),
                                            h('div', this.$t('admin_placeholder_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('admin_working_out')),
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
                    title: this.$t('operator_schedule'),
                    key: "statistics",
                    align: "left",
                    maxWidth: 230,
                    minWidth: 50,
                    render: (h, para) => {
                        // return h('div', [
                        //     h('div', [
                        //         h('div', (para.row.stat.work_count ? para.row.stat.work_count : '0') + ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0)),
                        //         h('Tooltip', {
                        //             props: {
                        //                 content: this.getProcess(para.row) + '%',
                        //                 placement: 'top'
                        //             },
                        //             'class': 'tool_tip',
                        //             style: {
                        //                 display: 'inline'
                        //             }
                        //         }, [
                        //             h('Progress', {
                        //                 props: {
                        //                     percent: this.getProcess(para.row),
                        //                     status: "active",
                        //                     strokeWidth: 3
                        //                 },
                        //             }, '')
                        //         ]),

                        //     ]),
                        // ]);
                        return h('div', [
                            h('div', [
                                h('Tooltip', {
                                    props: {
                                        content: (para.row.stat.work_count ? para.row.stat.work_count : '0') + ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0),
                                        placement: 'top-start',
                                        transfer: true
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
                                            h('div', this.$t('admin_schedule_execute_description')),
                                            h('div', this.$t('admin_schedule_audit_description')),
                                            h('div', this.$t('admin_schedule_qc_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('admin_schedule')),
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
                    title: this.$t('admin_performance'),
                    key: "statistics",
                    align: "center",
                    maxWidth: 130,
                    render: (h, para) => {
                        if (para.row.step.type === '1') {
                            return h("span", (para.row.stat.allowed_count ? para.row.stat.allowed_count : '0') + ' / ' + (para.row.stat.work_count ? para.row.stat.work_count : '0'));
                        } else if (para.row.step.type === '2') {
                            return h("span", (para.row.stat.allow_count ? para.row.stat.allow_count : '0') + ' / ' + (para.row.stat.work_count ? para.row.stat.work_count : '0'));
                        } else {
                            return h("span", this.getStatistics(para.row.stat));
                        }
                    },
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
                                            h('div', this.$t('admin_performance_execute_description')),
                                            h('div', this.$t('admin_performance_audit_description')),
                                            h('div', this.$t('admin_performance_qc_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('admin_performance')),
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
                    title: this.$t('admin_end_time'),
                    key: 'end_time',
                    align: 'center',
                    maxWidth: 120,
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                placement: 'top-start',
                                transfer: true
                            },
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('admin_start_time') + ': ' + util.timeFormatter(new Date(+params.row.start_time * 1000), 'MM-dd hh:mm')),
                                        h('div', this.$t('admin_end_time') + ': ' + util.timeFormatter(new Date(+params.row.end_time * 1000), 'MM-dd hh:mm')),
                                    ]);
                                }
                            },
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', util.timeFormatter(new Date(+params.row.end_time * 1000), 'MM-dd hh:mm'))
                        ]);
                    },
                    sortable: 'custom',
                },
                {
                    title: this.$t('operator_handle'),
                    align: "left",
                    maxWidth: 130,
                    render: (h, para) => {
                        return h('div', [
                            h(taskOp, {
                                props: {
                                    project_id: para.row.project_id,
                                    task_id: para.row.id,
                                    user_count: para.row.user_count,
                                    refused_revise: para.row.refused_revise,
                                    difficult_revise: para.row.difficult_revise,
                                    refuse_revised: para.row.refuse_revised,
                                }
                            })
                        ]);
                    },
                    renderHeader: (h, params) => {
                        return h('span', [
                            h('Poptip', {
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
                                            h('div', this.$t('admin_setting_members')),
                                            h('div', this.$t('admin_operate_reject_description')),
                                            h('div', this.$t('admin_operate_difficult_description')),
                                            h('div', this.$t('admin_operate_rework_description')),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', this.$t('admin_handle')),
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
                }
            ],
            tableData: [],
            selItem: []
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
        getProcess (row) {
            let work_count = row.stat !== "" ? row.stat.work_count : 0;
            let amount = row.stat.amount * 1;
            if (!amount || (amount === 0)) {
                return 0;
            }
            return +(work_count * 1 / amount * 100).toFixed(2);
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
            this.count = parseInt(data.count); // 整数
            // let categoryMap = [];
            // let categories = data.categories;
            this.categories = data.categories;
            // Object.keys(categories).forEach(v => {
            //     let category = {
            //         label: categories[v],
            //         value: v
            //     };
            //     categoryMap.push(category);
            // });
            // this.changeCatrgoryFilter(categoryMap);
        },
        // changeCatrgoryFilter (categoryMap, statusMap) {
        //     // 动态调整项目类型过滤器
        //     this.tableOption.find(function (col) {
        //         return col.key == "category_id";
        //     }).filters = categoryMap;
        // },
        // filterChange (filter) {
        //     let key = filter.key;
        //     this[key] = filter._filterChecked.slice();
        //     this.page = 1;
        //     this.getData();
        // },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        getStatistics (stat) {
            let work_count = stat.work_count ? stat.work_count : 0;
            let refused_count = stat.refused_count ? stat.refused_count : 0;
            let allowed_count = stat.allowed_count ? stat.allowed_count : 0;
            return allowed_count + '  /  ' + work_count;
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.task.list,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit
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
                        this.step_types = res.data.step_types;
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
        batchSelectChange (selection) {
            let selArr = [];
            selection.forEach(ele => {
                selArr.push(ele['id']);
            });
            this.selItem = selArr;
        },
        batchSettingMember () {
            this.$router.push({
                name: 'batch-set-task-user',
                params: {
                    task_id: this.selItem.toString()
                }
            });
            // if (this.selItem.length > 1) {
            //     this.$router.push({
            //         name: 'batch-set-task-user',
            //         params: {
            //             task_id: this.selItem.toString()
            //         }
            //     });
            // } else {
            //     this.$router.push({
            //         name: 'set-task-user',
            //         params: {
            //             task_id: this.selItem[0]
            //         }
            //     });
            // }
        }
    },
    components: {
        taskOp,
        progressOp
    }
};
</script>

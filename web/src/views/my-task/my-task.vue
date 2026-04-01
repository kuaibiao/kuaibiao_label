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
            <Table
                size="large"
                highlight-row
                ref="prejectTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                stripe
                show-header
                @on-filter-change="filterChange"
                @on-sort-change="sortChange"
            ></Table>
            <div style="margin: 10px;overflow: hidden">
                <div style="float: right;">
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
                        @on-page-size-change="changePageSize"
                    ></Page>
                </div>
            </div>
        </Row>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import Vue from 'vue';
import taskOp from './components/task-op.vue';
import progressOp from './components/progress.vue';
export default {
    name: 'my-task',
    data () {
        return {
            loading: false,
            count: 0,
            keyword: '',
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            categories: [],
            step_types: {},
            assign_types:[],
            category_id: '',
            tableOption: [
                {
                    title: this.$t('operator_project_id'),
                    key: 'project_id',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
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
                                transfer: true
                            },
                            scopedSlots: {
                                content: () => {
                                    return h('span', {
                                    }, [
                                        h('div', this.$t('operator_batch_id') + ': ' + params.row.batch.id),
                                        h('div', this.$t('operator_step_id') + ': ' + params.row.step_id),
                                        h('div', this.$t('operator_task_id') + ': ' + params.row.id),
                                        h('div', this.$t('operator_task_name') + ': ' + params.row.name),
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
                                            id: params.row.id,
                                            tab: 'work-list',
                                            index: 'all'
                                        },
                                    }
                                }
                            }, params.row.name)
                        ]);
                    }
                },
                {
                    title: this.$t('operator_task_type'),
                    key: 'category_id',
                    align: 'center',
                    maxWidth: 130,
                    render: (h, para) => {
                        return h('span', this.categories[para.row.project.category_id]);
                    },
                    filters: [],
                    filterMultiple: false,
                    filterMethod: () => true
                },
                {
                    title: this.$t('operator_step'),
                    key: 'task_step',
                    align: 'center',
                    maxWidth: 110,
                    render: (h, para) => {
                        return h('span', this.step_types[para.row.step.type]);
                    }
                },
                {
                  title: this.$t('admin_assign_type'),
                  key: 'assign_type',
                  maxWidth: 130,
                  align: 'center',
                  render: (h, para) => {
                    return h('span', this.assign_types[para.row.project.assign_type]);
                  },
                  // filterMultiple: false,
                  // filters: [],
                  // filterMethod: () => true
                },
                {
                    title: this.$t('operator_working_out'),
                    key: 'amount',
                    align: 'center',
                    maxWidth: 160,
                    render: (h, para) => {

                        var show_stat;
                        if (para.row.batch.amount <= 0){
                            show_stat = '解析中,请稍后刷新页面';
                        }
                        //有任务最大领取张数的情况;教学模式;
                        else if (para.row.max_times > 0){
                            show_stat = (para.row.statUser.work_count ? para.row.statUser.work_count : '0') + 
                            ' / ' + (para.row.max_times ? para.row.max_times : 0) ;
                        }else{
                            //争抢模式
                            show_stat = (para.row.stat.work_count ? para.row.stat.work_count : '0') + 
                            ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + 
                            ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0);
                        }
                        
                        return h("span", show_stat);
                    },
                    renderHeader: (h, params) => {
                        return h('div', [
                            h('div', [
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
                        var show_stat;
                        //有任务最大领取张数的情况;教学模式;
                        if (para.row.max_times > 0){
                            show_stat = (para.row.statUser.work_count ? para.row.statUser.work_count : '0') + 
                            ' / ' + (para.row.max_times ? para.row.max_times : 0) ;
                        }else{
                            //争抢模式
                            show_stat = (para.row.stat.work_count ? para.row.stat.work_count : '0') + 
                                ' / ' + (para.row.stat.amount ? para.row.stat.amount : 0) + 
                                ' / ' + (para.row.batch.amount ? para.row.batch.amount : 0);
                        }

                        return h('div', [
                            h('div', [
                                h('Tooltip', {
                                    props: {
                                        content: show_stat,
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
                        return h('div', [
                            h('div', [
                                h('Poptip', {
                                    props: {
                                        trigger: "hover",
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
                {
                    title: this.$t('operator_end_time'),
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
                                        h('div', this.$t('operator_starting_time') + ': ' + util.timeFormatter(new Date(+params.row.start_time * 1000), 'MM-dd hh:mm')),
                                        h('div', this.$t('operator_end_time') + ': ' + util.timeFormatter(new Date(+params.row.end_time * 1000), 'MM-dd hh:mm')),
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
                    align: 'left',
                    maxWidth: 200,
                    render: (h, para) => {
                        return h('div', [
                            h(taskOp, {
                                props: {
                                    project_id: para.row.project_id,
                                    task_id: para.row.id,
                                    step_type: para.row.step.type,
                                    file_type: para.row.project.category.file_type,
                                    refused_revise: para.row.refused_revise,
                                    difficult_revise: para.row.difficult_revise,
                                    refuse_revised: para.row.refuse_revised,
                                }
                            })
                        ]);
                    },
                    renderHeader: (h, params) => {
                        return h('div', [
                            h('div', [
                                h('Poptip', {
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
                                                h('div', this.$t('operator_description')),
                                                h('div', this.$t('operator_reject_work_description')),
                                                h('div', this.$t('operator_difficult_work_description')),
                                                h('div', this.$t('operator_rework_work_description')),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', this.$t('operator_handle')),
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
        }
    },
    mounted () {
        this.getData();
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
            let categoryMap = [];
            let categories = data.categories;
            this.categories = data.categories;
            let statuses = data.statuses;
            Object.keys(categories).forEach(v => {
                let category = {
                    label: categories[v],
                    value: v
                };
                categoryMap.push(category);
            });
            this.changeCatrgoryFilter(categoryMap);
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        changeCatrgoryFilter (categoryMap, taskStepMap) {
            // 动态调整项目类型过滤器
            let index = util.getKeyIndexFromTableOption(this.tableOption, 'category_id');
            let endIndex = util.getKeyIndexFromTableOption(this.tableOption, 'end_time');
            if (index < 0) {
                return;
            }
            let cateType = this.tableOption[index];
            cateType.filters = categoryMap;
            // hack 动态filter
            Vue.nextTick(() => {
                if (this.category_id !== '') {
                    this.$set(this.$refs.prejectTable.cloneColumns[index], '_filterChecked', [this.category_id]);
                    this.$set(this.$refs.prejectTable.cloneColumns[index], '_isFiltered', true);
                }
                this.$set(this.$refs.prejectTable.cloneColumns[endIndex], '_sortType', this.sort);
            });
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
        getData () {
            this.loading = true;
            $.ajax({
                url: api.task.tasks,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit,
                    category_id: this.category_id,
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
                        this.assign_types = res.data.assign_types || [];
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
    },
    components: {
        taskOp,
        progressOp
    }
};
</script>

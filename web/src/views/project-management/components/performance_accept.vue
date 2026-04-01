<template>
    <div>
        <Table
            border
            size="small"
            highlight-row
            stripe
            show-header
            :loading="loading"
            :columns="tableOption" 
            :data="tableData"
            @on-sort-change="sortChange">
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
import api from '@/api';
import util from '@/libs/util';
export default {
    props: {
        currentTask: {
            type: String
        },
        stepType: {
            type: String
        }
    },
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 5,
            users: '',
            work_count: '',
            work_time: '',
            invalid_data: '',
            tableOption: [
                {
                    title: this.$t('operator_batch_id'),
                    align: 'center',
                    sortable: 'custom',
                    render: (h,para) => {
                        return h('span', para.row.batch.id)
                    }
                },
                // {
                //     title: "所属团队",
                //     align: 'center'
                // },
                {
                    title: '作业人数',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', this.users);
                    }
                },
                {
                    title: '作业张数',
                    align: 'center',
                    render: (h, para) => {
                        return h('span',this.work_count);
                    }
                },
                {
                    title: this.$t('project_operation_average_time'),
                    key: 'averageTime',
                    sortable: 'custom',
                    align: 'center',
                    render: (h, para) => {
                        if ((para.row.work_time * 1 == 0) || (para.row.work_count * 1 == 0)) {
                            return h('span', '0');
                        }
                        let averageTime = Math.ceil((para.row.work_time * 1) / (para.row.work_count * 1));
                        return h('span', averageTime);
                    }
                },
                {
                    title:this.$t('project_number_rejections'),
                    key: 'refused_count',
                    sortable: 'custom',
                    align: 'center',
                },
                // {
                //     title: '合格率',
                //     key: 'accuracy',
                //     align: 'center',
                //     render: (h, para) => {
                //         return h('span', {}, para.row.accuracy + '%');
                //     }
                // },
                // {
                //     title: this.$t('operator_invalid_data'),
                //     align: 'center',
                //     render: (h, para) => {
                //         return h('span', this.invalid_data);
                //     }
                // },
                // {
                //     title: this.$t('operator_submitted_work'),
                //     align: 'center',
                //     key: "submit_count"
                // },
                // {
                //     title: this.$t('admin_status'),
                //     key: 'status',
                //     align: 'center',
                //     render: (h, para) => {
                //         return h('span', this.statuses[para.row.status]);
                //     }
                // },
                // {
                //     title: this.$t('operator_valid_data'),
                //     align: 'center',
                //     key: 'valid_data',
                //     children: []
                // },
                {
                    title: this.$t('operator_join_time'),
                    key: "start_time",
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'yy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
                {
                    title: this.$t('operator_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.updated_at * 1000),
                                'yy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
            ],
            tableData:[]
        }
    },
    watch: {
        currentTask () {
            if (this.currentTask && (this.stepType == '3')) {
                this.getStepData();
            }
        }
    },
    methods: {
        changePage (page) {
            this.page = page;
            this.getStepData(this.currentTask);
        },
        changePageSize (size) {
            this.limit = size;
            this.getStepData(this.currentTask);
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getStepData(this.currentTask);
        },
        changeKeyword () {
            this.getStepData(this.currentTask);
        },
        getStepData (taskId) {
            this.loading = true;
            $.ajax({
                url: api.stat.statTask,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    limit: this.limit,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    project_id: this.$route.params.id,
                    task_id: this.currentTask
                },
                success: (res) => {
                    let data = res.data;
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.count = res.data.count * 1;
                        this.users = res.data.total.users ? res.data.total.users : 0; // 作业人数
                        this.work_count = res.data.total.work_count ? res.data.total.work_count : 0; // 作业张数
                        this.work_time = res.data.total.work_time ? res.data.total.work_time : 0;   // 作业总时间
                        // this.invalid_data = res.data.total.inv0alid_data_effective_count; // 无效数据
                        this.tableData = res.data.list;
                        // $.each(res.data.template_label_types, (k, v) => {
                            
                        //     $.each(this.tableData, (j, t) => {
                                
                        //         t[v + '_count'] = (t.project_label_stat && t.project_label_stat[v]) ? t.project_label_stat[v].effective_count : 0
                        //     })
                        // })
                        // let index = util.getKeyIndexFromTableOption(this.tableOption, 'valid_data');
                        // this.tableOption[index].children = [];
                        // $.each(res.data.template_label_types, (k, v) => {
                        //     this.tableOption[index].children.push({
                        //         title: res.data.label_types[v],
                        //         key: v + '_count',
                        //         align: 'center',
                        //         render: (h,para) => {
                        //             return h('Tooltip', {
                        //                 props: {
                        //                     placement: 'top',
                        //                     maxWidth: 250,
                        //                     transfer: true,
                        //                 },
                        //                 'class': 'tool_tip',
                        //                 style: {
                        //                     display: 'inline'
                        //                 },
                        //                 scopedSlots: {
                        //                     content: () => {
                        //                         return h('div', {
                        //                         }, [
                        //                             h('p', res.data.label_types[v]+ "：" + para.row[v + '_count']),
                        //                             h('p', this.$t('operator_delete_count') + '：' + ((para.row.project_label_stat && para.row.project_label_stat[v]) ? para.row.project_label_stat[v].delete_count : "0")),
                        //                         ]);
                        //                     }
                        //                 }
                        //             }, [
                        //                 h('span', para.row[v + '_count'])
                        //             ]);
                        //         }
                        //     })
                        // }) 
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
    mounted () {
        if (this.currentTask && (this.stepType == '3')) {
                this.getStepData();
            }
    }
}
</script>

<style scoped>

</style>
<template>
    <div class="subcontent" id="project_index">
        <div class="main-con">
            <Card>
                <div>
                    <div class="model-title">
                        <span></span>
                        <h2>{{$t('operator_query_condition')}}</h2>
                    </div>
                    <Form :label-width="100" inline>
                        <FormItem :label="this.$t('operator_batch_id')" style="font-size:14px;">
                            <i-input v-model="batch_id" placeholder="请输入批次ID"></i-input>
                        </FormItem>
                        <FormItem :label="this.$t('operator_job_id')" style="font-size:14px;">
                            <i-input v-model="work_id" :placeholder="this.$t('operator_input_join_id')"></i-input>
                        </FormItem>
                        <!-- <FormItem :label="this.$t('operator_status')">
                            <Select v-model="status" style="width:90px;">
                                <Option value="all">全部</Option>
                                <Option v-for="(item,index) in statuses" :key="index" :value="index">{{item}}</Option>
                            </Select>
                        </FormItem> -->
                        <FormItem :label="this.$t('operator_join_time')">
                            <DatePicker v-model="workTime" type="daterange" split-panels :placeholder="this.$t('operator_start_time_and_end_time')"></DatePicker>
                        </FormItem>
                        <FormItem :label="this.$t('operator_update_time')">
                            <DatePicker v-model="updataTime" type="daterange" split-panels :placeholder="this.$t('operator_start_time_and_end_time')"></DatePicker>
                        </FormItem>
                        <FormItem>

                            <div style="float: right;">
                                <Button @click="queryInfo" type="primary"  style="margin-right:20px;">{{$t('operator_query')}}</Button>
                                <Button @click="reset" type="default" >{{$t('operator_reset')}}</Button>
                            </div>
                        </FormItem>
                    </Form>
                
                    
                </div>
                <div style="margin-top:10px">
                    <div class="model-title padding-bottom-10">
                        <span></span>
                        <h2>{{$t('project_data_list')}}</h2>
                    </div>
                    <Table
                        border
                        size="large"
                        highlight-row
                        ref="dataTable"
                        :columns="tableOption"
                        :data="tableData"
                        stripe
                        show-header
                        :loading="loading"
                        @on-filter-change = "filterChange"
                        @on-sort-change = "sortChange"
                    ></Table>
                    <div style="margin: 10px;overflow: hidden;padding: 1px;">
                        <div style="float: right;">
                            <Page
                                :total="count"
                                :current="page"
                                :page-size ="limit"
                                :page-size-opts="[10,15,20,25,50]"
                                show-total
                                show-elevator
                                show-sizer
                                placement = "top"
                                @on-change="changePage"
                                transfer
                                @on-page-size-change = "changePageSize"
                                ></Page>
                        </div>
                    </div>
                </div>
            </Card>
        </div>
        <Modal
            :width="800"
            v-model="recordModal"
            :title="$t('project_operation_records')"
            @on-ok="recordModal = false"
            @on-cancel="recordModal = false">
            <p slot="header" style="text-align:center">
                <span>{{$t('project_operation_records')}}</span>
                <Tooltip trigger="hover" placement="right">
                    <div slot="content" style="font-weight: normal">{{$t('operator_refreshing_tip')}}<br>1. {{$t('operator_refreshing_tip_desc1')}}<br>2. {{$t('operator_refreshing_tip_desc2')}}</div>
                    <Icon type="ios-help-circle" />
                </Tooltip>
            </p>
            <work-record :recordData="recordData" :types="types" :stepTypes="step_types" :workStatus="workStatus" :modelLoading="modelLoading"></work-record>
        </Modal>
        <Modal v-model="viewModal"
               fullscreen
               :mask-closable="false"
               footer-hide
        >
            <component
                    :is="viewResultType[currentViewType]"
                    :projectId="projectId"
                    :dataId="dataId"
                    :result="workdata"
                    :dataInfo="dataInfo"
                    :workUser="workUser"
                    :categoryView="task_view"
                    :showResultList="task_view === 'image_label'"
                    v-if="viewModal"
            >
            </component>
        </Modal>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import Cookies from 'js-cookie';
import workRecord from '../components/work-record';
import resultItemAnnotation from '@/views/task-perform/components/text-annotation-result.vue';
import {
    resultComponent,
    viewResultType,
} from '@/common/components/task-result-view/index';
export default {
    props: {
        project: {
            type: Object
        },
    },
    data () {
        return {
            loading: false,
            modelLoading: false,
            recordModal: false,
            viewModal: false,
            recordData: [],
            step_types: {},
            workStatus: {},
            types: {},
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            batch_id: '',
            work_id: '',
            workTime: [],
            updataTime: [],
            // status: '',
            orderby: '',
            sort: '',
            viewResultType: viewResultType,
            currentViewType: '',
            dataInfo: {},
            projectId: '',
            dataId: '',
            workdata: {},
            workUser: {},
            statuses: {},
            tableOption: [
                // {
                //     type: 'selection',
                //     width: 60,
                //     align: 'center',
                // },
                // {
                //     title: this.$t('project_sort'),
                //     key: 'sort',
                //     width: 100,
                //     sortable: 'custom',
                //     align: 'center'
                // },
                {
                    title: this.$t('project_batch_id'),
                    key: 'batch',
                    width: 100,
                    align: 'center',
                    render: (h, para) => {
                        return h('span', para.row.batch.id);
                    }
                },
                // {
                //     title: "上传包名",
                //     key: 'batch_id',
                //     ellipsis: true,
                //     render: (h, para) => {
                //         return h('span', para.row.batch.name);
                //     },
                //     filterMultiple: false,
                //     filters: [],
                //     filterMethod: () => true
                // },
                {
                    title: this.$t('project_work_id'),
                    key: 'id',
                    width: 120,
                    sortable: 'custom',
                    align: 'center'
                },
                {
                    title: this.$t('project_data_name'),
                    key: 'name',
                    minWidth: 300,
                    render: (h, para) => {
                        return h('Tooltip', {
                            props: {
                                content: para.row.name,
                                placement: 'top-start',
                                width: 300,
                                transfer: true,
                            },
                            'class': 'tool_tip',
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', para.row.name)
                        ]);
                    }
                },
                // {
                //     title: this.$t('operator_status'),
                //     align: 'center',
                //     width: 120,
                //     render: (h,para) => {
                //         return h(
                //             'span', this.statuses[para.row.status]
                //         )
                //     }
                // },
                {
                    title: this.$t('operator_invalid_data'),
                    key: 'invalid_data_effective_count',
                    align: 'center',
                    width: 100
                },
                {
                    title: this.$t('operator_valid_data'),
                    align: 'center',
                    key: 'valid_data',
                    children: []
                },
                {
                    title: this.$t('operator_join_time'),
                    key: 'created_at',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(para.row.created_at * 1000),
                                'yy-MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('project_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(para.row.updated_at * 1000),
                                'yy-MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('project_result'),
                    align: 'center',
                    width: 100,
                    render: (h, para) => {
                        let fileType = this.viewResultType[this.task_view];
                        if (!fileType) {
                            return '';
                        } else {
                            return h('div', [
                                h(
                                    'Button',
                                    {
                                        props: {
                                            size: 'small'
                                        },
                                        on: {
                                            click: () => {
                                                this.dataId = para.row.id;
                                                this.projectId = para.row.project_id;
                                                this.dataInfo = {id: para.row.id, name: para.row.name};
                                                this.workdata = para.row.dataResult ? JSON.parse(para.row.dataResult.result || para.row.dataResult.ai_result || '{}') : {};
                                                this.workUser = (para.row.work && para.row.work.user) ? para.row.work.user : {};                                       
                                                this.viewModal = true;
                                                this.currentViewType = this.task_view;
                                            }
                                        }
                                    },
                                    this.$t('operator_job_results')
                                ), ]
                            );
                        }
                    }
                },

                {
                    title: this.$t('project_check'),
                    align: 'center',
                    width: 100,
                    render: (h, para) => {
                        return h('div', [
                            h(
                                'Button',
                                {
                                    props: {
                                        size: 'small'
                                    },
                                    on: {
                                        click: () => {
                                            this.getRecord(para.row.id, para.row.project.id);
                                        }
                                    }
                                },
                                this.$t('project_operation_records')
                            ),
                        ]);
                    }
                },
            ],
            tableData: [],
        };
    },
    computed: {
        image_label () {
            return (this.project && this.project.category) ? this.project.category.file_type : '0';
        },
        task_view () {
            return (this.project && this.project.category) ? this.project.category.view : '';
        }
    },
    mounted () {
        this.getData();
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        }
    },
    methods: {
        queryInfo () {
            this.page = 1;
            this.getData();
        },
        reset () {
            this.batch_id = '';
            this.work_id = '';
            this.workTime = [];
            this.updataTime = [];
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
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = Number(data.count);
            let batchMap = [];
            Object.keys(data.batches).forEach(v => {
                let batch = {
                    label: data.batches[v],
                    value: v
                };
                batchMap.push(batch);
            });
            this.changeCatrgoryFilter(batchMap);
            $.each(data.template_label_types, (k, v) => {
                $.each(this.tableData, (j, t) => {
                    t[v + '_count'] = (t.stat && t.stat[v]) ? t.stat[v].effective_count : 0
                })
            })
            let index = util.getKeyIndexFromTableOption(this.tableOption, 'valid_data');
            this.tableOption[index].children = [];
            $.each(data.template_label_types, (k, v) => {
                this.tableOption[index].children.push({
                    title: data.label_types[v],
                    key: v + '_count',
                    align: 'center',
                    width: 100,
                    render: (h,para) => {
                        return h('Tooltip', {
                            props: {
                                placement: 'top',
                                maxWidth: 250,
                                transfer: true,
                            },
                            'class': 'tool_tip',
                            style: {
                                display: 'inline'
                            },
                            scopedSlots: {
                                content: () => {
                                    return h('div', {
                                    }, [
                                        h('p', data.label_types[v]+ "：" + para.row[v + '_count']),
                                        h('p', this.$t('operator_delete_count') + '：' + ((para.row.stat && para.row.stat[v]) ? para.row.stat[v].delete_count : "0")),
                                    ]);
                                }
                            }
                        }, [
                            h('span', para.row[v + '_count'])
                        ]);
                    }
                })
            }) 
        },
        changeCatrgoryFilter (batchMap) {
            // 动态调整租户类型过滤器
            let batchIndex = util.getKeyIndexFromTableOption(this.tableOption, 'batch_id');
            let updateIndex = util.getKeyIndexFromTableOption(this.tableOption, 'updated_at');
            if (batchIndex < 0 || updateIndex < 0) {
                return;
            }
            let batch = this.tableOption[batchIndex];
            batch.filters = batchMap;
            // hack 动态filter
            this.$nextTick(() => {
                if (this.batch_id) {
                    this.$set(this.$refs.dataTable.cloneColumns[batchIndex], '_filterChecked', [this.batch_id]);
                    this.$set(this.$refs.dataTable.cloneColumns[batchIndex], '_isFiltered', true);
                }
                if (this.orderby === 'updated_at') {
                    this.$set(this.$refs.dataTable.cloneColumns[updateIndex], '_sortType', this.sort);
                }
            });
        },
        changeKeyword () {
            this.getData();
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        sortChange ({column, key, order}) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.data.list,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    page: this.page,
                    limit: this.limit,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    batch_id: this.batch_id,
                    data_id: this.work_id,
                    project_id: this.$route.params.id,
                    create_start_time: this.workTime[0] ? util.timeFormatter(this.workTime[0], 'yyyy-MM-dd hh:mm:ss') : "",
                    create_end_time: this.workTime[1] ? util.timeFormatter(this.workTime[1], 'yyyy-MM-dd hh:mm:ss') : "",
                    update_start_time: this.updataTime[0] ? util.timeFormatter(this.updataTime[0], 'yyyy-MM-dd hh:mm:ss') : "",
                    update_end_time: this.updataTime[1] ? util.timeFormatter(this.updataTime[1], 'yyyy-MM-dd hh:mm:ss') : "",
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.statuses = res.data.statuses;
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
        getRecord (dataId, projectId) {
            this.modelLoading = true;
            $.ajax({
                url: api.work.workRecords,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    project_id: projectId,
                    data_id: dataId,
                },
                success: res => {
                    this.modelLoading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.statuses = res.data.statuses;
                        this.recordData = res.data.list;
                        this.step_types = res.data.step_types;
                        this.types = res.data.types;
                        this.workStatus = res.data.work_status;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.modelLoading = false;
                    });
                }
            });
            this.recordModal = true;
        },
    },
    components: {
        ...resultComponent,
        workRecord,
        resultItemAnnotation,
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
    .model-title {
        display: flex;
    }
    .model-title span{
    display: inline-block;
    width: 5px;
    height: 25px;
    background: #2d8cf0;
    margin-right: 15px;
    }
    .model-title h2 {
        margin-bottom: 10px;
        font-size: 18px;
    }
    .padding-bottom-10 {
        padding-bottom: 10px;
    }
</style>
<style>
    #project_index .item_title {
        margin-left: 8px;
        font-size: 15px;
        font-weight: 700;
    }
</style>
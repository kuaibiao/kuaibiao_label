<style lang="less">
    @import "./execute.less";
</style>
<template>
    <div>
        <Row>
            <i-col span="12" class="padding-bottom-30">
                <h2>{{$t('operator_instrument_panel')}}</h2>
            </i-col>
            <i-col span="2" offset="10" class="padding-bottom-30" >
                <Button type="primary" @click="showDetail('all')">{{$t('operator_view_details')}}</Button>
            </i-col>
        </Row>
        <Row>
            <i-col span="12" class="padding-bottom-30" v-for="(item,index) in carInfo" :key="index" >
                <infoCard :item="item" v-on:show-detail="showDetail"></infoCard>
            </i-col>
        </Row>
        <Row>
            <i-col span="24">
                <Card>
                    <p slot="title" style="color:#999;font-size:15px;">
                        {{this.$t('operator_label_amount')}}
                    </p>
                    <Row v-if="Object.keys(labelTypeList).length > 0">
                        <i-col span="3" class="card-info" v-for="(item,value,index) in labelTypeList" :key="index">
                            <Tooltip placement="top-start">
                                <div slot="content">
                                    <p>{{item.title}}</p>
                                    <p class="toolTip">{{$t('operator_wait_audit')}}：{{item.value ? item.value.toaudit_count : 0}}</p>
                                    <p class="toolTip">{{$t('admin_passed')}}：{{item.value ? item.value.allowed_count : 0}}</p>
                                    <p>{{$t('operator_delete_record')}}</p>
                                    <p class="toolTip">{{$t('operator_delete_frame')}}：{{item.value ? item.value.delete_count : 0}}</p>
                                </div>
                                <p style="color:#999;">{{item.title}}</p>
                                <p style="font-size:30px;" class="padding-top-13">{{item.value ? (item.value.effective_count * 1) : 0}}</p>
                            </Tooltip>
                        </i-col>
                    </Row>
                    <Row v-if="Object.keys(labelTypeList).length == 0">
                        <p style="text-align:center">{{$t('common_no_data')}}</p>
                    </Row>
                </Card>
            </i-col>
        </Row>
        <Modal
            fullscreen
            v-model="modelDetail"
            :footer-hide="true"
            width="1200"
            >
            <div class="model-title">
                <span></span>
                <h2>{{$t('operator_query_condition')}}</h2>
            </div>
            <Form :label-width="100" inline>
                 <FormItem :label="this.$t('operator_job_id')" style="font-size:14px;">
                    <i-input v-model="keyword" :placeholder="this.$t('operator_input_join_id')"></i-input>
                </FormItem>
                <FormItem :label="this.$t('operator_status')">
                    <Select v-model="status" multiple>
                        <Option v-for="(item,index) in selData" :key="index" :value="item.index">{{item.value}}</Option>
                    </Select>
                </FormItem>
                 <FormItem :label="this.$t('operator_join_time')">
                    <DatePicker v-model="workTime" type="daterange" split-panels :placeholder="this.$t('operator_start_time_and_end_time')"></DatePicker>
                </FormItem>
                <FormItem :label="this.$t('operator_update_time')">
                    <DatePicker v-model="updataTime" type="daterange" split-panels :placeholder="this.$t('operator_start_time_and_end_time')"></DatePicker>
                </FormItem>
            </Form>
            <Row>
                <div style="margin:0px 0px 20px 15px;">
                    <Button type="primary" @click="queryInfo" style="margin-right:20px;">{{$t('operator_query')}}</Button>
                    <Button type="default" @click="reset">{{$t('operator_reset')}}</Button>
                </div>
            </Row>
            <div class="model-title padding-bottom-20">
                <span></span>
                <h2>{{$t('project_data_list')}}</h2>
            </div>
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
        </Modal>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
import infoCard from "./info-card.vue";
export default {
    name: 'execute',
    props:{
        sumData: {
            type: Object
        },
        labelTypeList: {
            type: Object
        },
        projectId: {
            type: String
        },
        stepid: {
            type: String
        }
    },
    watch: {
        sumData () {
            this.carInfo =  [
                {
                   title: this.$t('operator_processed_work_count'),
                   dasc: [
                        this.$t('operator_totle_submitted_work') + '：' + this.$t('operator_totle_submitted_work_dasc'),
                        this.$t('operator_totle_valid_work') + '：' + this.$t('operator_totle_valid_work_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_totle_valid_work'),
                           num: this.sumData.work_count ? this.getTransform(this.sumData.work_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "3"
                       },
                       {
                           name: this.$t('operator_totle_submitted_work'),
                           num: this.sumData.submit_count ? this.getTransform(this.sumData.submit_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "3"
                       }
                   ]
                },
                {
                    title: this.$t('operator_work_status_count'),
                    dasc: [
                        this.$t('operator_totle_pass_work') + '：' + this.$t('operator_totle_passed_work_dasc'),
                        this.$t('operator_totle_rejected_work') + '：' + this.$t('operator_totle_rejected_work_dasc'),
                        this.$t('operator_totle_reseted_work') + '：' + this.$t('operator_totle_reseted_work_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_totle_pass_work'),
                           num: this.sumData.allowed_count ? this.getTransform(this.sumData.allowed_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "4"
                       },
                       {
                           name: this.$t('operator_totle_rejected_work'),
                           num: this.sumData.refused_count ? this.getTransform(this.sumData.refused_count) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_totle_reseted_work'),
                           num: this.sumData.reseted_count ? this.getTransform(this.sumData.reseted_count) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       }
                    ]
                },
                {
                    title: this.$t('operator_label_count'),
                    dasc: [
                        this.$t('tool_percent_pass') + '：' + this.$t('operator_execute_percent_pass_dasc'),
                        this.$t('operator_work_time') + '：' + this.$t('operator_execute_work_time_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('tool_percent_pass'),
                           num: this.sumData.pass_rate ?  ((this.sumData.pass_rate * 1).toFixed(2) + "%") : "0.00%",
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_execute_work_total_time'),
                           num: this.sumData.work_time ? this.getTransform(this.sumData.work_time) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       }
                    ]
                },
                {
                    title: this.$t('operator_work_count'),
                    dasc: [
                        this.$t('operator_invalid_data_amount') + '：' + this.$t('operator_invalid_data_amount_dasc'),
                        this.$t('operator_valid_data_amount') + '：' + this.$t('operator_valid_data_amount_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_invalid_data_amount'),
                           num: Object.keys(this.sumData).length ? this.getTransform(this.sumData.invalid_data_effective_count * 1) : 0,
                           skip: 0,
                           hint: 1,
                           poptip:[
                               this.$t('operator_invalid_data_amount'),
                               this.$t('operator_submitted') + "：" + (Object.keys(this.sumData).length ? this.sumData.invalid_data_toaudit_count : 0),
                               this.$t('admin_passed') + "：" + (Object.keys(this.sumData).length ? this.sumData.invalid_data_allowed_count : 0)
                           ],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_valid_data_amount'),
                           num: Object.keys(this.sumData).length ? this.getTransform(this.sumData.valid_data_effective_count * 1): 0,
                           skip: 0,
                           hint: 1,
                           poptip:[
                               this.$t('operator_valid_data_amount'),
                               this.$t('operator_submitted') +"：" + (Object.keys(this.sumData).length ? this.sumData.valid_data_toaudit_count : 0),
                               this.$t('admin_passed') + "：" + (Object.keys(this.sumData).length ? this.sumData.valid_data_allowed_count : 0)
                           ],
                           status: "-1"
                       }
                    ]
                }
            ]
        }
    },
    data () {
        return {
            carInfo: [
                {
                   title: this.$t('operator_processed_work_count'),
                   dasc: [
                        this.$t('operator_totle_submitted_work') + '：' + this.$t('operator_totle_submitted_work_dasc'),
                        this.$t('operator_totle_valid_work') + '：' + this.$t('operator_totle_valid_work_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_totle_valid_work'),
                           num: this.sumData.work_count ? this.getTransform(this.sumData.work_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "3"
                       },
                       {
                           name: this.$t('operator_totle_submitted_work'),
                           num: this.sumData.submit_count ? this.getTransform(this.sumData.submit_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "3"
                       }
                   ]
                },
                {
                    title: this.$t('operator_work_status_count'),
                    dasc: [
                        this.$t('operator_totle_pass_work') + '：' + this.$t('operator_totle_passed_work_dasc'),
                        this.$t('operator_totle_rejected_work') + '：' + this.$t('operator_totle_rejected_work_dasc'),
                        this.$t('operator_totle_reseted_work') + '：' + this.$t('operator_totle_reseted_work_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_totle_pass_work'),
                           num: this.sumData.allowed_count ? this.getTransform(this.sumData.allowed_count) : 0,
                           skip: 1,
                           hint: 0,
                           poptip:[],
                           status: "4"
                       },
                       {
                           name: this.$t('operator_totle_rejected_work'),
                           num: this.sumData.refused_count ? this.getTransform(this.sumData.refused_count) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_totle_reseted_work'),
                           num: this.sumData.reseted_count ? this.getTransform(this.sumData.reseted_count) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       }
                    ]
                },
                {
                    title: this.$t('operator_label_count'),
                    dasc: [
                        this.$t('tool_percent_pass') + '：' + this.$t("operator_execute_percent_pass_dasc"),
                        this.$t('operator_work_time') + '：' + this.$t('operator_execute_work_time_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('tool_percent_pass'),
                           num: this.sumData.pass_rate ?  ((this.sumData.pass_rate * 1).toFixed(2) + "%") : "0%",
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_execute_work_total_time'),
                           num: this.sumData.work_time ? this.getTransform(this.sumData.work_time) : 0,
                           skip: 0,
                           hint: 0,
                           poptip:[],
                           status: "-1"
                       }
                    ]
                },
                {
                    title: this.$t('operator_work_count'),
                    dasc: [
                        this.$t('operator_invalid_data_amount') + '：' + this.$t('operator_invalid_data_amount_dasc'),
                        this.$t('operator_valid_data_amount') + '：' + this.$t('operator_valid_data_amount_dasc')
                    ],
                    info: [
                       {
                           name: this.$t('operator_invalid_data_amount'),
                           num: Object.keys(this.sumData).length ? this.getTransform(this.sumData.invalid_data_effective_count * 1) : 0,
                           skip: 0,
                           hint: 1,
                           poptip:[
                               this.$t('operator_invalid_data_amount'),
                               this.$t('operator_submitted') + "：" + (Object.keys(this.sumData).length ? this.sumData.invalid_data_toaudit_count : 0),
                               this.$t('admin_passed') + "：" + (Object.keys(this.sumData).length ? this.sumData.invalid_data_allowed_count : 0)
                           ],
                           status: "-1"
                       },
                       {
                           name: this.$t('operator_valid_data_amount'),
                           num: Object.keys(this.sumData).length ? this.getTransform(this.sumData.valid_data_effective_count * 1) : 0,
                           skip: 0,
                           hint: 1,
                           poptip:[
                               this.$t('operator_valid_data_amount'),
                               this.$t('operator_submitted') + "：" + (Object.keys(this.sumData).length ? this.sumData.valid_data_toaudit_count : 0),
                               this.$t('admin_passed') + "：" + (Object.keys(this.sumData).length ? this.sumData.valid_data_allowed_count : 0)
                           ],
                           status: "-1"
                       }
                    ]
                }
            ],
            modelDetail: false,
            selData: [],
            statuses: [],
            tableData: [],
            status: [],
            workTime: [],
            updataTime: [],
            loading: false,
            user_id: this.$store.state.user.userInfo.id,
            count: 0,
            page: 1,
            limit: 10,
            keyword: '',
            orderby: '',
            sort: '',
            tableOption: [
                {
                    title: this.$t('operator_job_id'),
                    align: 'center',
                    key: "id",
                    sortable: 'custom'
                },
                {
                    title: this.$t('operator_operation_time'),
                    key: "time",
                    align: 'center',
                    render: (h, para) => {
                        return h('span', para.row.end_time - para.row.start_time + '(s)');
                    },
                },
                {
                    title: this.$t('operator_submitted_work'),
                    align: 'center',
                    key: "submit_count"
                },
                {
                    title: this.$t('admin_status'),
                    key: 'status',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', this.statuses[para.row.status]);
                    }
                },
                {
                    title: this.$t('operator_invalid_data'),
                    align: 'center',
                    key: "invalid_data_count"
                },
                {
                    title: this.$t('operator_valid_data'),
                    align: 'center',
                    key: 'valid_data',
                    children: []
                },
                {
                    title: this.$t('operator_join_time'),
                    key: "start_time",
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.start_time * 1000),
                                'MM-dd hh:mm:ss'
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
                                'MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
            ]
        }
        
    },
    methods: {
        showDetail (status) {
            if (status == 'all') {
                this.status = '';
            } else {
                this.status = [status];
            }
            this.modelDetail = true;
            this.getDetails();
        },
        queryInfo () {
            this.page = 1;
            this.getData();
        },
        reset () {
            this.keyword = "";
            this.status = [];
            this.workTime = [];
            this.updataTime = [];
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
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        getDetails () {
            this.getSelData();
        },
        getSelData () {
            $.ajax({
                url: api.stat.workForm,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    step_id: this.stepid
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        let arrStatus = [];
                        $.each(res.data, (k, v) => {
                            arrStatus.push({
                                index: k,
                                value: v
                            })
                        })
                        this.selData = arrStatus;
                        this.getData();
                    }
                }
            })
        },
        getData () {
            this.modelDetail = true;
            this.loading = true;
            $.ajax({
                url: api.stat.work,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.user_id,
                    task_id: this.$route.params.id,
                    project_id: this.projectId,
                    page: this.page,
                    limit: this.limit,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    status: this.status.toString(),
                    work_start_time: this.workTime[0] ? util.timeFormatter(this.workTime[0], 'yyyy-MM-dd hh:mm:ss') : "",
                    work_end_time: this.workTime[1] ? util.timeFormatter(this.workTime[1], 'yyyy-MM-dd hh:mm:ss') : "",
                    updata_start_time: this.updataTime[0] ? util.timeFormatter(this.updataTime[0], 'yyyy-MM-dd hh:mm:ss') : "",
                    updata_end_time: this.updataTime[1] ? util.timeFormatter(this.updataTime[1], 'yyyy-MM-dd hh:mm:ss') : "",
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
                        this.count = res.data.count * 1;
                        this.statuses = res.data.produce_statuses;
                        this.tableData = res.data.list;
                        $.each(res.data.template_label_types, (k, v) => {
                            $.each(this.tableData, (j, t) => {
                                t[v + '_count'] = t.label_stat[v] ? t.label_stat[v].effective_count : 0
                            })
                        })
                        let index = util.getKeyIndexFromTableOption(this.tableOption, 'valid_data');
                        this.tableOption[index].children = [];
                        $.each(res.data.template_label_types, (k, v) => {
                            this.tableOption[index].children.push({
                                title: res.data.label_types[v],
                                key: v + '_count',
                                align: 'center',
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
                                                    h('p', res.data.label_types[v]+ "：" + para.row[v + '_count']),
                                                    h('p', this.$t('operator_delete_count') + '：' + (para.row.label_stat[v] ? para.row.label_stat[v].delete_count : "0")),
                                                ]);
                                            }
                                        }
                                    }, [
                                        h('span', para.row[v + '_count'])
                                    ]);
                                }
                            })
                        })     
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            })
        }
    },
    computed: {
        getTransform () {
            return function (num) {
                if (!num) {
                    return "0";
                }
                return num.toString().replace(/\B(?=(\d{3})+$)/g,","); // 数字展示用千分符隔开
            }
        }
    },
    components: {
        infoCard
    }
};
</script>

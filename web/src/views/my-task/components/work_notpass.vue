<template>
    <div class="subcontent">
        <Row>
                <div class="search_input" style="float:right">
                    <Input v-model="keyword"
                           @on-enter="changeKeyword"
                           @on-search="changeKeyword"
                           :placeholder="$t('operator_input_text')"
                           clearable
                           search
                           :enter-button="true"/>
                </div>
                <div style="float:right; margin-right:10px;">
                    <span style="margin-right:10px;font-size:14px;">{{$t('operator_status')}}</span>
                    <Select v-model="selValue" style="width:auto" @on-change="getData">
                        <Option value="all">{{$t('home_all')}}</Option>
                        <Option v-for="(item,i) in invalid_statuses" :value="item.index" :key="i">{{item.value}}</Option>
                    </Select>
                </div>
        </Row>
        <div style="margin-top:10px">
            <Table
                size="large"
                highlight-row ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                @on-sort-change="sortChange"
                @on-filter-change="filterChange"
                stripe
                show-header>
            </Table>
        </div>
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
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
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
        <Modal
            :width="800"
            v-model="recordModal"
            :title="$t('operator_record')"
            @on-ok="recordModal = false"
            @on-cancel="recordModal = false">
            <p slot="header" style="text-align:center">
                <span>{{$t('operator_record')}}</span>
                <Tooltip :transfer="true" trigger="hover" placement="right">
                    <div slot="content" style="font-weight: normal">{{$t('operator_refreshing_tip')}}<br>1. {{$t('operator_refreshing_tip_desc1')}}<br>2. {{$t('operator_refreshing_tip_desc2')}}</div>
                    <Icon type="ios-help-circle" />
                </Tooltip>
            </p>
            <work-record :recordData="recordData" :types="types" :stepTypes="step_types" :modelLoading="modelLoading" :workStatus="workStatuses"></work-record>
        </Modal>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
import Vue from 'vue';
import Cookies from 'js-cookie';
import resultItemAnnotation from '../../task-perform/components/text-annotation-result.vue';
import workRecord from '../../../common/components/task-result-view/work-record';
import {
    resultComponent,
    viewResultType,
} from '../../../common/components/task-result-view/index';
import ErrorTaskReasonShow from '../../../common/components/error-task-reason-show.vue';
export default {
    props: {
        image_label: {
            type: String
        },
        step_type: {
            type: String
        },
        task_view: {
            type: String
        },
        templateInfo: {
            type: Array
        }
    },
    data () {
        return {
            lodingText: 'loading...',
            recordModal: false,
            types: {},
            statuses: {},
            step_types: {},
            modelLoading: false,
            workStatuses: {},
            viewModal: false,
            dataInfo: {},
            dataId: '',
            projectId: '',
            viewResultType: viewResultType,
            currentViewType: '',
            loading: false,
            workdata: {},
            workUser: {},
            keyword: '',
            count: 0,
            page: 1,
            limit: +(localStorage.getItem('workListLimit') || 10),
            orderby: 'updated_at',
            sort: 'desc',
            type: [],
            notpassTypes: [],
            invalid_statuses: [],
            selValue: "all",
            tableOption: [
                {
                    title: this.$t('operator_job_id'),
                    key: 'data_id',
                    align: 'center',
                    width: 120,
                    sortable: 'custom',
                },
                // {
                //     title: this.$t('operator_job_name'),
                //     key: 'data_name',
                //     align: 'left',
                //     ellipsis: true,
                //     render: (h, para) => {
                //         return h('Tooltip', {
                //             props: {
                //                 content: para.row.data.name,
                //                 placement: 'top-start',
                //                 transfer: true
                //             },
                //             'class': 'tool_tip',
                //             style: {
                //                 display: 'inline'
                //             }
                //         }, [
                //             h('span', para.row.data.name)
                //         ]);
                //     }
                // },
                {
                    title: this.$t('operator_status'),
                    key: 'status',
                    align: 'center',
                    // maxWidth: 110,
                    render: (h, para) => {
                        return h('span', this.workStatuses[para.row.status]);
                    }
                },
                {
                    title: this.$t('operator_audit_time'),
                    key: 'start_time',
                    align: 'center',
                    sortable: 'custom',
                    // maxWidth: 160,
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.start_time * 1000),
                                'yy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
                {
                    title: this.$t('operator_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    sortable: 'custom',
                    // maxWidth: 160,
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
                {
                    title: this.$t('operator_failure_Cause'),
                    key: 'type',
                    align: 'center',
                    // maxWidth: 130,
                    render: (h, para) => {
                        if (para.row.workResult === '' || para.row.workResult.feedback === '') {
                            return h('div', [
                                h('span', {
                                }, this.notpassTypes[para.row.type]),
                            ]);
                        } else {
                            return h('div', [
                                h('span', {
                                }, this.notpassTypes[para.row.type]),
                                h('span', [
                                    h('Poptip', {
                                        props: {
                                            trigger: "hover",
                                            title: this.$t('operator_failure_Cause'),
                                            // content: para.row.workResult.feedback,
                                            transfer: true,
                                            placement: 'right-start',
                                        },
                                    }, [
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
                                        h(ErrorTaskReasonShow, {
                                            props: {
                                                reason: para.row.workResult.feedback,
                                            },
                                            slot: 'content'
                                        })

                                    ])
                                ]),
                            ]);
                        }
                    },
                    filters: [],
                    filterMethod: () => true
                },
                {
                    title: this.$t('operator_view_data'),
                    align: "center",
                    // width: 120,
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
                                                this.dataId = para.row.data_id;
                                                this.projectId = para.row.project_id;
                                                this.dataInfo = para.row.data;
                                                this.workdata = para.row.dataResult ? JSON.parse(para.row.dataResult.result || para.row.dataResult.ai_result || '{}') : {};
                                                this.workUser = para.row.user !== "" ? para.row.user : {};
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
                    title: this.$t('operator_handle'),
                    align: "center",
                    // width: 130,
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
                                            this.getRecord(para.row.data.id, para.row.project_id);
                                        }
                                    }
                                },
                                this.$t('operator_record')
                            ),
                        ]);
                    }
                },
            ],
            tableData: [],
            recordData: [],
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
    components: {
        ...resultComponent,
        resultItemAnnotation,
        workRecord,
    },
    methods: {
        getSelData (status) {
            let arrStatuses = [];
            $.each(status,(k, v) => {
                arrStatuses.push({
                    index: k,
                    value: v
                })
            })
            this.invalid_statuses = arrStatuses;
        },
        getRecord (data_id, project_id) {
            this.modelLoading = true;
            $.ajax({
                url: api.work.workRecords,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    project_id: project_id,
                    data_id: data_id,
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
                        this.workStatuses = res.data.work_status;
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
        changeCatrgoryFilter (operatorMap) {
            // 动态调整项目类型过滤器
            let index = util.getKeyIndexFromTableOption(this.tableOption, 'type');
            if (index < 0) {
                return;
            }
            let type = this.tableOption[index];
            type.filters = operatorMap;
            // hack 动态filter
            Vue.nextTick(() => {
                if (this.type.toString() !== '') {
                    this.$set(this.$refs.userTable.cloneColumns[index], '_filterChecked', this.type);
                    this.$set(this.$refs.userTable.cloneColumns[index], '_isFiltered', true);
                }
            });
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked;
            this.page = 1;
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = data.count * 1; // 整数
            let operatorMap = [];
            let users = data.types;
            Object.keys(users).forEach(v => {
                let operator = {
                    label: users[v],
                    value: v
                };
                operatorMap.push(operator);
            });
            this.changeCatrgoryFilter(operatorMap);
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        getData () {
            this.loading = true;
            this.$store.state.app.userInfoRequest.then(res => {
                this.requestData(res.data.user.id);
            });
        },
        requestData (user_id) {
            $.ajax({
                url: api.work.workList,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id,
                    user_id: user_id,
                    op: '4',
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                    type: this.type.toString(),
                    status: this.selValue === "all" ? "" : this.selValue
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.notpassTypes = res.data.types;
                        this.getTableData(res.data);
                        this.workStatuses = res.data.statuses;
                        this.getSelData(res.data.invalid_statuses);
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
        this.getData();
    }
};
</script>
<style scoped>
    .subcontent{
        background: #fff;
        margin-top:10px;
        padding: 20px;
    }
</style>
<style lang="scss">
@import url('../../../styles/table.css');
</style>

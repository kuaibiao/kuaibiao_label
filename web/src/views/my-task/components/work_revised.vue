<template>
    <div class="subcontent">
        <Row>
            <i-col span="8" push="16">
                <div class="search_input">
                    <Input v-model="keyword"
                           @on-enter="changeKeyword"
                           @on-search="changeKeyword"
                           :placeholder="$t('operator_input_text')"
                           clearable
                           search
                           :enter-button="true"/>
                </div>
            </i-col>
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
                @on-selection-change="selChange"
                show-header>
                <div slot="footer">
                    <Button type="primary" style="margin-left:10px" @click="batchRevise" :disabled="selItem.length == 0"
                            :loading="batchOperatorLoading">{{step_type == '1' ? this.$t('operator_batch_review') :
                        this.$t('operator_batch_qc')}}
                    </Button>
                    <div style="float: right;margin-right:10px">
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
            </Table>
        </div>
        <!-- <div style="margin: 10px;overflow: hidden">
            <div style="float: right;margin-right:10px">
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
        </div> -->
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
            :title="this.$t('operator_record')"
            @on-ok="recordModal = false"
            @on-cancel="recordModal = false">
            <p slot="header" style="text-align:center">
                <span>{{$t('operator_record')}}</span>
                <Tooltip trigger="hover" placement="right" :transfer="true">
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
import resultItemAnnotation from '../../task-perform/components/text-annotation-result.vue';
import workRecord from '../../../common/components/task-result-view/work-record';
import {
    resultComponent,
    viewResultType,
} from '../../../common/components/task-result-view/index';

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
            loadingText: 'loading...',
            batchOperatorLoading: false,
            recordModal: false,
            types: {},
            statuses: {},
            step_types: {},
            workStatuses: {},
            modelLoading: false,
            operatorDataId: '',
            userId: '',
            viewModal: false,
            dataInfo: {},
            dataId: '',
            projectId: '',
            viewResultType: viewResultType,
            currentViewType: '',
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: +(localStorage.getItem('workListLimit') || 10),
            orderby: 'updated_at',
            sort: 'desc',
            workdata: {},
            workUser: {},
            tableOption: [
                {
                    type: 'selection',
                    width: 60,
                    align: 'center',
                },
                {
                    title: this.$t('operator_job_id'),
                    key: 'data_id',
                    align: 'center',
                    width: 100,
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
                    title: this.$t('operator_receive_time'),
                    key: 'start_time',
                    align: 'center',
                    // maxWidth: 160,
                    sortable: 'custom',
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
                    // maxWidth: 160,
                    sortable: 'custom',
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
                        if ((para.row.status == '5')) {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        size: 'small',
                                        type: 'dashed'
                                    },
                                    style: {
                                        margin: '5px',
                                        color: 'red'
                                    },
                                }, 
				// '待返工'
				this.$t('operator_need_rework')),
                                h(
                                    'Button',
                                    {
                                        props: {
                                            size: 'small'
                                        },
                                        style: {
                                            margin: '5px'
                                        },
                                        on: {
                                            click: () => {
                                                this.getRecord(para.row.data.id, para.row.project_id);
                                            }
                                        }
                                    },
                                    // '操作记录'
                                    this.$t('operator_record')
                                ),
                            ]);
                        }
                        return h('div', [
                            h(
                                'Button',
                                {
                                    props: {
                                        type: 'primary',
                                        size: 'small',
                                    },
                                    style: {
                                        margin: '5px'
                                    },
                                    on: {
                                        click: () => {
                                            this.loading = true;
                                            this.operatorDataId = para.row.data.id;
                                            this.projectId = para.row.project_id;
                                            this.userId = para.row.user.id;
                                            this.revise();
                                        }
                                    }
                                },
                                //this.$t('operator_audit') 审核
                                //this.$t('operator_qc') 质检
                                this.step_type == '1' ? this.$t('operator_audit') : this.$t('operator_qc')
                            ),
                            h(
                                'Button',
                                {
                                    props: {
                                        size: 'small'
                                    },
                                    style: {
                                        margin: '5px'
                                    },
                                    on: {
                                        click: () => {
                                            this.getRecord(para.row.data.id, para.row.project_id);
                                        }
                                    }
                                },
                                // '操作记录'
                                this.$t('operator_record')
                            ),
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
                                    title: this.$t('operator_button'),
                                    transfer: true,
                                    placement: 'right-start',
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', this.$t('operator_audit_review')),
                                            h('div', this.$t('operator_audit_qc')),
                                            h('div', this.$t('operator_need_rework_tips')),
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
                        ]);
                    },
                },
            ],
            tableData: [],
            recordData: [],
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
    components: {
        ...resultComponent,
        resultItemAnnotation,
        workRecord,
    },
    methods: {
        selChange (selection, row) {
            let arr = [];
            $.each(selection, (k, v) => {
                arr.push(v.data.id);
            });
            this.selItem = arr;
        },
        batchRevise () {
            if (this.batchOperatorLoading) {
                return false;
            }
            this.batchOperatorLoading = true;
            if (this.selItem.length) {
                this.projectId = this.tableData[0].project_id;
                this.userId = this.tableData[0].user.id;
            } else {
                this.$Message.warning({
                    content: this.$t('operator_unchecked_job'),
                    duration: 3
                });
                return;
            }
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.projectId,
                    task_id: this.$route.params.id,
                    user_id: this.userId,
                    op: 'refusesubmit_receive',
                    data_id: this.selItem.toString(),
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                        this.batchOperatorLoading = false;
                    } else {
                        this.selItem = [];
                        this.$router.push({
                            name: 'perform-task',
                            query: {
                                project_id: this.projectId,
                                task_id: this.$route.params.id,
                            }
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.batchOperatorLoading = false;
                    });
                }
            });
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
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = data.count * 1; // 整数
            $.each(this.tableData, (k, v) => {
                if (v.status == '5') {
                    this.$set(data.list[k], '_disabled', true);
                }
            });
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
                    op: '8',
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getTableData(res.data);
                        this.selItem = [];
                        this.workStatuses = res.data.statuses;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        //功能：审核
        revise () {
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.projectId,
                    task_id: this.$route.params.id,
                    user_id: this.userId,
                    op: 'refusesubmit_receive',
                    data_id: this.operatorDataId,
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                        this.loading = false;
                    } else {
                        this.$router.push({
                            name: 'perform-task',
                            query: {
                                project_id: this.projectId,
                                task_id: this.$route.params.id,
                                data_id: this.operatorDataId,
				isReturnWork:true,
                            }
                        });
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
.voice-transcription {
    .file-placeholder {
        background: #fff!important;
    }
}
</style>

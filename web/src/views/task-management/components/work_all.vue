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
                    @on-filter-change="filterChange"
                    @on-sort-change="sortChange"
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
            <component :is="viewResultType[currentViewType]"
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
                :title="$t('admin_record')"
                @on-ok="recordModal = false"
                @on-cancel="recordModal = false">
            <p slot="header" style="text-align:center">
                <span>{{$t('operator_record')}}</span>
                <Tooltip :transfer="true" trigger="hover" placement="right">
                    <div slot="content" style="font-weight: normal">{{$t('operator_refreshing_tip')}}<br>1. {{$t('operator_refreshing_tip_desc1')}}<br>2. {{$t('operator_refreshing_tip_desc2')}}</div>
                    <Icon type="ios-help-circle"/>
                </Tooltip>
            </p>
            <work-record :recordData="recordData" :types="types" :stepTypes="step_types" :modelLoading="modelLoading"
                         :workStatus="workStatuses"></work-record>
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
                recordModal: false,
                modelLoading: false,

                viewModal: false,
                dataInfo: {},
                dataId: '',
                viewResultType: viewResultType,
                currentViewType: '',
                operatorDataId: '',
                projectId: '',
                userId: '',
                loading: false,
                keyword: '',
                workdata: {},
                workUser: {},
                count: 0,
                page: 1,
                limit: +(localStorage.getItem('workListLimit') || 10),
                step_types: {},
                statuses: {},
                workStatuses: {},
                orderby: 'updated_at',
                sort: 'desc',
                tableOption: [
                    {
                        title: this.$t('admin_job_id'),
                        key: 'data_id',
                        align: 'center',
                        width: 120,
                        sortable: 'custom',
                    },
                    {
                        title: this.$t('admin_job_name'),
                        key: 'data_name',
                        align: 'left',
                        ellipsis: true,
                        render: (h, para) => {
                            return h('Tooltip', {
                                props: {
                                    content: para.row.data.name,
                                    placement: 'top-start',
                                    transfer: true
                                },
                                'class': 'tool_tip',
                                style: {
                                    display: 'inline'
                                }
                            }, [
                                h('span', para.row.data.name)
                            ]);
                        }
                    },
                    {
                        title: this.$t('admin_operator'),
                        key: 'user_id',
                        align: 'center',
                        maxWidth: 140,
                        render: (h, para) => {
                            return h('div', [
                                h('Tooltip', {
                                    props: {
                                        // content: '邮箱: ' + para.row.user.email,
                                        placement: 'top',
                                        transfer: true
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {}, [
                                                h('div', 'ID: ' + para.row.user.id),
                                                h('div', this.$t('admin_email') + ': ' + para.row.user.email),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', para.row.user.nickname)
                                ]),
                            ]);
                        },
                        filters: [],
                        filterMultiple: false,
                        filterMethod: () => true
                    },
                    {
                        title: this.$t('admin_status'),
                        key: 'status',
                        maxWidth: 100,
                        align: 'center',
                        render: (h, para) => {
                            return h('span', this.workStatuses[para.row.status]);
                        }
                    },
                    {
                        title: this.$t('admin_view_data'),
                        align: "center",
                        maxWidth: 120,
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
                        title: this.$t('admin_update_time'),
                        key: 'updated_at',
                        align: 'center',
                        maxWidth: 140,
                        sortable: 'custom',
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
                    {
                        title: this.$t('admin_handle'),
                        align: "left",
                        width: 130,
                        render: (h, para) => {
                            if ((para.row.status === '3') || (para.row.status === '4')) {
                                return h('div', [
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                type: 'error',
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
                                                    this.reset('force_reset');
                                                }
                                            }
                                        },
                                        // '重置'
                                        this.$t('admin_reset')
                                    ),
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                type: 'warning',
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
                                                    this.refuse();
                                                }
                                            }
                                        },
                                        // '驳回'
                                        this.$t('admin_reject')
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
                                        this.$t('admin_record')
                                    ),
                                ]);
                            } else if ((para.row.status === '6') || (para.row.status === '7') || (para.row.status === '8')) {
                                let option;
                                if (para.row.status === '6') {
                                    option = 'refuse_reset';
                                } else if (para.row.status === '7') {
                                    option = 'difficult_reset';
                                } else {
                                    option = 'refusesubmit_reset';
                                }
                                return h('div', [
                                    h(
                                        'Button',
                                        {
                                            props: {
                                                type: 'error',
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
                                                    this.reset(option);
                                                }
                                            }
                                        },
                                        // '重置'
                                        this.$t('admin_reset')
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
                                        this.$t('admin_record')
                                    ),
                                ]);
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
                                                    this.getRecord(para.row.data.id, para.row.project_id);
                                                }
                                            }
                                        },
                                        // '操作记录'
                                        this.$t('admin_record')
                                    ),
                                ]);
                            }
                        }
                    },
                ],
                tableData: [],
                recordData: [],
                types: {},
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
            reset (option) {
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        task_id: this.$route.params.id,
                        user_id: this.userId,
                        op: option,
                        data_id: this.operatorDataId,
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
                            this.getData();
                            this.$Message.destroy();
                            this.$Message.info(this.$t('admin_success'));
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            refuse () {
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        task_id: this.$route.params.id,
                        user_id: this.userId,
                        op: 'force_refuse',
                        data_id: this.operatorDataId,
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.getData();
                            this.$Message.destroy();
                            this.$Message.info(this.$t('admin_success'));
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
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
            sortChange ({column, key, order}) {
                this.orderby = key;
                this.sort = order;
                this.page = 1;
                this.getData();
            },
            filterChange (filter) {
                let key = filter.key;
                this[key] = filter._filterChecked.toString();
                this.page = 1;
                this.getData();
            },
            getTableData (data) {
                let tableData = [];
                this.tableData = data.list;
                this.count = +data.count; // 整数
                let operatorMap = [];
                let users = data.users;
                Object.keys(users).forEach(v => {
                    let operator = {
                        label: users[v]['nickname'],
                        value: users[v]['id']
                    };
                    operatorMap.push(operator);
                });
                this.changeCatrgoryFilter(operatorMap);
            },
            changeKeyword () {
                this.page = 1;
                this.getData();
            },
            changeCatrgoryFilter (operatorMap) {
                // 动态调整项目类型过滤器
                let index = util.getKeyIndexFromTableOption(this.tableOption, 'user_id');
                if (index < 0) {
                    return;
                }
                let user_id = this.tableOption[index];
                user_id.filters = operatorMap;
                // hack 动态filter
                Vue.nextTick(() => {
                    if (this.user_id !== '') {
                        this.$set(this.$refs.userTable.cloneColumns[index], '_filterChecked', [this.user_id]);
                        this.$set(this.$refs.userTable.cloneColumns[index], '_isFiltered', true);
                    }
                });
            },
            getData () {
                this.loading = true;
                $.ajax({
                    url: api.work.workList,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        task_id: this.$route.params.id,
                        op: '-1',
                        keyword: this.keyword,
                        limit: this.limit,
                        orderby: this.orderby,
                        sort: this.sort,
                        page: this.page,
                        user_id: this.user_id,
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
                            this.getTableData(res.data);
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
            getRecord (data_id, project_id) {
                this.modelLoading = true;
                $.ajax({
                    url: api.work.workRecords,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
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
        mounted () {
            this.getData();
        }
    };
</script>
<style scoped>
    .subcontent {
        position: relative;
        top: -30px;
        background: #fff;
        padding: 0 20px 20px;
    }
</style>
<style lang="scss">
    @import url('../../../styles/table.css');

    .voice-transcription {
        .file-placeholder {
            background: #fff !important;
        }
    }
</style>

<template>
    <div class="subcontent">
        <Row>
            <i-col span="24">
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
                    @on-sort-change="sortChange"
                    @on-filter-change="filterChange"
                    @on-selection-change="selChange"
                    stripe
                    show-header>
                <div slot="footer">
                    <Button type="primary" style="margin-left:10px" @click="batchReset" :disabled="selItem.length == 0"
                            :loading="batchOperatorLoading">{{$t('admin_batch_reset')}}
                    </Button>
                    <div style="float: right;margin-right:10px;font-size:12px">
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
                modelLoading: false,
                operatorDataId: '',
                projectId: '',
                userId: '',
                viewModal: false,
                dataInfo: {},
                dataId: '',
                workStatuses: {},
                viewResultType: viewResultType,
                currentViewType: '',
                loading: false,
                keyword: '',
                workdata: {},
                workUser: {},
                count: 0,
                page: 1,
                limit: +(localStorage.getItem('workListLimit') || 10),
                orderby: 'updated_at',
                sort: 'desc',
                types: {},
                user_id: '',
                tableOption: [
                    {
                        type: 'selection',
                        width: 60,
                        align: 'center',
                    },
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
                        title: this.$t('admin_mark_number'),
                        key: 'label_count',
                        align: 'center',
                        maxWidth: 120,
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
                                            return h('span', {}, [
                                                h('div', this.$t('admin_picture_description')),
                                                h('div', this.$t('admin_text_description')),
                                                h('div', this.$t('admin_voice_description')),
                                            ]);
                                        }
                                    }
                                }, [
                                    h('span', this.$t('admin_mark_number')),
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
                        title: this.$t('admin_operating_points'),
                        key: 'point_count',
                        align: 'center',
                        maxWidth: 120,
                    },
                    {
                        title: this.$t('admin_update_time'),
                        key: 'updated_at',
                        sortable: 'custom',
                        align: 'center',
                        maxWidth: 140,
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
                        title: this.$t('admin_view_data'),
                        align: "center",
                        width: 120,
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
                        align: "left",
                        width: 130,
                        render: (h, para) => {
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
                                                this.reset();
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
                        },
                        renderHeader: (h, params) => {
                            return h('span', [
                                h('Poptip', {
                                    'class': {
                                        tablePop: true,
                                    },
                                    props: {
                                        trigger: "hover",
                                        title: this.$t('admin_button_explain'),
                                        transfer: true,
                                        placement: 'right-start',
                                    },
                                    scopedSlots: {
                                        content: () => {
                                            return h('span', {}, [
                                                h('div', this.$t('admin_explain_reset')),
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
                        },
                    },
                ],
                tableData: [],
                statuses: {},
                step_types: {},
                ststuses: [],
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
            changePage (page) {
                this.page = page;
                this.getData();
            },
            changePageSize (size) {
                this.limit = size;
                localStorage.setItem('workListLimit', size);
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
            filterChange (filter) {
                let key = filter.key;
                this[key] = filter._filterChecked.toString();
                this.page = 1;
                this.getData();
            },
            sortChange ({column, key, order}) {
                this.orderby = key;
                this.sort = order;
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
            getData () {
                this.loading = true;
                $.ajax({
                    url: api.work.workList,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        task_id: this.$route.params.id,
                        op: '7',
                        keyword: this.keyword,
                        limit: this.limit,
                        page: this.page,
                        user_id: this.user_id,
                        orderby: this.orderby,
                        sort: this.sort,
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
                            this.types = res.data.types;
                            this.getTableData(res.data);
                            this.selItem = [];
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            reset () {
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.projectId,
                        task_id: this.$route.params.id,
                        user_id: this.userId,
                        op: 'difficult_reset',
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
            batchReset () {
                this.batchOperatorLoading = true;
                if (this.selItem.length) {
                    this.projectId = this.tableData[0].project_id;
                    this.userId = this.tableData[0].user.id;
                } else {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('admin_unchecked'),
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
                        op: 'difficult_reset',
                        data_id: this.selItem.toString(),
                    },
                    success: res => {
                        this.batchOperatorLoading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.destroy();
                            this.$Message.success(this.$t('admin_success'));
                            this.selItem = [];
                            this.getData();
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

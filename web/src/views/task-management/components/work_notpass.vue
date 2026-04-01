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

    import ErrorTaskReasonShow from 'components/error-task-reason-show.vue';

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
                workStatuses: {},
                step_types: {},
                modelLoading: false,

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
                user_id: '',
                notpassTypes: [],
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
                                    transfer: true,
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
                        title: this.$t('admin_audited_time'),
                        key: 'start_time',
                        align: 'center',
                        maxWidth: 140,
                        sortable: 'custom',
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
                        title: this.$t('admin_failure_reason'),
                        key: 'type',
                        align: 'left',
                        maxWidth: 140,
                        render: (h, para) => {
                            if (para.row.workResult === '' || para.row.workResult.feedback === '') {
                                return h('div', [
                                    h('span', {}, this.notpassTypes[para.row.type]),
                                ]);
                            } else {
                                return h('div', [
                                    h('span', {}, this.notpassTypes[para.row.type]),
                                    h('span', [
                                        h('Poptip', {
                                            props: {
                                                trigger: "hover",
                                                title: this.$t('admin_rejected_reason'),
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
                        title: this.$t('admin_update_time'),
                        key: 'updated_at',
                        sortable: 'custom',
                        maxWidth: 140,
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
                        title: this.$t('admin_handle'),
                        align: "center",
                        width: 130,
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
                                    // '操作记录'
                                    this.$t('admin_record')
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
            ErrorTaskReasonShow,
        },
        methods: {
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
            changeCatrgoryFilter (typeMap, operatorMap) {
                // 动态调整项目类型过滤器
                let index = util.getKeyIndexFromTableOption(this.tableOption, 'type');
                let userIndex = util.getKeyIndexFromTableOption(this.tableOption, 'user_id');
                if (index < 0 || userIndex < 0) {
                    return;
                }
                let type = this.tableOption[index];
                type.filters = typeMap;
                let user_id = this.tableOption[userIndex];
                user_id.filters = operatorMap;
                // hack 动态filter
                Vue.nextTick(() => {
                    if (this.type.toString() !== '') {
                        this.$set(this.$refs.userTable.cloneColumns[index], '_filterChecked', this.type);
                        this.$set(this.$refs.userTable.cloneColumns[index], '_isFiltered', true);
                    }
                    if (this.user_id !== '') {
                        this.$set(this.$refs.userTable.cloneColumns[userIndex], '_filterChecked', [this.user_id]);
                        this.$set(this.$refs.userTable.cloneColumns[userIndex], '_isFiltered', true);
                    }
                });
            },
            filterChange (filter) {
                let key = filter.key;
                if (key == 'type') {
                    this[key] = filter._filterChecked;
                } else {
                    this[key] = filter._filterChecked.toString();
                }
                this.page = 1;
                this.getData();
            },
            getTableData (data) {
                let tableData = [];
                this.tableData = data.list;
                this.count = data.count * 1; // 整数
                let typeMap = [];
                let types = data.types;
                Object.keys(types).forEach(v => {
                    let type = {
                        label: types[v],
                        value: v
                    };
                    typeMap.push(type);
                });
                let operatorMap = [];
                let users = data.users;
                Object.keys(users).forEach(v => {
                    let operator = {
                        label: users[v]['nickname'],
                        value: users[v]['id']
                    };
                    operatorMap.push(operator);
                });
                this.changeCatrgoryFilter(typeMap, operatorMap);
            },
            changeKeyword () {
                this.page = 1;
                this.getData();
            },
            getData () {
                let app = this;
                app.loading = true;
                $.ajax({
                    url: api.work.workList,
                    type: 'post',
                    data: {
                        access_token: app.$store.state.user.userInfo.accessToken,
                        task_id: app.$route.params.id,
                        op: '4',
                        keyword: app.keyword,
                        limit: app.limit,
                        page: app.page,
                        orderby: app.orderby,
                        sort: app.sort,
                        type: app.type.toString(),
                        user_id: this.user_id,
                    },
                    success: res => {
                        app.loading = false;
                        if (res.error) {
                            app.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.notpassTypes = res.data.types;
                            app.getTableData(res.data);
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

<template>
    <div style="position:relative; min-height: 100%;">
        <Row style="width: 100%;" type="flex">
            <i-col span="18">
                <audit-by-user style="float: left;position: relative;top: -1px" :userList="userList"></audit-by-user>
                <div style="float: left">
                    <Tooltip :transfer="true" style="margin-right:10px" placement="top" :max-width="200" transfer :content="$t('tool_analy_tip_desc')">
                        <span style="font-size:12px">{{$t('tool_precision_rate')}}：</span>
                        <Icon style="font-size: 16px" type="ios-help-circle-outline"/>
                    </Tooltip>
                    <Select v-model="selType" style="width: auto;display:inline-block">
                        <Option v-for="item in selectData" :value="item.value" :key="item.value">{{ item.label }}</Option>
                    </Select>
                    <InputNumber
                        :max="100"
                        v-model="persentage"
                        :min="0"
                        :formatter="value => `${value}%`"
                        :parser="value => value.replace('%', '')" style="margin-right:10px;"></InputNumber>
                    <Button type="primary" @click="fetchTask">{{$t('tool_filter')}}</Button>
                </div>
            </i-col>
            <i-col span="6">
                <Button type="error" size="small" v-show="taskListIsNull"
                    @click.native="rejectComfirmModal = true" style="float:right;margin-top: 5px">{{$t('tool_batch_rejection')}}</Button>
                <Button type="primary" size="small" v-show="taskListIsNull"
                    @click.native="passComfirmModal = true"
                    style="float:right;margin-right:10px;margin-top: 5px"
                    >{{$t('tool_batch_pass')}}</Button>
            </i-col>
        </Row>
        <div class="audit-wrapper">
            <Table  ref="selection"
                size="large"
                :columns="columnsConfig"
                :data="tableData"
                @on-sort-change = "sortChange"
                ></Table>
        </div>
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
        <Spin fix v-if="loading">{{loadingText}}</Spin>
        <Modal v-model="editModal"
            :class="'edit-modal-wrapper voice-transcription'"
            width="100"
            style="min-height:100%"
            :closable="false"
            :mask-closable="false"
            scrollable
            @on-visible-change="modalVisibleChange">
            <div slot="header">
                <div class="edit-btn-group">
                    <!-- <RadioGroup v-model="currentId" style="margin-right:30px">
                        <Radio v-for="(item,index) in parentWorkResults" :label="item.work_id" :key="index">结果：{{index+1}}</Radio>
                    </RadioGroup> -->
                    <!-- <Button type="primary" size="small" @click="submitEditTask()">提交</Button> -->
                    <!-- <Button type="success" @click="taskPass(dataId,currentId)">通过</Button>
                    <Button type="error" @click="taskWillReject">驳回</Button> -->
                    <Button @click="editModal = false" type="primary">{{$t('tool_close')}}</Button>
                </div>
            </div>
            <div id="text_audit">
                <Row type="flex" justify="center"  align="top">
                    <i-col span="14">
                        <Row type="flex" justify="center"  align="middle" style="min-height:100%;">
                            <div class="data-container-wrapper">
                                {{ $t('tool_text_loading') }}
                            </div>
                        </Row>
                    </i-col>
                    <i-col span="10">
                        <!--<Row v-for="( item, index) in parentWorkResults" :key="index" >-->
                            <!--<text-annotation-result-->
                                <!--:info="JSON.parse(item.result).info || []"-->
                                <!--:data="JSON.parse(item.result).data || []"-->
                                <!--:index="index"-->
                                <!--:user="parentWorks[index].user"-->
                             <!--/>-->
                        <!--</Row>-->
                        <text-annotation-result-list v-if="editModal"
                                     :resultList="parentWorkResults"
                                     :workerList="parentWorks">
                        </text-annotation-result-list>
                    </i-col>
                </Row>
            </div>
            <Spin fix v-if="editLoading">{{loadingText}}</Spin>
            <span slot="footer"></span>
        </Modal>
        <Modal v-model="regjectModal"
            :title="$t('tool_fillreject_reason')"
            @on-ok= "handleModalOnOk"
            @on-visible-change="regjectModalVisibleChange"
            :mask-closable="false"
            >
            <Input v-model="rejectReason" autofocus/>
        </Modal>
        <Modal
                v-model="rejectComfirmModal"
                :title="$t('tool_operate_tips')">
            <!-- <p>确定驳回准确率{{selectData[selType].label}}<strong style="font-size:14px;">{{persentage}}%</strong>的作业吗？</p> -->
            <i18n path="tool_rejection_accuracy" tag="p">
                <span place="type">{{selectData[selType].label}}</span>
                <strong place="persentage" style="font-size:14px;">{{persentage}}%</strong>
            </i18n>
            <div slot="footer">
                <Button type="text" @click="rejectComfirmModal = false">{{$t('tool_think_again')}}</Button>
                <Button type="error" @click="reject_batch" :loading='loading'>{{$t('tool_reject')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="passComfirmModal"
                :title="$t('tool_operate_tips')">
                <!-- <p>确定通过准确率{{selectData[selType].label}}<strong style="font-size:14px;">{{persentage}}%</strong>的作业吗？</p> -->
            <i18n path="tool_pass_accuracy" tag="p">
                <span place="type">{{selectData[selType].label}}</span>
                <strong place="persentage" style="font-size:14px;">{{persentage}}%</strong>
            </i18n>
            <div slot="footer">
                <Button type="text" @click="passComfirmModal = false">{{$t('tool_think_again')}}</Button>
                <Button type="primary" @click="pass_batch" :loading='loading'>{{$t('tool_pass')}}</Button>
            </div>
        </Modal>
        <Modal
                v-model="exitfirmModal"
                :title="$t('tool_operate_tips')">
                <p>{{$t('tool_exit_review')}}</p>
            <div slot="footer">
                <Button type="text" @click="exitfirmModal = false">{{$t('tool_cancel')}}</Button>
                <Button type="error" @click="exit">{{$t('tool_quit')}}</Button>
            </div>
        </Modal>
    </div>
</template>
<script>
import Vue from 'vue';
import api from '@/api';
import uuid from "uuid/v4";
import Mark from 'mark.js';
import EventBus from '@/common/event-bus';
import util from "@/libs/util";
import cloneDeep from 'lodash.clonedeep';
import TextAnnotationResultList from '@/common/components/task-result-view/text-annotation-result-list.vue';

// import textAnalysisResult from '../components/text-analysis-result.vue';
import textAnnotationResult from '../components/text-annotation-result.vue';
import auditByUser from '../components/audit-by-user.vue';
export default {
    name: 'batch-audit-text-analysis',
    data () {
        return {
            exitfirmModal: false,
            count: 0,
            page: 1,
            limit: 10,
            orderby: 'currate_rate',
            sort: 'desc',
            projectId: '',
            taskId: '',
            selType: '0',
            persentage: 0,
            templateInfo: [],
            taskInfo: {},
            taskList: [],
            objectList: [],
            rejectComfirmModal: false,
            passComfirmModal: false,
            categoryInfo: null,
            serverTime: 0,
            timeout: 0,
            taskStat: {},
            currentId: '',
            currentIds: [],
            currentTaskIndex: 0,
            userId: this.$store.state.user.userInfo.id,
            dataId: '',
            loading: true,
            submiting: false,
            editLoading: false,
            taskItemInfo: '',
            loadingText: this.$t('tool_loading'),
            editModal: false,
            selectedTask: [],
            selectAllIsOn: false,
            columnsConfig: [
                {
                    title: this.$t('tool_job_id'),
                    key: 'data_id',
                    width: 100,
                    render: (h, params) => {
                        return h('span', params.row.data.id);
                    }
                },
                {
                    title: this.$t('tool_filename'),
                    key: 'data',
                    ellipsis: true,
                    render: (h, params) => {
                        return h('Tooltip', {
                            props: {
                                content: params.row.data.name,
                                placement: 'top-start',
                                transfer: true
                            },
                            'class': 'tool_tip',
                            style: {
                                display: 'inline'
                            }
                        }, [
                            h('span', params.row.data.name)
                        ]);
                    }
                },
                {
                    title: this.$t('tool_precision_rate'),
                    key: 'correct_rate',
                    sortable: 'custom',
                    render: (h, params) => {
                        return h('span', params.row.work.correct_rate + '%');
                    }
                },
                // {
                //     title: '创建日期',
                //     key: 'data',
                //     render: (h, params) => {
                //         let parentWorks = params.row.parentWorks;
                //         let parentWorksCreateTime = parentWorks.map((works) => {
                //             return works.created_at;
                //         }).sort();
                //         return h('span', util.timeFormatter(new Date(parentWorksCreateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss'));
                //     }
                // },
                {
                    title: this.$t('tool_updated_time'),
                    key: 'data',
                    render: (h, params) => {
                        let parentWorks = params.row.parentWorks;
                        let parentWorksUpdateTime = parentWorks.map((works) => {
                            return works.updated_at;
                        }).sort((a, b) => a - b);
                        return h('span', +parentWorksUpdateTime[0] > 0 ? util.timeFormatter(new Date(parentWorksUpdateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss') : '');
                    }
                },
                {
                    title: this.$t('tool_handle'),
                    align: 'center',
                    render: (h, params) => {
                        return h('div', [
                            h('Button', {
                                props: {
                                    type: 'primary',
                                    size: 'small'
                                },
                                style: {
                                    marginRight: '5px',
                                },
                                on: {
                                    click: () => {
                                        this.dataId = params.row.data.id;
                                        this.currentTaskIndex = params.index;
                                        this.editModal = true;
                                    }
                                }
                            }, this.$t('tool_view')),
                            // h('Button', {
                            //     props: {
                            //         type: 'success',
                            //         size: 'small'
                            //     },
                            //     style: {
                            //         marginRight: '5px',
                            //     },
                            //     on: {
                            //         click: () => {
                            //             this.taskPass(params.row.data.id,params.row.parentWorkResults[params.row.parentWorkResults.length-1].work_id)
                            //         }
                            //     }
                            // }, '通过'),
                            // h('Button', {
                            //     props: {
                            //         type: 'error',
                            //         size: 'small'
                            //     },
                            //     style: {
                            //         marginRight: '5px',
                            //     },
                            //     on: {
                            //         click: () => {
                            //             this.taskWillReject(params.row.data.id);
                            //             //this.taskReject(params.row.data.id, this.rejectReason)
                            //         }
                            //     }
                            // }, '驳回'),
                        ]);
                    }

                }
            ],
            selectData: [
                {
                    value: '0',
                    label: this.$t('tool_gt')
                },
                {
                    value: '1',
                    label: this.$t('tool_lt')
                },
                {
                    value: '2',
                    label: this.$t('tool_ge')
                },
                {
                    value: '3',
                    label: this.$t('tool_le')
                },
                {
                    value: '4',
                    label: this.$t('tool_eq')
                }
            ],
            tableData: [],
            rejectReason: '',
            regjectModal: false,
            rejectTaskId: '',
            parentWorkResults: [],
            parentWorks: [],
            userList: [],
            currentUserId: '',
            marker: null,
            // resultViewType : {
            //     'text_analysis': textAnalysisResult,
            //     'text_annotation': textAnnotationResult
            // }
        };
    },
    computed: {
        selectAllText () {
            let text = '';
            if (this.selectedTask.length > 0) {
                this.selectAllIsOn = true;
                text = this.$t('tool_all_cancel');
            } else {
                text = this.$t('tool_all_select');
                this.selectAllIsOn = false;
            }
            return text;
        },
        taskListIsNull () {
            if (!this.taskList.length) {
                this.loading = false;
            }
            return this.taskList.length;
        }
    },
    watch: {
        // taskList (newV, oldV) {
        //     this.tableData = newV;
        //     if(newV.length === 0) {
        //         EventBus.$emit('perform-fetchTask');
        //     }
        // }
        // taskList (newV, oldV) {
        //     // this.tableData = newV;
        //     this.tableData = cloneDeep(this.taskList);
        //     if (newV.length === 0) {
        //         // EventBus.$emit('perform-fetchTask');
        //         this.$Message.warning({
        //             content: '没有剩余作业',
        //             duration: 3,
        //         });
        //     }
        // }
    },
    mounted () {
        this.projectId = this.$route.query.project_id;
        this.taskId = this.$route.query.task_id;
        this.fetchTask();
        EventBus.$on('clear-fetchTask', this.userIdChange);
        EventBus.$on('highlightRange', this.highlightRange);
        // this.tableData = this.taskList;
        // Vue.nextTick( () => {
        //     this.loading = false;
        // })
    },
    methods: {
        highlightRange (range) {
            this.marker && this.marker.unmark({
                done: () => {
                    this.marker.markRanges([range]);
                }
            });
        },
        checkTaskList () {
            if (this.tableData.length === 0) {
                this.fetchTask();
            }
        },
        userIdChange (e) {
            if (e.type === 'workerChange') {
                this.currentUserId = e.data.cur;
            }
            this.fetchTask();
        },
        cloneDeep (a) {
            return cloneDeep(a);
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.fetchTask();
        },
        filterData () {
            this.loading = true;
            this.objectList = this.tableData.filter((k, v) => {
                if (this.selType === 'gt') {
                    return k.work.correct_rate >= this.persentage;
                } else {
                    return k.work.correct_rate < this.persentage;
                }
            });
            setTimeout(() => {
                this.loading = false;
            }, 300);
        },
        changePage (page) {
            this.page = page;
            this.fetchTask();
        },
        changePageSize (size) {
            this.limit = size;
            this.fetchTask();
        },
        handleClick (index) {
            // console.log(index);
        },
        onSelectChange (selection) {
            this.selectedTask = selection;
        },
        fetchTask () {
            this.loading = true;
            $.ajax({
                url: api.task.batchExecute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.projectId,
                    task_id: this.taskId,
                    user_id: this.currentUserId,
                    op: 'fetch',
                    page: this.page,
                    limit: this.limit,
                    orderby: this.orderby,
                    sort: this.sort,
                    rate: this.persentage,
                    operator: this.selType
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.needConfirmLeave = false;
                        this.$Message.info({
                            content: res.message,
                            duration: 3
                        });
                        this.$store.commit('removeTag', 'perform-batch-audit');
                        let preRouter = this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                    } else {
                        this.categoryInfo = res.data.category;
                        // this.taskList = res.data.list;
                        // this.stepInfo = res.data.step;
                        // this.templateInfo = res.data.template && res.data.template.config || [];
                        // this.serverTime = res.data.time;
                        // this.timeout = res.data.timeout;
                        // this.taskStat = res.data.statUserToday || {
                        //     label_count: 0,
                        //     point_count: 0,
                        //     work_count: 0
                        // };
                        this.taskList = res.data.list;
                        this.tableData = cloneDeep(this.taskList);
                        if (this.tableData.length === 0) {
                            this.$Message.warning({
                                content: this.$t('tool_no_jobs'),
                                duration: 3,
                            });
                        }
                        this.userList = res.data.parentWorkUsers;
                        this.count = +res.data.count;
                        this.objectList = this.tableData.filter((k, v) => {
                            if (this.selType === 'gt') {
                                return k.work.correct_rate >= this.persentage;
                            } else {
                                return k.work.correct_rate < this.persentage;
                            }
                        });
                    }
                },
                error: (res) => {
                    this.loading = false;
                    this.needConfirmLeave = false;
                    // 错误处理
                }
            });
        },
        reject_batch () {
            this.loading = true;
            $.ajax({
                url: api.task.batchExecute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    type: 0,
                    op: 'submit',
                    rate: this.persentage,
                    operator: this.selType
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.fetchTask();
                        this.rejectComfirmModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        pass_batch () {
            this.loading = true;
            $.ajax({
                url: api.task.batchExecute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    type: 1,
                    op: 'submit',
                    rate: this.persentage,
                    operator: this.selType
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.fetchTask();
                        this.passComfirmModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        initTask () {
            let app = this;
            this.getTaskResource(this.dataId);
            this.parentWorkResults = this.tableData[this.currentTaskIndex].parentWorkResults;
            this.parentWorks = this.tableData[this.currentTaskIndex].parentWorks;
            app.currentIds = [];
            $.each(this.parentWorkResults, function (k, v) {
                app.currentIds.push(v.work_id);
            });
            Vue.nextTick(() => {
                this.currentId = this.parentWorkResults[this.parentWorkResults.length - 1].work_id;
            });
        },
        getTaskResource (dataId) {
            this.loading = true;
            $.ajax({
                url: api.task.resource,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: dataId,
                    type: 'ori',
                },
                success: (res) => {
                    this.loading = false;
                    this.editLoading = false;
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.taskInfo = res.data.taskInfo;
                        let resource = Object.entries(res.data || {});
                        if (resource.length === 0) {
                            // this.$Message.error({
                            //     content: '作业资源获取失败',
                            //     duration: 2,
                            // });
                            // return;
                            resource = [['subject', {}]];
                        }
                        Vue.nextTick(() => {
                            let html = '';
                            resource.forEach((item) => {
                                let key = item[0];
                                let value = item[1];
                                value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                                html += `<pre class="data-container">${value}</pre>`;
                            });
                            $('#text_audit .data-container-wrapper').html(html);
                            this.marker = new Mark($('#text_audit .data-container-wrapper').get(0));
                            // $('#text_audit .data-container').text(res.data.subject);
                        });
                    }
                },
                error: (res) => {
                    this.loading = false;
                    this.editLoading = false;
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                    // 错误处理
                }
            });
        },
        // executeTask(dataId, subject) {
        //     $.ajax({
        //         url: api.task.execute,
        //         type: 'post',
        //         data: {
        //             access_token: this.$store.state.user.userInfo.accessToken,
        //             project_id: this.$route.query.project_id,
        //             task_id: this.$route.query.task_id,
        //             data_id: dataId,
        //             op: 'edit',
        //         },
        //         success: (res) => {
        //             this.editLoading = false;
        //             if(res.error) {
        //                 EventBus.$emit('needConfirmLeave', false);
        //                 this.$Message.error({
        //                     content: res.message,
        //                     duration: 2,
        //                 });
        //                 // 错误处理
        //             } else {
        //                 let result;
        //                 Vue.nextTick(() => {
        //                     $('[data-tpl-type="text-file-placeholder"] .text-container').text(subject);
        //                     EventBus.$emit('setupMarker');
        //                     EventBus.$emit('ready');
        //                     for(let i=0;i<this.parentWorkResults.length;i++){
        //                         result = JSON.parse(this.parentWorkResults[i].result);
        //                         console.log(result)
        //                         if(result && result.data instanceof Array) {
        //                             result.data.forEach((item) => {
        //                                 EventBus.$emit('setValue', {
        //                                     ...item,
        //                                     scope: this.$refs['templateView' + i][0].$el
        //                                 });
        //                             });
        //                         }
        //                     }

        //                 })

        //                 // if(result && result.data instanceof Array) {
        //                 //     result.data.forEach((item) => {
        //                 //         EventBus.$emit('setValue', item);
        //                 //     });
        //                 // }
        //                 EventBus.$emit('needConfirmLeave', true);
        //             }
        //         },
        //         error: () => {
        //             EventBus.$emit('needConfirmLeave', false);
        //             this.editLoading = false;
        //             this.$Message.error({
        //                 content: '请求失败',
        //                 duration: 2,
        //             });
        //         }
        //     })
        // },
        // submitEditTask(index) {
        //     if(this.submiting) {
        //         return;
        //     }
        //     let data = this.$refs['templateView'+index][0].getData();
        //     let result = {};
        //     result[this.dataId] = {
        //         data
        //     };
        //     this.loading = true;
        //     this.submiting = true;
        //     $.ajax({
        //         url: api.task.execute,
        //         type: 'post',
        //         data: {
        //             access_token: this.$store.state.user.userInfo.accessToken,
        //             project_id: this.$route.query.project_id,
        //             task_id: this.$route.query.task_id,
        //             data_id: this.dataId,
        //             op: 'edit_submit',
        //             data_result : JSON.stringify(result)
        //         },
        //         success: (res) => {
        //             this.submiting = false;
        //             this.loading = false;
        //             if(res.error) {
        //                 this.$Message.warning({
        //                     content: res.message,
        //                     duration: 2,
        //                 });
        //             } else {
        //                 this.taskPass(this.dataId);
        //             }
        //         },
        //         error: (res) => {
        //             this.loading = false;
        //             this.submiting = false;
        //             this.$Message.error({
        //                 content: '请求失败',
        //                 duration: 2,
        //             });
        //         }
        //     });
        // },
        batchReject () {
            this.selectedTask.forEach((task, index) => {
                setTimeout(() => {
                    this.taskReject(task.data.id, this.rejectReason);
                }, 100 * index);
            });
        },
        batchPass () {
            this.selectedTask.forEach((task, index) => {
                setTimeout(() => {
                    this.taskPass(task.data.id, task.parentWorkResults[task.parentWorkResults.length - 1].work_id);
                }, 100 * index);
            });
        },
        batchSelect () {
            if (this.selectAllIsOn) {
                this.$refs.selection.selectAll(false);
            } else {
                this.$refs.selection.selectAll(true);
            }
        },
        taskPass (dataId, currentId) {
            let result = {};
            result[dataId] = {
                verify: {
                    verify: 1,
                    feedback: '',
                    correct_work_id: currentId,
                }
            };
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    op: 'submit',
                    result: JSON.stringify(result)
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let taskIndex = '';
                        this.tableData.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        this.tableData.splice(taskIndex, 1);
                        this.selectedTask.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        this.selectedTask.splice(taskIndex, 1);
                        this.editModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        taskWillReject (dataId) {
            this.regjectModal = true;
            this.rejectTaskId = dataId;
        },
        handleModalOnOk () {
            if (this.rejectReason.trim() === '') {
                this.$Message.error({
                    content: this.$t('tool_reason_empty'),
                    duration: 2,
                });
                return;
            }
            if (this.selectAllIsOn && this.selectedTask.length > 0) {
                this.batchReject();
            } else {
                let app = this;
                app.taskReject(app.rejectTaskId, app.rejectReason);
                // $.each(app.currentIds,function(k,v) {
                //     setTimeout( () => {
                //         app.taskReject(app.dataId, app.rejectReason, v);
                //     }, 100 * k);
                // })
            }
        },
        // taskReject(dataId, reason, rejectId) {
        //     let result = {};
        //     result[dataId] = {
        //         verify: {
        //             verify: 0,
        //             feedback: reason
        //         }
        //     };
        //     $.ajax({
        //         url: api.task.execute,
        //         type: 'post',
        //         data: {
        //             access_token: this.$store.state.user.userInfo.accessToken,
        //             project_id: this.$route.query.project_id,
        //             task_id: this.$route.query.task_id,
        //             // data_id: dataId,
        //             correct_work_id: rejectId,
        //             op: 'submit',
        //             result: JSON.stringify(result)
        //         },
        //         success: (res) => {
        //             if(res.error) {
        //                 this.$Message.error({
        //                     content: res.message,
        //                     duration: 2,
        //                 })
        //             } else {
        //                 // let selectedIndex = this.selectedTask.indexOf(dataId);
        //                 // if(selectedIndex !== -1)  {
        //                 //     this.selectedTask.splice(selectedIndex, 1);
        //                 // }
        //                 let taskIndex = '';
        //                 this.tableData.forEach((task, index) => {
        //                     if(task.data.id === dataId) {
        //                         taskIndex = index;
        //                     }
        //                 });
        //                 this.tableData.splice(taskIndex, 1);
        //                 this.selectedTask.forEach((task, index) => {
        //                     if(task.data.id === dataId) {
        //                         taskIndex = index;
        //                     }
        //                 });
        //                 this.selectedTask.splice(taskIndex, 1);
        //                 this.editModal = false;
        //             }
        //         },
        //         error: (res) => {
        //             this.$Message.error({
        //                 content: "发生了错误",
        //                 duration: 2,
        //             })
        //         }
        //     })
        // },
        taskReject (dataId, reason) {
            let result = {};
            result[dataId] = {
                verify: {
                    verify: 0,
                    feedback: reason
                }
            };
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    data_id: dataId,
                    op: 'submit',
                    result: JSON.stringify(result)
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        // let selectedIndex = this.selectedTask.indexOf(dataId);
                        // if(selectedIndex !== -1)  {
                        //     this.selectedTask.splice(selectedIndex, 1);
                        // }
                        let taskIndex = -1;
                        this.tableData.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.tableData.splice(taskIndex, 1);
                        }
                        taskIndex = -1;
                        this.selectedTask.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.selectedTask.splice(taskIndex, 1);
                        }
                        this.editModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        regjectModalVisibleChange (flag) {
            if (flag) {
                this.rejectReason = '';
            }
        },
        modalVisibleChange (flag) { // true 打开 false 关闭
            if (flag) {
                this.editLoading = true;
                this.initTask();
            } else {
                this.editLoading = false;
                EventBus.$emit('needConfirmLeave', false);
            }
        },
        exit () {
            $.ajax({
                url: api.task.execute,
                type: 'POST',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    user_id: this.currentUserId,
                    op: 'clear',
                },
                success: () => {
                    this.$store.commit('removeTag', 'perform-task');
                    let preRouter = this.$store.state.app.prevPageUrl;
                    if (preRouter) {
                        this.$router.push({
                            path: preRouter.path,
                            params: preRouter.params,
                            query: preRouter.query,
                        });
                    }
                },
                error: () => {
                    this.$store.commit('removeTag', 'perform-task');
                    let preRouter = this.$store.state.app.prevPageUrl;
                    if (preRouter) {
                        this.$router.push({
                            path: preRouter.path,
                            params: preRouter.params,
                            query: preRouter.query,
                        });
                    }
                }
            });
        },
    },
    beforeDestroy () {
        EventBus.$off('clear-fetchTask', this.userIdChange);
    },
    components: {
        'template-view': () => import('components/template-produce'),
        'task-progress': () => import('../components/taskprogress.vue'),
        textAnnotationResult,
        TextAnnotationResultList,
        'audit-by-user': auditByUser
    }
};
</script>

<style lang="scss">
@import url('../../../styles/table.css');
.edit-modal-wrapper {
    .data-container-wrapper {
        position: fixed;
        top: 68px;
        width: 56%;
        max-height:calc(100vh - 84px);
        overflow-y: auto;
        .data-container:first-child {
            margin-top: 15px;
        }
        .data-container {
            font-size: 14px;
            color: #333;
            white-space: pre-wrap;
            padding: 0px 15px;
            margin: 0;
        }
    }
    .file-placeholder {
        background: #fff!important;
    }
    .ivu-modal {
        width: 100%;
        height: 100%;
        top: 0;
    }
    .ivu-modal-content {
        height: 100%;
        border-radius: 0;
    }
    .ivu-modal-header {
        text-align: right;
        padding: 6px 15px;
    }
    .ivu-modal-body {
        padding: 0;
         border: 2px solid #eee;
    }
    .ivu-modal-footer {
        display: none;
    }
    .edit-btn-group {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        button {
            margin-right: 5px;
        }
    }
}
#text_audit {
    overflow-y:auto;
    height: calc(100vh - 55px);
}
</style>


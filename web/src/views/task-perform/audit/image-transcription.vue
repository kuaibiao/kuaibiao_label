<!--图片审核:列表-->
<template>
    <div class="" style="position:relative; min-height: 100%;">
        <div class="task-header">
            <!-- <div></div> -->
            <audit-by-user :userList="userList"></audit-by-user>
            <div class="task-btn-group" v-show="taskListIsNull">
                <task-progress
                    :total="0"
                    :current="0"
                    :timeout="timeout"
                    :noticeAble = "taskListCopy.length > 0"
                ></task-progress>
                <Button type="primary" size="small"
                    @click.native="batchSelect"> {{ selectAllText }} </Button>
                <Button type="primary" size="small"
                    @click.native="batchPass"
                    v-if="selectedTask.length > 0 ">{{$t('tool_batch_pass')}}</Button>
                <Button type="primary" size="small"
                    v-if="selectedTask.length > 0 "
                    @click.native="taskWillReject('', true)">{{$t('tool_batch_rejection')}}</Button>
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0 "
                        @click.native="taskWillReset('', true)">{{$t('tool_bulk_reset')}}</Button>
                <Button type="warning" size="small"
                    v-if="selectedTask.length > 0"
                    @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}</Button>
                <Button type="error" size="small" @click="exitfirmModal = true">{{$t('tool_clear_and_exit')}}</Button>
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        {{$t('tool_shortcuts')}}<br>
                        <code>D </code>  {{$t('tool_approved')}}<br>
                        <code>W </code>  {{$t('tool_reject')}}<br>
                        <code>Q </code>  {{$t('tool_reset')}}<br>
                        <code>A </code>  {{$t('tool_previous')}}<br>
                        <code>S </code>  {{$t('tool_next')}} <br>
                    </div>
                </Tooltip>
            </div>
        </div>
        <div class="audit-wrapper">
            <div v-show="!taskListIsNull"
                 style="height: 200px;background: #eeeeee;font-size: 26px;text-align:center;line-height:200px">
                {{$t('tool_no_remaining_jobs')}}</div>
            <div class="audit-content" v-show="taskListIsNull">
                <!--图片列表-->
                <CheckboxGroup v-model="selectedTask" class="small" v-if="taskListCopy.length">
                    <div class="audit-item" v-for="(task, index) in taskListCopy" :key="index">
                        <Spin size="small" class="image-loading" v-if="!(task[layoutType + 'Image'] || task.imageLoadError)">
                            <Icon type="ios-loading" size=18 class="spin-icon-load"></Icon>
                            <div>Loading</div>
                        </Spin>
                        <div class="audit-result" @click = "imageViewer(task.data.id, index, $event)">
                            <img v-if="!task.imageLoadError"
                                :src="task['rawImage']"
                                class="audit-result-image"
                                 alt="Image"
                             />
                            <p v-else class="image-load-error">
                                {{$t('tool_resource_failed')}}
                            </p>
                        </div>
                        <div class="audit-button" v-if="task['rawImage']">
                            <Button type="primary" size="small"
                                @click.native="taskPass(task.data.id, task.parentWorkResults[task.parentWorkResults.length - 1].work_id)">{{$t('tool_pass')}}</Button>
                            <Button type="error" size="small"
                                style="margin-left:2px;"
                                @click.native="taskWillReject(task.data.id)">{{$t('tool_reject')}}</Button>
                            <Button size="small"
                                style="margin-left:2px;"
                                @click.native="taskWillReset(task.data.id)">{{$t('tool_reset')}}</Button>
                            <Button size="small"
                                    type="warning"
                                style="margin-left:2px;"
                                @click.native="taskSetDifficult(task.data.id)">{{$t('tool_diffcult_job')}}</Button>
                        </div>
                        <div class="audit-result-info">
                            <Checkbox :label="task.data.id">
                                <span class="task-data-name">{{ task.data.name.length < 25 ?  task.data.name : task.data.name.slice(0, 25) + '...'}}</span>
                                <span class="task-data-id">{{ task.data.id }}</span>
                            </Checkbox>
                            <Tooltip :transfer="true" placement="top-start" style="margin-right:20px" v-if="task.workResult.feedback">
                                <span style="color:red">{{$t('tool_rejected')}}</span>
                                <ErrorTaskReasonShow slot="content"
                                                     :reason="task.workResult.feedback"></ErrorTaskReasonShow>
                            </Tooltip>
                        </div>
                    </div>
                </CheckboxGroup>
            </div>
        </div>
        <Spin fix v-if="loading">{{loadingText}}</Spin>
        <!--弹窗:图片审核-->
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
               :transition-names="[]">
            <image-audit-view
                    :taskList = "taskListCopy"
                    :index="currentIndex"
                    :taskInfo="taskInfo"
                    :timeout="timeout"
                    :needEdit="false"
                    :categoryView = "categoryInfo.view"
                    :viewType="[{
                        type: 'raw',
                        text: this.$t('tool_original_image')
                    }]"
                    :canHandleKeyboard="canHandleKeyboard"
                    @edit="showEditModal"
                    @close ="viewModal = false"
                    :showResultList="false"
                    @task-pass ="taskPass"
                    @task-reject="taskReject"
                    @task-reset="taskReset"
                    @task-setDifficult="taskSetDifficult"
                    ref="viewModal"
            ></image-audit-view>
        </Modal>

        <!--弹窗:显示编辑-->
        <Modal v-model="editModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
               @on-visible-change="editModalVisibleChange">
            <div slot="header" class="edit-modal-header">
                {{$t('tool_rotation_angle')}}
                <InputNumber v-model="rotateAngle"
                             :min="0"
                             :max="180"
                             :step="1"
                             size="small"></InputNumber>
                <ButtonGroup style="margin-right: 30px;">
                    <Button @click="rotateLeft" size="small"> {{$t('tool_left_rotate')}}</Button>
                    <Button @click="rotateRight" size="small"> {{$t('tool_right_rotate')}}</Button>
                    <Button @click="rotateOrigin" size="small"> {{$t('tool_diaplasis')}}</Button>
                </ButtonGroup>
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble="false"
                ></task-progress>
                <Poptip trigger="hover" placement="bottom">
                    <div class="task-info" style="cursor:pointer;">
                        {{taskItemInfo}}
                    </div>
                    <task-info slot="content" :taskInfo="taskItemInfoMore"/>
                </Poptip>
                <div class="edit-btn-group">
                    <!--提交-->
                    <Button class="btn-1" type="primary" size="small" @click="submitEditTask()">{{$t('tool_submit')}}</Button>
                    <!--取消-->
                    <Button class="btn-2" type="info" size="small" @click="editModal = false">{{$t('tool_cancel')}}</Button>
                </div>
            </div>
            <template-view
                    :config="templateInfo"
                    scene="execute"
                    ref="templateView"
                    v-if="editModal">
            </template-view>
            <Spin fix v-if="loading">{{loadingText}}</Spin>
        </Modal>


        <Modal v-model="rejectModal"
               :title="$t('tool_fillreject_reason')"
               @on-ok= "handleModalOnOk"
               @on-visible-change="rejectModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
            >
            <Input v-model="rejectReason" autofocus ref="rejectModalInput" @on-enter="handleModalOnOk"/>
        </Modal>

        <Modal v-model="resetModal"
               :title="$t('tool_fillreset_reason')"
               @on-ok= "handleResetModalOnOk"
               @on-visible-change="resetModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
            >
            <Input v-model="resetReason" autofocus ref="resetModalInput" @on-enter="handleResetModalOnOk"/>
        </Modal>

        <Modal
                v-model="exitfirmModal"
                :title="$t('tool_operate_tips')">
                <p>{{$t('tool_exit_review')}}</p>
            <div slot="footer">
                <Button type="text" @click="exitfirmModal = false">{{$t('tool_cancel')}}</Button>
                <Button type="error" @click="exit" :loading="loading">{{$t('tool_quit')}}</Button>
            </div>
        </Modal>
    </div>
</template>
<script>
import Vue from 'vue';
import api from '@/api';
import util from "@/libs/util";
import EventBus from '@/common/event-bus';
import TaskInfo from '../components/task-info.vue';
import auditByUser from '../components/audit-by-user.vue';
import cloneDeep from 'lodash.clonedeep';
import '@/libs/viewerjs/viewer.min.css';
import Viewer from '@/libs/viewerjs/viewer.min.js';
import dataIsValid from '../../../common/dataIsValid';
import AssetsLoader from '../../../libs/assetLoader.js';
import commonMixin from '../mixins/commom';
export default {
    name: 'audit-image-transcription',
    mixins: [commonMixin],
    props: {
        templateInfo: {
            type: Array,
            default: [],
        },
        taskList: {
            type: Array,
            required: true,
        },
        userList: {
            type: Array,
            required: true,
        },
        categoryInfo: {
            type: Object,
            required: true,
        },
        serverTime: {
            type: Number,
            required: true,
        },
        taskInfo: {
            type: Object,
            required: true,
        },
        timeout: {
            type: Number,
            required: true,
        },
        taskStat: {
            type: Object,
            required: true,
        },
        stepInfo: {
            type: Object,
            required: true,
        },
    },
    data () {
        return {
            exitfirmModal: false,
            currentIndex: 0,
            loading: true,
            submiting: false,
            taskItemInfo: '',
            taskItemInfoMore: {},
            loadingText: this.$t('tool_loading'),
            editModal: false,
            layoutType: 'raw',
            selectedTask: [],
            selectAllIsOn: false,
            isBatch: false,
            rejectReason: '',
            resetReason: '',
            rejectModal: false,
            resetModal: false,
            rejectTaskId: '',
            resetTaskId: '',
            resultInfo: [],
            currentFeedback: '',
            currentUserId: '',
            taskListCopy: [],
            dataIdsCache: {}, // 提交中或提交过的数据ID缓存 防止针对同一数据ID同时执行驳回通过重置等操作
            taskIsTimeOut: false,
            viewModal: false,
            rotateAngle: 90,
            viewer: null,
            assetLoader: null,
        };
    },
    computed: {
        canHandleKeyboard () {
            return this.viewModal && !(this.editModal || this.rejectModal || this.resetModal || this.taskIsTimeOut);
        },
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
        },
        userId () {
            return this.$store.state.user.userInfo.id;
        }
    },
    watch: {
        taskList (newV, oldV) {
            this.taskListCopy = cloneDeep(this.taskList);
            this.dataIdsCache = {};
            this.taskIsTimeOut = false;
            if (newV.length > 0 &&
               newV.every(v => {
                   return typeof v.rawImage === 'undefined'; // 区分 taskList  props传入，审核时变更
               })) {
                this.assetLoader && this.assetLoader.abort();
                this.getTaskImage(this.layoutType);
            }
            if (newV.length === 0) {
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_no_jobs'),
                    duration: 3,
                });
                this.viewModal = false;
                // EventBus.$emit('perform-fetchTask');
            }
        }
    },
    mounted () {        
        this.taskListCopy = cloneDeep(this.taskList);        
        this.dataIdsCache = {};
        this.taskIsTimeOut = false;
        if (this.taskListCopy.length === 0) {
            // 一进来就没有作业
            this.$Message.destroy();
            this.$Message.warning({
                content: this.$t('tool_no_jobs'),
                duration: 3,
            });
        }
        Vue.nextTick(() => {
            this.assetLoader && this.assetLoader.abort();
            this.getTaskImage(this.layoutType);
        });
        // EventBus.$on('submitTask', this.submitEditTask);
        EventBus.$on('task-timeout', this.setTaskTimeout);
        EventBus.$on('clear-fetchTask', this.userIdChange);
    },
    methods: {
        rotateOrigin () {
            this.viewer && this.viewer.rotateTo(0);
        },
        rotateLeft () {
            this.viewer && this.viewer.rotate(-this.rotateAngle);
        },
        rotateRight () {
            this.viewer && this.viewer.rotate(this.rotateAngle);
        },
        setTaskTimeout () {
            this.loadingText = this.$t('tool_timed_out');
            this.loading = true;
            this.taskIsTimeOut = true;
            this.editModal = false;
            this.viewModal = false;
        },
        checkTaskList () {
            if (this.taskListCopy.length === 0) {
                EventBus.$emit('perform-fetchTask');
            }
        },
        userIdChange (oldId, newId) {
            this.currentUserId = newId;
        },
        //显示编辑:弹窗
        showEditModal (index) {
            if (this.taskIsTimeOut) {
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_timed_out'),
                    duration: 2,
                });
                return;
            }
            this.editModal = true;
            this.currentIndex = index;
        },
        getTaskImage (type) {
            let requestList = this.taskListCopy.map((task) => {
                return {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: task.data.id,
                    type: 'ori',
                };
            });
            this.assetLoader = new AssetsLoader(api.task.resource, requestList, 3, (res, req) => {                
                let taskIndex = -1;
                let taskItem = null;
                this.taskListCopy.forEach((task, index) => {
                    if (task.data.id === req.data_id) {
                        taskIndex = index;
                        taskItem = task;
                    }
                });
                if (!taskItem) {
                    return;
                }
                let taskData = res.data;
                let resource = Object.keys(taskData || {});
                let src = '';
                let labelList = [];
                resource.forEach((key) => {
                    if (key === 'image_url') {
                        src = taskData[key].url;
                    } else {
                        labelList.push({
                            [key]: taskData[key]
                        });
                    }
                });
                taskItem['rawImage'] = src || '';
                taskItem['labelList'] = labelList;
                let image = new Image();
                image.onerror = () => {
                    taskItem.imageLoadError = true;
                    this.taskListCopy.splice(taskIndex, 1, taskItem);                    
                };
                image.onload = () => {
                    taskItem.imageLoadError = false;
                    this.taskListCopy.splice(taskIndex, 1, taskItem);
                };
                image.src = src;
            }, (res, req) => {
                let taskIndex = -1;
                let taskItem = null;
                this.taskListCopy.forEach((task, index) => {
                    if (task.data.id === req.data_id) {
                        taskIndex = index;
                        taskItem = task;
                    }
                });
                if (taskItem) {
                    taskItem['rawImage'] = '';
                    taskItem['labelList'] = [];
                    taskItem.imageLoadError = true;
                    this.taskListCopy.splice(taskIndex, 1, taskItem);
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_request_failed'),
                        duration: 2,
                    });
                }
            });
            this.loading = false;
        },
        batchReject () {
            this.taskListCopy.forEach((task, index) => {
                if (~this.selectedTask.indexOf(task.data.id)) {
                    setTimeout(() => {
                        this.taskReject(task.data.id, this.rejectReason);
                    }, 100 * index);
                }
            });
        },
        batchReset () {
            this.taskListCopy.forEach((task, index) => {
                if (~this.selectedTask.indexOf(task.data.id)) {
                    setTimeout(() => {
                        this.taskReset(task.data.id, this.resetReason);
                    }, 100 * index);
                }
            });
        },
        batchPass () {
            this.taskListCopy.forEach((task, index) => {
                if (~this.selectedTask.indexOf(task.data.id)) {
                    setTimeout(() => {
                        this.taskPass(task.data.id, task.parentWorkResults[task.parentWorkResults.length - 1].work_id);
                    }, 100 * index);
                }
            });
        },
        batchSelect () {
            if (this.selectAllIsOn) {
                this.selectedTask = [];
            } else {
                this.selectedTask = this.taskListCopy.map((task) => {
                    return task.data.id;
                });
            }
        },
        taskWillSetDifficult () {
            this.taskListCopy.forEach((task, index) => {
                if (~this.selectedTask.indexOf(task.data.id)) {
                    setTimeout(() => {
                        this.taskSetDifficult(task.data.id);
                    }, 100 * index);
                }
            });
        },
        taskSetDifficult (dataId) {
            if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                return;
            }
            let result = {};
            result[dataId] = {
                is_difficult: 1
            };
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                data_id: dataId,
                user_id: this.currentUserId,
                op: 'submit',
                result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.task_id || !reqData.data_id || !reqData.project_id) {
                return;
            }
            this.loading = true;
            this.dataIdsCache[dataId] = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.submiting = false;
                    this.loading = false;

                    if (res.error) {
                        delete this.dataIdsCache[dataId];
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = -1;
                        this.taskListCopy.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.taskListCopy.splice(taskIndex, 1);
                        }
                        this.checkTaskList();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        delete this.dataIdsCache[dataId];
                        this.submiting = false;
                        this.loading = false;
                    });
                }
            });
        },
        taskPass (dataId, currentId) {
            if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                return;
            }
            let result = {};
            result[dataId] = {
                verify: {
                    verify: 1,
                    feedback: '',
                    correct_work_id: currentId,
                }
            };
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                user_id: this.currentUserId,
                data_id: dataId,
                op: 'submit',
                result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                return;
            }
            this.loading = true;
            this.dataIdsCache[dataId] = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;

                    if (res.error) {
                        delete this.dataIdsCache[dataId];
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = -1;
                        this.taskListCopy.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.taskListCopy.splice(taskIndex, 1);
                        }
                        this.editModal = false;
                        this.checkTaskList();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        delete this.dataIdsCache[dataId];
                    });
                }
            });
        },
        taskWillReject (dataId, isBatch = false) {
            this.rejectModal = true;
            this.rejectTaskId = dataId;
            this.isBatch = isBatch;
        },
        taskWillReset (dataId, isBatch = false) {
            this.resetModal = true;
            this.resetTaskId = dataId;
            this.isBatch = isBatch;
        },
        handleModalOnOk () {
            if (this.loading) return;
            if (this.rejectReason.trim() === '') {
                this.$Message.destroy();
                this.$Message.error({
                    content: this.$t('tool_reason_empty'),
                    duration: 2,
                });
                return;
            }
            if (this.selectAllIsOn && this.selectedTask.length > 0 && this.isBatch) {
                this.batchReject();
            } else {
                this.taskReject(this.rejectTaskId, this.rejectReason);
            }
        },
        handleResetModalOnOk () {
            if (this.loading) return;
            if (this.resetReason.trim() === '') {
                this.$Message.destroy();
                this.$Message.error({
                    content: this.$t('tool_reason_empty'),
                    duration: 2,
                });
                return;
            }
            if (this.selectAllIsOn && this.selectedTask.length > 0 && this.isBatch) {
                this.batchReset();
            } else {
                this.taskReset(this.resetTaskId, this.resetReason);
            }
        },
        taskReject (dataId, reason) {
            if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                return;
            }
            let result = {};
            result[dataId] = {
                verify: {
                    verify: 0,
                    feedback: reason
                }
            };
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                user_id: this.currentUserId,
                data_id: dataId,
                op: 'submit',
                result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                return;
            }
            this.loading = true;
            this.dataIdsCache[dataId] = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;

                    if (res.error) {
                        delete this.dataIdsCache[dataId];
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.rejectModal = false;
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = -1;
                        this.taskListCopy.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.taskListCopy.splice(taskIndex, 1);
                        }
                        this.checkTaskList();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        delete this.dataIdsCache[dataId];
                    });
                }
            });
        },
        taskReset (dataId, reason) {
            if (this.taskIsTimeOut || this.dataIdsCache[dataId]) {
                return;
            }
            let result = {};
            result[dataId] = {
                verify: {
                    verify: 2,
                    feedback: reason
                }
            };
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                user_id: this.currentUserId,
                data_id: dataId,
                op: 'submit',
                result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                return;
            }
            this.loading = true;
            this.dataIdsCache[dataId] = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;

                    if (res.error) {
                        delete this.dataIdsCache[dataId];
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.resetModal = false;
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = -1;
                        this.taskListCopy.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        if (taskIndex > -1) {
                            this.taskListCopy.splice(taskIndex, 1);
                        }
                        this.checkTaskList();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        delete this.dataIdsCache[dataId];
                    });
                }
            });
        },
        imageViewer (dataId, index) {
            this.viewModal = true;
            this.currentIndex = index;
            this.$nextTick(() => {
                this.$refs.viewModal.init(index);
            });
        },
        rejectModalVisibleChange (flag) {
            if (flag) {
                this.rejectReason = '';
                Vue.nextTick(() => {
                    this.$refs.rejectModalInput.$el.querySelector('input').focus();
                });
            }
        },
        resetModalVisibleChange (flag) {
            if (flag) {
                this.resetReason = '';
                Vue.nextTick(() => {
                    this.$refs.resetModalInput.$el.querySelector('input').focus();
                });
            }
        },
        //功能:监测编辑弹窗是否打开
        editModalVisibleChange (flag) { // true 打开 false 关闭
            if (flag) {
                this.$nextTick(() => {
                    this.editTask(this.taskListCopy[this.currentIndex]);
                });
            }
        },
        //功能:获取当前要编辑的作业数据和作业结果数据
        editTask (task) { //至-----------------------917行结束
            if (this.taskIsTimeOut && this.loading) {
                return;
            }
            let parentWorkResult = task.parentWorkResults[0];
            let work_id = parentWorkResult && parentWorkResult.work_id;
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                user_id: this.currentUserId,
                data_id: task.data.id,
                work_id,
                op: 'edit',
            };
            if (!reqData.access_token || !reqData.data_id || !reqData.task_id || !reqData.project_id) {
                return;
            }
            this.loading = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let taskData = this.taskListCopy[this.currentIndex].data;
                        let parentWorks = this.taskListCopy[this.currentIndex].parentWorks;
                        this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                        this.taskItemInfoMore = {
                            ...this.taskInfo,
                            dataName: taskData.name,
                            dataId: taskData.id,
                            user: (parentWorks && parentWorks[0] && parentWorks[0].user) || {}
                        };
                        let result = res.data.dataResultInfo.result || res.data.dataResultInfo.ai_result;
                        let height = window.innerHeight - 68; // 68  模态框标题部分高度
                            height = height > 420 ? height : 420;
                        let container = $(this.$refs.templateView.$el);
                            container.find('[data-tpl-type="task-file-placeholder"] .instance-container').height(height).html(
                            `<img class="image-rotate" width=100% src=${task['rawImage']}>`
                            );
                        if (!this.editModal) {
                            return;
                        }
                        this.viewer = new Viewer(this.$refs.templateView.$el.getElementsByClassName('image-rotate')[0], {
                            inline: true,
                            button: false,
                            navbar: false,
                            toolbar: false,
                            title: false,
                            transition: false,
                            ready: () => {
                                $('.image-rotate').css('visibility', 'hidden');
                            }
                        });
                        EventBus.$emit('ready');
                        setTimeout(() => {
                            if (result && result.info instanceof Array) {
                                result.info.forEach((item) => {
                                    EventBus.$emit('setValue', {
                                        ...item,
                                        scope: this.$refs.templateView.$el
                                    });
                                });
                            }
                        }, 10);
                    }
                },
                error: (res) => {
                    this.loading = false;
                }
            });
        },
        getTaskResource (dataId, type, index) {
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                data_id: dataId,
                type: type,
            };
            if (!reqData.access_token || !reqData.project_id || !reqData.data_id) {
                return;
            }
            $.ajax({
                url: api.task.resource,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let taskData = res.data;
                        let resource = Object.keys(taskData || {});
                        if (resource.length === 0) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                            return;
                        }
                        let src;
                        let labelList = [];
                        resource.forEach((key) => {
                            if (key === 'image_url') {
                                src = taskData[key].url;
                            } else {
                                labelList.push({
                                    [key]: taskData[key]
                                });
                            }
                        });
                        if (!src) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                            return;
                        }
                        let task = this.taskListCopy[index];
                        task['rawImage'] = src || '_-_';
                        task['labelList'] = labelList;
                        this.taskListCopy.splice(index, 1, task);
                    }
                },
                error: (res) => {
                    this.loading = false;
                    // 错误处理
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_request_failed'),
                        duration: 2,
                    });
                    let task = this.taskListCopy[index];
                    task['labelList'] = [];
                    task['rawImage'] = '_-_';
                    this.taskListCopy.splice(index, 1, task);
                }
            });
        },
        //保存修改
        submitEditTask () {
            if (this.taskIsTimeOut) {
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_timed_out'),
                    duration: 3,
                });
                return;
            }
            if (this.loading) {
                return;
            }
            this.loading = true;
            let info = this.$refs.templateView.getGlobalData();
            let validValue = this.$refs.templateView.getDataIsValid();
            if (validValue) {
                switch (validValue.value) {
                    case dataIsValid.yes: {
                        // 判断是否有表单信息
                        let InfoHasEmpty = false;
                        InfoHasEmpty = info.filter(item => {
                            return item.type !== 'data-is-valid' && item.required;
                            // required 属性可能会undefined 其布尔值为false
                        }).some((filteredItem) => {
                            return filteredItem.value.length === 0; // 表单信息 有为空的 String or Array
                        });
                        if (info.length && InfoHasEmpty) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: this.$t('tool_required_item'),
                                duration: 2,
                            });
                            this.loading = false;
                            return;
                        }
                        break;
                    }
                    case dataIsValid.no: {
                        info = [validValue];
                        break;
                    }
                    case dataIsValid.unknown : {
                        break;
                    }
                }
            }
            let result = {
                info,
            };
            let dataId = this.taskListCopy[this.currentIndex].data.id;
            let parentWorkResult = this.taskListCopy[this.currentIndex].parentWorkResults[0];
            let work_id = parentWorkResult && parentWorkResult.work_id;
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                user_id: this.currentUserId,
                data_id: dataId,
                work_id,
                op: 'edit_submit',
                data_result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.data_id || !reqData.task_id || !reqData.project_id) {
                this.loading = false;
                return;
            }
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let parentWorkResults = this.taskListCopy[this.currentIndex].parentWorkResults;
                        this.taskPass(dataId, parentWorkResults[parentWorkResults.length - 1].work_id);
                    }
                },
                error: (res) => {
                    this.loading = false;
                }
            });
        },
        exit () {
            this.assetLoader && this.assetLoader.abort();
            this.assetLoader = null;
            this.loading = true;
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
                    let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
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
                    let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
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
        this.assetLoader && this.assetLoader.abort();
        // EventBus.$off('submitTask', this.submitEditTask);
        EventBus.$off('clear-fetchTask', this.userIdChange);
        EventBus.$off('task-timeout', this.setTaskTimeout);
    },
    components: {
        'template-view': () => import('components/template-produce'),
        'task-progress': () => import('../components/taskprogress.vue'),
        'image-audit-view': () => import('components/task-audit-view/image-audit-view'),
        'task-info': TaskInfo,
        'audit-by-user': auditByUser,
        ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue'),
    }
};
</script>
<style lang="scss">
    .edit-modal-header {
        display: flex;
        justify-content: flex-end;
        align-items: center;
        .task-info {
            margin-right: 20px;
        }
    }
    .spin-icon-load {
        animation: ani-spin 1s linear infinite;
    }

    @keyframes ani-spin {
        from { transform: rotate(0deg);}
        50%  { transform: rotate(180deg);}
        to   { transform: rotate(360deg);}
    }
    .audit-wrapper {
        position: relative;
        min-height: calc(100vh - 158px);
        .viewer-fullscreen::before {
            background-position: -260px 0;
        }
    }
    .task-audit-button {
        position: absolute;
        left: 32px;
        top: 32px;
    }
    .result-info-wrapper {
        position: absolute;
        min-width: 180px;
        max-width: 25%;
        padding: 15px;
        right: 10px;
        top: 80px;
        z-index: 0;
        background-color: rgba(255,255,255,0.8);
        border: 1px solid #eee;
        border-radius: 12px;
        font-size: 14px;
        color: #000;
        text-align: left;
        p {
            line-height: 1.5;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
            span {
                min-width: 60px;
            }
        }
        .format-text {
            white-space: pre;
        }
    }
    .audit-content > .ivu-checkbox-group {
        margin-right: 48px;
        display: flex;
        justify-content: flex-start;
        flex-wrap: wrap;
        &.small {
            .audit-item {
                flex: 0 1 19.2%;
                min-width: 180px;
            }
        }
        &.big {
            .audit-item {
                flex: 0 1 32.4%;
                min-width: 300px;
            }
        }
        &.ori {
            .audit-item {
                flex: 0 1 49%;
                min-width: 420px;
            }
        }
        &.list {
            .audit-item {
                flex: 0 1 100%;
            }
        }
    }
    .audit-item {
        margin: 10px 1px;
        border: 1px solid #eee;
        border-radius: 4px;
        position: relative;
        padding-bottom: 48px;
        background-clip: content-box;
        background-color: #f5f5f5;
        .image-loading {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
        }
        .audit-result {
            text-align: center;
            position: relative;
            padding: 4px;
            width: 100%;
            height: 100%;
            min-width: 128px;
            min-height: 128px;
        }
        .image-load-error {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            color: lightcoral;
        }

        .audit-button {
            position: absolute;
            bottom: 50%;
            width: 100%;
            display: none;
            text-align: center;
        }
        &:hover .audit-button {
            display: block;
        }
        .audit-result-image {
            width: 100%;
            min-width: 128px;
            min-height: 128px;
            cursor: zoom-in; // 放大查看
        }
        .audit-result-info {
            padding: 4px 8px;
            position: absolute;
            bottom: 0;
            width: 100%;
            .ivu-checkbox-wrapper {
                display: block;
                margin-right: 0;
            }
            .task-data-name {
                max-width: 80%;
                word-break: break-all;
            }
            .task-data-id {
                float: right;
                vertical-align: -1px;
            }
        }
    }
    .audit-side {
        position: absolute;
        right: 0;
        top: 0;
        width: 48px;
        text-align: center;
        .layout-type {
            display: block;
            cursor: pointer;
            &.active {
                color: #2d8cf0;
            }
        }
    }
    .edit-modal-wrapper {
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
</style>




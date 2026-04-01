<template>
    <div class="" style="position:relative; min-height: 100%;">
        <div class="task-header">
            <quality-info :qualityRate="qualityDataSelf" />
            <div class="task-btn-group">
                <task-progress
                    :total="0"
                    :current="0"
                    :timeout="timeout"
                ></task-progress>
                <Button type="primary" size="small"
                        @click.native="batchSelect"> {{ selectAllText }}
                </Button>
                <Button type="primary" size="small"
                    @click.native="batchPass"
                    v-if="selectedTask.length > 0" >{{$t('tool_batch_pass')}}</Button>
                <Button type="primary" size="small"
                    v-if="selectedTask.length > 0"
                    @click.native="taskWillReject('', true)" >{{$t('tool_batch_rejection')}}</Button>
                <!-- <Button type="warning" size="small"
                    v-if="selectedTask.length > 0"
                    @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}</Button> -->
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        {{$t('tool_shortcuts')}}<br>
                        <code>D </code>  {{$t('tool_approved')}}<br>
                        <code>W </code>  {{$t('tool_reject')}}<br>
                        <code>A </code>  {{$t('tool_previous')}}<br>
                        <code>S </code>  {{$t('tool_next')}} <br>
                        <code>N </code> {{$t('tool_view_mask')}} <br/>
                        <code>E </code> {{$t('tool_view_original')}} <br/>
                        <code>M </code> {{$t('tool_view_default')}}<br/>
                        <code>L </code> {{$t('tool_unlabeled_map')}}<br/>
                        <code>{{$t('tool_space')}} </code> {{$t('tool_modify')}}
                    </div>
                </Tooltip>

            </div>
        </div>
        <div class="audit-wrapper">
            <div class="audit-content"  >
                <CheckboxGroup v-model="selectedTask" class="small" v-if="taskList.length">
                    <div class="audit-item" v-for="(task, index) in taskList" :key="index">
                        <Spin size="small" class="image-loading" v-if="!(task['rawImage'] || task.imageLoadError)">
                            <Icon type="ios-loading" size=18 class="spin-icon-load"></Icon>
                            <div>Loading</div>
                        </Spin>
                        <div class="audit-result" @click="imageViewer(task.data.id, index, $event)">
                            <img v-if="!task.imageLoadError"
                                    :src="task['rawImage']"
                                    class="audit-result-image"
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
                            <!-- <Button size="small" type="warning"
                                style="margin-left:2px;"
                                @click.native="taskSetDifficult(task.data.id)">{{$t('tool_diffcult_job')}}</Button> -->
                        </div>
                        <div class="audit-result-info">
                            <Checkbox :label="task.data.id">
                                <span class="task-data-name">{{ task.data.name.length < 25 ?  task.data.name : task.data.name.slice(0, 25) + '...'}}</span>
                                <span class="task-data-id">{{ task.data.id }}</span>
                            </Checkbox>
                        </div>
                    </div>
                </CheckboxGroup>
            </div>
        </div>
        <Spin fix v-if="loading">{{loadingText}}</Spin>
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
               :transition-names="[]"
        >
            <image-audit-view
                    :taskList = "taskList"
                    :index="currentIndex"
                    :taskInfo="taskInfo"
                    :timeout="timeout"
                    :isAudit="false"
                    :needEdit="false"
                    :categoryView = "categoryInfo.view"
                    :canHandleKeyboard="canHandleKeyboard"
                    @edit="showEditModal"
                    @close ="viewModal = false"
                    @task-pass ="taskPass"
                    @task-reject="taskReject"
                    @task-setDifficult="taskSetDifficult"
                    ref="viewModal"
            ></image-audit-view>
        </Modal>
        <Modal v-model="editModal"
            :class="'edit-modal-wrapper'"
            width="100"
            style="min-height:100%"
            :closable="false"
            :mask-closable="false"
            @on-visible-change="modalVisibleChange">
            <div slot="header" class="edit-modal-header">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble="false"
                ></task-progress>
                <Poptip trigger="hover" placement="bottom">
                    <div class="task-info" style="cursor:pointer;">
                        {{ taskItemInfo }}
                    </div>
                    <task-info slot="content" :taskInfo = "taskItemInfoMore" />
                </Poptip>
                <div class="edit-btn-group">
                    <Button type="primary" size="small" @click="submitEditTask()">{{$t('tool_submit')}}</Button>
                    <Button type="info" size="small" @click="editModal = false">{{$t('tool_cancel')}}</Button>
                    <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                        <Icon type="ios-help-circle-outline" size="24"></Icon>
                        <div slot="content">
                            <code>X </code>  {{$t('tool_label_mode')}}<br>
                            <code>D </code>  {{$t('tool_two_modes')}}<br>
                            <code>G </code>  {{$t('tool_draw_point')}}<br>
                            <code>F </code>  {{$t('tool_draw_line')}}<br>
                            <code>R </code>  {{$t('tool_draw_rec')}}<br>
                            <code>T </code>  {{$t('tool_draw_polygons')}}<br>
                            <code>U </code>  {{$t('tool_curve')}}<br>
                            <code>Y </code>  {{$t('tool_draw_polyline')}}<br>
                            <code>K </code>  {{$t('tool_auxiliary_line')}}<br>
                            <code>M </code>  {{$t('tool_switch_label')}}<br>
                            <code>E </code>  {{$t('tool_press_lift')}}<br>
                            <code>C </code>  {{$t('tool_narrow_picture')}}<br>
                            <code>V </code>  {{$t('tool_zoom_picture')}}<br>
                            <code>B </code>  {{$t('tool_diaplasis')}}<br>
                            <code>< </code>  {{$t('tool_tilt_left')}} <code>shift + <</code> {{$t('tool_greatly')}}<br>
                            <code>> </code>  {{$t('tool_tilt_right')}}  <code>shift + ></code> {{$t('tool_greatly')}}<br>
                            <code>? </code>  {{$t('tool_angle_reset')}}<br>
                            <code>N </code>  {{$t('tool_polygon_share_N')}}<br>
                            <code>: </code>  {{$t('tool_side_share')}}<br>
                            <code>= </code>  {{$t('tool_rect_size')}}<br>
                            <code>A </code>  {{$t('tool_switch_mask')}}
                            <code>Alt +{{$t('tool_left_mouse')}} </code> {{$t('tool_key_point')}}<br>{{$t('tool_delete_group')}}<br>
                            <code>UP </code> <code>Down </code> {{$t('tool_switch_picture')}}<br>
                            <code>{{$t('tool_right_mouse')}} </code>  {{$t('tool_delete_selection_label')}}<br>
                            <code>Shift + {{$t('tool_right_mouse')}} </code>  {{$t('tool_delete_all_in_adjust_mode')}}
                        </div>
                    </Tooltip>
                </div>
            </div>
            <Row>
                <Col span="21">
                    <template-view
                            :config="templateInfo"
                            scene="execute"
                            ref="templateView"
                              v-if="editModal">
                    </template-view>
                </Col>
                <Col span="3">
                    <ImageLabelResultListView></ImageLabelResultListView>
                </Col>
            </Row>
            <Spin fix v-if="editLoading">{{loadingText}}</Spin>
            <span slot="footer"></span>
        </Modal>
        <Modal v-model="rejectModal"
               :title="$t('tool_fillreject_reason')"
               @on-ok= "handleModalOnOk"
               @on-visible-change="regjectModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
            >
            <Input v-model="rejectReason" autofocus ref="rejectModalInput" @on-enter="handleModalOnOk"/>
        </Modal>
    </div>
</template>
<script>
import Vue from 'vue';
import api from '@/api';
import util from "@/libs/util";
import EventBus from '@/common/event-bus';
import TaskInfo from '../components/task-info.vue';
import QualityInfo from '../components/quality-rate.vue';
import '@/libs/image-label/image-label.css';
import '@/libs/image-label/image-label.min.js';
import commonMixin from '../mixins/commom';
import imageLabelMixin from '../mixins/imageLabelMixin';
import dataIsValid from '../../../common/dataIsValid';
import AssetsLoader from '../../../libs/assetLoader.js';

export default {
    name: 'quality-image-label',
    mixins: [commonMixin, imageLabelMixin],
    props: {
        templateInfo: {
            type: Array,
            default: [],
        },
        taskList: {
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
        qualityData: {
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
            currentIndex: 0,
            loading: true,
            submiting: false,
            editLoading: false,
            taskItemInfo: '',
            taskItemInfoMore: {},
            loadingText: this.$t('tool_loading'),
            editModal: false,
            layoutType: 'ori',
            selectedTask: [],
            selectAllIsOn: false,
            currentViewType: 'raw', // mask , mark , raw
            isBatch: false,
            rejectReason: '',
            rejectModal: false,
            rejectTaskId: '',
            resultInfo: [],
            qualityDataSelf: {},
            dataIdsCache: {}, // 提交中或提交过的数据ID缓存 防止针对同一数据ID同时执行驳回通过重置等操作
            taskIsTimeOut: false,
            viewModal: false,
            imageToolConfig: {
                supportShapeType: [],
                advanceTool: []
            },
            assetLoader: null,
        };
    },
    computed: {
        canHandleKeyboard () {
            return this.viewModal && !(this.editModal || this.rejectModal || this.taskIsTimeOut);
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
        // dataId() {
        //     return this.taskList[this.currentTaskIndex].data.id;
        // }
        userId () {
            return this.$store.state.user.userInfo.id;
        },
    },
    watch: {
        taskList (newV, oldV) {
            this.dataIdsCache = {};
            this.taskIsTimeOut = false;
            if (newV.length > 0 &&
                newV.every(v => {
                    return typeof v.rawImage === 'undefined'; // 区分 taskList  props传入，审核时变更
                })) {
                this.assetLoader && this.assetLoader.abort();
                this.getTaskImage(this.currentViewType);
            }
            if (newV.length === 0) {
                // this.viewModal = false;
                EventBus.$emit('perform-fetchTask');
            }
        }
    },
    mounted () {
        Vue.nextTick(() => {
            this.getTaskImage(this.currentViewType);
        });
        this.dataIdsCache = {};
        this.taskIsTimeOut = false;
        this.setImageToolConfig(this.imageToolConfig);
        EventBus.$on('task-timeout', this.setTaskTimeout);
        EventBus.$on('submitTask', this.submitEditTask);
        EventBus.$on('ImageToolConfig', this.setImageToolConfig);
        this.qualityDataSelf = this.qualityData;
    },
    methods: {
        setImageToolConfig (config) {
            this.imageToolConfig.supportShapeType = config.supportShapeType.toString();
            // if (config.supportShapeType.length) {
            //     this.imageToolConfig.supportShapeType = config.supportShapeType.toString();
            // } else {
            //     this.imageToolConfig.supportShapeType = this.categoryInfo.draw_type;
            // }
        },
        setTaskTimeout () {
            this.loadingText = this.$t('tool_timed_out');
            this.loading = true;
            this.taskIsTimeOut = true;
        },
        showEditModal (index) {
            this.editModal = true;
            this.currentIndex = index;
        },
        getTaskImage (type) {
            let requestList = this.taskList.map((task) => {
                return {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: task.data.id,
                    type: 'ori',
                    is_show_label: 1,
                };
            });
            this.assetLoader = new AssetsLoader(api.task.resource, requestList, 3, (res, req) => {
                let resource = Object.entries(res.data || {});
                let file = resource[0][1];
                let taskIndex = -1;
                let taskItem = null;
                this.taskList.forEach((task, index) => {
                    if (task.data.id === req.data_id) {
                        taskIndex = index;
                        taskItem = task;
                    }
                });
                if (taskItem) {
                    let image = new Image();
                    taskItem[type + 'Image'] = file.url || '';
                    taskItem.currentViewType = type;
                    image.onerror = () => {
                        taskItem.imageLoadError = true;
                        this.taskList.splice(taskIndex, 1, taskItem);
                    };
                    image.onload = () => {
                        taskItem.imageLoadError = false;
                        this.taskList.splice(taskIndex, 1, taskItem);
                    };
                    image.src = file.url;
                }
            }, (res, req) => {
                let taskIndex = -1;
                let taskItem = null;
                this.taskList.forEach((task, index) => {
                    if (task.data.id === req.data_id) {
                        taskIndex = index;
                        taskItem = task;
                    }
                });
                if (taskItem) {
                    taskItem[type + 'Image'] = '';
                    taskItem.currentViewType = type;
                    taskItem.imageLoadError = true;
                    this.taskList.splice(taskIndex, 1, taskItem);
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_request_failed'),
                        duration: 2,
                    });
                }
            });
            this.loading = false;
        },
        // no use
        getTaskMarkImage (dataId, type, index) {
            let imageSrc = this.taskList[index][type + 'Image'];
            if (imageSrc && imageSrc !== '') {
                return;
            }
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                data_id: dataId,
                type: 'ori',
                is_show_label: 1,
            };
            if (!reqData.access_token || !reqData.data_id || !reqData.project_id) {
                return;
            }
            $.ajax({
                url: api.task.mark,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let task = this.taskList[index];
                        let resource = Object.entries(res.data || {});
                        if (resource.length === 0) {
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                            return;
                        }
                        task[type + 'Image'] = resource[0][1] || '';
                        task.currentViewType = type;
                        this.taskList.splice(index, 1, task);
                    }
                },
                error: (res) => {
                    this.loading = false;
                    this.$Message.error({
                        content: this.$t('tool_request_failed'),
                        duration: 2,
                    });
                    let task = this.taskList[index];
                    task[type + 'Image'] = '';
                    this.taskList.splice(index, 1, task);
                    // 错误处理
                }
            });
        },
        batchReject () {
            this.taskList.forEach((task, index) => {
                if (~this.selectedTask.indexOf(task.data.id)) {
                    setTimeout(() => {
                        this.taskReject(task.data.id, this.rejectReason);
                    }, 100 * index);
                }
            });
        },
        batchPass () {
            this.taskList.forEach((task, index) => {
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
                this.selectedTask = this.taskList.map((task) => {
                    return task.data.id;
                });
            }
        },
        taskWillSetDifficult () {
            this.taskList.forEach((task, index) => {
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
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.qualityDataSelf = res.data;
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = '';
                        this.taskList.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        this.taskList.splice(taskIndex, 1);
                        this.editModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        this.submiting = false;
                        delete this.dataIdsCache[dataId];
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
                data_id: dataId,
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
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.qualityDataSelf = res.data;
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = '';
                        this.taskList.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        this.taskList.splice(taskIndex, 1);
                        this.editModal = false;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        this.submiting = false;
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
        handleModalOnOk () {
            if (this.loading) return;
            if (this.rejectReason.trim() === '') {
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
                data_id: dataId,
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
                    this.loading = false;
                    delete this.dataIdsCache[dataId];
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        this.rejectModal = false;
                        this.qualityDataSelf = res.data;
                        let selectedIndex = this.selectedTask.indexOf(dataId);
                        if (selectedIndex !== -1) {
                            this.selectedTask.splice(selectedIndex, 1);
                        }
                        let taskIndex = '';
                        this.taskList.forEach((task, index) => {
                            if (task.data.id == dataId) {
                                taskIndex = index;
                            }
                        });
                        this.taskList.splice(taskIndex, 1);
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
        regjectModalVisibleChange (flag) {
            if (flag) {
                this.rejectReason = '';
                Vue.nextTick(() => {
                    this.$refs.rejectModalInput.$el.querySelector('input').focus();
                });
            }
        },
        modalVisibleChange (flag) { // true 打开 false 关闭
            if (flag) {
                this.editLoading = true;
                this.getTaskResource(this.taskList[this.currentIndex].data.id, 'ori');
            } else {
                this.editLoading = false;
                // window.destroyTask();
            }
        },
        getTaskResource (dataId, type) {
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                data_id: dataId,
                type: type || 'ori',
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
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let resource = Object.entries(res.data || {});
                        if (resource.length === 0) {
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                            return;
                        }
                        let file = resource[0][1];
                        this.editTask(dataId, file.url);
                    }
                },
                error: (res) => {
                    this.loading = false;
                    // 错误处理
                    this.$Message.error({
                        content: this.$t('tool_request_failed'),
                        duration: 2,
                    });
                }
            });
        },
        editTask (dataId, image_url) {
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                data_id: dataId,
                op: 'edit',
            };
            if (!reqData.access_token || !reqData.data_id || !reqData.task_id || !reqData.project_id) {
                return;
            }
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.editLoading = false;
                    if (res.error) {
                        this.$Message.error({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let taskData = this.taskList[this.currentIndex].data;
                        let parentWorks = this.taskList[this.currentIndex].parentWorks;
                        this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                        this.taskItemInfoMore = {
                            ...this.taskInfo,
                            dataName: taskData.name,
                            dataId: taskData.id,
                            user: (parentWorks && parentWorks[0] && parentWorks[0].user) || {}
                        };
                        let result = res.data.dataResultInfo.result ||
                                     res.data.dataResultInfo.ai_result;
                        // 质检的修改 已关闭
                        window.initTask({
                            EventBus,
                            user_id: this.userId,
                            server_time: this.serverTime,
                            draw_type: this.imageToolConfig.supportShapeType,
                            photo_url: image_url,
                            isAudit: false,
                            result: result
                        });
                        EventBus.$emit('ready');
                        if (result && result.info) {
                            result.info.forEach((item) => {
                                EventBus.$emit('setValue', {
                                    ...item,
                                    scope: this.$refs.templateView.$el
                                });
                            });
                        }
                    }
                },
                error: (res) => {
                    this.editLoading = false;
                }
            });
        },
        submitEditTask () {
            if (this.taskIsTimeOut) {
                this.$Message.warning({
                    content: this.$t('tool_timed_out'),
                    duration: 3,
                });
                return;
            }
            if (this.submiting) {
                return;
            }
            let data = window.canvasStage.getSubmitData();
            let info = this.$refs.templateView.getGlobalData();
            let validValue = this.$refs.templateView.getDataIsValid();
            if (validValue) {
                switch (validValue.value) {
                    case dataIsValid.yes: {
                        if (typeof data === 'string') {
                            this.$Message.warning({
                                content: this.$t('tool_undone'),
                                duration: 3,
                            });
                            return;
                        } else if (data instanceof Array && data.length === 0) { // 图片标注数据为空
                            this.$Message.warning({
                                content: this.$t('tool_result_empty'),
                                duration: 3,
                            });
                            return;
                        } else if (data instanceof Array && data.length) {
                            // 检验标签
                            if (this.checkRequiredTagGroup(data)) {
                                if (!this.checkRequiredTag(data)) {
                                    return;
                                }
                            } else {
                                return;
                            }
                            // 判断是否有表单信息
                            let InfoHasEmpty = false;
                            InfoHasEmpty = info.filter(item => {
                                return item.type !== 'data-is-valid' && item.required;
                                // required 属性可能会undefined 其布尔值为false
                            }).some((item) => {
                                return item.value.length === 0; // 表单信息 有为空的 String or Array
                            });
                            console.log(InfoHasEmpty);
                            if (info.length && InfoHasEmpty) {
                                this.$Message.warning({
                                    content: this.$t('tool_required_item'),
                                    duration: 3,
                                });
                                return;
                            }
                        }
                        break;
                    }
                    case dataIsValid.no: { // 无效的时候 清空标注数据和表单数据 保留数据有效无效的信息
                        data = [];
                        info = [validValue];
                        break;
                    }
                    case dataIsValid.unknown : { // 不确定的时候不做任何判断有结果就保存
                        break;
                    }
                }
            }
            let result = {};
            result.data = data;

            if (info.length) {
                result.info = info;
            }
            let dataId = this.taskList[this.currentIndex].data.id;
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.$route.query.project_id,
                task_id: this.$route.query.task_id,
                data_id: dataId,
                op: 'edit_submit',
                data_result: JSON.stringify(result)
            };
            if (!reqData.access_token || !reqData.data_id || !reqData.task_id || !reqData.project_id) {
                return;
            }
            this.loading = true;
            this.submiting = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    this.submiting = false;
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 2,
                        });
                    } else {
                        let parentWorkResults = this.taskList[this.currentIndex].parentWorkResults;
                        this.taskPass(dataId, parentWorkResults[parentWorkResults.length - 1].work_id);
                    }
                },
                error: (res) => {
                    this.loading = false;
                    this.submiting = false;
                }
            });
        }
    },
    beforeDestroy () {
        this.assetLoader && this.assetLoader.abort();
        EventBus.$off('submitTask', this.submitEditTask);
        EventBus.$off('task-timeout', this.setTaskTimeout);
        EventBus.$off('ImageToolConfig', this.setImageToolConfig);
        // this.viewer && this.viewer.destroy();
    },
    components: {
        'template-view': () => import('components/template-produce'),
        'task-progress': () => import('../components/taskprogress.vue'),
        'image-audit-view': () => import('components/task-audit-view/image-audit-view'),
        'task-info': TaskInfo,
        'quality-info': QualityInfo,
        ImageLabelResultListView: () =>
            import('../../../common/components/task-result-view/image-label-result-list-view.vue'),
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
        max-width: 25%;
        min-width: 180px;
        padding: 15px;
        right: 10px;
        top: 80px;
        z-index: 0;
        background-color: rgba(255,255,255,0.8);
        border: 1px solid #eee;
        border-radius: 12px;
        font-size: 14px;
        color: #000;
        p {
            line-height: 1.5;
        }
        .info-item {
            display: flex;
            justify-content: space-between;
            margin: 2px 0;
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
            max-height: 240px;
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

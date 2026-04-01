<template>
    <div class="" style="position:relative; height: 100%;">
        <div class="task-header" id="task-produce">
            <user-stat :userStat="userStat"/>
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <div class="task-btn-group">
                <task-progress
                        :total="taskList.length"
                        :current="currentTaskIndex"
                        :timeout="timeout"
                ></task-progress>
                <Button type="primary" size="small" @click="aimodel">AI标注</Button>
                <Button type="default" size="small" @click.native="saveTemp">
                    <Icon type="md-checkmark-circle-outline" :color="saveStatus?'green':'gray'"/>
                    {{saveStatus ? $t('tool_save_temp'): $t('tool_saving_temp')}}
                </Button>
                <Button type="default" size="small" @click.native="nearByTask">{{$t('tool_check_before_after_jobs')}}
                </Button>
                <Button v-if="feedback.trim() !== ''" type="error" size="small"
                        @click.native="viewFeedback">{{$t('tool_view_reject')}}
                </Button>
                <Button type="warning" size="small" @click.native="setDifficult" :disabled="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit" :disabled="loading">{{$t('tool_submit')}}
                </Button>
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        <code>X </code> {{$t('tool_label_mode')}}<br>
                        <code>D </code> {{$t('tool_two_modes')}}<br>
                        <code>G </code> {{$t('tool_curve')}}<br>
                        <code>H </code> {{$t('tool_closed_curve')}}<br>
                        <code>P </code> {{$t('tool_line')}}<br>
                        <code>R </code> {{$t('tool_rectangle')}}<br>
                        <code>T </code> {{$t('tool_polygon_frame')}}<br>
                        <code>U</code>  {{$t('tool_quadrilateral')}}<br>
                        <code>F</code> {{$t('tool_polyline')}}<br>
                        <code>Y</code>  {{$t('tool_cuboid')}}<br>
                        <code>I</code> {{$t('tool_trapezoid')}}<br>
                        <code>O</code> {{$t('tool_triangle')}}<br>
                        <code>J</code> {{$t('tool_select_point')}}<br>
                        <code>K </code> {{$t('tool_auxiliary_line')}}<br>
                        <code>M </code> {{$t('tool_switch_label')}}<br>
                        <code>E </code> {{$t('tool_press_lift')}}<br>
                        <code>C </code> {{$t('tool_narrow_picture')}}<br>
                        <code>V </code> {{$t('tool_zoom_picture')}}<br>
                        <code>B </code> {{$t('tool_diaplasis')}}<br>
                        <code>< </code> {{$t('tool_tilt_left')}} <code>shift + <</code> {{$t('tool_greatly')}}<br>
                        <code>> </code> {{$t('tool_tilt_right')}} <code>shift + ></code> {{$t('tool_greatly')}}<br>
                        <code>? </code> {{$t('tool_angle_reset')}}<br>
                        <code>N </code> {{$t('tool_polygon_share_N')}}<br>
                        <code>: </code> {{$t('tool_side_share')}}<br>
                        <code>= </code> {{$t('tool_rect_size')}}<br>
                        <code>A </code> {{$t('tool_switch_mask')}}<br>
                        <code> ESC </code> {{$t('tool_cancel_mark')}}<br>
                        <code>Alt +{{$t('tool_left_mouse')}} </code> <br>&nbsp;&nbsp;{{$t('tool_key_point')}}<br>&nbsp;&nbsp;{{$t('tool_delete_group')}}<br>
                        <code>UP </code> <code>Down </code> {{$t('tool_switch_picture')}}<br>
                        <code>{{$t('tool_right_mouse')}} </code> {{$t('tool_delete_selection_label')}}<br>
                        <code>Shift + {{$t('tool_right_mouse')}} </code> {{$t('tool_delete_all_in_adjust_mode')}}
                    </div>
                </Tooltip>
            </div>
        </div>
        <Row>
            <i-col span="21">
                <template-view
                        :config="templateInfo"
                        scene="execute"
                        ref="templateView">
                </template-view>
            </i-col>
            <i-col span="3">
                <ImageLabelResultListView></ImageLabelResultListView>
            </i-col>
        </Row>
        <Spin fix v-if="loading">
            <!-- <Icon type="ios-loading" size="66" class="demo-spin-icon-load"></Icon> -->
            <div>{{loadingText}}</div>
        </Spin>
        <Modal v-model="feedbackModal"
               :title="$t('tool_reject_reason')">
            <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
        </Modal>
        <Modal v-model="nearbyTaskModal"
               class="modal-view-task-nums"
               :closable="false"
               :title="$t('tool_check_before_after_jobs')"
               width="60%"
        >
            <div slot="header" style="text-align:right">
                <Button @click="nearbyTaskModal = false">{{$t('tool_close')}}</Button>
            </div>
            <div>
                <showNearbyImage :nearbyList="nearbyList" ref="NearbyImage"></showNearbyImage>
            </div>
        </Modal>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from "@/libs/util";
    import TemplateView from 'components/template-produce';
    import '@/libs/image-label/image-label.css';
    import '@/libs/image-label/image-label.min.js';
    import taskProgress from '../components/taskprogress.vue';
    import TaskInfo from '../components/task-info.vue';
    import UserStat from '../components/user-stat.vue';
    import EventBus from '@/common/event-bus';
    import showNearbyImage from '../components/show-nearby-image.vue';
    import commonMixin from '../mixins/commom';
    import imageLabelMixin from '../mixins/imageLabelMixin';
    import dataIsValid from '../../../common/dataIsValid';
    import AssetsLoader from '../../../libs/assetLoader.js';
    import AutoSave from '../../../libs/autosave.js';
    let ImageLabelInstance = null;
    export default {
        name: 'produce-image-label',
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
                nearbyTaskModal: false,
                currentTaskIndex: 0,
                loading: true,
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                feedback: '',
                feedbackModal: false,
                nearbyList: {
                    after: [],
                    before: []
                },
                workedTaskId: {},
                imageList: {},
                isReadySubmit: false,
                imageToolConfig: {
                    supportShapeType: [],
                    advanceTool: []
                },
                assetLoader: null,
                autoSave: null,
                saveStatus: true,
                resultList:[],
            };
        },
        computed: {
            dataId () {
                return this.taskList[this.currentTaskIndex].data.id;
            },
            userId () {
                return this.$store.state.user.userInfo.id;
            }
        },
        watch: {
            taskList () {
                this.getTaskResource(this.dataId);
                this.imageList = {};
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            Vue.nextTick(() => {
                this.getTaskResource(this.dataId);
            });
            this.setImageToolConfig(this.imageToolConfig);
            this.workedTaskId = {};
            this.userStat = this.taskStat;
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('submitTask', this.saveTaskResult);
            EventBus.$on('ImageToolConfig', this.setImageToolConfig);
            window.addEventListener('keydown', this.keydownHandle);
            EventBus.$on('renderResultList', this.updateResultList);


        },
        methods: {
            aimodel(){
              if(this.resultList.length){
                this.$Message.warning({
                  content: '请清空标注列表后使用AI功能',
                  duration: 2,
                });
                return;
              }
              if (this.autoSave) {
                this.autoSave.destroy();
                this.autoSave = null;
              }
              this.loading = true;
              $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                  access_token: this.$store.state.user.userInfo.accessToken,
                  project_id: this.$route.query.project_id,
                  task_id: this.$route.query.task_id,
                  data_id: this.dataId,
                  op: 'aimodel',
                  aimodel_name:'image/BaiduMultiObjectDetect'
                },
                success: (res) => {
                  this.loading = false;
                  if (res.error) {

                    EventBus.$emit('needConfirmLeave', false);
                    this.$Message.destroy();
                    this.$Message.error({
                      content: res.message,
                      duration: 2,
                    });
                    // 错误处理
                  } else {
                    console.log(this.taskList[this.currentTaskIndex],res,'this.taskList[this.currentTaskIndex]')
                    this.taskList[this.currentTaskIndex].workResult.result = res.data[this.dataId];
                    console.log(this.taskList[this.currentTaskIndex],'this.taskList[this.currentTaskIndex]')
                    let result = this.prepareResult(this.taskList[this.currentTaskIndex]);


                    if (ImageLabelInstance) {
                      ImageLabelInstance.destroy();
                    }
                    ImageLabelInstance = new window.ImageLabel({
                      viewMode: false,
                      EventBus,
                      container: document.querySelector('[data-tpl-type="task-file-placeholder"]'),
                      user_id: this.userId,
                      step: this.taskInfo.step_id,
                      server_time: this.serverTime,
                      draw_type: this.imageToolConfig.supportShapeType,
                      photo_url: this.imageList[this.dataId].url,
                      orientation: this.imageList[this.dataId].rotate_orientation, // 图片旋转角度
                      isAudit: false,
                      result: result,
                    });
                    ImageLabelInstance.setLang(this.$store.state.app.lang);

                    EventBus.$emit('ready');
                    EventBus.$emit('renderResultList', {
                      resultList:res.data[this.dataId].data
                    });
                  }
                },
                error: () => {
                  EventBus.$emit('needConfirmLeave', false);
                  this.loading = false;
                  this.$Message.destroy();
                  this.$Message.error({
                    content: this.$t('tool_failed'),
                    duration: 2,
                  });
                }
              });
            },
            updateResultList(payload){
              this.resultList = payload.resultList || payload;
            },
            keydownHandle (e) {
                let keyCode = e.keyCode || e.which;
                if (keyCode === 83 && e.ctrlKey) { // ctrl + s 保存
                    e.preventDefault();
                    this.saveTemp();
                }
            },
            saveTemp () {
                if (!(this.loading && !this.autoSave)) {
                    this.autoSave.save();
                }
            },
            setImageToolConfig (config) {
                this.imageToolConfig.supportShapeType = config.supportShapeType.toString();
            },
            preloadTaskResource () {
                let [first, ...last] = this.taskList;
                if (last && last.length < 1) {
                    return;
                }
                let requestList = last.map((task) => {
                    return {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        data_id: task.data.id,
                        type: 'ori',
                    };
                });
                // 作业文件加载器 接口地址, 请求数据参数信息, 一次加载几个 单个成功回调
                this.assetLoader = new AssetsLoader(api.task.resource, requestList, 1, (res, req) => {
                    let resource = Object.entries(res.data || {});
                    if (resource.length === 0) {
                        return;
                    }
                    let file = resource[0][1];
                    // {
                    //     size: 3434,
                    //     type: 'image',
                    //     url: ''
                    // }
                    this.imageList[req.data_id] = file;
                    let image = new Image();
                    image.src = file.url;
                }, (res, req) => {
                });
            },
            nearByTask () {
                this.$refs.NearbyImage.abort();
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    op: 'nearby',
                    data_id: this.dataId,
                    count: 10
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                    return;
                }
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.nearbyList = res.data;
                            this.nearbyTaskModal = true;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            viewFeedback () {
                this.feedbackModal = true;
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.$Modal.remove();
                this.autoSave && this.autoSave.destroy();
                this.autoSave = null;
            },
            getTaskResource (dataId) {
                let taskItemData = this.taskList[this.currentTaskIndex].data;
                this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                this.taskItemInfoMore = {
                    ...this.taskInfo,
                    dataId: taskItemData.id,
                    dataName: taskItemData.name,
                };
                if (this.imageList[dataId]) {
                    this.executeTask(dataId, this.imageList[dataId]);
                    return;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    data_id: dataId,
                    type: 'ori',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.data_id) {
                    return;
                }
                this.loading = true;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        if (res.error) {
                            this.loading = false;
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                this.loading = false;
                                this.$Message.destroy();
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            let file = resource[0][1];
                            this.imageList[dataId] = file;
                            this.executeTask(dataId, file);
                            // 第一个加载完成开始加载后边的图片资源
                            if (this.currentTaskIndex === 0) {
                                this.preloadTaskResource();
                            }
                            // if (src.slice(0, 4) === 'data') { // base 64
                            //     if (/^data:image/.test(src)) { // 确定是图片
                            //         this.imageList[dataId] = src;
                            //         this.executeTask(dataId, src);
                            //     } else {
                            //         this.loading = false;
                            //         this.$Message.destroy();
                            //         this.$Message.error({
                            //             content: '作业资源不是图片',
                            //             duration: 2,
                            //         });
                            //     }
                            // } else { // http(s) 链接
                            //     this.imageList[dataId] = src;
                            //     this.executeTask(dataId, src);
                            // }
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            executeTask (dataId, image) {
                if (this.autoSave) {
                    this.autoSave.destroy();
                    this.autoSave = null;
                }
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        data_id: dataId,
                        op: 'execute',
                    },
                    success: (res) => {
                        if (res.error) {
                            this.loading = false;
                            EventBus.$emit('needConfirmLeave', false);
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                            // 错误处理
                        } else {
                            this.timeout = res.data.timeout;
                            let result = this.prepareResult(this.taskList[this.currentTaskIndex]);
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
                            if (ImageLabelInstance) {
                                ImageLabelInstance.destroy();
                            }
                            ImageLabelInstance = new window.ImageLabel({
                                viewMode: false,
                                EventBus,
                                container: document.querySelector('[data-tpl-type="task-file-placeholder"]'),
                                user_id: this.userId,
                                step: this.taskInfo.step_id,
                                server_time: this.serverTime,
                                draw_type: this.imageToolConfig.supportShapeType,
                                photo_url: image.url,
                                orientation: image.rotate_orientation, // 图片旋转角度
                                isAudit: false,
                                result: result,
                            });
                            ImageLabelInstance.setLang(this.$store.state.app.lang);
                            ImageLabelInstance.Stage.on('ready', () => {
                                this.loading = false;
                                this.isReadySubmit = true;
                                this.autoSave = new AutoSave({
                                    saveUrl: api.task.execute,
                                    data: () => {
                                        let data = ImageLabelInstance.getSubmitData(false); // false  不过滤结果
                                        let info = this.$refs.templateView.getGlobalData();
                                        let result = {};
                                        if (typeof data === 'string') {
                                            return void 0;
                                        } else {
                                            result = {
                                                data,
                                            };
                                        }
                                        if (info.length) {
                                            result.info = info;
                                        }
                                        return {
                                            access_token: this.$store.state.user.userInfo.accessToken,
                                            project_id: this.$route.query.project_id,
                                            task_id: this.$route.query.task_id,
                                            data_id: dataId,
                                            work_result: JSON.stringify(result),
                                            op: 'temporary_storage',
                                        };
                                    }
                                });
                                this.autoSave.on('beforeSave', () => {
                                    this.saveStatus = false;
                                });
                                this.autoSave.on('save', () => {
                                    this.saveStatus = true;
                                });
                                this.autoSave.on('error', () => {

                                });
                            });
                            ImageLabelInstance.Stage.on('image.error', () => {
                                this.loading = false;
                                if (ImageLabelInstance.Stage) {
                                    this.$Modal.error({
                                        name: 'errorModal',
                                        title: this.$t('tool_resource_failed'),
                                        content: this.$t('tool_resource_failed_tips'),
                                        onOk: () => {
                                            this.isReadySubmit = true;
                                        }
                                    });
                                    this.isReadySubmit = false;
                                }
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
                            EventBus.$emit('needConfirmLeave', true);
                        }
                    },
                    error: () => {
                        EventBus.$emit('needConfirmLeave', false);
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_failed'),
                            duration: 2,
                        });
                    }
                });
            },
            saveTaskResult (next = true, isDifficult = 0) { // isDifficult 是否挂起作业 执行时挂起作业不对结果进行校验
                let dataId = this.dataId;
                if (this.loading || !this.isReadySubmit) {
                    return;
                }
                if (this.workedTaskId[dataId]) {
                    return
                }
                let data = [];
                if (this.imageList[dataId]) { // 有图片加载才去获取工具提供的数据,否则为空
                    data = ImageLabelInstance.getSubmitData();
                }
                let info = this.$refs.templateView.getGlobalData();
                if (typeof data === 'string') {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_undone'),
                        duration: 3,
                    });
                    return;
                }
                let validValue = this.$refs.templateView.getDataIsValid();
                if (validValue && isDifficult === 0) {
                    switch (validValue.value) {
                        case dataIsValid.yes: {
                            if (data instanceof Array && data.length === 0) { // 图片标注数据为空
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: this.$t('tool_result_empty'),
                                    duration: 3,
                                });
                                return;
                            } else if (data instanceof Array && data.length) { // 有标注数据, 检测表单数据
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
                                if (info.length && InfoHasEmpty) {
                                    this.$Message.destroy();
                                    this.$Message.warning({
                                        content: this.$t('tool_required_item'),
                                        duration: 3,
                                    });
                                    return;
                                }
                            }
                            break;
                        }
                        case dataIsValid.no: {
                            data = [];
                            info = [validValue];
                            break;
                        }
                        case dataIsValid.unknown : {
                            break;
                        }
                    }
                }
                let result = {};
                result[dataId] = {
                    data,
                    is_difficult: isDifficult,
                };
                if (info.length) {
                    result[dataId].info = info;
                }
                this.loading = true;
                this.isReadySubmit = false;
                this.workedTaskId[dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        data_id: dataId,
                        result: JSON.stringify(result),
                        op: 'submit',
                    },
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            this.isReadySubmit = true;
                            delete this.workedTaskId[dataId];
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                            // 错误处理
                        } else {
                            this.$Message.destroy();
                            this.$Message.success({
                                content: this.$t('tool_submit_success'),
                                duration: 1,
                            });
                            let stat = res.data[dataId];
                            let label_count = Number(this.userStat.label_count) + Number(stat.label_count);
                            let point_count = Number(this.userStat.point_count) + Number(stat.point_count);
                            let work_count = Number(this.userStat.work_count) + Number(stat.work_count);
                            this.userStat = {
                                label_count,
                                point_count,
                                work_count
                            };
                            if (next) {
                                if (this.currentTaskIndex === this.taskList.length - 1) {
                                    this.currentTaskIndex = 0;
                                    EventBus.$emit('perform-fetchTask');
                                } else {
                                    this.currentTaskIndex++;
                                    this.getTaskResource(this.dataId);
                                }
                            } else {
                                this.clearTask();
                            }
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        delete this.workedTaskId[dataId];
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                            this.isReadySubmit = true;
                        });
                    }
                });
            },
            setDifficult () {
                this.saveTaskResult(true, 1);
            },
            submit () {
                this.saveTaskResult(true, 0);
            },
            submitAndExit () {
                if (this.taskList.length - this.currentTaskIndex - 1) {
                    this.$Modal.confirm({
                        title: this.$t('tool_submitexit_confirmation'),
                        content: this.$t('tool_submit_exit_description', {num: this.taskList.length - this.currentTaskIndex}),
                        okText: this.$t('tool_submit_exit'),
                        cancelText: this.$t('tool_cancel'),
                        loading: true,
                        onOk: () => {
                            this.saveTaskResult(false, 0);
                            setTimeout(() => { // 连续点击时 remove会有bug
                                this.$Modal.remove();
                            }, 150);
                        }
                    });
                } else {
                    this.saveTaskResult(false, 0);
                }
            },
            exit () {
                this.$Modal.confirm({
                    title: this.$t('tool_exit_confirmation'),
                    content: this.$t('tool_exit_description', {num: this.taskList.length - this.currentTaskIndex}),
                    loading: true,
                    onOk: () => {
                        this.clearTask();
                    }
                });
            },
            clearTask () {
                this.loading = true;
                this.assetLoader && this.assetLoader.abort();
                this.assetLoader = null;
                EventBus.$emit('needConfirmLeave', false);
                $.ajax({
                    url: api.task.execute,
                    type: 'POST',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.query.project_id,
                        task_id: this.$route.query.task_id,
                        op: 'clear',
                    },
                    success: () => {
                        this.loading = false;
                        this.$Modal.remove();
                        this.$store.commit('removeTag', 'perform-task');
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                        // this.$router.push({
                        //     name: 'my-task'
                        // });
                    },
                    error: () => {
                        this.loading = false;
                        this.$Modal.remove();
                        this.$store.commit('removeTag', 'perform-task');
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                        // this.$router.push({
                        //     name: 'my-task'
                        // });
                    }
                });
            }
        },
        beforeDestroy () {
            this.assetLoader && this.assetLoader.abort();
            this.autoSave && this.autoSave.destroy();
            this.autoSave = null;
            EventBus.$off('submitTask', this.saveTaskResult);
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('ImageToolConfig', this.setImageToolConfig);
            ImageLabelInstance && ImageLabelInstance.destroy();
            ImageLabelInstance = null;
            window.removeEventListener('keydown', this.keydownHandle);
            this.$Modal.remove();
          EventBus.$off('renderResultList', this.updateResultList);
        },
        components: {
            'template-view': TemplateView,
            'task-progress': taskProgress,
            'task-info': TaskInfo,
            'user-stat': UserStat,
            'showNearbyImage': showNearbyImage,
            ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue'),
            ImageLabelResultListView: () =>
                import('../../../common/components/task-result-view/image-label-result-list-view.vue'),
        }
    };
</script>
<style lang="scss">
</style>




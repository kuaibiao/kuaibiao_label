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
                <Button type="default" size="small" @click.native="saveTemp" >
                    <Icon type="md-checkmark-circle-outline" :color="saveStatus?'green':'gray'"/>
                    {{saveStatus ? $t('tool_save_temp'): $t('tool_saving_temp')}}
                </Button>
                <Button v-if="feedback.trim() !== ''" type="error" size="small"
                        @click.native="viewFeedback">{{$t('tool_view_reject')}}
                </Button>
                <Button type="warning" size="small" @click.native="setDifficult" :disabled="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit" :disabled="loading">
                    {{$t('tool_submit_exit')}}
                </Button>
                <Button type="primary" size="small" @click.native="submit" :disabled="loading">{{$t('tool_submit')}}
                </Button>
            </div>
        </div>
        <Row>
            <Col span="21">
                <template-view
                        :config="templateInfo"
                        scene="execute"
                        ref="templateView">
                </template-view>
            </Col>
            <Col span="3">
                <ImageLabelResultListView></ImageLabelResultListView>
            </Col>
        </Row>
        <Spin fix v-if="loading">
            <div>{{loadingText}}</div>
        </Spin>
        <Modal v-model="feedbackModal"
               :title="$t('tool_reject_reason')">
            <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
        </Modal>
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from "@/libs/util";
    import PQueue from 'p-queue';
    import TemplateView from 'components/template-produce';
    import taskProgress from '../components/taskprogress.vue';
    import TaskInfo from '../components/task-info.vue';
    import UserStat from '../components/user-stat.vue';
    import EventBus from '@/common/event-bus';
    import commonMixin from '../mixins/commom';
    import imageLabelMixin from '../mixins/imageLabelMixin';
    import dataIsValid from '../../../common/dataIsValid';
    import AutoSave, { saveAsFile } from '../../../libs/autosave.js';
    import PointCloudComponent from '../../../common/point-cloud/point-cloud';
    import PointCloudSegmentComponent from '../../../common/point-cloud/pointcloud-segment';
    let PointCloudCtor = null;
    export default {
        name: 'produce-pointcloud-3d',
        mixins: [commonMixin, imageLabelMixin],
        pointCloud: null,
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
                currentTaskIndex: 0,
                loading: true,
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                feedback: '',
                feedbackModal: false,
                workedTaskId: {},
                isReadySubmit: false,
                autoSave: null,
                saveStatus: true,
            };
        },
        computed: {
            dataId () {
                return this.taskList[this.currentTaskIndex].data.id;
            },
            userId () {
                return this.$store.state.user.userInfo.id;
            },
            isPointCloudSegment () {
                return this.categoryInfo.view === 'pointcloud_segment';
            }
        },
        watch: {
            categoryInfo () {
                switch(this.categoryInfo.view) {
                    case '3d_pointcloud' : {
                        PointCloudCtor = Vue.extend(PointCloudComponent);
                        break;
                    }
                    case 'pointcloud_segment': {
                        PointCloudCtor = Vue.extend(PointCloudSegmentComponent);
                        break;
                    }
                }
            },
            taskList () {
                this.getTaskResource(this.dataId);
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            if (this.categoryInfo) {
                switch(this.categoryInfo.view) {
                    case '3d_pointcloud' : {
                        PointCloudCtor = Vue.extend(PointCloudComponent);
                        break;
                    }
                    case 'pointcloud_segment': {
                        PointCloudCtor = Vue.extend(PointCloudSegmentComponent);
                        break;
                    }
                }
            }
            Vue.nextTick(() => {
                this.getTaskResource(this.dataId);
            });
            this.loading = false;
            this.workedTaskId = {};
            this.userStat = this.taskStat;
            EventBus.$on('task-timeout', this.setTaskTimeout);
            window.addEventListener('keydown', this.keydownHandle);
        },
        methods: {
            keydownHandle (e) {
                let keyCode = e.keyCode || e.which;
                if (keyCode === 83 && e.ctrlKey) { // ctrl + s 保存
                    e.preventDefault();
                    this.saveTemp();
                }
            },
            viewFeedback () {
                this.feedbackModal = true;
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.$Modal.remove();
            },
            getTaskResource (dataId) {
                let taskItemData = this.taskList[this.currentTaskIndex].data;
                this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                this.taskItemInfoMore = {
                    ...this.taskInfo,
                    dataId: taskItemData.id,
                    dataName: taskItemData.name,
                };
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
                            this.executeTask(dataId, res.data);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            saveTemp () {
                if (!(this.loading && !this.autoSave)) {
                    this.autoSave.save();
                }
            },
            executeTask (dataId, urls) {
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
                            if (this.isPointCloudSegment) {
                                let data = result.data || [];
                                // 队列式 加载
                                let queue = new PQueue({
                                    concurrency: 2,
                                });
                                data.forEach((json, index) => {
                                    queue.add(function () {
                                            return new Promise((resolve, reject) => {
                                                $.getJSON(json.indexs).done((res) => {
                                                    result.data[index].indexs = res;
                                                    resolve({index: index, indexs: res});
                                                }).fail((res) => {
                                                    reject(json.indexs + 'load error');
                                                })
                                        })
                                    })
                                });
                                queue.start();
                                let onIdle = queue.onIdle();
                                onIdle.then(() => {
                                    this._initTask(urls, result, dataId);
                                })
                            } else {
                                this._initTask(urls, result, dataId);
                            }
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
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
            _initTask(urls, result, dataId) {
                this.initPointCloud(urls, result.data || [], dataId);
                this.isReadySubmit = true;
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
            },
            initPointCloud (urls, result, dataId) {
                let container = this.$refs.templateView.$el.querySelector('[data-tpl-type="task-file-placeholder"]');

                if (container) {
                    container = container.firstElementChild;
                }
                if (!this.pointCloud) {
                    this.pointCloud = new PointCloudCtor({
                        parent: this
                    });
                    this.pointCloud.$mount(container);
                }
                // let src = res.data['3d_url'];
                let pcdUrl = urls['3d_url'].url;
                let image2dList = [];
                let cameraConfigUrl = urls['camera_config'];
                // todo
                let groundOffset = urls['groundOffset']
                let labelRangeRaidus = urls['labelRangeRaidus'];
                if (cameraConfigUrl && typeof cameraConfigUrl.url === 'string') {
                    $.getJSON(cameraConfigUrl.url, (cameraConfig) => {
                        Object.keys(urls).map((key) => {
                            if (key.indexOf('3d_img') === 0) {
                                image2dList.push({
                                    ...cameraConfig[key],
                                    url: urls[key].url,
                                });
                            }
                        });
                        this.$nextTick(() => {
                            this.pointCloud.init({
                                allowEditing: true,
                                src: pcdUrl,
                                result,
                                image2dList,
                                groundOffset,
                                labelRangeRaidus
                            });
                        });
                    });
                } else {
                    this.$nextTick(() => {
                        this.pointCloud.init({
                            allowEditing: true,
                            src: pcdUrl,
                            result,
                            image2dList,
                            groundOffset,
                            labelRangeRaidus
                        });
                    });
                }
                this.pointCloud.$on('progress', (e) => {
                    this.loadingText = this.$t('tool_loading') + e.message.toFixed(2) + '%';
                });
                this.pointCloud.$on('ready', () => {
                    this.loading = false;
                    this.loadingText = this.$t('tool_loading')
                });
                this.pointCloud.$on('error', () => {
                    this.loading = false;
                    this.loadingText = this.$t('tool_loading')
                });
                let option = {};
                if (this.isPointCloudSegment) {
                    option = {
                        saveType: 'file',
                        timewait: 3 * 60 * 1000,
                    }
                }
                this.autoSave = new AutoSave({
                    ...option,
                    saveUrl: api.task.execute,
                    project_id: this.$route.query.project_id,
                    access_token: this.$store.state.user.userInfo.accessToken,
                    dataId: dataId,
                    data: () => {
                        let data = this.pointCloud.getResult(true);
                        let info = this.$refs.templateView.getGlobalData();
                        let result = {
                            data,
                        };
                        if (info.length) {
                            result.info = info;
                        }
                        return {
                            access_token: this.$store.state.user.userInfo.accessToken,
                            project_id: this.$route.query.project_id,
                            task_id: this.$route.query.task_id,
                            data_id: dataId,
                            work_result: this.isPointCloudSegment ? result : JSON.stringify(result),
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
            },
            saveTaskResult (next = true, isDifficult = 0) { // isDifficult 是否挂起作业 执行时挂起作业不对结果进行校验
                let dataId = this.dataId;
                if (this.loading || !this.isReadySubmit) {
                    return;
                }
                if (this.workedTaskId[dataId]) {
                    return;
                }
                let data = [];
                if (this.pointCloud) {
                    data = this.pointCloud.getResult(true);
                }
                let info = this.$refs.templateView.getGlobalData();
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
                                // if (this.checkRequiredTagGroup(data)) {
                                //     if (!this.checkRequiredTag(data)) {
                                //         return;
                                //     }
                                // } else {
                                //     return;
                                // }
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
                if (this.isPointCloudSegment) {
                    let queue = new PQueue({
                        concurrency: 2,
                    });
                    data.forEach((json, index) => {
                        queue.add( () => {
                            return saveAsFile(JSON.stringify(json.indexs), index, data, {
                                    project_id: this.$route.query.project_id,
                                    dataId: dataId,
                                    access_token: this.$store.state.user.userInfo.accessToken
                                });
                        })
                    })
                    queue.start();
                    let onIdle = queue.onIdle();
                    onIdle.then(() => {
                        if (data.some((item) => {
                            return item.indexs === '';
                        })) {
                            this.$Message.destroy();
                            this.$Message.success({
                                content: this.$t('tool_failed'),
                                duration: 1,
                            });
                            this.loading = false;
                            this.isReadySubmit = true;
                            return;
                        }
                        this._saveResult(result, dataId, next)
                    });
                } else {
                    this._saveResult(result, dataId, next)
                }
            },
            _saveResult(result, dataId, next) {
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
                        this.isReadySubmit = true;
                        if (res.error) {
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
            this.autoSave && this.autoSave.destroy();
            this.pointCloud && this.pointCloud.$destroy();
            window.removeEventListener('keydown', this.keydownHandle);
            this.$Modal.remove();
        },
        components: {
            'template-view': TemplateView,
            'task-progress': taskProgress,
            'task-info': TaskInfo,
            'user-stat': UserStat,
            ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue'),
            ImageLabelResultListView: () =>
                import('../../../common/components/task-result-view/image-label-result-list-view.vue'),
        }
    };
</script>
<style lang="scss">
</style>




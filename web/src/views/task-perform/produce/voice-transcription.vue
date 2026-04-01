<template>
    <div class="voice-transcription" style="position:relative; height: 100%;" >
        <div class="task-header">
            <user-stat :userStat = "userStat" />
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{taskItemInfo}}
                </div>
                <task-info slot="content" :taskInfo = "taskItemInfoMore" />
            </Poptip>
            <div class="task-btn-group">
                <task-progress
                        :total="taskList.length"
                        :current="currentTaskIndex"
                        :timeout="ptimeout"
                ></task-progress>
                <Button type="primary" size="small" @click="aimodel">AI标注</Button>
                <Button type="default" size="small" @click.native="saveTemp">
                    <Icon type="md-checkmark-circle-outline" :color="saveStatus?'green':'gray'"/>
                    {{saveStatus ? $t('tool_save_temp'): $t('tool_saving_temp')}}
                </Button>
                <Button v-if="feedback.trim() !== ''" type="error" size="small"
                        @click.native="viewFeedback">{{$t('tool_view_reject')}}</Button>
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}
                </Button>
            </div>
        </div>
        <template-view
                :config="templateInfo"
                scene="execute"
                ref="templateView">
        </template-view>
        <Spin fix v-if="loading">
            <!-- {{loadingText}} -->
            <Progress style="width: 300px" :percent="Math.floor($store.state.app.getBase64Process)" status="active" />
            <p>{{loadingText}}</p>
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
    import TemplateView from 'components/template-produce';
    import taskProgress from '../components/taskprogress.vue';
    import TaskInfo from '../components/task-info.vue';
    import EventBus from '@/common/event-bus';
    import UserStat from '../components/user-stat.vue';
    import dataIsValid from '../../../common/dataIsValid'; // 数据清洗结果类型 Yes No Unknown
    import AudioSegmentComponent from '../../../common/audio-segment/audio-segment';
    import commonMixin from '../mixins/commom';
    import AutoSave from '../../../libs/autosave.js';
    import cloneDeep from "lodash.clonedeep";

    const AudioSegmentCtor = Vue.extend(AudioSegmentComponent);
    export default {
        name: 'produce-audio-translate',
        mixins: [commonMixin],
        audioSegment: null,
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
                ptimeout: 600,
                currentTaskIndex: 0,
                loading: false,
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                clientTime: Math.floor(new Date().valueOf() / 1000),
                totalTime: 0,
                isReady: false,
                feedback: '',
                feedbackModal: false,
                workedTaskId: {},
                autoSave: null,
                saveStatus: true,
                voice:'',
              segments:[],
            };
        },
        computed: {
            dataId () {
                return this.taskList[this.currentTaskIndex].data.id;
            },
            userId () {
                return this.$store.state.user.userInfo.id;
            },
        },
        watch: {
            timeout (v) {
                this.ptimeout = v;
            },
            taskList () {
                this.getTaskResource(this.dataId);
            },
            taskStat (value) {
                this.userStat = value;
            }
        },
        mounted () {
            Vue.nextTick(() => {
                this.getTaskResource(this.dataId);
            });
            this.workedTaskId = {};
            this.userStat = this.taskStat;
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('formElementChange', this.saveRegionInfo);
            this.handleKeyUp = this.handleKeyUp.bind(this);
            this.handleKeyDown = this.handleKeyDown.bind(this);
            $(window).on('keyup', this.handleKeyUp);
            $(window).on('keydown', this.handleKeyDown);
        },
        methods: {
          aimodel(){
            if(this.audioSegment){
              this.segments = cloneDeep(this.audioSegment.getSegments());
            }

            if(this.segments.length){
              this.$Message.warning({
                content: '请清空标注列表后使用AI功能',
                duration: 2,
              });
              return;
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
                //aimodel_name:'audio/BaiduAudio'
                aimodel_name:'audio/AliAudio'
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
                  // if(this.taskList[this.currentTaskIndex].workResult.result){
                  //   this.taskList[this.currentTaskIndex].workResult.result = JSON.stringify(res.data[this.dataId]);
                  // }else{
                  //   this.$set(this.taskList[this.currentTaskIndex].workResult.result,'data',JSON.stringify(res.data[this.dataId]));
                  // }
                  this.taskList[this.currentTaskIndex].workResult.result = res.data[this.dataId];

                  let result = this.prepareResult(this.taskList[this.currentTaskIndex]);

                  if (this.audioSegment) {
                    this.audioSegment.pause();
                    this.audioSegment.$destroy();
                  }
                  this.audioSegment = new AudioSegmentCtor({
                    parent: this
                  });
                  let mountNode = this.$refs.templateView.$el.querySelector('[data-tpl-type="audio-file-placeholder"]');
                  if (mountNode) {
                    mountNode = mountNode.firstElementChild;
                  }
                  console.log(result.data,'result.dataresult.dataresult.data')
                  this.audioSegment.$mount(mountNode);
                  this.segments = result.data;
                  this.$nextTick(() => {
                    this.audioSegment.init({
                      userId: this.userId,
                      serverTime: this.serverTime,
                      src: this.voice.url,
                      waveform: this.voice.waveform,
                      segments: result.data,
                      allowEditing: true,
                    });
                  });



                  EventBus.$emit('ready');
                  EventBus.$emit('needConfirmLeave', true);
                  // 整体表单标注结果 回显
                  // setTimeout(() => {
                  //   if (result.info && result.info instanceof Array) {
                  //     result.info.forEach(item => {
                  //       EventBus.$emit('setValue', {
                  //         ...item,
                  //         scope: this.$refs.templateView.$el
                  //       });
                  //     });
                  //   }
                  // }, 100);
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
            handleKeyUp (e) {
                let target = e.target;
                // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (e.keyCode === 68) { // D 提交
                    this.submit();
                }
            },
            handleKeyDown (e) {
                let keyCode = e.keyCode || e.which;
                if (keyCode === 83 && e.ctrlKey) {
                    e.preventDefault();
                    this.saveTemp();
                }
            },
            saveTemp () {
                if (!(this.loading && !this.autoSave)) {
                    this.autoSave.save();
                }
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
            getAttrInfo () {
                return this.$refs.templateView.getData();
            },
            getGlobalInfo () {
                return this.$refs.templateView.getGlobalData();
            },
            showRegionInfo (attr) {
                attr.forEach(item => {
                    EventBus.$emit('setValue', {
                        ...item,
                        scope: this.$refs.templateView.$el
                    });
                });
            },
            saveRegionInfo () {
                this.audioSegment.saveSegmentAttr(this.getAttrInfo());
            },
            getTaskResource (dataId) {
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
                            this.$Message.destroy();
                            this.$Message.error({
                                content: this.$t('tool_request_failed'),
                                duration: 2,
                            });
                        } else {
                            let voice = res.data.voice_url;
                            this.voice = res.data.voice_url;
                            this.executeTask(this.dataId, voice);
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                        // 错误处理
                    }
                });
            },
            initAudioSegment (voice, regions, dataId) {
                this.segments = regions;
                this.isReady = false;
                let mountNode = this.$refs.templateView.$el.querySelector('[data-tpl-type="audio-file-placeholder"]');
                if (mountNode) {
                    mountNode = mountNode.firstElementChild;
                }
                if (this.audioSegment) {
                    this.audioSegment.pause();
                    this.audioSegment.$destroy();
                }
                this.audioSegment = new AudioSegmentCtor({
                    parent: this
                });
                this.audioSegment.$mount(mountNode);
                this.$nextTick(() => {
                    this.audioSegment.init({
                        userId: this.userId,
                        serverTime: this.serverTime,
                        src: voice.url,
                        waveform: voice.waveform,
                        segments: regions,
                        allowEditing: true,
                    });
                });
                this.audioSegment.$on('ready', () => {
                    this.$store.commit('changeGetBase64Process', 0);
                    this.loading = false;
                    this.isReady = true;
                    this.audioSegment.setDefaultAttr(this.getAttrInfo());
                    this.autoSave = new AutoSave({
                        saveUrl: api.task.execute,
                        data: () => {
                            let data = this.audioSegment.getSegments().map(item => {
                                return {
                                    ...item,
                                    type: 'voice_transcription',
                                };
                            });
                            let info = this.getGlobalInfo();
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
                this.audioSegment.$on('error', () => {
                    this.$store.commit('changeGetBase64Process', 0);
                    this.loading = false;
                    this.isReady = false;
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                });
                this.audioSegment.$on('loadProgress', (loaded) => {
                    this.$store.commit('changeGetBase64Process', loaded);
                });
                this.audioSegment.$on('showSegmentAttr', (attr) => {
                    this.showRegionInfo(attr);
                });
            },
            executeTask (dataId, voice) {
                if (this.autoSave) {
                    this.autoSave.destroy();
                    this.autoSave = null;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: dataId,
                    op: 'execute',
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
                            EventBus.$emit('needConfirmLeave', false);
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.ptimeout = res.data.timeout;
                            let taskItemData = this.taskList[this.currentTaskIndex].data;
                            this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskItemData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataId: taskItemData.id,
                                dataName: taskItemData.name,
                            };
                            let result = this.prepareResult(this.taskList[this.currentTaskIndex]);
                            this.feedback = this.taskList[this.currentTaskIndex].workResult.feedback || '';
                            this.initAudioSegment(voice, result.data || [], dataId);
                            EventBus.$emit('ready');
                            EventBus.$emit('needConfirmLeave', true);
                            // 整体表单标注结果 回显
                            setTimeout(() => {
                                if (result.info && result.info instanceof Array) {
                                    result.info.forEach(item => {
                                        EventBus.$emit('setValue', {
                                            ...item,
                                            scope: this.$refs.templateView.$el
                                        });
                                    });
                                }
                            }, 100);
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
            saveTaskResult (next = true, isDifficult = 0) {
                if (this.workedTaskId[this.dataId]) {
                    return;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: this.dataId,
                    op: 'submit',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                    return;
                }
                this.audioSegment && this.audioSegment.pause();
                let result = {};
                result[this.dataId] = {};
                let notes = this.audioSegment.getSegments().map(item => {
                    return {
                        ...item,
                        type: 'voice_transcription',
                    };
                });
                let info = this.getGlobalInfo();
                let validValue = this.$refs.templateView.getDataIsValid();
                if (validValue && isDifficult === 0) {
                    switch (validValue.value) {
                        case dataIsValid.yes: {
                            if (notes instanceof Array && notes.length === 0) { // 标注数据为空
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: this.$t('tool_result_empty'),
                                    duration: 3,
                                });
                                return;
                            }
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
                                return;
                            }
                            break;
                        }
                        case dataIsValid.no: {
                            notes = [];
                            info = [validValue];
                            break;
                        }
                        case dataIsValid.unknown : {
                            break;
                        }
                    }
                }
                result[this.dataId] = {
                    data: notes,
                    is_difficult: isDifficult
                };

                if (info.length) {
                    result[this.dataId].info = info;
                }
                reqData.result = JSON.stringify(result);
                this.loading = true;
                this.workedTaskId[this.dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        if (res.error) {
                            delete this.workedTaskId[this.dataId];
                            this.loading = false;
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
                            let stat = res.data[this.dataId];
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
                        delete this.workedTaskId[this.dataId];
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
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
                    },
                    error: () => {
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
                    }
                });
            },
        },
        beforeDestroy () {
            this.autoSave && this.autoSave.destroy();
            this.autoSave = null;
            if (this.audioSegment) {
                this.audioSegment.pause();
                this.audioSegment.$destroy();
            }
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('formElementChange', this.saveRegionInfo);
            $(window).off('keyup', this.handleKeyUp);
            $(window).off('keydown', this.handleKeyDown);
        },
        components: {
            'template-view': TemplateView,
            'task-progress': taskProgress,
            'task-info': TaskInfo,
            'user-stat': UserStat,
            ErrorTaskReasonShow: () => import('../../../common/components/error-task-reason-show.vue'),
        }
    };
</script>
<style lang="scss" >
    .voice-transcription {
        .file-placeholder {
            background: #fff!important;
        }
    }
</style>

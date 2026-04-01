<template>
    <div class="voice-audit-view">
        <div class="result-view-header">
            <Button type="primary" size="small" @click="close" icon="md-return-left">{{$t('tool_return')}}</Button>
            <div class="button-tools">
                <ButtonGroup style="margin-left: 15px;">
                    <Button @click="prevTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex > 0)">{{$t('tool_previous')}}
                    </Button>
                    <Button size="small" type="info" v-if="taskListCopy.length">
                        {{ (currentIndex+1) + '/' + taskListCopy.length }}
                    </Button>
                    <Button @click="nextTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex < taskListCopy.length - 1)">
                        {{$t('tool_next')}}
                    </Button>
                </ButtonGroup>
            </div>
            <Poptip trigger="hover" placement="bottom">
                <div class="task-info" style="cursor:pointer;">
                    {{ taskItemInfo }}
                </div>
                <task-info slot="content" :taskInfo="taskItemInfoMore"/>
            </Poptip>
            <task-progress
                    :total="0"
                    :current="0"
                    :timeout="timeout"
                    :noticeAble="false"
                    style="height: 48px; padding: 8px"
            ></task-progress>
            <div>
                <Button type="warning" size="small" v-if="isAudit" @click="taskSetDifficult" :loading="loading">
                    {{$t('tool_diffcult_job')}}
                </Button>
                <Button type="error" size="small" v-if="isAudit" @click="errorTask('reset')" :loading="loading">
                    {{$t('tool_q')}}
                </Button>
                <Button type="warning" size="small" @click="errorTask('reject')" :loading="loading">{{$t('tool_w')}}
                </Button>
                <Button type="info" size="small" v-if="needEdit" @click="editResult" :loading="loading">
                    {{$t('tool_modify_space')}}
                </Button>
                <Button type="primary" size="small" @click="taskPass" :loading="loading">{{$t('tool_d')}}</Button>
            </div>
        </div>
        <div class="result-view-body" :class="categoryView">
            <Spin v-if="loading" fix>
                <!--                <Progress style="width: 300px" :percent="Math.floor($store.state.app.getBase64Process)" status="active" />-->
                <p>{{loadingText}}</p>
            </Spin>
            <div class="voice-wrapper">
                <div class="voice-container" ref="container">
                    <div class="voice-container-placeholder"></div>
                </div>
            </div>
            <div class="result-view-wrapper">
                <result-item-analysis
                        :data="(result && result.info) || []"
                        :index="-1"
                        :user="workUser"
                        class="result-analysis"
                />
                <ErrorTaskReasonFill
                        :type="errorTaskType"
                        v-if="errorTaskReasonShow"
                        @cancel="errorTaskReasonShow = false"
                        @confirm="setErrorTask"
                        ref="errorTaskReason"
                ></ErrorTaskReasonFill>
                <div v-if="feedback" class="info-item-wrapper feedback">
                    <h4 class="info-item-header">{{$t('tool_rejected_quality_inspection')}}</h4>
                    <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                    <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
                </div>
                <div v-if="redofeedback" class="info-item-wrapper feedback">
                    <h4 class="info-item-header">{{$t('tool_Reasons_for_rejection_of_rework')}}</h4>
                    <!--<p style="height: 4em; overflow: auto">{{ feedback }}</p>-->
                    <ErrorTaskReasonShow :reason="redofeedback"></ErrorTaskReasonShow>
                </div>
                <ImageLabelResultListView :canDelete="false" v-if="isOpen" ref=resultListView></ImageLabelResultListView>
            </div>
        </div>
    </div>
</template>

<script>
    import Vue from 'vue';
    import api from '@/api';
    import PQueue from 'p-queue';
    import TaskInfo from '@/views/task-perform/components/task-info.vue';
    import TaskProgress from '@/views/task-perform/components/taskprogress.vue';
    import cloneDeep from 'lodash.clonedeep';
    import resultItemAnalysis from '@/views/task-perform/components/text-analysis-result.vue';
    import ErrorTaskReasonFill from '../error-task-reason-fill.vue';
    import ErrorTaskReasonShow from '../error-task-reason-show';
    import PointCloudTrackingComponent from '../../point-cloud/pointcloud-tracking';
    let  PointCloudCtor = Vue.extend(PointCloudTrackingComponent);
    export default {
        name: "pointcloud-tracking-audit-view",
        pointCloud: null,
        props: {
            taskList: {
                type: Array,
                require: true,
            },
            index: {
                type: Number,
                require: true
            },
            timeout: {
                type: Number,
                require: true,
            },
            taskInfo: {
                type: Object,
                require: true,
            },
            categoryView: {
                type: String,
                required: true,
            },
            canHandleKeyboard: {
                type: Boolean,
            },
            isAudit: {
                type: Boolean,
                default: true,
            },
            needEdit: {
                type: Boolean,
                default: true,
            },
        },
        data () {
            return {
                taskListCopy: [],
                loading: false,
                currentIndex: 0,
                currentId: '',
                isReady: false,
                isOpen: false,
                errorTaskType: '',
                errorTaskReasonShow: false,
                isAuditAgain: false,
                loadingText: this.$t('tool_loading')
            };
        },
        watch: {
            taskList (newV, oldV) {
                oldV = this.taskListCopy;
                this.taskListCopy = cloneDeep(newV);
                if (!this.isOpen) {
                    return;
                }
                if (newV.length !== oldV.length) {
                    this.errorTaskReasonShow = false;
                }
                if (newV.length < oldV.length) {
                    this.currentIndex--;
                    this.currentIndex = this.currentIndex < 0 ? 0 : this.currentIndex;
                    if (newV.length > 0) {
                        this.getResource(this.currentIndex);
                    }
                } else if (newV.length > oldV.length) {
                    this.currentIndex = 0;
                    this.getResource(this.currentIndex);
                }
                if (newV.length === 0) {
                    this.currentIndex = 0;
                }
            },
            errorTaskReasonShow () {
                this.$nextTick(() => {
                    this.$refs.resultListView.setResultListHeight()
                })
            }
        },
        computed: {
            dataInfo () {
                return this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].data;
            },
            taskItemInfo () {
                return this.$t('tool_job_id') + ':' + ((this.dataInfo && this.dataInfo.id) || '');
            },
            taskItemInfoMore () {
                return {
                    ...this.taskInfo,
                    dataId: this.dataInfo && this.dataInfo.id,
                    dataName: this.dataInfo && this.dataInfo.name,
                };
            },
            feedback () {
                let ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].workResult;
                if (ret) {
                    ret = ret.feedback;
                } else {
                    ret = '';
                }
                return ret;
            },
            redofeedback () {
                let ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].lastWorkResults[0];
                if (ret) {
                    ret = ret.feedback;
                } else {
                    ret = '';
                }
                return ret;
            },
            result () {
                let ret = {};
                if (this.isAudit) {
                    let parentWorkResults = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorkResults;
                    if (Array.isArray(parentWorkResults)) { // some 方法对于空数组总是返回false
                        this.isAuditAgain = parentWorkResults.length === 0 || parentWorkResults.some((task) => {
                            let result = JSON.parse(task.result || '{}');
                            return !(result.data || result.info);
                        });
                    }
                    if (this.isAuditAgain) { // 再次审核
                        ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                        if (ret) {
                            ret = ret.result || {};
                        }
                    } else {
                        ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorkResults[0];
                        if (ret) {
                            ret = JSON.parse(ret.result || '{}');
                        }
                    }
                } else {
                    ret = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                    if (ret) {
                        ret = ret.result || {};
                    }
                }
                return ret;
            },
            workUser () {
                return this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorks[0].user;
            },
        },
        mounted () {
            this.taskListCopy = cloneDeep(this.taskList);
            this.currentIndex = this.index;
        },
        methods: {
            bindEvent () {
                this.unbindEvent();
                $(window).on('keydown', this.handleKeyDown);
                $(window).on('keyup', this.handleKeyUp);
            },
            unbindEvent () {
                $(window).off('keydown', this.handleKeyDown);
                $(window).off('keyup', this.handleKeyUp);
            },
            handleKeyDown (e) {
                let target = e.target;
                // 屏蔽输入框内的键盘事件
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (!this.canHandleKeyboard || this.loading) { // 模板框打开的时候 才响应键盘事件
                    return;
                }
                let keyCode = e.which || e.keyCode;
                // switch (keyCode) {
                // 空格 修改
                // case 32 : {
                //     e.preventDefault();
                //     this.needEdit && this.editResult();
                //     break;
                // }
                // }
            },
            handleKeyUp (e) {
                let target = e.target;
                // 屏蔽输入框内的键盘事件
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (!this.canHandleKeyboard || this.loading) { // 模板框打开的时候 才响应键盘事件
                    return;
                }
                let keyCode = e.which || e.keyCode;
                // switch (keyCode) {
                // d 通过
                // case 68 : {
                //     this.taskPass();
                //     break;
                // }
                // W 驳回
                // case 87 : {
                //     this.errorTask('reject');
                //     break;
                // }
                // Q 重置
                // case 81 : {
                //     this.isAudit && this.errorTask('reset');
                //     break;
                // }
                // A 上一张
                // case 65 : {
                //     this.prevTask();
                //     break;
                // }
                // S 下一张
                // case 83 : {
                //     this.nextTask();
                //     break;
                // }
                // 右方向键
                // case 39: {
                //     this.isAudit && this.nextResult();
                //     break;
                // }
                // 左方向键
                // case 37: {
                //     this.isAudit && this.preResult();
                //     break;
                // }
                // }
            },
            prevTask () {
                if (this.loading) return;
                if (this.taskListCopy.length > 1 && this.currentIndex > 0) {
                    this.currentIndex--;
                    this.getResource(this.currentIndex);
                    this.errorTaskReasonShow = false;
                } else {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_first_one_received_task'),
                        duration: 1,
                    });
                }
            },
            nextTask () {
                if (this.loading) return;
                if (this.taskListCopy.length > 1 && this.currentIndex < this.taskListCopy.length - 1) {
                    this.currentIndex++;
                    this.getResource(this.currentIndex);
                    this.errorTaskReasonShow = false;
                } else {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_last_one_received_task'),
                        duration: 1,
                    });
                }
            },
            init (index) {
                this.currentIndex = index;
                this.isOpen = true;
                this.getResource(this.currentIndex);
                this.bindEvent();
            },
            close () {
                this.$emit('close');
                this.isOpen = false;
                if (this.pointCloud) {
                    this.pointCloud.$destroy();
                    this.pointCloud = null;
                }
            },
            errorTask (type) {
                if (this.loading) return;
                this.errorTaskReasonShow = true;
                this.errorTaskType = type; // 重置
            },
            setErrorTask (type, reason) {
                if (this.loading) return;
                let dataId = this.dataInfo.id;
                switch (type) {
                    case 'reject': {
                        this.loading = true;
                        this.$emit('task-reject', dataId, reason);
                        break;
                    }
                    case 'reset': {
                        this.loading = true;
                        this.$emit('task-reset', dataId, reason);
                        break;
                    }
                }
            },
            taskPass () {
                if (this.loading) return;
                this.loading = true;
                let dataId = this.dataInfo.id;
                let parentWorkResults = this.taskListCopy[this.currentIndex].parentWorkResults;
                this.$emit('task-pass', dataId, parentWorkResults[parentWorkResults.length - 1].work_id);
            },
            taskSetDifficult () {
                if (this.loading) return;
                this.loading = true;
                let dataId = this.dataInfo.id;
                this.$emit('task-setDifficult', dataId);
            },
            editResult () {
                if (this.loading) return;
                this.$emit('edit', this.currentIndex, this.taskListCopy[this.currentIndex].resource);
            },
            updatePointCloudViewer (index) {
                let url = this.taskListCopy[index].resource;
                let data = this.result.data || [];
                this.initPointCloud(url, data);
            },
            initPointCloud (urls, result) {
                let mountNode = this.$refs.container;
                if (mountNode) {
                    mountNode = mountNode.firstElementChild;
                }
                if (!this.pointCloud) {
                    this.pointCloud = new PointCloudCtor({
                        parent: this
                    });
                    this.pointCloud.$mount(mountNode);
                }
                let pcdurls = urls.map((url) => {
                    return url['3d_url']
                })
                this.$nextTick(() => {
                    this.pointCloud.init({
                        allowEditing: false,
                        urls: pcdurls,
                        result,
                    });
                });
                this.pointCloud.$on('progress', (e) => {
                    this.loadingText = this.$t('tool_loading') + e.message.toFixed(2) + '%';
                })
                this.pointCloud.$on('ready', () => {
                    this.loading = false;
                    this.isReady = true;
                    this.loadingText = this.$t('tool_loading')
                });
                this.pointCloud.$on('error', () => {
                    this.loading = false;
                    this.isReady = false;
                    this.loadingText = this.$t('tool_loading')
                    this.$Message.destroy();
                    this.$Message.error({
                        content: this.$t('tool_failed'),
                        duration: 2,
                    });
                });
            },
            getResource (index) {
                this.loading = true;
                if (this.taskListCopy[index] && this.taskListCopy[index].resource) {
                    this.updatePointCloudViewer(index);
                    return;
                }
                let projectId = this.taskListCopy[this.currentIndex].data.project_id;
                let dataId = this.taskListCopy[this.currentIndex].data.id;
                $.ajax({
                    url: api.task.resource,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: projectId,
                        data_id: dataId,
                        // type: 'ori',
                    },
                    success: (res) => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            this.taskListCopy[index].resource = res.data;
                            this.updatePointCloudViewer(index);
                        }
                    },
                    error: () => {
                        this.loading = false;
                        this.$Message.destroy();
                        this.$Message.error({
                            content: this.$t('tool_request_failed'),
                            duration: 2,
                        });
                    }
                });
            },
        },
        destroyed () {
            this.unbindEvent();
            if (this.pointCloud) {
                this.pointCloud.$destroy();
                this.pointCloud = null;
            }
        },
        components: {
            'task-info': TaskInfo,
            'task-progress': TaskProgress,
            ImageLabelResultListView: () =>
                import('../task-result-view/image-label-result-list-view.vue'),
            resultItemAnalysis,
            ErrorTaskReasonFill,
            ErrorTaskReasonShow,
        },
    };
</script>
<style lang="scss" scoped>
    .info-item-wrapper {
        border: 1px solid #edf0f6;
        margin-bottom: 12px;
        min-width: 260px;

        &.feedback {
            border-color: #f92929;
            border-radius: 8px 8px 0 0;

            p {
                padding: 8px;

            }

            .info-item-header {
                padding: 2px 6px;
                color: #fff;
                border-radius: 7px 7px 0 0;
                background: #f92929;
            }
        }
    }

    .demo-spin-icon-load {
        animation: ani-demo-spin 1s linear infinite;
    }

    @keyframes ani-demo-spin {
        from {
            transform: rotate(0deg);
        }
        50% {
            transform: rotate(180deg);
        }
        to {
            transform: rotate(360deg);
        }
    }

    .demo-spin-col {
        height: 100px;
        position: relative;
        border: 1px solid #eee;
    }

    .result-view-header {
        border-top: 1px solid #eee;
        height: 48px;
        display: flex;
        justify-content: space-around;
        align-items: center;
    }

    .result-view-body {
        min-height: calc(100vh - 52px);
        width: 100%;
        padding: 0;
        display: flex;
        flex-direction: row;
        justify-content: flex-start;

        &.voice_classify {
            flex-direction: column;

            .voice-wrapper {
                width: calc(100vw - 30px);
            }

            .result-analysis {
                width: 100%;
            }

            .result-view-wrapper {
                display: flex;
                flex-direction: row;
            }
        }

        .voice-wrapper {
            width: calc(100vw - 320px - 30px);
        }

        .result-analysis {
            width: 320px;
            max-height: 240px;
            overflow: auto;
        }

    }
</style>

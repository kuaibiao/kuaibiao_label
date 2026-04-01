<template>
    <div class="text-audit-view">
        <div class="result-view-header">
            <Button type="primary" size="small" @click="close" icon="md-return-left">{{$t('tool_return')}}</Button>
            <div class="button-tools">
                <ButtonGroup style="margin-left: 15px;">
                    <Button @click="prevTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex > 0)">{{$t('tool_previous')}}(A)
                    </Button>
                    <Button size="small" type="info" v-if="taskListCopy.length">
                        {{ (currentIndex+1) + '/' + taskListCopy.length }}
                    </Button>
                    <Button @click="nextTask" size="small"
                            :disabled="!(taskListCopy.length > 1 && currentIndex < taskListCopy.length - 1)">
                        {{$t('tool_next')}}(S)
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
            <checkbox-group v-model="currentId" ref="currentWork" v-if="isAudit">
                <checkbox v-for="(item,index) in parentWorkResults" :label="item.work_id" :key="item.work_id">
                    {{$t('tool_result')}}：{{index + 1}}
                    <!--{{$t('tool_result')}}：{{item.work_id}}-->
                </checkbox>
            </checkbox-group>
            <!-- <Button type="primary" size="small" @click="submitEditTask()">提交</Button> -->
            <div>
                <Button type="warning" size="small" v-if="isAudit" @click="taskSetDifficult" :loading="loading">
                    {{$t('tool_diffcult_job')}}
                </Button>
                <Button type="error" size="small" v-if="isAudit" @click="errorTask('reset')" :loading="loading">
                    {{$t('tool_q')}}
                </Button>
                <Button type="warning" size="small" @click="errorTask('reject')" :loading="loading">{{$t('tool_w')}}
                </Button>
                <Button type="info" size="small"
                        v-if="needEdit && parentWorks && parentWorks.length === 1"
                        @click="editResult"
                        :loading="loading">{{$t('tool_modify_space')}}
                </Button>
                <Button type="primary" size="small" @click="taskPass" :loading="loading">{{$t('tool_d')}}</Button>
            </div>
        </div>
        <div class="result-view-body">
            <Spin v-if="loading" fix>
                    <Icon type="ios-loading" size=18 class="demo-spin-icon-load"></Icon>
                <div>Loading</div>
            </Spin>
            <div class="text-wrapper">
                <div class="text-container" ref="textContainer">
                </div>
            </div>
            <div class="text-info">
                <ErrorTaskReasonFill
                        :type="errorTaskType"
                        v-if="errorTaskReasonShow"
                        @cancel="errorTaskReasonShow = false"
                        @confirm="setErrorTask"
                        ref="errorTaskReason"
                ></ErrorTaskReasonFill>
                <div class="scroll-content" :style="scrollContentStyle">
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
                    <text-annotation-result-list
                            :resultList="parentWorkResults"
                            :workerList="parentWorks">
                    </text-annotation-result-list>
                </div>
            </div>
        </div>

    </div>
</template>

<script>
    // import Vue from 'vue';
    import Mark from 'mark.js';
    import EventBus from '@/common/event-bus';
    import api from '@/api';
    import TaskInfo from '@/views/task-perform/components/task-info.vue';
    import TaskProgress from '@/views/task-perform/components/taskprogress.vue';
    import cloneDeep from 'lodash.clonedeep';
    import textAnnotationResultList from '../task-result-view/text-annotation-result-list.vue';
    import ErrorTaskReasonFill from '../error-task-reason-fill.vue';
    import ErrorTaskReasonShow from '../error-task-reason-show.vue';
    export default {
        name: "text-audit-view",
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
                currentId: [],
                marker: null,
                isOpen: false,
                errorTaskType: '',
                errorTaskReasonShow: false,
            };
        },
        watch: {
            taskList (newV, oldV) {
                oldV = this.taskListCopy;
                this.taskListCopy = cloneDeep(newV);
                if (!this.isOpen) { // 只有该组件打开的时候才处理
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
        },
        computed: {
            scrollContentStyle () {
                if (this.errorTaskReasonShow) {
                    return {
                        maxHeight: window.innerHeight - 48 - 142 + 'px'
                    };
                } else {
                    return {
                        maxHeight: window.innerHeight - 48 + 'px'
                    };
                }
            },
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
            parentWorkResults () {
                let ret = [];
                if (this.isAudit) { // 审核分布
                    ret = (this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorkResults) || [];
                    if (ret.length) {
                        ret = ret.map((r) => {
                            return {
                                ...r,
                                result: r.result || '{}'
                            };
                        });
                    }
                    ret = ret.filter((r) => {
                        let result = JSON.parse(r.result);
                        return !!(result.data || result.info);
                    });
                    if (ret.length === 0) { // 审核父分布不是执行
                        let r = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                        if (r) {
                            r.work_id = '';
                            ret = [r || {}];
                        }
                    }
                } else { // 质检分布
                    let r = this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].dataResult;
                    if (r) {
                        ret = [r || {}];
                    }
                }
                this.currentId = ([ret[ret.length - 1] && ret[ret.length - 1].work_id]).filter((t) => {
                    return !!t
                });
                return ret;
            },
            parentWorks () {
                return this.taskListCopy[this.currentIndex] && this.taskListCopy[this.currentIndex].parentWorks;
            },
        },
        mounted () {
            this.taskListCopy = cloneDeep(this.taskList);
            this.currentIndex = this.index;
            // this.getResource(this.currentIndex);
            // let length = this.parentWorkResults.length;
            // this.currentId = [this.parentWorkResults[length - 1] && this.parentWorkResults[length - 1].work_id];
            // console.log(this.currentId)
            EventBus.$on('highlightRange', this.highlightRange);
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
                if (!this.canHandleKeyboard || this.loading) {
                    return;
                }
                let keyCode = e.which || e.keyCode;
                switch (keyCode) {
                    // 空格 修改
                    case 32 : {
                        e.preventDefault();
                        this.needEdit && this.parentWorks.length === 1 && this.editResult();
                        break;
                    }
                    default: {} // eslint-disable-line
                }
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
                switch (keyCode) {
                    // d 通过
                    case 68 : {
                        this.taskPass();
                        break;
                    }
                    // W 驳回
                    case 87 : {
                        this.errorTask('reject');
                        break;
                    }
                    // Q 重置
                    case 81 : {
                        this.isAudit && this.errorTask('reset');
                        break;
                    }
                    // A 上一张
                    case 65 : {
                        this.prevTask();
                        break;
                    }
                    // S 下一张
                    case 83 : {
                        this.nextTask();
                        break;
                    }
                    // 右方向键
                    case 39: {
                        // this.isAudit && this.nextResult();
                        break;
                    }
                    // 左方向键
                    case 37: {
                        //  this.isAudit && this.preResult();
                        break;
                    }
                }
            },
            // nextResult () {
            //     let curId = this.currentId;
            //     let index = -1;
            //     this.parentWorkResults.forEach((item, i) => {
            //         if (curId === item.work_id) {
            //             index = i;
            //         }
            //     });
            //     index++;
            //     if (index >= this.parentWorkResults.length) {
            //         index = this.parentWorkResults.length - 1;
            //     }
            //     this.currentId = this.parentWorkResults[index].work_id;
            // },
            // preResult () {
            //     let curId = this.currentId;
            //     let index = -1;
            //     this.parentWorkResults.forEach((item, i) => {
            //         if (curId === item.work_id) {
            //             index = i;
            //         }
            //     });
            //     index--;
            //     if (index < 0) {
            //         index = 0;
            //     }
            //     this.currentId = this.parentWorkResults[index].work_id;
            // },
            init (index) {
                this.bindEvent();
                this.currentIndex = index;
                this.getResource(index);
                this.isOpen = true;
            },
            prevTask () {
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
            taskSetDifficult () {
                if (this.loading) return;
                this.loading = true;
                let dataId = this.dataInfo.id;
                this.$emit('task-setDifficult', dataId);
            },
            taskPass () {
                if (this.loading) return;
                let dataId = this.dataInfo.id;

                if (this.currentId.length === 0) {
                    let parentWorkResults = this.taskListCopy[this.currentIndex].parentWorkResults;
                    this.currentId = [parentWorkResults[parentWorkResults.length - 1].work_id];
                }
                this.loading = true;
                this.$emit('task-pass', dataId, this.currentId.toString());
            },
            close () {
                this.$emit('close');
                this.isOpen = false;
                this.errorTaskReasonShow = false;
            },
            editResult () {
                if (this.loading) return;
                // let [result] = this.parentWorkResults.filter((item) => {
                //     return item.work_id === this.currentId;
                // });
                // result = typeof result.result === 'string' ? JSON.parse(result.result) : result.result;
                this.$emit('edit', this.currentIndex, this.taskListCopy[this.currentIndex].resource, /* result */);
            },
            highlightRange (range) {
                this.marker && this.marker.unmark({
                    done: () => {
                        this.marker.markRanges([range]);
                    }
                });
            },
            getResource (index) {
                if (!this.marker) {
                    this.marker = new Mark(this.$refs.textContainer);
                }
                if (this.taskListCopy[index] && this.taskListCopy[index].resource) {
                    this.loading = false;
                    this.updateTextViewer(index);
                    return;
                }
                this.loading = true;
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
                        this.loading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                this.$Message.destroy();
                                this.$Message.error({
                                    content: this.$t('tool_request_failed'),
                                    duration: 2,
                                });
                                return;
                            }
                            this.taskListCopy[index].resource = resource;
                            this.updateTextViewer(index);
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
            updateTextViewer (index) {
                let resource = this.taskListCopy[index].resource;
                let html = '';
                resource.forEach((item) => {
                    let key = item[0];
                    let value = item[1];
                    value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                    html += `<pre class="data-container">${value}</pre>`;
                });                
                this.$refs.textContainer.innerHTML = html;
            }
        },
        destroyed () {
            EventBus.$off('highlightRange', this.highlightRange);
            this.unbindEvent();
        },
        components: {
            textAnnotationResultList,
            ErrorTaskReasonFill,
            ErrorTaskReasonShow,
            'task-info': TaskInfo,
            'task-progress': TaskProgress,
        },
    };
</script>

<style lang="scss">
    .text-audit-view {
        .info-item-wrapper {
            border: 1px solid #edf0f6;
            margin-bottom: 12px;
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
            display: flex;
            flex-direction: row;
            justify-content: flex-start;

            .text-wrapper {
                width: 100%;
                border-right: 1px solid #d7d7d7;

                .text-container {
                    max-height: calc(100vh - 60px);
                    overflow-y: auto;

                    pre:first-child {
                        margin-top: 15px;
                    }

                    pre {
                        font-size: 14px;
                        color: #333;
                        white-space: pre-wrap;
                        padding: 0px 15px;
                        margin: 0;
                    }
                }
            }

            .text-info {
                max-height: calc(100vh - 60px);
                flex-basis: 620px;
                max-width: 620px;
                flex-shrink: 0;
                flex-grow: 0;
                padding: 0 4px;

                .scroll-content {
                    max-height: calc(100vh - 60px);
                    overflow-y: auto;
                }
            }

        }
    }
</style>

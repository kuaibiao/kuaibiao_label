<template>
    <div style="position:relative; min-height: 100%;">
        <div class="task-header">
            <audit-by-user :userList="userList"></audit-by-user>
            <div class="task-btn-group" v-show="taskListIsNull">
                <task-progress
                        :total="0"
                        :current="0"
                        :timeout="timeout"
                        :noticeAble="tableData.length > 0"
                ></task-progress>
                <Button type="primary" size="small"
                        @click.native="batchSelect"> {{ selectAllText }}
                </Button>
                <Button type="primary" size="small"
                        @click.native="batchPass"
                        v-if="selectedTask.length > 0">{{$t('tool_batch_pass')}}
                </Button>
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillReject('', true)">{{$t('tool_batch_rejection')}}
                </Button>
                <Button type="primary" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillReset('', true)">{{$t('tool_bulk_reset')}}
                </Button>
                <Button type="warning" size="small"
                        v-if="selectedTask.length > 0"
                        @click.native="taskWillSetDifficult">{{$t('tool_batch_troublesome_work')}}
                </Button>
                <Button type="error" size="small" @click="exitfirmModal = true">{{$t('tool_clear_and_exit')}}</Button>
            </div>
        </div>
        <div class="audit-wrapper">
            <Table ref="selection"
                   :columns="columnsConfig"
                   :data="tableData"
                   @on-selection-change="onSelectChange"
            ></Table>
        </div>
        <Spin fix v-if="loading">{{ loadingText }}</Spin>
        <Modal v-model="viewModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
        >
            <text-audit-view
                    :taskList="tableData"
                    :index="currentTaskIndex"
                    :taskInfo="taskInfo"
                    :timeout="timeout"
                    :categoryView="categoryInfo.view"
                    :canHandleKeyboard="canHandleKeyboard"
                    :needEdit="false"
                    @edit="showEditModal"
                    @close="viewModal = false"
                    @task-pass="taskPass"
                    @task-reject="taskReject"
                    @task-reset="taskReset"
                    @task-setDifficult="taskSetDifficult"
                    ref="viewModal"
            ></text-audit-view>
        </Modal>
        <Modal v-model="editModal"
               :class="'edit-modal-wrapper'"
               width="100"
               style="min-height:100%"
               :mask-closable="false"
               :closable="false"
               @on-visible-change="editModalVisibleChange">
            <div slot="header" class="edit-modal-header">
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
                    <Button type="primary" size="small" @click="submitEditTask()">{{$t('tool_submit')}}</Button>
                    <Button type="info" size="small" @click="editModal = false">{{$t('tool_cancel')}}</Button>
                </div>

            </div>
            <template-view
                    :config="templateInfo"
                    scene="execute"
                    ref="templateView"
                    v-if="editModal">
            </template-view>
            <div class="region-table-list" ref="regionList">
                <h4 class="drag-header">
                    <div>
                        <span>{{$t('tool_job_result')}}：({{$t('tool_drag')}})</span>
                        <span style="padding-left: 15px; color: #00B83F">
                        {{ annotateModal ? $t('tool_annotated_schema'): $t('tool_not_annotated_schema')}}
                        ({{$t('tool_key_switch')}})
                    </span>
                    </div>
                    <Button size="small" type="error" @click="deleteAllResult">{{$t('tool_delete_all')}}</Button>
                </h4>
                <!--  <div class="drag-header"> <Checkbox v-model="draggableIsOn"> 开启拖拽功能 </Checkbox> </div>  -->
                <table class="table table-hover" width="100%" v-if="selectionList.length > 0 ">
                    <thead>
                    <tr>
                        <th width="5%">{{$t('tool_serial')}}</th>
                        <th width="25%" class="note-text">{{$t('tool_tagging_content')}}</th>
                        <th width="10%" class="note-text">{{$t('tool_text_point')}}</th>
                        <th width="15%" class="note-text">{{$t('tool_label')}}</th>
                        <th width="20%" class="note-text">{{$t('tool_attribute')}}</th>
                        <!-- <th width="15" >错误原因</th> -->
                        <th width="15%">{{$t('tool_handle')}}</th>
                    </tr>
                    </thead>
                    <tbody>
                    <tr v-for="(selection, index ) in selectionList"
                        :class=" selectText && selectText.id === selection.id ? 'active' : ''"
                        :key="index"
                        @click="editSelection(selection, index)"
                    >
                        <td width="5%">{{ index + 1}}</td>
                        <td width="25%" class="note-text"> {{ selection.text }}</td>
                        <td width="10%" class="note-text">{{ selection.start + '--' + selection.end}}</td>
                        <td width="15%" class="note-text">
                            {{ selection.label | formatLabel }}
                        </td>
                        <td width="20%" class="note-text"> {{ selection.attr.filter( item => {
                            return item.value.length;
                            }).map( item => {
                            return item.header + ':' + item.value;
                            }).join(' | ') }}
                        </td>
                        <td width="15%">
                            <!-- <Button type="primary" size="small" @click.stop="editSelection(selection, index)">{{$t('tool_edit')}}</Button> -->
                            <Button type="error" size="small" @click.stop="deleteSelection(selection, index)">
                                {{$t('tool_delete')}}
                            </Button>
                        </td>
                    </tr>
                    </tbody>
                </table>
            </div>
            <Spin fix v-if="loading">{{loadingText}}</Spin>
        </Modal>

        <Modal v-model="rejectModal"
               :title="$t('tool_fillreject_reason')"
               @on-ok="handleModalOnOk"
               @on-visible-change="rejectModalVisibleChange"
               :mask-closable="false"
               :ok-text="$t('tool_enter')"
               :cancel-text="$t('tool_esc')"
        >
            <Input v-model="rejectReason" autofocus ref="rejectModalInput" @on-enter="handleModalOnOk"/>
        </Modal>
        <Modal v-model="resetModal"
               :title="$t('tool_fillreset_reason')"
               @on-ok="handleResetModalOnOk"
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
    import EventBus from '@/common/event-bus';
    import util from "@/libs/util";
    import cloneDeep from 'lodash.clonedeep';
    import TaskInfo from '../components/task-info.vue';
    import auditByUser from '../components/audit-by-user.vue';
    import TextAnnotationResultList from "components/task-result-view/text-annotation-result-list";
    import ErrorTaskReasonShow from '../../../common/components/error-task-reason-show.vue';
    import findIndex from 'lodash.findindex';
    import uuid from 'uuid/v4';
    import 'jquery-ui';
    import Mark from 'mark.js';
    import dataIsValid from '../../../common/dataIsValid';
    import commonMixin from '../mixins/commom';

    export default {
        name: 'audit-text-annotation',
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
                currentTaskIndex: 0,
                userId: this.$store.state.user.userInfo.id,
                clientTime: Math.floor(new Date().valueOf() / 1000),
                loading: true,
                submiting: false,
                editLoading: false,
                loadingText: this.$t('tool_loading'),
                editModal: false,
                viewModal: false,
                selectedTask: [],
                selectAllIsOn: false,
                columnsConfig: [
                    {
                        type: 'selection',
                        width: 60,
                        align: 'center'
                    },
                    {
                        title: this.$t('tool_job_id'),
                        key: 'data',
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
                                    transfer: true,
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
                        title: this.$t('tool_created_time'),
                        key: 'data',
                        render: (h, params) => {
                            let parentWorks = params.row.parentWorks;
                            let parentWorksCreateTime = parentWorks.map((works) => {
                                return works.created_at;
                            }).sort();
                            return h('span', util.timeFormatter(new Date(parentWorksCreateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss'));
                        }
                    },
                    {
                        title: this.$t('tool_updated_time'),
                        key: 'data',
                        render: (h, params) => {
                            let parentWorks = params.row.parentWorks;
                            let parentWorksUpdateTime = parentWorks.map((works) => {
                                return works.updated_at;
                            }).sort((a, b) => a - b);
                            return h('span', util.timeFormatter(new Date(parentWorksUpdateTime[0] * 1000), 'yyyy-MM-dd hh:mm:ss'));
                        }
                    },
                    {
                        title: this.$t('tool_reject'),
                        align: 'center',
                        key: 'data',
                        render: (h, params) => {
                            if (params.row.workResult.feedback) {
                                return h('div', [
                                    h('span', {}, this.$t('tool_rejected')),
                                    h('span', [
                                        h('Poptip', {
                                            props: {
                                                trigger: "hover",
                                                title: this.$t('tool_reject_reason'),
                                                // content: params.row.workResult.feedback,
                                                transfer: true,
                                                placement: 'right-start',
                                            },
                                        }, [
                                            h('Icon', {
                                                style: {
                                                    marginLeft: '6px',
                                                    verticalAlign: 'top'
                                                },
                                                props: {
                                                    type: 'ios-help-circle-outline',
                                                    size: 18
                                                },
                                            }),
                                            h(ErrorTaskReasonShow, {
                                                props: {
                                                    reason: params.row.workResult.feedback,
                                                },
                                                slot: 'content'
                                            })

                                        ])
                                    ]),
                                ]);
                            }
                        }
                    },
                    {
                        title: this.$t('tool_handle'),
                        align: 'center',
                        width: 320,
                        render: (h, params) => {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        type: 'info',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.viewModal = true;
                                            this.currentTaskIndex = params.index;
                                            this.$refs.viewModal.init(params.index);
                                        }
                                    }
                                }, this.$t('tool_view')),
                                h('Button', {
                                    props: {
                                        type: 'success',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskPass(params.row.data.id, params.row.parentWorkResults[params.row.parentWorkResults.length - 1].work_id);
                                        }
                                    }
                                }, this.$t('tool_pass')),
                                h('Button', {
                                    props: {
                                        type: 'error',
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskWillReject(params.row.data.id);
                                            // this.taskReject(params.row.data.id, this.rejectReason)
                                        }
                                    }
                                }, this.$t('tool_reject')),
                                h('Button', {
                                    props: {
                                        size: 'small'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskWillReset(params.row.data.id);
                                            // this.taskReject(params.row.data.id, this.rejectReason)
                                        }
                                    }
                                }, this.$t('tool_reset')),
                                h('Button', {
                                    props: {
                                        size: 'small',
                                        type: 'warning'
                                    },
                                    style: {
                                        marginRight: '5px',
                                    },
                                    on: {
                                        click: () => {
                                            this.taskSetDifficult(params.row.data.id);
                                            // this.taskReject(params.row.data.id, this.rejectReason)
                                        }
                                    }
                                }, this.$t('tool_diffcult_job'))
                            ]);
                        }

                    }
                ],
                tableData: [],
                isBatch: false,
                taskItemInfo: '',
                taskItemInfoMore: {},
                rejectReason: '',
                resetReason: '',
                rejectModal: false,
                resetModal: false,
                rejectTaskId: '',
                resetTaskId: '',
                parentWorkResults: [], // 作业结果
                parentWorks: null, // 作业人员
                currentUserId: '',
                dataIdsCache: {}, // 提交中或提交过的数据ID缓存 防止针对同一数据ID同时执行驳回通过重置等操作
                taskIsTimeOut: false,
                currentTaskResource: [],
                selectionList: [],
                selectText: null,
                label: null,
                defaultAttrInfo: [],
                draggableIsOn: false,
                textPlaceHolderReady: false,
                annotateModal: true,
                marker: null,
                markerOption: {
                    element: 'span',
                    className: 'highlight',
                    each: function (node, range) {
                        // console.log(node, range);
                        node.style.backgroundColor = range.color;
                        if (range.isCurrentSelection) { // 当前选中的标注文本添加 border 来做视觉上的区分
                            node.style.outline = '1px solid red';
                        } else {
                            node.style.border = '';
                        }
                    }
                },
                usedLabel: {},
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
            },
            canHandleKeyboard () {
                return this.viewModal && !(this.rejectModal || this.editModal || this.resetModal || this.taskIsTimeOut);
            },
        },
        watch: {
            taskList (newV, oldV) {
                // this.tableData = newV;
                this.tableData = cloneDeep(this.taskList);
                this.dataIdsCache = {};
                this.taskIsTimeOut = false;
                if (newV.length === 0) {
                    // EventBus.$emit('perform-fetchTask');
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_no_jobs'),
                        duration: 3,
                    });
                    this.viewModal = false;
                }
            }
        },
        mounted () {
            // this.tableData = this.taskList;
            this.tableData = cloneDeep(this.taskList);
            this.dataIdsCache = {};
            this.taskIsTimeOut = false;
            if (this.tableData.length === 0) {
                // 一进来就没有作业
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_no_jobs'),
                    duration: 3,
                });
            }
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('clear-fetchTask', this.userIdChange);
            EventBus.$on('setLabel', this.setLabel);
            EventBus.$on('setDefaultLabel', this.setDefaultLabel);
            EventBus.$on('appendLabel', this.appendLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('formElementChange', this.saveSelectionAttr);
            this.handleMouseUp = this.handleMouseUp.bind(this);
            this.handleKeyUp = this.handleKeyUp.bind(this);
            $(window).on('keyup', this.handleKeyUp);
            Vue.nextTick(() => {
                this.loading = false;
            });
        },
        methods: {
            updateWorkerInfo (target) {
                target.mBy = this.userId;
                target.mTime = this.getTime();
                target.step = this.taskInfo.step_id;
            },
            // 用于更新标签使用变化情况,
            // 文本标注结果的标签和图片标注结果的标签数据因历史原因结构不同 so
            updateUsedLabel (labelList, add = true) {
                let usedLabel = this.usedLabel;
                labelList.forEach((v) => {
                    let key = v.category + '-' + v.label;
                    if (usedLabel[key]) {
                        if (add) {
                            usedLabel[key]++;
                        } else {
                            usedLabel[key]--;
                        }
                    } else {
                        if (add) {
                            usedLabel[key] = 1;
                        }
                    }
                    if (usedLabel[key] <= 0) {
                        delete usedLabel[key];
                    }
                });
            },
            // 派发使用的标签信息.
            usedLabelChange () {
                let usedLabel = this.usedLabel;
                let ret = Object.keys(usedLabel);
                EventBus.$emit('usedLabelChange', ret); // 派发标签使用变化的事件
            },
            deleteAllResult () {
                this.selectionList = [];
                this.selectText = null;
                this.usedLabel = {};
                this.usedLabelChange();
                this.highLightSelection();
                EventBus.$emit('renderLabelList', []);
            },
            handleKeyUp (e) {
                if (!this.editModal) {
                    return;
                }
                let target = e.target;
                // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
                if (target.tagName.toLowerCase() === 'input' && target.type === "text") {
                    return;
                }
                if (target.tagName.toLowerCase() === 'textarea') {
                    return;
                }
                if (e.keyCode === 68) { // D 提交
                    this.submitEditTask();
                    return;
                }
                if (e.keyCode === 88 && !e.ctrlKey && !e.metaKey && !e.shiftKey) { // X  切换标注和复制模式
                    this.annotateModal = !this.annotateModal;
                    if (this.annotateModal) {
                        let selection = window.getSelection();
                        selection.removeAllRanges();
                    }
                    return;
                }
                if (e.keyCode === 90 && e.ctrlKey && this.selectText) { // ctrl Z 删除刚标注的内容
                    this.selectionList.shift();
                    this.selectText = null;
                    EventBus.$emit('renderLabelList', []);
                    this.highLightSelection();
                    return;
                }
                if (e.keyCode === 86 && this.selectText) { // V 复制当前选中的标注信息
                    let index = this.selectionList.indexOf(this.selectText);
                    let copy = cloneDeep(this.selectText);
                    copy.id = uuid();
                    copy.cBy = this.userId;
                    copy.cTime = this.getTime();
                    copy.step = this.taskInfo.step_id;
                    this.selectionList.splice(index, 0, copy); // 在选中的索引位置插入复制的结果
                    this.selectText = copy;
                    this.renderLabelList(this.selectText);
                    this.highLightSelection();
                }
            },
            checkRequiredTagGroup () {
                if (this.requiredTagGroup.length < 1) {
                    return true;
                }
                let returnValue = this.selectionList.every((item) => {
                    let labels = item.label;
                    return this.requiredTagGroup.every((requiredLabel) => {
                        if (typeof requiredLabel === 'string') { // 标签组 分类 字符串
                            return labels.some(label => {
                                return label.category === requiredLabel;
                            });
                        } else { // 非标签组 包含所有标签的数组 item的标签只要有一个包含在内就满足
                            return requiredLabel.some((l) => {
                                return labels.some(label => {
                                    return label.category === l;
                                });
                            });
                        }
                    });
                });
                if (!returnValue) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_asterisk_tag_group'),
                        duration: 2,
                    });
                }
                return returnValue;
            },
            checkRequiredTag () {
                let selLabels = [];
                this.selectionList.forEach((item) => {
                    selLabels = selLabels.concat(item.label);
                });
                let returnValue = true;
                for (let i = 0; i < selLabels.length; i++) {
                    let item = selLabels[i];
                    let labelArr = this.requiredTagForSingleTag[item.category]; // 该标签所在标签组的 所有必选标签
                    let flag = false;
                    labelArr && labelArr.forEach((v) => {
                        if (v.isRequired && v.text !== item.label) { // 某必需标签是否已选择
                            flag = !selLabels.some((sItem) => {
                                return sItem.category === v.category && v.text === sItem.label;
                            });
                        }
                    });
                    if (flag) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: this.$t('tool_tags_not_selected'),
                            duration: 2,
                        });
                        returnValue = false;
                        break;
                    }
                }
                return returnValue;
            },
            editSelection (selection) {
                this.selectText = selection;
                this.renderLabelList(selection);
                selection.attr.forEach((item) => {
                    EventBus.$emit('setValue', {
                        ...item,
                        scope: this.$refs.templateView.$el
                    });
                });
                this.highLightSelection();
            },
            saveSelectionAttr () {
                if (this.selectText) {
                    this.$set(this.selectText, 'attr', this.getAttrInfo());
                    this.updateWorkerInfo(this.selectText);
                }
            },
            deleteSelection (selection, index) {
                EventBus.$emit('renderLabelList', []);
                this.selectionList.splice(index, 1);
                this.updateUsedLabel(selection.label, false);
                this.usedLabelChange();
                this.highLightSelection();
            },
            deleteLabel (index) {
                if (this.selectText) {
                    if (~index) {
                        let [v] = this.selectText.label.splice(index, 1);
                        let key = v.category + '-' + v.label;
                        if (this.usedLabel[key]) {
                            this.usedLabel[key]--;
                            this.usedLabel[key] <= 0 && delete this.usedLabel[key];
                        }
                        this.usedLabelChange();
                        this.updateWorkerInfo(this.selectText);
                    }
                    this.renderLabelList(this.selectText);
                }
            },
            setLabel (obj) {
                let label = {
                    label: obj.label,
                    code: obj.shortValue,
                    category: obj.category,
                    color: obj.color,
                };
                // this.label = {...label};
                if (this.selectText) {
                    this.updateUsedLabel(this.selectText.label, false);
                    this.$set(this.selectText, 'label', [{...label}]);
                    this.updateUsedLabel(this.selectText.label);
                    this.usedLabelChange();
                    this.renderLabelList(this.selectText);
                    this.highLightSelection();
                    this.updateWorkerInfo(this.selectText);
                }
            },
            setDefaultLabel () {
                let labelList = [].slice.apply(arguments);
                let label = labelList.map((item) => {
                    return {
                        label: item.label,
                        code: item.shortValue,
                        category: item.category,
                        color: item.color,
                    };
                });
                this.label = [...label];
            },
            appendLabel (obj) {
                let label = {
                    label: obj.label,
                    code: obj.shortValue,
                    category: obj.category || obj.label,
                    color: obj.color,
                };
                if (!this.selectText) return;
                let selLabels = this.selectText.label;
                this.updateUsedLabel(selLabels, false);
                let index = findIndex(selLabels, (item) => {
                    return item.category === (label.category || label.label);
                });
                // 值为1  可以有多个
                if (obj.localTagIsUnique) {
                    if (~index) {
                        let labelIndex = findIndex(selLabels, (item) => {
                            return item.category === label.category && item.label === label.label;
                        });
                        if (~labelIndex) {
                            selLabels.splice(labelIndex, 1, label);
                        } else {
                            selLabels.push(label);
                        }
                    } else {
                        selLabels.push(label);
                    }
                } else {
                    if (~index) {
                        selLabels.splice(index, 1, label);
                    } else {
                        selLabels.push(label);
                    }
                }
                this.$set(this.selectText, 'label', [...selLabels]);
                this.updateUsedLabel(this.selectText.label);
                this.usedLabelChange();
                this.renderLabelList(this.selectText);
                this.highLightSelection();
                this.updateWorkerInfo(this.selectText);
            },
            renderLabelList (target) {
                let labelList = [];
                let labelArr = target.label;
                labelArr.forEach((item) => {
                    labelList.push({
                        text: item.label,
                        shortValue: item.code,
                        categoryText: item.category
                    });
                });
                EventBus.$emit('renderLabelList', labelList);
            },
            getAttrInfo () {
                return this.$refs.templateView.getData();
            },
            getGlobalInfo () {
                return this.$refs.templateView.getGlobalData();
            },
            getTime () {
                let now = Math.floor(new Date().valueOf() / 1000);
                return +this.serverTime + (now - this.clientTime);
            },
            bindLabelEvent () {
                this.$refs.templateView && $(this.$refs.templateView.$el).find('.text-container').parents('.children-con').on('mouseup', this.handleMouseUp);
            },
            unbindLabelEvent () {
                this.$refs.templateView && $(this.$refs.templateView.$el).find('.text-container').parents('.children-con').off('mouseup', this.handleMouseUp);
            },
            checkSelectionIsRepetitive (selItem) { // 检测是否已选择过
                return this.selectionList.some(item => {
                    return selItem.text === item.text &&
                        selItem.start === item.start &&
                        selItem.end === item.end;
                });
            },
            handleMouseUp () {
                // let selection = window.getSelection();
                // console.log(selection);
                // console.log(selection.getRangeAt(0));
                // 非标准模式不做处理
                if (!this.annotateModal) return;
                let selection = window.getSelection();
                // 考虑 没添加标签组件的情况
                if (this.label && this.selectText && this.selectText.label.length === 0) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_selection_not_tagged'),
                        duration: 3,
                    });
                    // 清楚选择的区域
                    selection.removeAllRanges();
                    return;
                }
                // console.log(selection);
                // console.log(selection.getRangeAt(0));
                if (selection && !selection.isCollapsed && selection.toString().trim()) {
                    let parentOffset = 0;
                    let range = selection.getRangeAt(0);
                    let text = selection.toString();
                    let start = 0;
                    let end = 0;
                    let startContainer = range.startContainer;
                    let startPre = null;
                    let endContainer = range.endContainer;
                    let endPre = null;
                    // 起始点所在元素容器
                    let startWrapper = $(range.startContainer).parents('[data-tpl-type="text-file-placeholder"]');
                    // 是文本节点 并且属于高亮的元素的子节点
                    if (startContainer.nodeType === Node.TEXT_NODE && startContainer.parentElement.dataset.markjs) {
                        startPre = startContainer.parentElement.previousSibling;
                    } else {
                        startPre = startContainer.previousSibling;
                    }
                    while (startPre !== null) {
                        start += startPre.textContent.length;
                        startPre = startPre.previousSibling;
                    }
                    let preWrapper = startWrapper.prevAll('[data-tpl-type="text-file-placeholder"]');
                    preWrapper.each((index, wrapper) => {
                        parentOffset += $(wrapper).find('.text-container pre').get(0).textContent.length;
                    });
                    start = start + range.startOffset + parentOffset;
                    // 兼容多个文本容器占位符的情况
                    // 结束位置
                    let endWrapper = $(range.endContainer).parents('[data-tpl-type="text-file-placeholder"]');
                    // 比较DOM 元素 如果起始点和结束点在同一个文本占位符内
                    if (startWrapper.get(0) === endWrapper.get(0)) {
                        if (startContainer === endContainer) { // 其实点在同一个元素上
                            end = start - range.startOffset;
                        } else {
                            if (endContainer.nodeType === Node.TEXT_NODE && endContainer.parentElement.dataset.markjs) {
                                endPre = endContainer.parentElement.previousSibling;
                            } else {
                                endPre = endContainer.previousSibling;
                            }
                            while (endPre !== startPre && endPre !== null) {
                                end += endPre.textContent.length;
                                endPre = endPre.previousSibling;
                            }
                        }
                    }
                    {
                        parentOffset = 0;
                        let preWrapper = endWrapper.prevAll('[data-tpl-type="text-file-placeholder"]');
                        preWrapper.each((index, wrapper) => {
                            parentOffset += $(wrapper).find('.text-container pre').get(0).textContent.length;
                        });
                        end = parentOffset;
                        if (endContainer.nodeType === Node.TEXT_NODE && endContainer.parentElement.dataset.markjs) {
                            endPre = endContainer.parentElement.previousSibling;
                        } else {
                            endPre = endContainer.previousSibling;
                        }
                        while (endPre !== startPre && endPre !== null) {
                            end += endPre.textContent.length;
                            endPre = endPre.previousSibling;
                        }
                    }
                    end = end + range.endOffset;
                    let selItem = {
                        type: "text-annotation",
                        text: text,
                        label: this.label ? [...this.label] : [],
                        id: uuid(),
                        start: start,
                        end: end, // 选中的字符串 [ ) 不包括end位置的字符
                        attr: this.defaultAttrInfo.slice(),
                        cBy: this.userId,
                        cTime: this.getTime(),
                        mBy: '',
                        mTime: '',
                        step: this.taskInfo.step_id,
                    };
                    if (this.checkSelectionIsRepetitive(selItem)) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: selItem.text + this.$t('tool_already_exist'),
                            duration: 2
                        });
                        // 清楚选择的区域
                        selection.removeAllRanges();
                        return;
                    }
                    this.selectionList.unshift(selItem);
                    this.selectText = selItem;
                    this.updateUsedLabel(this.selectText.label);
                    this.usedLabelChange();
                    this.renderLabelList(selItem);
                    // 添加后清楚选择的区域
                    selection.removeAllRanges();
                    this.highLightSelection();
                    // EventBus.$emit('ready');
                }
            },
            highLightSelection () {
                let rangList = this.selectionList.map((selection) => {
                    let color = (selection.label && selection.label[0].color) || '0xffff00';
                    return {
                        start: selection.start,
                        length: selection.end - selection.start,
                        color: color,
                        isCurrentSelection: selection === this.selectText,
                    };
                });
                if (!this.marker) {
                    let target = this.$refs.templateView.$el.querySelectorAll('.text-container');
                    this.marker = new Mark(target);
                }
                this.marker.unmark({
                    className: 'highlight',
                    done: () => {
                        this.marker.markRanges(rangList, this.markerOption);
                    }
                });
            },
            showEditModal (index, resource, result) {
                if (this.taskIsTimeOut) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 2,
                    });
                    return;
                }
                this.editModal = true;
                this.currentTaskIndex = index;
                this.currentTaskResource = resource;
                // this.currentTaskResult = result;
                this.marker = null; // 再次打开 marker 初始化时的元素已经重建
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.taskIsTimeOut = true;
                this.viewModal = false;
            },
            checkTaskList () {
                if (this.tableData.length === 0) {
                    EventBus.$emit('perform-fetchTask');
                }
            },
            // 按作业员审核
            userIdChange (e) {
                if (e.type === 'workerChange') {
                    this.currentUserId = e.data.cur;
                }
            },
            cloneDeep (a) {
                return cloneDeep(a);
            },
            onSelectChange (selection) {
                this.selectedTask = selection;
            },
            batchReject () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskReject(task.data.id, this.rejectReason);
                    }, 100 * index);
                });
            },
            batchReset () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskReset(task.data.id, this.resetReason);
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
            taskWillSetDifficult () {
                this.selectedTask.forEach((task, index) => {
                    setTimeout(() => {
                        this.taskSetDifficult(task.data.id);
                    }, 100 * index);
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
                    user_id: this.currentUserId,
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
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
                            // this.editModal = false;
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
            taskPass (dataId, currentId) {
                // console.log(dataId, currentId);
                if (this.taskIsTimeOut && this.editModal) {
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
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
                    op: 'submit',
                    result: JSON.stringify(result)
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
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
                if (this.taskIsTimeOut) {
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                this.rejectModal = true;
                this.rejectTaskId = dataId;
                this.isBatch = isBatch;
            },
            taskWillReset (dataId, isBatch = false) {
                if (this.taskIsTimeOut) {
                    this.$Message.warning({
                        content: this.$t('tool_timed_out'),
                        duration: 3,
                    });
                    return;
                }
                this.resetModal = true;
                this.resetTaskId = dataId;
                this.isBatch = isBatch;
            },
            handleModalOnOk () {
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
                    let app = this;
                    app.taskReject(app.rejectTaskId, app.rejectReason);
                }
            },
            handleResetModalOnOk () {
                if (this.resetReason.trim() === '') {
                    this.$Message.error({
                        content: this.$t('tool_reason_empty'),
                        duration: 2,
                    });
                    return;
                }
                if (this.selectAllIsOn && this.selectedTask.length > 0 && this.isBatch) {
                    this.batchReset();
                } else {
                    let app = this;
                    app.taskReset(app.resetTaskId, app.resetReason);
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
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
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
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            // let selectedIndex = this.selectedTask.indexOf(dataId);
                            // if(selectedIndex !== -1)  {
                            //     this.selectedTask.splice(selectedIndex, 1);
                            // }
                            this.rejectModal = false;
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
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id) {
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
                            this.$Message.error({
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            // let selectedIndex = this.selectedTask.indexOf(dataId);
                            // if(selectedIndex !== -1)  {
                            //     this.selectedTask.splice(selectedIndex, 1);
                            // }
                            this.resetModal = false;
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
            editModalVisibleChange (flag) {
                if (flag) {
                    this.$nextTick(() => {
                        this.editTask(this.tableData[this.currentTaskIndex]);
                    });
                }
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
            editTask (task) {
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
                            let taskData = this.tableData[this.currentTaskIndex].data;
                            let parentWorks = this.tableData[this.currentTaskIndex].parentWorks;
                            this.taskItemInfo = this.$t('tool_job_id') + ':' + taskData.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataName: taskData.name,
                                dataId: taskData.id,
                                user: (parentWorks && parentWorks[0] && parentWorks[0].user) || {}
                            };
                            let resource = this.currentTaskResource;
                            // this.currentTaskResult ||
                            let result = res.data.dataResultInfo.result ||
                                res.data.dataResultInfo.ai_result;
                            let container = $(this.$refs.templateView.$el);
                            let targetList = $.makeArray(container.find('[data-tpl-type="text-file-placeholder"] .text-container'));
                            let unmatchedResource = [];
                            // 先检查是否和数据锚点匹配
                            resource.forEach((item) => {
                                let key = item[0];
                                let value = item[1];
                                value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                                let target = container.find(`[data-tpl-type="text-file-placeholder"][data-target='${key}'] .text-container`);
                                if (target.length) {
                                    target.html(`<pre style="white-space: pre-wrap;padding: 4px;">${value}</pre>`);
                                    let index = container.find('[data-tpl-type="text-file-placeholder"] .text-container').index(target);
                                    targetList.splice(index, 1);
                                } else {
                                    unmatchedResource.push(item);
                                    // $(targetList[i]).html(`<pre style="white-space: pre-wrap;">${value}</pre>`);
                                }
                            });
                            unmatchedResource.forEach((item, i) => {
                                let key = item[0];
                                let value = item[1];
                                value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                                let target = $(targetList[i]);
                                target.html(`<pre style="white-space: pre-wrap;padding: 4px;">${value}</pre>`);
                            });
                            EventBus.$emit('setupMarker');
                            EventBus.$emit('ready');
                            this.selectionList = result.data || [];
                            this.selectText = null;
                            this.usedLabel = {};
                            this.selectionList.forEach((v) => {
                                v.step = v.step || '';
                                this.updateUsedLabel(v.label);
                            });
                            this.usedLabelChange();
                            this.highLightSelection();
                            this.unbindLabelEvent();
                            this.bindLabelEvent();
                            $('.region-table-list').draggable({
                                containment: 'parent'
                            });
                            let ref = this.$refs.templateView.$el;
                            let layout = ref.querySelector('[data-tpl-type="layout"]');
                            let layoutBox = layout.getBoundingClientRect();
                            let regionList = this.$refs.regionList;
                            if (!regionList.isfixed) {
                                regionList.isfixed = true;
                                regionList.style.top = layoutBox.top + layoutBox.height - regionList.getBoundingClientRect().top + 'px';
                            }
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
                            Vue.nextTick(() => {
                                this.defaultAttrInfo = this.getAttrInfo();
                            });
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                    }
                });
            },
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
                let data = this.selectionList;
                let info = this.$refs.templateView.getGlobalData();
                let validValue = this.$refs.templateView.getDataIsValid();
                if (validValue) {
                    switch (validValue.value) {
                        case dataIsValid.yes: {
                            if (data instanceof Array && data.length === 0) { // 标注数据为空
                                this.loading = false;
                                this.$Message.warning({
                                    content: this.$t('tool_result_empty'),
                                    duration: 3,
                                });
                                return;
                            } else if (data instanceof Array && data.length) {
                                // 检验标签
                                if (this.checkRequiredTagGroup()) {
                                    if (!this.checkRequiredTag()) {
                                        this.loading = false;
                                        return;
                                    }
                                } else {
                                    this.loading = false;
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
                                    this.loading = false;
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
                let result = {
                    data,
                };
                if (info.length) {
                    result.info = info;
                }
                let dataId = this.tableData[this.currentTaskIndex].data.id;
                let parentWorkResult = this.tableData[this.currentTaskIndex].parentWorkResults[0];
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
                            let parentWorkResults = this.tableData[this.currentTaskIndex].parentWorkResults;
                            this.taskPass(dataId, parentWorkResults[parentWorkResults.length - 1].work_id);
                        }
                    },
                    error: (res) => {
                        this.loading = false;
                    }
                });
            },
            exit () {
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
        filters: {
            formatLabel: function (label) {
                let ret = ' ';
                if (!label) return ret;
                label.forEach(item => {
                    ret += item.label || ' ';
                    ret += (item.code && ` < ${item.code} >`) || ' ';
                });
                return ret;
            },
        },
        beforeDestroy () {
            this.unbindLabelEvent();
            EventBus.$off('clear-fetchTask', this.userIdChange);
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('formElementChange', this.saveSelectionAttr);
            EventBus.$off('setLabel', this.setLabel);
            EventBus.$off('setDefaultLabel', this.setDefaultLabel);
            EventBus.$off('appendLabel', this.appendLabel);
            EventBus.$off('deleteLabel', this.deleteLabel);
            $(window).off('keyup', this.handleKeyUp);
        },
        components: {
            TextAnnotationResultList,
            'template-view': () => import('components/template-produce'),
            'task-progress': () => import('../components/taskprogress.vue'),
            'task-info': TaskInfo,
            'audit-by-user': auditByUser,
            ErrorTaskReasonShow,
            'text-audit-view': () => import('components/task-audit-view/text-audit-view'),
        }
    };
</script>

<style lang="scss">
    @import url('../../../styles/table.css');

    .region-table-list {
        max-height: 420px;
        overflow-y: auto;
        background-color: #fff;
        padding: 0 15px;
        border: 2px solid #eee;
        border-radius: 8px;
        z-index: 2;

        .drag-header {
            cursor: move;
            padding: 10px;
            display: flex;
            justify-content: space-between;
        }
    }

    .edit-modal-wrapper {
        .edit-modal-header {
            display: flex;
            justify-content: flex-end;
            align-items: center;

            .task-info {
                margin-right: 15px;
            }
        }

        .data-container-wrapper {
            position: fixed;
            top: 68px;
            width: 56%;
            max-height: calc(100vh - 84px);
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
            background: #fff !important;
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
</style>

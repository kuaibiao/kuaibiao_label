<template>
    <div class="" style="position:relative;">
        <div class="task-header">
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
                        :timeout="ptimeout"
                ></task-progress>
                <Button v-if="feedback.trim() !== ''" type="error" size="small" @click.native="viewFeedback">
                    {{$t('tool_view_reject')}}
                </Button>
                <Button type="warning" size="small" @click.native="setDifficult" :loading="loading">
                    {{$t('tool_submit_difficult_job')}}
                </Button>
                <Button type="warning" size="small" @click.native="exit">{{$t('tool_quit')}}</Button>
                <Button type="primary" size="small" @click.native="submitAndExit">{{$t('tool_submit_exit')}}</Button>
                <Button type="primary" size="small" @click.native="submit" :loading="loading">{{$t('tool_submit_D')}}
                </Button>
                <Tooltip :transfer="true" placement="bottom-end" style="margin-left:10px; margin-right:10px;">
                    <Icon type="ios-help-circle-outline" size="24"></Icon>
                    <div slot="content">
                        <code>X </code> {{$t('tool_switch_label_modes')}}<br>
                        <code>Ctrl + Z </code>{{$t('tool_undo_label_result')}}<br>
                        <code>V </code>{{$t('tool_copy_label_result')}}<br>
                    </div>
                </Tooltip>
            </div>
        </div>
        <template-view
                :config="templateInfo"
                scene="execute"
                ref="templateView"
        >
        </template-view>
        <Spin fix v-if="loading">{{loadingText}}</Spin>
        <Modal v-model="feedbackModal"
               :title="$t('tool_reject_reason')">
            <ErrorTaskReasonShow :reason="feedback"></ErrorTaskReasonShow>
        </Modal>
        <div class="region-table-list" ref="regionList" style="position:static;margin:20px 0;">
            <h4 class="drag-header">
                <div>
                    <span>{{$t('tool_job_result')}}：({{$t('tool_drag')}})</span>
                    <span style="padding-left: 15px; color: #00B83F">
                        {{ annotateModal ? this.$t('tool_annotated_schema'): this.$t('tool_not_annotated_schema')}}
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
    </div>
</template>
<script>
    import Vue from 'vue';
    import api from '@/api';
    import util from "@/libs/util";
    import TemplateView from 'components/template-produce';
    import taskProgress from '../components/taskprogress.vue';
    import TaskInfo from '../components/task-info.vue';
    import UserStat from '../components/user-stat.vue';
    import EventBus from '@/common/event-bus';
    import findIndex from 'lodash.findindex';
    import uuid from 'uuid/v4';
    import 'jquery-ui';
    import Mark from 'mark.js';
    import cloneDeep from 'lodash.clonedeep';
    import dataIsValid from '../../../common/dataIsValid';
    import commonMixin from '../mixins/commom';

    export default {
        name: 'produce-text-annotation',
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
                ptimeout: 100,
                currentTaskIndex: 0,
                loading: true,
                clientTime: Math.floor(new Date().valueOf() / 1000),
                taskItemInfo: '',
                taskItemInfoMore: {},
                loadingText: this.$t('tool_loading'),
                userStat: {},
                feedback: '',
                feedbackModal: false,
                selectionList: [],
                selectText: null,
                label: null,
                defaultAttrInfo: [],
                draggableIsOn: false,
                textPlaceHolderReady: false,
                workedTaskId: {},
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
            dataId () {
                return this.taskList[this.currentTaskIndex].data.id;
            },
            userId () {
                return this.$store.state.user.userInfo.id;
            }
        },
        watch: {
            timeout (v) {
                this.ptimeout = v;
            },
            taskList () {
                this.taskInit(this.taskList[this.currentTaskIndex]);
            },
        },
        mounted () {
            EventBus.$on('textFilePlaceholderReady', this.initTask);
            this.userStat = this.taskStat;
            this.workedTaskId = {};
            EventBus.$on('task-timeout', this.setTaskTimeout);
            EventBus.$on('setLabel', this.setLabel);
            EventBus.$on('setDefaultLabel', this.setDefaultLabel);
            EventBus.$on('appendLabel', this.appendLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('formElementChange', this.saveSelectionAttr);
            this.handleMouseUp = this.handleMouseUp.bind(this);
            this.handleKeyUp = this.handleKeyUp.bind(this);
            $(window).on('keyup', this.handleKeyUp);
            $('.region-table-list').draggable({
                containment: 'parent'
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
                    }
                    this.updateWorkerInfo(this.selectText);
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
                    this.renderLabelList(this.selectText);
                    this.highLightSelection();
                    this.updateUsedLabel(this.selectText.label);
                    this.usedLabelChange();
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
            viewFeedback () {
                this.feedbackModal = true;
            },
            getTime () {
                let now = Math.floor(new Date().valueOf() / 1000);
                return +this.serverTime + (now - this.clientTime);
            },
            setTaskTimeout () {
                this.loadingText = this.$t('tool_timed_out');
                this.loading = true;
                this.$Modal.remove();
            },
            bindLabelEvent () {
                $(this.$refs.templateView.$el).find('.text-container').parents('.children-con').on('mouseup', this.handleMouseUp);
            },
            unbindLabelEvent () {
                $(this.$refs.templateView.$el).find('.text-container').parents('.children-con').off('mouseup', this.handleMouseUp);
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
            initTask () {
                Vue.nextTick(() => { // 初始化第一个任务
                    if (this.textPlaceHolderReady) {
                        return;
                    }
                    this.taskInit(this.taskList[this.currentTaskIndex]);
                    this.textPlaceHolderReady = true;
                });
            },
            taskInit (taskData) {
                this.getTaskResource(taskData.data.id);
            },
            highLightSelection () {
                let rangList = this.selectionList.map((selection) => {
                    let color = (selection.label && selection.label[0].color) || '0xffff00';
                    return {
                        start: selection.start,
                        length: selection.end - selection.start,
                        color: color,
                        isCurrentSelection: selection === this.selectText
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
                                content: res.message,
                                duration: 2,
                            });
                        } else {
                            let resource = Object.entries(res.data || {});
                            if (resource.length === 0) {
                                // this.$Message.destroy();
                                // this.$Message.error({
                                //     content: '作业资源获取失败',
                                //     duration: 2,
                                // });
                                // return;
                                resource = [['subject', {}]];
                            }
                            this.executeTask(this.dataId, resource);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            executeTask (dataId, resource) {
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
                            this.ptimeout = res.data.timeout;
                            let taskData = this.taskList[this.currentTaskIndex];
                            this.taskItemInfo = this.$t('tool_job_id') + ': ' + taskData.data.id;
                            this.taskItemInfoMore = {
                                ...this.taskInfo,
                                dataId: taskData.data.id,
                                dataName: taskData.data.name,
                            };
                            let result = this.prepareResult(taskData);
                            this.feedback = taskData.workResult.feedback || '';
                            let targetList = $.makeArray($('[data-tpl-type="text-file-placeholder"] .text-container'));
                            let unmatchedResource = [];
                            // 先检查是否和数据锚点匹配
                            resource.forEach((item) => {
                                let key = item[0];
                                let value = item[1];
                                value = (~key.indexOf('subject') ? '' : (key + ': ')) + value.content;
                                let target = $(`[data-tpl-type="text-file-placeholder"][data-target='${key}'] .text-container`);
                                if (target.length) {
                                    target.html(`<pre style="white-space: pre-wrap;padding: 4px;">${value}</pre>`);
                                    let index = $('[data-tpl-type="text-file-placeholder"] .text-container').index(target);
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
                            // console.log(targetList, unmatchedResource);
                            // $('[data-tpl-type="text-file-placeholder"] .text-container').html(`<pre style="white-space: pre-wrap;">${subject}</pre>`);
                            this.textPlaceHolderReady = false;
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
                            let ref = this.$refs.templateView.$el;
                            let layout = ref.querySelector('[data-tpl-type="layout"]');
                            let layoutBox = layout.getBoundingClientRect();
                            let regionList = this.$refs.regionList;
                            if (!regionList.isfixed) {
                                regionList.isfixed = true;
                                regionList.style.top = layoutBox.top + layoutBox.height + 32 - regionList.getBoundingClientRect().top + 'px';
                            }
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

                            Vue.nextTick(() => {
                                this.defaultAttrInfo = this.getAttrInfo();
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
            saveTaskResult (next = true, isDifficult = 0) {
                if (this.workedTaskId[this.dataId]) {
                    return;
                }
                if (this.loading) {
                    return;
                }
                let data = this.selectionList;
                let info = this.getGlobalInfo();
                let validValue = this.$refs.templateView.getDataIsValid();
                if (validValue && isDifficult === 0) {
                    switch (validValue.value) {
                        case dataIsValid.yes: {
                            if (data instanceof Array && data.length === 0) { // 标注数据为空
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: this.$t('tool_result_empty'),
                                    duration: 3,
                                });
                                return;
                            } else if (data instanceof Array && data.length) {
                                // 检验标签
                                if (this.checkRequiredTagGroup()) {
                                    if (!this.checkRequiredTag()) {
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
                result[this.dataId] = {
                    data,
                    is_difficult: isDifficult
                };
                if (info.length) {
                    result[this.dataId].info = info;
                }
                let reqData = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.query.project_id,
                    task_id: this.$route.query.task_id,
                    data_id: this.dataId,
                    result: JSON.stringify(result),
                    op: 'submit',
                };
                if (!reqData.access_token || !reqData.project_id || !reqData.task_id || !reqData.data_id) {
                    return;
                }
                this.loading = true;
                this.workedTaskId[this.dataId] = true;
                $.ajax({
                    url: api.task.execute,
                    type: 'post',
                    data: reqData,
                    success: (res) => {
                        this.loading = false;
                        if (res.error) {
                            delete this.workedTaskId[this.dataId];
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
                            this.selectText = null;
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
                        loading: true,
                        okText: this.$t('tool_submit_exit'),
                        cancelText: this.$t('tool_cancel'),
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
            }
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
            EventBus.$off('task-timeout', this.setTaskTimeout);
            EventBus.$off('textFilePlaceholderReady', this.initTask);
            EventBus.$off('formElementChange', this.saveSelectionAttr);
            EventBus.$off('setLabel', this.setLabel);
            EventBus.$off('setDefaultLabel', this.setDefaultLabel);
            EventBus.$off('deleteLabel', this.deleteLabel);
            EventBus.$off('appendLabel', this.appendLabel);
            $(window).off('keyup', this.handleKeyUp);
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
</style>



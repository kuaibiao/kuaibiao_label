<template>
    <div class="" style="position:relative; height: 100%;">
        <Row>
            <i-col>
                <template-view
                        :config="templateInfo"
                        scene="execute"
                        ref="templateView">
                </template-view>
            </i-col>
        </Row>
        <div class="region-table-list" ref="regionList">
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
        <Modal v-model="showResultModal"
               :width="550"
               :title="$t('tool_job_result')"
               footer-hide>
            <pre style="padding: 0 15px;max-height: 500px;overflow-y: auto">{{JSON.stringify(submitData, null, 2)}}</pre>
        </Modal>
    </div>
</template>
<script>
    import TemplateView from 'components/template-produce';
    import EventBus from '@/common/event-bus';
    import commonMixin from '../mixins/commom.js';
    import findIndex from 'lodash.findindex';
    import cloneDeep from 'lodash.clonedeep';
    import uuid from 'uuid/v4';
    import 'jquery-ui';
    import Mark from 'mark.js';
    import {TEXT} from '../../../common/previewDefaultData';

    export default {
        mixins: [commonMixin],
        props: {
            templateInfo: {
                type: Array,
                default: [],
            },
            categoryInfo: {
                type: Object,
                required: true,
            },
        },
        data () {
            return {
                submitData: [],
                showResultModal: false,
                selectionList: [],
                selectText: null,
                label: null,
                defaultAttrInfo: [],
                draggableIsOn: false,
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
        mounted () {
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

            setTimeout(() => {
                this.init();
            }, 300);
        },
        methods: {
            init () {
                let ref = this.$refs.templateView.$el;
                let target = ref.querySelector('[data-tpl-type="text-file-placeholder"] .text-container');
                target.innerHTML = `<pre style="white-space: pre-wrap; padding: 4px">${TEXT} </pre>`;
                EventBus.$emit('setupMarker');
                EventBus.$emit('ready');
                this.selectionList = [];
                this.selectText = null;
                this.usedLabel = {};
                this.highLightSelection();
                this.unbindLabelEvent();
                this.bindLabelEvent();
                let layout = ref.querySelector('[data-tpl-type="layout"]');
                let layoutBox = layout.getBoundingClientRect();
                let regionList = this.$refs.regionList;
                if (!regionList.isfixed) {
                    regionList.isfixed = true;
                    regionList.style.top = layoutBox.top + layoutBox.height - regionList.getBoundingClientRect().top + 'px';
                }
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
                if (target.tagName.toLowerCase() === 'input' && target.type === 'text') {
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
                    this.selectionList.splice(index, 0, copy); // 在选中的索引位置插入复制的结果
                    this.selectText = copy;
                    this.renderLabelList(this.selectText);
                    this.highLightSelection();
                }
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
                        type: 'text-annotation',
                        text: text,
                        label: this.label ? [...this.label] : [],
                        id: uuid(),
                        start: start,
                        end: end, // 选中的字符串 [ ) 不包括end位置的字符
                        attr: this.defaultAttrInfo.slice(),
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
            getSubmitData () {
                this.submitData = {
                    data: this.selectionList,
                    info: this.$refs.templateView.getGlobalData()
                };
                this.showResultModal = true;
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
            EventBus.$off('formElementChange', this.saveSelectionAttr);
            EventBus.$off('setLabel', this.setLabel);
            EventBus.$off('setDefaultLabel', this.setDefaultLabel);
            EventBus.$off('deleteLabel', this.deleteLabel);
            EventBus.$off('appendLabel', this.appendLabel);
            $(window).off('keyup', this.handleKeyUp);
        },
        components: {
            'template-view': TemplateView,
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



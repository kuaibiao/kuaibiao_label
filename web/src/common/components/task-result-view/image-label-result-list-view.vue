<!--标注结果列表-->
<template>
    <div class="image-label-result-wrapper" ref="wrapper">
        <h1 style="font-size: 14px;">{{$t('tool_result_list')}}({{resultList.length}})</h1>
        <div class="result-list" ref="resultList">
            <div class="result-item" v-for="(result, index ) in resultList"
                 :key="result.id"
                 :class="currentItem && (currentItem.id === result.id) ? 'selected' : ''"
                 @click="selectResult(result)"
            >
            <!-- 矩形框的角度显示 -->
                <div v-if="result.angle" class="result-angle">
                    <span class="result-index">{{index + 1}}</span>
                    <Icon :custom="'annotation-'+shapeIconName[result.type]" size="16"
                      :title="labelShapeTypeTitleFun(result.type)"
                      v-if="shapeIconName[result.type] && result.angle"/>
                    <span class="angle-icon">{{result.angle.toFixed(2)}}</span>
                </div>
                <div class="result-info" :class=" result.angle ? 'has-angle' : ''">
                    <span class="result-index" v-if="!result.angle">{{index + 1}}</span>
                    <!--形状-->
                    <Icon :custom="'annotation-'+shapeIconName[result.type]" size="16"
                        :title="labelShapeTypeTitleFun(result.type)"
                        v-if="shapeIconName[result.type] && !result.angle"/>
                    <!--标签-->
                    <div class="result-label-text" v-if="result.type !== 'bonepoint'">
                        <div class="result-label-list">
                            <span class="result-label" v-for="(label, index) in result.label" :key="index"
                                v-show="label.toString()">
                                {{ result.code[index] ? result.code[index].toString() : label }}
                            </span>
                        </div>
                        <div class="result-text" v-if="result.text">
                            <pre>{{result.text}}</pre>
                        </div>
                    </div>
                    <div class="result-is-key-point" v-else>
                        <div class="key-point-name">{{ result.name }}</div>
                        <div class="key-point-number"> {{result.length + '/' + result.totalPointNumber}}</div>
                    </div>
                    <!--删除-->
                    <div class="operator-box" v-if="(result.type !== 'bonepoint') && canDelete">
                        <Icon type="ios-trash" size="18" @click.stop="deleteResult(result)"/>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
    import EventBus from '../../event-bus';

    export default {
        name: 'image-label-result-list',
        props: {
            canDelete: {
                type: Boolean,
                default: true
            }
        },
        data () {
            return {
                resultList: [],
                currentItem: null,
                shapeIconName: {
                    'line': 'shape-line',
                    'circler': 'shape-circler',
                    'ellipse': 'shape-ellipse',
                    'unclosedpolygon': 'cutoffline',
                    'rect': 'shape-rectangle',
                    'rectP': 'shape-rectp',
                    'rectS': 'shape-rects',
                    'polygon': 'shape-polygon',
                    'trapezoid': 'shape-trapezoid',
                    'triangle': 'shape-triangle',
                    'quadrangle': 'shape-quadrangle',
                    'cuboid': 'shape-cuboid',
                    'bonepoint': 'shape-bonepoint',
                    'point': 'shape-dot',
                    'closedcurve': 'shape-closecurve',
                    'splinecurve': 'shape-curve',
                    'pencilline': 'shape-pencilline',
                },
            };
        },
        methods: {
            // 功能：返回标注提示
            labelShapeTypeTitleFun (type) {
                let result = '';
                let supportType = ['rect', 'polygon','line','circler', 'ellipse',
                                    'unclosedpolygon', 'rectP', 'rectS','trapezoid',
                                    'quadrangle', 'cuboid', 'bonepoint', 'point',
                                    'closedcurve', 'splinecurve', 'pencilline', 'rectS']
                if (~supportType.indexOf(type)) {
                    result = this.$t('operator_shape_' + type)
                }
                return result;
            },
            selectResult (item) {
                // if (item.type === 'bonepoint') {
                //     return;
                // }
                EventBus.$emit('selectShape', item.id);
            },
            deleteResult (item) {
                EventBus.$emit('removeShape', item.id);
            },
            updateResultList (payload) {
                this.resultList = payload.resultList || payload;
                this.currentItem = payload.currentItem || null;
                this.$nextTick(() => {
                    let scrollElement = this.$refs.resultList;
                    if (!scrollElement) return;
                    let selectResultEle = scrollElement.querySelector('.result-item.selected');
                    let position = 0;
                    let needScroll = false;
                    if (scrollElement.clientHeight >= scrollElement.scrollHeight) {
                        needScroll = false;
                    } else if (selectResultEle) {
                        let preElement = selectResultEle.previousElementSibling;
                        while (preElement) {
                            position += preElement.getBoundingClientRect().height;
                            preElement = preElement.previousElementSibling;
                        }
                        let fromTop = position - scrollElement.scrollTop; // 选中的元素距离父元素顶部的距离
                        if (fromTop > scrollElement.clientHeight) { // 大于父元素的可视高亮,在下边
                            needScroll = true;
                            position -= scrollElement.clientHeight - selectResultEle.clientHeight;
                        } else if (fromTop < 0) { // 在上边不可见
                            needScroll = true;
                        }
                    }
                    if (needScroll) {
                        scrollElement.scrollTo(0, position);
                    }
                });
            },
            setResultListHeight () {
                if (this.$refs.wrapper && this.$refs.resultList) {
                    let parent = this.$refs.wrapper;
                    let height = window.innerHeight - parent.getBoundingClientRect().top;
                    height = height > 520 ? height : 520;
                    let ele = this.$refs.resultList;
                    ele.style.height = height - 80 + 'px';
                    ele.style.overflowY = 'auto';
                    return true;
                } else {
                    setTimeout(() => {
                        this.setResultListHeight();
                    }, 1000);
                }
            }
        },
        mounted () {
            EventBus.$on('renderResultList', this.updateResultList);
            window.addEventListener('resize', this.setResultListHeight);
            setTimeout(() => {
                this.setResultListHeight();
            }, 1000);
        },
        destroyed () {
            EventBus.$off('renderResultList', this.updateResultList);
            window.removeEventListener('resize', this.setResultListHeight);
        }
    };
</script>
<style lang="scss" scoped>
    .image-label-result-wrapper {
        padding: 5px 4px;
        border: 1px solid #d7d7d7;
    }

    .result-list {
        height: calc(80vh);
        overflow-y: hidden;
    }

    .result-item {
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        padding: 6px;
        position: relative;

        & + .result-item {
            border-top: 1px solid #eee;
        }

        &.selected {
            color: #000;
            background-color: rgba(46, 135, 240, 0.65);
        }
        .result-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            &.has-angle {
                margin-left: 12px;
            }
        }
        .result-index {
            font-weight: bolder;
            padding-right: 4px;
            color: #515a6e;
        }
        .result-angle {
            color: #FAAD14;
            .angle-icon {
                padding-left: 24px;
                background-image: url('../../../images/rect-angle.svg');
                background-size: contain;
                background-position: 4px center;
                background-repeat: no-repeat;
            }
        }
    }

    .result-label-text {
        flex-basis: 100%;
        display: flex;
        flex-direction: column;
        align-items: flex-start;
        line-height: 1.5;
        padding: 0 4px;

        .result-label {
            border: 1px solid rgba(209, 209, 209, 1);
            padding: 2px 4px;
            border-radius: 3px;
            margin-right: 3px;
            margin-top: 3px;
            display: inline-block;
            background-color: #fff;
        }

        .result-text {
            pre {
                margin: 0;
                white-space: pre-line;
            }

        }

        .result-label-list + .result-text {
            margin-top: 4px;
        }
    }

    .result-is-key-point {
        flex-basis: 100%;
        display: flex;
        justify-content: space-around;
    }
</style>

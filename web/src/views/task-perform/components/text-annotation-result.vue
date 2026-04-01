<template>
    <div class="result-wrapper" >
        <h4>
            <span v-if="index >= 0">{{$t('tool_result')}}：{{index + 1}} </span>
            <span class="result-operator" v-if="user.email"> {{$t('tool_operator')}}:
                <Tooltip :transfer="true">
                    <span>{{ user.nickname }}</span>
                    <div slot="content">
                        <div>ID: {{ user.id }}</div>
                        <div>{{$t('tool_email')}}: {{user.email }}</div>
                    </div>
                </Tooltip>
            </span>
        </h4>
        <p class="result-item" v-for="item in info" :key="item.id" v-if="info && info.length > 0">
            <span>{{ item.header }}:</span>
            <span class="result-answer">{{ item.value.toString() }}</span>
        </p>
        <table class="table" width="100%" v-if="sortedData && sortedData.length > 0">
            <thead>
                <tr>
                    <th width="40%" class="note-text ">{{$t('tool_tagging_content')}}</th>
                    <th width="20%" class="note-text ">{{$t('tool_label')}}</th>
                    <th width="15%" class="note-text ">{{$t('tool_text_point')}}</th>
                    <th width="25%" class="note-text ">{{$t('tool_attribute')}}</th>
                </tr>
            </thead>
            <tbody >
                <tr v-for="(item, index) in sortedData" :key="item.id" @click="handleTrClick(item)" style="cursor: pointer;">
                    <td width="40%" class="note-text note-content"
                        :class="highlight('text', item.text, index) ? 'isdiff' : ''"> {{ item.text }} </td>
                    <td width="20%" class="note-text note-label"
                        :class="highlight('label', formatLabel(item.label), index) ? 'isdiff' : ''">
                        {{ formatLabel(item.label) }}
                    </td>
                    <td width="15%" class="note-text note-pos"
                        :class="highlight('position', (item.start + '--' + item.end), index) ? 'isdiff' : ''"
                     >{{ item.start + '--' + item.end}}</td>
                    <td width="25%" class="note-text note-attr "
                        :class="highlight('attr', item.attr, index) ? 'isdiff' : ''">
                        <div v-for="(attr,index) in item.attr" :key="index" v-if="attr.value && attr.value.length > 0">
                            {{attr.header + ':' + attr.value}}
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import sortBy from 'lodash.sortby';
import * as Diff from 'diff';
import EventBus from '@/common/event-bus';
export default {
    name: 'text-annotation-result',
    props: {
        data: {
            type: Array,
            required: true,
        },
        info: {
            type: Array,
            required: false,
        },
        index: {
            type: Number,
            required: true,
        },
        baseData: {
            type: Object,
            required: false,
        },
        user: {
            type: Object,
            required: false,
        }
    },
    data () {
        return {
            sortedData: [],
        };
    },
    watch: {
        data (source) {
            this.sortedData = sortBy(source, (item) => {
                return +item.start;
            });
        }
    },
    mounted () {
        this.sortedData = sortBy(this.data, (item) => {
            return +item.start;
        });
    },
    methods: {
        handleTrClick (item) {
            EventBus.$emit('highlightRange', {
                start: item.start,
                length: item.end - item.start
            });
        },
        formatLabel (label) {
            let ret = ' ';
            if (!label) return ret;
            label.forEach(item => {
                ret += item.label || ' ';
                ret += (item.code && ` < ${item.code} >`) || ' ';
            });
            return ret;
        },
        highlight (attr, newText, index) {
            if (!this.baseData) {
                return false;
            }
            switch (attr) {
                case 'text': {
                    let item = this.baseData.result.data[index] || {};
                    let oldText = item[attr] || '';
                    let diff = Diff.diffChars(oldText, newText);
                    // console.log(diff, index);
                    return oldText === '' || diff.length > 1;
                }
                case 'label': {
                    let item = this.baseData.result.data[index] || {};
                    let label = item[attr] || [];
                    let oldText = this.formatLabel(label);
                    let diff = Diff.diffChars(oldText, newText);
                    return oldText === '' || diff.length > 1;
                }
                case 'position': {
                    // let item = this.baseData.result.data[index] || {};
                    // let oldText = (item.start || 0) + '--' + (item.end || 0);
                    // return oldText !== newText;
                    return false; // 把这行注释 把上边的打开即可高亮文本位置不一样的标注
                }
                case 'attr': {
                    let item = this.baseData.result.data[index] || {};
                    let attrs = item[attr] || [];
                    let oldText = '';
                    attrs.map(item => {
                        oldText += item.header + ':' + item.value;
                    });
                    let newText2 = '';
                    newText.map(item => {
                        newText2 += item.header + ':' + item.value;
                    });
                    let diff = Diff.diffChars(oldText, newText2);
                    return oldText === '' || diff.length > 1;
                }
            }
        },
    },
};
</script>
<style lang="scss">
    .isdiff {
        background-color: yellowgreen;
        background-clip: content-box;
    }
    .result-wrapper {
        padding: 15px;
        border: 2px solid #edf0f6;
        width: 100%;
        overflow: auto;
        .select-text, .label-item {
            font-size: 16px;
            color: #000;
        }
        .result-item {
            margin: 0 2px;
            padding: 2px;
            color: #2d8cf0;
            width: 100%;
            text-align: left;
            font-size: 13px;
            line-height: 1.35;
            /*overflow-y: scroll;*/
            span {
                min-width: 60px;
            }
            .result-answer {
                display: block;
                color: #333;
                padding-left: 15px;
                white-space: pre;
                /*white-space: nowrap;*/
            }
        }
        .result-operator {
            padding-left: 15px;
            color: cornflowerblue;
        }
    }
</style>



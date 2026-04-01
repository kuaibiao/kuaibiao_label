<template>
    <div class="result-wrapper">
        <h4 >
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
        <div v-for="item in info" :key="item.id" v-if="info && info.length">
            <p class="result-item"> {{ item.header }} :  <span class="result-answer">{{ item.value.toString() }}</span></p>
        </div>
        <table class="table table-hover" width="100%"  v-if=" sortedData && sortedData.length > 0 ">
            <thead>
            <tr>
                <th width="5%">{{$t('tool_serial')}}</th>
                <th width="10%">{{$t('tool_time')}}</th>
                <th width="30%" class="note-text">{{$t('tool_tagging_content')}}</th>
                <th width="25%" class="note-text">{{$t('tool_label')}}</th>
                <!-- <th width="15" >错误原因</th> -->
                <th width="15%">{{$t('tool_handle')}}</th>
            </tr>
            </thead>
            <tbody >
                <tr v-for="(region, index ) in sortedData"
                    :class=" selectRegion && selectRegion.id === region.id ? 'active' : ''"
                    :key="index"
                    @click="regionClick(region)"
                >
                    <td width="5%">{{ index + 1}}</td>
                    <td width="12%">{{(region.start * 1).toFixed(3) + ' -- ' + (region.end * 1).toFixed(3)}}</td>
                    <td width="40%" class="note-text"> {{ region.note && region.note.text }} </td>
                    <td width="40%" class="note-text">{{ region.note && region.note.attr.filter( item => {
                        return item.value.length;
                        }).map( item => {
                        return item.header + ':' + item.value
                    }).join(' / ') }}</td>
                    <!-- <th width="15" >{{ region.reason }}</th> -->
                    <td width="4%">
                        <Button type="primary" size="small" @click.stop="regionPlay(region)"><Icon type="md-play"></Icon></Button>
                    </td>
                </tr>
            </tbody>
        </table>
    </div>
</template>
<script>
import sortBy from 'lodash.sortby';
export default {
    name: 'voice-trans-result',
    props: {
        data: {
            type: Array,
            required: true,
        },
        info: {
            type: Array,
            required: false,
        },
        selectRegion: {
            type: Object,
            required: false,
        },
        index: {
            type: Number,
            required: true,
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
    mounted () {
        this.sortedData = sortBy(this.data, (item) => {
            return +item.start;
        });
    },
    watch: {
        data (source) {
            this.sortedData = sortBy(source, (item) => {
                return +item.start;
            });
        }
    },
    methods: {
        regionClick (region) {
            this.$emit('click-region', region);
        },
        regionPlay (region) {
            this.$emit('play-region', region);
        },
    }
};
</script>

<style lang="scss" scoped>
@import url('../../../styles/table.css');
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
        padding: 2px 8px;
        color: #666;
        .result-answer {
            color: #333;
            font-size: 14px;
            white-space: pre;
        }
    }
    .result-operator {
        padding-left: 15px;
        color: cornflowerblue;
    }
}
</style>

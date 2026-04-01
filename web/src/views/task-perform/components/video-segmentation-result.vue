<!--视频分割:查看结果-->
<template>
    <div class="result-wrapper">
        <h4>
            <span v-if="index >= 0">{{this.$t('tool_result')}}：{{index + 1}} </span>
            <span class="result-operator" v-if="user && user.email"> {{this.$t('tool_operator')}}:
                <Tooltip :transfer="true">
                    <span>{{ user.nickname }}</span>
                    <div slot="content">
                        <div>ID: {{ user.id }}</div>
                        <div>{{this.$t('tool_email')}}: {{user.email }}</div>
                    </div>
                </Tooltip>
            </span>
        </h4>
        <h4 v-if="data.length"> {{$t('tool_form_result')}}:</h4>
        <div class="result-list">
            <p class="result-item" v-for="item in info" :key="item.id">                                
                <span>{{ item.header }}:</span>
                <span class="result-answer">{{ item.value.toString() }}</span>                 
            </p>            
        </div>
    </div>
</template>

<script>
export default {
    name: 'video-segmentation-result',
    props: {
        data: {
            type: Array,
            required: true,
        },
        info: {
            type: Array,
            required: true,
        },
        index: {
            type: Number,
            required: true,
        },
        user: {
            type: Object,
            required: false,
        }
    }
};
</script>
<style lang="scss" scoped>
    .result-wrapper {
        padding: 8px;
        border: 2px solid #edf0f6;
        width: 100%;
        overflow: auto;
        .select-text, .label-item {
            font-size: 16px;
            color: #000;
        }
        .result-list {
            display: flex;
            flex-wrap: wrap;
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
            color: #2d8cf0;
        }
    }
</style>
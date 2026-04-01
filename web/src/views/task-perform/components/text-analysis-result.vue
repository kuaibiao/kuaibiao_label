<template>
    <div class="result-wrapper">
        <h4>
            <span v-if="index >= 0">{{$t('tool_result')}}：{{index + 1}} </span>
            <span class="result-operator" v-if="user && user.email"> {{$t('tool_operator')}}:
                <Tooltip :transfer="true">
                    <span>{{ user.nickname }}</span>
                    <div slot="content">
                        <div>ID: {{ user.id }}</div>
                        <div>{{$t('tool_email')}}: {{user.email }}</div>
                    </div>
                </Tooltip>
            </span>
        </h4>
        <h4 v-if="data.length"> {{$t('tool_form_result')}}:</h4>
        <div class="result-list">
            <p class="result-item" v-for="item in data" :key="item.id">
                <span>{{ item.header }}:</span>
                <span class="result-answer" v-html="processObj(item)"></span>
            </p>
        </div>
    </div>
</template>
<script>
import util from '@/libs/util.js';
    export default {
        name: 'text-analysis-result',
        props: {
            data: {
                type: Array,
                required: true
            },
            index: {
                type: Number,
                required: true
            },
            user: {
                type: Object,
                required: false
            }
        },
        watch:{
            data(val){
                
            }
        },
        mounted(){
        },
        methods:{
            //功能：对'作业结果'进行过滤处理
            processObj: function (obj) {
                var str = '';
                if(obj.type=='form-upload'){ //'文件上传'组件                    
                    if(obj.value){
                        var arr = obj.value;                        
                        for(var i=0;i<arr.length;i++){                            
                            str = str + '<li>';
                            str = str + util.getHtmlByFileExt(arr[i].name,arr[i].uri);//根据文件类型显示不同的html标签
                            str = str + '</li>';
                        }
                        str='<ol class="list-1439">'+str+'</ol>';
                    }
                }else{
                    str = obj.value.toString();
                }
                return str;
            }            
        },
        filters: {
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
<style>
    ol.list-1439{}
    ol.list-1439 li{padding-top: 2px;padding-bottom: 2px;}
    ol.list-1439 li img{}
    ol.list-1439 li a.link-1536{color: #666;text-decoration: underline;}
    ol.list-1439 li a.link-1536:visited{color: #999;text-decoration: underline;}
</style>
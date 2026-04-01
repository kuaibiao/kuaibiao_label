<template>
    <div v-if="status === '1'">
        <div v-if="row.unpack">
            <div v-if="row.unpack.unpack_status == '3' && row.unpack.unpack_message.length>0">
                <p>{{statuses[status]}}</p>
                <Tooltip transfer placement="top-start" max-width="260" :word-wrap="true">
                    <div>
                        <p :style="{color: 'green', cursor: 'pointer'}">{{$t('project_unpack_tip')}}</p>
                    </div> 
                    <div slot="content">
                        <p v-for="(item, index) in row.unpack.unpack_message" v-show="item" :key="index">{{item}}</p>
                    </div>
                </Tooltip>
            </div>
            <div v-else-if="row.unpack.unpack_status == '4' && row.unpack.unpack_message.length>0">
                <p>{{statuses[status]}}</p>
                <Tooltip transfer placement="top-start" max-width="260" :word-wrap="true">
                    <div>
                        <p :style="{color: 'red', cursor: 'pointer'}">{{$t('project_unpack_err')}}</p>
                    </div> 
                    <div slot="content">
                        <p v-for="(item, index) in row.unpack.unpack_message" v-show="item" :key="index">{{item}}</p>
                    </div>
                </Tooltip>
            </div>
            <div v-else>
                <p>{{statuses[status]}}</p>
                <Tooltip transfer :content="$t('project_unpack_process')" style="display: block; width: 100%; min-width: 100px" >
                    <Progress :percent="+row.unpack.unpack_progress" :strokeWidth="5"></Progress>
                </Tooltip>
            </div>
        </div>
        <div v-else>
            {{statuses[status]}}
        </div>
    </div>
    <div v-else-if="status === '2' || status === '3'">
        <div v-if="row.unpack">
            <div v-if="row.unpack.unpack_status == '3' && row.unpack.unpack_message.length>0">
                <p>{{statuses[status]}}</p>
                <Tooltip transfer placement="top-start" max-width="260" :word-wrap="true">
                    <div>
                        <p :style="{color: 'green', cursor: 'pointer'}">{{$t('project_unpack_tip')}}</p>
                    </div> 
                    <div slot="content">
                        <p v-for="(item, index) in row.unpack.unpack_message" v-show="item" :key="index">{{item}}</p>
                    </div>
                </Tooltip>
            </div>
            <div v-else-if="row.unpack.unpack_status == '4' && row.unpack.unpack_message.length>0">
                <p>{{statuses[status]}}</p>
                <Tooltip transfer placement="top-start" max-width="260" :word-wrap="true">
                    <div>
                        <p :style="{color: 'red', cursor: 'pointer'}">{{$t('project_unpack_err')}}</p>
                    </div> 
                    <div slot="content">
                        <p v-for="(item, index) in row.unpack.unpack_message" v-show="item" :key="index">{{item}}</p>
                    </div>
                </Tooltip>
            </div>
            <div v-else>
                <p>{{statuses[status]}}</p>
            </div>
        </div>
        <div v-else>
            {{statuses[status]}}
        </div>
    </div>
    <div v-else>
        {{statuses[status]}}
    </div>
</template>
<script>
export default {
    props: {
        status: {
            type: String
        },
        statuses: {
            type: Object,
            default: {}
        },
        row: {
            type: Object,
        }
    },
    data () {
        return {

        };
    }
};
</script>


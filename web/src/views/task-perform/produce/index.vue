<template>
    <div>
        <component :is="produceView[categoryInfo.view]"
            :templateInfo = "templateInfo"
            :taskList = "taskList"
            :categoryInfo = "categoryInfo"
            :serverTime = "serverTime"
            :timeout="timeout"
            :taskStat ="taskStat"
            :taskInfo= "taskInfo"
            :stepInfo = "stepInfo"
        ></component>
    </div>
</template>

<script>
export default {
    name: 'task-produce',
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
        taskInfo: {
            type: Object,
            required: true,
        },
        serverTime: {
            type: Number,
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
            produceView: {
                'image_label': 'image-label',
                'collection': 'collection',
                'voice_transcription': 'voice-transcription',
                'voice_classify': 'voice-classify',
                'text_analysis': 'text-analysis',
                'text_annotation': 'text-annotation',
                // 'image_translate': 'image-translate',
                'data_collection': 'data-collection',//数据采集
                'image_transcription': 'image-transcription',//图片审核
                'video-tail': 'video-tail',
                'video_classify': 'video-classify',
                '3d_pointcloud': 'pointcloud-3d',
                'pointcloud_segment': 'pointcloud-3d', // 点云分割和标注采用同一个模板
                'pointcloud_tracking': 'pointcloud-tracking',
                'video_segmentation':'video-segmentation', //视频分割
            },
            userId: this.$store.state.user.userInfo.id
        };
    },
    mounted () {        
        this.$emit('componentsLoaded');
    },
    components: {
        'image-label': () => import('./image-label.vue'),
        'data-collection': () => import('./data-collection.vue'),//数据采集
        'image-transcription': () => import('./image-transcription.vue'),//图片审核
        'voice-transcription': () => import('./voice-transcription.vue'),
        'voice-classify': () => import('./voice-classify.vue'),
        'text-analysis': () => import('./text-analysis.vue'),
        'text-annotation': () => import('./text-annotation.vue'),
        'video-tail': () => import('./video-tail.vue'),
        'video-classify': () => import('./video-classify.vue'),
        'pointcloud-3d': () => import('./pointcloud-3d.vue'),
        'pointcloud-tracking': () => import('./pointcloud-tracking.vue'),
        'video-segmentation': () => import('./video-segmentation.vue'), //视频分割
    }
};
</script>
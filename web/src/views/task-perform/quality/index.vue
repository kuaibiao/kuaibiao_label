<template>
    <component :is="AuditView[categoryInfo.view]"
        :templateInfo = "templateInfo"
        :taskList = "taskList"
        :categoryInfo = "categoryInfo"
        :serverTime = "serverTime"
        :timeout="timeout"
        :taskStat="taskStat"
        :taskInfo = "taskInfo"
        :qualityData="qualityData"
        :stepInfo = "stepInfo"
    ></component>
</template>

<script>
export default {
    name: 'task-quality',
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
        qualityData: {
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
            AuditView: {
                'image_label': 'image-label',
                'collection': 'collection',
                'voice_transcription': 'voice-transcription',
                'voice_classify': 'voice-classify',
                'text_analysis': 'text-analysis',
                'image_transcription': 'image-transcription',
                'text_annotation': 'text-annotation',
                'video-tail': 'video-tail',
                'video_classify': 'video-classify',
                '3d_pointcloud': 'pointcloud-3d',
                'pointcloud_segment': 'pointcloud-3d', // 点云分割和标注采用同一个模板
                'pointcloud_tracking': 'pointcloud-tracking',
                'video_segmentation': 'video-segmentation', //视频分割  
            },
            userId: this.$store.state.user.userInfo.id,
        };
    },
    mounted () {        
        this.$emit('componentsLoaded');
    },
    components: {
        'image-label': () => import('./image-label.vue'),
        'image-transcription': () => import('./image-transcription.vue'),
        'voice-transcription': () => import('./voice-transcription.vue'),
        'voice-classify': () => import('./voice-transcription.vue'),
        'text-analysis': () => import('./text-analysis.vue'),
        'text-annotation': () => import('./text-annotation.vue'),
        'video-tail': () => import('./video-tail.vue'),
        'video-classify': () => import('./video-classify.vue'),
        'pointcloud-3d': () => import('./pointcloud-3d.vue'),
        'pointcloud-tracking': () => import('./pointcloud-tracking.vue'),
        'video-segmentation': () => import('./video-segmentation.vue'), 
    }
};
</script>
<style lang="scss">
.task-header {
    display: flex;
    justify-content: space-between;
    margin:0 0 5px;
    .task-btn-group {
        display: flex;
        justify-content: flex-end;
    }
}
</style>

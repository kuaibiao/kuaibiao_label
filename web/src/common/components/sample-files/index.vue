<template>
    <div>
        <component ref="projectPreview" :is="produceView[categoryInfo.view]"
            :templateInfo = "templateInfo"
            :categoryInfo="categoryInfo"
        ></component>
    </div>
</template>

<script>
export default {
    name: 'task-produce',
    props: {
        templateInfo: {
            type: Array,
            default: []
        },
        categoryInfo: {
            type: Object,
            required: true
        }
    },
    data () {
        return {
            produceView: {
                'image_label': 'image-label',
                'voice_transcription': 'voice-transcription',
                'voice_classify': 'voice-classify',
                'text_analysis': 'text-analysis',
                'text_annotation': 'text-annotation',
                'image_transcription': 'image-transcription',
                'video-tail': 'video-tail',
                'video_classify': 'video-classify',
                'data_collection': 'data-collection'                
            },
        };
    },
    methods: {
        getSubmitResult () {
            this.$refs.projectPreview.getSubmitData()
        }
    },
    mounted () {        
        this.$emit('componentsLoaded');
    },
    components: {
        'image-label': () => import('./image-label.vue'),
        'image-transcription': () => import('./image-transcription.vue'),
        'voice-transcription': () => import('./voice-transcription.vue'),
        'voice-classify': () => import('./voice-classify.vue'),
        'text-analysis': () => import('./text-analysis.vue'),
        'text-annotation': () => import('./text-annotation.vue'),
        'video-tail': () => import('./video-tail.vue'),
        'video-classify': () => import('./video-classify.vue'),
        'data-collection': () => import('./data-collection.vue') //数据采集
    }
};
</script>
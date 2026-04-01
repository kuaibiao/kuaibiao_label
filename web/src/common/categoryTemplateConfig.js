// 各种标注作业可用的模板组件 配置 基于分类的 view 字段
//     'single-input' 单行输入
//     'multi-input' 多行输入
//     'form-radio'  单选
//     'form-checkbox'  多选
//     'form-select'  下拉框
//     'form-group'  表单组
//     'form-upload'  上传组件
//     'key-point'  关键点
//     'key-point-group'  关键点组
//     'show-img'  示例图片
//     'show-text'  文字说明
//     'layout'  布局
//     'ocr'  图片文字转录
//     'tag' 标签
//     'data-is-valid'  数据清洗组件
//     'image-label-tool'  图片标注工具配置
//     'external-link'  外部链接

//      'audio-file-placeholder' 音频占位符
//     'task-file-placeholder'  图片文件占位符
//     'video-file-placeholder' 视频占位符
//     'text-file-placeholder' 文本占位符

// image_transcription  图片判断 不画框转录
// image_label  图片标注
// text_analysis 文本分析 判断
// text_annotation  文本标注
// voice_classify  语音分类 判断
// voice_transcription  语音标注 分割
// video_classify 视频分类 判断
// video-tail  视频跟踪标注
const common = [
    'single-input',
    'multi-input',
    'form-radio',
    'form-checkbox',
    'form-select',
    'data-is-valid',
    'layout',
    'show-img',
    'show-text'];

export default {
    image_label: {
        list: [
            'task-file-placeholder',
            'tag',
            //'ocr',
            'image-label-tool',
            'key-point',
            'key-point-group'
        ].concat(common),
        required: ['task-file-placeholder']
    },
    image_transcription: {
        list: [
            'task-file-placeholder',
        ].concat(common),
        required: ['task-file-placeholder']
    },
    text_analysis: {
        list: [
            'text-file-placeholder'
        ].concat(common),
        required: ['text-file-placeholder']
    },
    text_annotation: {
        list: [
            'text-file-placeholder',
            'form-group',
            'tag'
        ].concat(common),
        required: ['text-file-placeholder']
    },
    voice_classify: {
        list: [
            'audio-file-placeholder'
        ].concat(common),
        required: ['audio-file-placeholder']
    },
    voice_transcription: {
        list: [
            'audio-file-placeholder',
            'form-group',
        ].concat(common),
        required: ['audio-file-placeholder']
    },
    video_classify: {
        list: [
            'video-file-placeholder'
        ].concat(common),
        required: ['video-file-placeholder']
    },
    'video-tail': {
        list: [
            'video-file-placeholder',
            'form-group',
            'tag'
        ].concat(common),
        required: ['video-file-placeholder']
    },
    '3d_pointcloud': {
        list: [
            'task-file-placeholder',
            'tag',
        ].concat(common),
        required: ['task-file-placeholder']
    },
    pointcloud_segment: {
        list: [
            'task-file-placeholder',
            'tag',
        ].concat(common),
        required: ['task-file-placeholder']
    },
    pointcloud_tracking: {
        list: [
            'task-file-placeholder',
            'tag',
        ].concat(common),
        required: ['task-file-placeholder']
    },
    video_segmentation: { // 视频分割
        list: [
            'task-file-placeholder',
            'show-text',
            'layout',
            'data-is-valid',
            'tag',
            'single-input',
            'multi-input',
            'form-radio',
            'form-checkbox',
            'form-select'
        ],
        required: ['task-file-placeholder']
    }
};

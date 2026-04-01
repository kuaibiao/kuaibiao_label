import {i18n} from '@/locale';
export default {
    image: {
        type: 'form-upload', // 上传组件 依据subType确定最终类型
        header: i18n.t('template-upload-data'),
        tips: '',
        fileMinSize: 0, // 单文件最小大小 单位MB
        fileMaxSize: 6, // 单文件最大大小 单位MB
        fileNumber: 1, // 上传数量
        fileFormat: [ // 可接受的文件格式
            'png',
            'jpeg',
            'bmp',
        ],
        subType: 'image', // 图片上传组件
        id: '',
    },
    audio: {
        type: 'form-upload',
        header: i18n.t('template-upload-data'),
        tips: '',
        fileMinSize: 0,
        fileMaxSize: 22,
        fileNumber: 1,
        fileFormat: [
            'mp3',
            'wav',
            'flac',
        ],
        subType: 'audio', // 音频上传
        fileMinLength: 0, // 音频文件最小时长  只有音视频上传，有时长的限制  单位秒  下同
        fileMaxLength: 300, // 音频文件最大时长
        id: '',
    },
    video: {
        type: 'form-upload',
        header: i18n.t('template-upload-data'),
        tips: '',
        fileMinSize: 0,
        fileMaxSize: 32,
        fileNumber: 3,
        fileFormat: [
            'mp4',
            'wmv',
        ],
        subType: 'video',
        fileMinLength: 0, // 视频文件最小时长
        fileMaxLength: 400, // 视频文件最大时长
        id: '',
    },
    other: {
        type: 'form-upload', // 上传组件
        header: i18n.t('template-upload-data'),
        tips: '',
        fileMinSize: 0, // 单文件最小大小 单位MB
        fileMaxSize: 6, // 单文件最大大小 单位MB
        fileNumber: 1, // 上传数量
        fileFormat: [// 可接受的文件格式
            'pdf',
            'txt',
            'docx'
        ],
        subType: 'other', // 其他文件上传组件
        id: '',
    }
};

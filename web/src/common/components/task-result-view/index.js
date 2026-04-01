import ImageLabelResult from './image-label';
import VoiceAnnotationResult from './voice-annotation';
import TextAnnotationResult from './text-annotation';
import PointCloudResult from './point-cloud';
import pointcloudTrackingResult from './pointcloud-tracking';
import VideoSegmentation from './video-segmentation';

export const viewResultType = {
    '3d_pointcloud': PointCloudResult, // 画框
    'pointcloud_segment': PointCloudResult, // 区域分割
    'pointcloud_tracking': pointcloudTrackingResult, // 框追踪
    'image_label': ImageLabelResult,
    'image_transcription': ImageLabelResult,
    'text_analysis': TextAnnotationResult,
    'text_annotation': TextAnnotationResult,
    'voice_classify': VoiceAnnotationResult,
    'voice_transcription': VoiceAnnotationResult,
    'video_segmentation': VideoSegmentation,
};
export {
    ImageLabelResult,
    VoiceAnnotationResult,
    TextAnnotationResult,
    PointCloudResult,
    pointcloudTrackingResult,
    VideoSegmentation
};

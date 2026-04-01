import uuid from 'uuid/v4';
// 各分类默认模板配置
export default function getBasicTemplate (view) {
    const defaultView = [
        {
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': []
            },
            'column1': {
                'span': 6,
                'children': []
            },
            'id': uuid(), // 能保证每次获取的都是不一样的id
            'ratio': 3,
            'scene': 'edit'
        }
    ];
    const categoryView = {
        // 图片标注
        image_label: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'task-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'image_url',
                },
                {
                    'type': 'image-label-tool',
                    'id': uuid(),
                    'supportShapeType': ['rect', 'polygon'],
                    'advanceTool': []
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 图片转录 不画框的
        image_transcription: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'task-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'image_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 文本审核
        text_analysis: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'text-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'text_url',
                    'searchEnable': true
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 文本标注
        text_annotation: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'text-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'text_url',
                    'searchEnable': true
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 语音分类审核
        voice_classify: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'audio-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'audio_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 语音转录
        voice_transcription: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'audio-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'audio_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 视频分类审核
        video_classify: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'video-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'video_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 视频跟踪标注
        'video-tail': [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'video-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': 'video_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        '3d_pointcloud': [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'task-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': '3d_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        pointcloud_segment: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'task-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': '3d_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 点云追踪
        pointcloud_tracking: [{
            'type': 'layout',
            'column0': {
                'span': 18,
                'children': [{
                    'type': 'task-file-placeholder',
                    'header': '',
                    'tips': '',
                    'id': uuid(),
                    'anchor': '3d_url'
                }]
            },
            'column1': {'span': 6, 'children': []},
            'id': uuid(),
            'ratio': 3,
            'scene': 'edit'
        }],
        // 视频分割
        video_segmentation: [
            {
                "type": "layout",
                "column0": {
                    "span": 18,
                    "children": [
                        {
                            "type": "task-file-placeholder",
                            "header": "",
                            "tips": "",
                            "id": uuid(),
                            "anchor": "video_url"
                        }
                    ]
                },
                "column1": {
                    "span": 6,
                    "children": []
                },
                "id": uuid(),
                "ratio": 3,
                "scene": "edit"
            }
        ]
    };
    return categoryView[view] || defaultView;
}

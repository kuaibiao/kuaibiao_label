export default
{
    type: 'tag',
    header: '',
    tips: '',
    subType: 'single',
    defaultColor: '#ffff00',
    data: [
        {
            'text': 'Car',
            'shortValue': '',
            'color': '#ffff00',
            'minWidth': 0,
            'minHeight': 0,
            'maxWidth': 0,
            'maxHeight': 0,
            exampleImageSrc: ''
        },
        {
            'text': 'Person',
            'shortValue': '',
            'color': '#ffff00',
            'minWidth': 0,
            'minHeight': 0,
            'maxWidth': 0,
            'maxHeight': 0,
            exampleImageSrc: '',
        },
        {
            'text': 'Sky',
            'shortValue': '',
            'color': '#ffff00',
            'minWidth': 0,
            'minHeight': 0,
            'maxWidth': 0,
            'maxHeight': 0,
            exampleImageSrc: '',
        },
    ],
    tagIsRequired: 0, // 0 不必须， 1 必须
    tagIsUnique: 0, //   0 只打一个， 1 可多个
    pointDistanceMin: 0,
    pointPositionNoLimit: false,
    pointTagShapeType: [],
    tagGroupLock: false,
    tagGroupOpen: false,
    tagIsSearchAble: true,
    deepLevel: 1,
    id: ''
};

// {
//     'type': 'tag',
//     'subType': 'group',
//     'header': '类型选择:',
//     'tips': '为标注对象选择所属类型',
//     'data': [
//         {
//             'text': '冰箱',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': '对的',
//                     'subData': []
//                 },
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': '豆得儿',
//                     'subData': []
//                 }
//             ]
//         },
//         {
//             'text': '香蕉',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': '对对对',
//                     'subData': []
//                 },
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': '二二',
//                     'subData': []
//                 }
//             ]
//         },
//         {
//             'text': '火腿',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': 'fgfgfg',
//                     'subData': []
//                 },
//                 {
//                     'color': '#ffff00',
//                     'minHeight': 0,
//                     'maxWidth': 0,
//                     'minWidth': 0,
//                     'maxHeight': 0,
//                     'shortValue': '',
//                     'text': '水电费水电费',
//                     'subData': []
//                 }
//             ]
//         }
//     ],
//     'deepLevel': 2,
//     'pointDistanceMin': 0
// };

// {
//     'type': 'tag',
//     'subType': 'group',
//     'header': '类型选择:',
//     'tips': '为标注对象选择所属类型',
//     'data': [
//         {
//             'text': '冰箱',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'text': '对的',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '而而非'
//                         },
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '大幅度'
//                         }
//                     ]
//                 },
//                 {
//                     'text': '豆得儿',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '的方法'
//                         },
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '电饭锅'
//                         }
//                     ]
//                 }
//             ]
//         },
//         {
//             'text': '香蕉',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'text': '对对对',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '还让他'
//                         },
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '儿童'
//                         }
//                     ]
//                 },
//                 {
//                     'text': '二二',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '儿童'
//                         },
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '柔荑花'
//                         }
//                     ]
//                 }
//             ]
//         },
//         {
//             'text': '火腿',
//             'tagIsRequired': 1,
//             'tagIsUnique': 0,
//             'subData': [
//                 {
//                     'text': 'fgfgfg',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '冠福股份'
//                         }
//                     ]
//                 },
//                 {
//                     'text': '水电费水电费',
//                     'subData': [
//                         {
//                             'color': '#ffff00',
//                             'minHeight': 0,
//                             'maxWidth': 0,
//                             'minWidth': 0,
//                             'maxHeight': 0,
//                             'shortValue': '',
//                             'text': '哈哈'
//                         }
//                     ]
//                 }
//             ]
//         }
//     ],
//     'deepLevel': 3,
//     'pointDistanceMin': 0
// };

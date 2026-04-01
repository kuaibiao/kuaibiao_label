import api from '@/api';
export default {
    header: '',
    tips: '',
    type: 'key-point-group',
    id: '',
    defaultColor: '#ffff00',
    data: [
        {
            exampleImageSrc: api.staticBase + '/images/template/human.png',
            name: 'face', // todo  需要翻译
            required: false,
            point: [
                { text: '1', position: { top: 0.06, left: 0.45 }, color: '#ffff00' },
                { text: '2', position: { top: 0.12, left: 0.46 }, color: '#ffff00' },
                { text: '3', position: { top: 0.04, left: 0.49 }, color: '#ffff00' },
                { text: '4', position: { top: 0.04, left: 0.41 }, color: '#ffff00' },
                { text: '5', position: { top: 0.05, left: 0.57 }, color: '#ffff00' },
                { text: '6', position: { top: 0.05, left: 0.34 }, color: '#ffff00' },
            ],
            // 配置关键点的连线 
            /*
            * [[0,2,3,4], [0,4,5,7]]
            */
            // lines:[], 
            // 配置哪些点之间需要等分
            /*
            * [{start: 3, end: 6, type: 'ordered'}, {equalPoints:[0,3,5,6] type:'custom}]
            */
            equalDiversionConfig: [] 
        }
    ]
};
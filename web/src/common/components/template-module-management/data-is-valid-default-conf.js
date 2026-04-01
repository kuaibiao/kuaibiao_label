// 不要编辑本文件的内容 除了 header 字段
import dataIsValid from '../../dataIsValid';
import {i18n} from '@/locale';
export default {
    type: 'data-is-valid',
    header: i18n.t('template_data_is_valid_header'),
    tips: '',
    id: '',
    data: [ // 选项数据
        {
            'checked': true, // 是否默认选中  true为默认选中
            'text': dataIsValid.yes // 有效
        },
        {
            'checked': false,
            'text': dataIsValid.no // 无效
        },
        {
            'checked': false,
            'text': dataIsValid.unknown // 不确定
        },
    ],
    value: dataIsValid.yes, // 默认值
};

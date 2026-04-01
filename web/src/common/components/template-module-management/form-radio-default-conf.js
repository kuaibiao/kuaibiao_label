import {i18n} from '@/locale';
export default {
    type: 'form-radio',
    header: i18n.t('template_form_radio_header'),
    tips: '',
    vertical: false,
    data: [ // 选项数据
        {
            'checked': true,
            'text': 'Option1'
        },
        {
            'checked': false, // 是否默认选中  true为默认选中
            'text': 'Option2'
        }
    ],
    value: 'Option2',
    required: true, // 是否必选    true为必选
    id: '',
    rules: [],
};

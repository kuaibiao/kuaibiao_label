import {i18n} from '@/locale';
export default {
    type: 'form-checkbox',
    header: i18n.t('template_form_checkbox_header'),
    tips: '',
    vertical: false,
    data: [ // 选项数据
        {
            'checked': false,
            'text': 'Option1',
        },
        {
            'checked': false,
            'text': 'Option2',
        }
    ],
    value: [],
    required: true, // 是否必选    true为必选
    id: '',
    rules: [],
};

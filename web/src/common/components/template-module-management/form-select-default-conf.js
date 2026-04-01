import {i18n} from '@/locale';
export default {
    type: 'form-select',
    header: i18n.t('template_form_select_header'),
    tips: '',
    data: [
        {
            'text': 'Option1',
            'selected': false // 是否默认选中
        },
        {
            'text': 'Option2',
            'selected': false
        },
        {
            'text': 'Option3',
            'selected': false
        }
    ],
    value: '', // 单选时为字符串， 多选时为数组 // 暂时只有单选 2018年05月18日10:46:03
    multiple: false,
    required: true, // 是否必选    true为必选
    id: '',
    rules: [],
};

import {i18n} from '@/locale';
export default {
    type: 'multi-input',
    header: i18n.t('template_multi_input_header'), // 模板标题
    tips: '', // 模板提示文字
    required: true, // 是否必填 false 可选填， true 必填
    id: '',
    rules: [], // 校验规则
    value: '', // 默认值,
    placeholder: '',
    maxRows: 3, // 最大行数
};

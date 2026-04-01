import zhCN from './zh-cn/index';
import enUS from './en-us/index';
import zhLocale from 'iview/dist/locale/zh-CN';
import enLocale from 'iview/dist/locale/en-US';
import VueI18n from 'vue-i18n';
import Vue from 'vue';
Vue.use(VueI18n);
// 多语言配置
const mergeZH = Object.assign(zhLocale, zhCN);
const mergeEN = Object.assign(enLocale, enUS);
// 自动设置语言
const navLang = navigator.language;
const localLang = (navLang === 'zh-CN' || navLang === 'en-US') ? navLang : false;
const lang = window.localStorage.lang || localLang || 'zh-CN';
export const i18n = new VueI18n({
    locale: lang, // set locale
    messages: {
        'zh-CN': mergeZH,
        'en-US': mergeEN
    }
});

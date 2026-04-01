import axios from 'axios';
import env from '../../build/env';
import semver from 'semver';
import XLSX from 'xlsx';

import packjson from '../../package.json';

let util = {

};
util.title = function (title) {
    title = 'LabelTool -' + title;
    window.document.title = title;
};
const dev = ['localhost', '192.168.'];
const ajaxUrl = env === 'development'
    ? 'http://127.0.0.1:8888'
    : env === 'production'
        ? 'https://www.url.com'
        : 'https://debug.url.com';

util.ajax = axios.create({
    baseURL: ajaxUrl,
    timeout: 30000
});

util.inOf = function (arr, targetArr) {
    let res = true;
    arr.forEach(item => {
        if (targetArr.indexOf(item) < 0) {
            res = false;
        }
    });
    return res;
};

util.oneOf = function (ele, targetArr) {
    if (targetArr.indexOf(ele) >= 0) {
        return true;
    } else {
        return false;
    }
};

util.showThisRoute = function (itAccess, currentAccess) {
    if (typeof itAccess === 'object' && Array.isArray(itAccess)) {
        return util.oneOf(currentAccess, itAccess);
    } else {
        return itAccess === currentAccess;
    }
};
util.checkUpdate = function (vm) {
    if (dev.some(v => location.origin.indexOf(v) > 0)) {
        return;
    }
    if (env !== 'production' || vm.updateTipsIsOpen) {
        return;
    }
    axios.get(location.origin + '/version.json?_=' + Date.now()).then(res => {
        let version = +res.data.version;
        let cur = window._version || 0;
        if (version > cur && !vm.updateTipsIsOpen) {
            vm.updateTipsIsOpen = true;
            vm.$Notice.info({
                title: vm.$t('tool_new_version_has_release'),
                desc: `<p>
                        <a style="font-size:15px;" href="javascript:location.reload(true);">
                        ${vm.$t('tool_version_update_tips')}
                        </a></p>`,
                duration: 0,
                onClose: () => {
                    vm.updateTipsIsOpen = false;
                }
            });
        }
    }).catch(() => {});
};
util.replaceUrl = function (url) {
    let host = 'yourdomain.com';
    let index = url.indexOf(host);
    if (~index) {
        return url.substring(index + host.length);
    } else {
        return url;
    }
};
util.getRouterObjByName = function (routers, name) {
    if (!name || !routers || !routers.length) {
        return null;
    }
    // debugger;
    let routerObj = null;
    for (let item of routers) {
        if (item.name === name) {
            return item;
        }
        routerObj = util.getRouterObjByName(item.children, name);
        if (routerObj) {
            return routerObj;
        }
    }
    return null;
};
util.getKeyIndexFromTableOption = function (tableOption, key) {
    let index = -1;
    for (let i = 0; i < tableOption.length; i++) {
        if (tableOption[i].key === key) {
            index = i;
            break;
        }
    }
    return index;
};
util.handleTitle = function (vm, item) {
    if (typeof item.title === 'object') {
        return vm.$t(item.title.i18n);
    } else {
        return item.title;
    }
};

util.setCurrentPath = function (vm, name, meta) {
    let title = '';
    let isOtherRouter = false;
    vm.$store.state.app.routers.forEach(item => {
        if (item.children.length === 1) {
            if (item.children[0].name === name) {
                title = util.handleTitle(vm, item);
                if (item.name === 'otherRouter') {
                    isOtherRouter = true;
                }
            }
        } else {
            item.children.forEach(child => {
                if (child.name === name) {
                    title = util.handleTitle(vm, child);
                    if (item.name === 'otherRouter') {
                        isOtherRouter = true;
                    }
                }
            });
        }
    });
    let currentPathArr = [];
    if (name === 'home_index') {
        currentPathArr = [
            {
                title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                path: '',
                name: 'home_index'
            }
        ];
    } else if ((name.indexOf('_index') >= 0 || isOtherRouter) && name !== 'home_index') {
        if (meta.parentPath) {
            currentPathArr = [
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                    path: '/home',
                    name: 'home_index'
                },
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, meta.parent)),
                    path: meta.parentPath,
                    name: meta.parent
                },
                {
                    title: title,
                    path: '',
                    name: name
                }
            ];
        } else {
            currentPathArr = [
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                    path: '/home',
                    name: 'home_index'
                },
                {
                    title: title,
                    path: '',
                    name: name
                }
            ];
        }
    } else {
        let currentPathObj = vm.$store.state.app.routers.filter(item => {
            if (item.children.length <= 1) {
                return item.children[0].name === name;
            } else {
                let i = 0;
                let childArr = item.children;
                let len = childArr.length;
                while (i < len) {
                    if (childArr[i].name === name) {
                        return true;
                    }
                    i++;
                }
                return false;
            }
        })[0];
        if (currentPathObj.children.length <= 1 && currentPathObj.name === 'home') {
            currentPathArr = [
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                    path: '',
                    name: 'home_index'
                }
            ];
        } else if (currentPathObj.children.length <= 1 && currentPathObj.name !== 'home') {
            currentPathArr = [
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                    path: '/home',
                    name: 'home_index'
                },
                {
                    title: currentPathObj.title,
                    path: '',
                    name: name
                }
            ];
        } else {
            let childObj = currentPathObj.children.filter((child) => {
                return child.name === name;
            })[0];
            currentPathArr = [
                {
                    title: util.handleTitle(vm, util.getRouterObjByName(vm.$store.state.app.routers, 'home_index')),
                    path: '/home',
                    name: 'home_index'
                },
                {
                    title: currentPathObj.title,
                    path: '',
                    name: currentPathObj.name
                },
                {
                    title: childObj.title,
                    path: currentPathObj.path + '/' + childObj.path,
                    name: name
                }
            ];
        }
    }
    vm.$store.commit('setCurrentPath', currentPathArr);

    return currentPathArr;
};

util.openNewPage = function (vm, name, argu, query) {
    let pageOpenedList = vm.$store.state.app.pageOpenedList;
    let openedPageLen = pageOpenedList.length;
    let i = 0;
    let tagHasOpened = false;
    while (i < openedPageLen) {
        if (name === pageOpenedList[i].name) { // 页面已经打开
            vm.$store.commit('pageOpenedList', {
                index: i,
                argu: argu,
                query: query
            });
            tagHasOpened = true;
            break;
        }
        i++;
    }
    if (!tagHasOpened) {
        let tag = vm.$store.state.app.tagsList.filter((item) => {
            if (item.children) {
                return name === item.children[0].name;
            } else {
                return name === item.name;
            }
        });
        tag = tag[0];
        if (tag) {
            tag = tag.children ? tag.children[0] : tag;
            if (argu) {
                tag.argu = argu;
            }
            if (query) {
                tag.query = query;
            }
            vm.$store.commit('increateTag', tag);
        }
    }
    vm.$store.commit('setCurrentPageName', name);
};

util.toDefaultPage = function (routers, name, route, next) {
    let len = routers.length;
    let i = 0;
    let notHandle = true;
    while (i < len) {
        if (routers[i].name === name && routers[i].children && routers[i].redirect === undefined) {
            route.replace({
                name: routers[i].children[0].name
            });
            notHandle = false;
            next();
            break;
        }
        i++;
    }
    if (notHandle) {
        next();
    }
};

util.fullscreenEvent = function (vm) {
    vm.$store.commit('initCachepage');
    // 权限菜单过滤相关
    vm.$store.commit('updateMenulist');
    // 全屏相关
};

util.timeFormatter = function (data, format) {
    if (data.valueOf() == 0) {
        return '';
    }
    let o = {
        'M+': data.getMonth() + 1, // 月份
        'd+': data.getDate(), // 日
        'h+': data.getHours(), // 小时
        'm+': data.getMinutes(), // 分
        's+': data.getSeconds(), // 秒
        'q+': Math.floor((data.getMonth() + 3) / 3), // 季度
        'S': data.getMilliseconds() // 毫秒
    };
    if (/(y+)/.test(format)) {
        format = format.replace(RegExp.$1, (data.getFullYear() + '').substr(4 - RegExp.$1.length));
    }
    for (var k in o) {
        if (new RegExp('(' + k + ')').test(format)) {
            format = format.replace(RegExp.$1, (RegExp.$1.length === 1) ? (o[k]) : (('00' + o[k]).substr(('' + o[k]).length)));
        }
    }
    return format;
};
util.getFileNameFromUrl = function (url) {
    return url.substring(url.lastIndexOf('/') + 1);
};
util.xlsxToJson = function (file, fn, vm) {
    var reader = new FileReader();
    reader.onload = function (e) {
        var data = e.target.result;
        var workbook = XLSX.read(data, {type: 'binary'});
        var resultJson = XLSX.utils.sheet_to_json(workbook.Sheets[workbook.SheetNames[0]]);
        typeof fn === 'function' && fn(resultJson);
    };
    reader.onerror = function () {
        vm.$Message.console.error({
            content: vm.$t('tool_fill_parse_error'),
            duration: 2
        });
    };
    reader.readAsBinaryString(file);
};

// 判断数组a是否包含b
util.is_array_contain = function (a, b) {
    let t;
    for (var i = 0; i < b.length; i++) {
        t = false;
        for (var j = 0; j < a.length; j++) {
            if (b[i] === a[j]) {
                t = true;
                break;
            }
        }
        if (!t) return false;
    }
    return true;
};
util.checkArr = function (arr1, arr2) {
    // var rs = false;
    // for (var i = 0; i < arr1.length; i++) {
    //     for (var j = 0; j < arr2.length; j++) {
    //         if (arr1[i] == arr2[j]) {
    //             rs = true;
    //         }
    //     }
    // }
    // return rs;
    let t;
    let arr = [];
    for (var i = 0; i < arr2.and.length; i++) {
        t = false;
        for (var j = 0; j < arr1.length; j++) {
            if (arr2.and[i] == arr1[j]) {
                t = true;
                break;
            }
        }
    }
    if (arr2.and.length) {
        if (t) {
            arr.push(1);
        } else {
            arr.push(0);
        }
    }

    var rs = false;
    for (let i = 0; i < arr1.length; i++) {
        for (let j = 0; j < arr2.or.length; j++) {
            if (arr1[i] == arr2.or[j]) {
                rs = true;
            }

            // if (arr1[i] == arr2.or[j]) {
            //     rs = true;
            // }
        }
    }
    if (arr2.or.length) {
        if (rs) {
            arr.push(1);
        } else {
            arr.push(0);
        }
    }

    if ($.inArray(0, arr) != -1) {
        return false;
    } else {
        return true;
    }
};
util.setCookie = function (cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = 'expires=' + d.toUTCString();
    document.cookie = cname + '=' + cvalue + '; ' + expires;
};
util.handleAjaxError = function (vm, res, textStatus, responseText, cb) {
    // vm 是 Vue组件示例 res text Status responseText 分别是jQuery ajax error 回调的参数
    // cb 是一个函数 用于出错时的业务逻辑处理
    typeof cb === 'function' && cb();
    let message = '';
    switch (textStatus) {
        case 'timeout' : {
            message = vm.$t('tool_request_timeout');
            break;
        }
        case 'abort': {
            return;
        }
        default: {
            message = (res.responseJSON && res.responseJSON.message) || responseText;
        }
    }
    message = message || vm.$t('tool_request_error');
    vm.$Message.destroy();
    vm.$Message.error({
        content: message,
        duration: 1
    });
};
// 功能：根据扩展名来生成不同的显示方式
util.getHtmlByFileExt = function (filename, url) {
    var index = filename.lastIndexOf(".");
    var suffix = filename.substr(index + 1).toUpperCase();
    var result = '';
    if (suffix == 'JPG' || suffix == 'JPEG' || suffix == 'PNG' || suffix == 'BMP' || suffix == 'GIF') { // 图片
        result = result + '<a target="_blank" href="' + url + '"><img height="50" width="auto" border="0" src="' + url + '"/></a>';
    } else if (suffix == 'MP3' || suffix == 'AAC' || suffix == 'WAV' || suffix == 'FLAC' || suffix == 'APE') { // 音频
        result = result + '<audio src="' + url + '" controls="controls" width="250" height="100">';
        result = result + 'Your browser does not support audio.';
        result = result + '</audio>';
    } else if (suffix == 'AVI' || suffix == 'MP4' || suffix == 'WMV' || suffix == 'RMVB' || suffix == 'MKV') { // 视频
        result = result + '<video src="' + url + '" controls="controls" width="250" height="100">';
        result = result + 'Your browser does not support video.';
        result = result + '</video>';
    } else {
        result = result + '<a class="link-1536" target="_blank" href="' + url + '">' + filename + '</a>';
    }
    return result;
};
// 功能：得到文件的类型
util.geFileTypeByExt = function (filename) {
    var index = filename.lastIndexOf(".");
    var suffix = filename.substr(index + 1).toUpperCase();
    var result = '';
    if (suffix == 'JPG' || suffix == 'JPEG' || suffix == 'PNG' || suffix == 'BMP' || suffix == 'GIF') { // 图片
        result = 'img-file';
    } else if (suffix == 'MP3' || suffix == 'AAC' || suffix == 'WAV' || suffix == 'FLAC' || suffix == 'APE') { // 音频
        result = 'audio-file';
    } else if (suffix == 'AVI' || suffix == 'MP4' || suffix == 'WMV' || suffix == 'RMVB' || suffix == 'MKV') { // 视频
        result = 'video-file';
    } else {
        result = 'other-file';
    }
    return result;
};

util.downloadFile = function (vm, url) {
    // 利用a标签下载文件
    const a = document.createElement('a');
    a.setAttribute('download', url);
    a.setAttribute('href', url);
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
};

/**
 * data  Date对象 或者 毫秒算的时间戳
 */
util.DateToSeconds = function (date) {
    let ret;
    if (typeof date === 'number') {
        ret = Math.floor(date / 1000);
    } else if (date instanceof Date) {
        ret = Math.floor(date.valueOf() / 1000);
    }
    return ret;
};
export default util;

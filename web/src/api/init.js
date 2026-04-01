const bases = window.basesTarget;
let curHost = location.origin;
let apiBase = '';
let hostBase = '';
let staticBase = '';
let helpBase = '';
let favicon_ico = '/images/favicon.png';

//本地测试环境配置
//
const devHosts = ['192.168.0.', '172.'];
if (devHosts.some(v => curHost.indexOf(v) > 0)) {
    // 本地开发
    curHost = 'http://localhost';
    console.log('init.js: localhost ' + curHost);
}else{
    console.log('init.js: host ' + curHost);
}

//路由匹配模式
/**
 *
 * 0 : 未匹配
 * 1 : 路由配置
 * 2 : 后缀模式
 */
let route_match_mod = 0;
console.log(location.origin,'location.originlocation.origin')
//匹配路由配置
for (let i = 0; i < bases.length; i++) {
    if (curHost === bases[i].host) {
        apiBase = bases[i].api;
        hostBase = bases[i].host;
        staticBase = bases[i].public;
        helpBase = bases[i].help;
        favicon_ico = bases[i].configs.favicon_ico;

        //路由模式
        route_match_mod = 1;
        break;
    }
}

console.log('init.js route_match_mod '+route_match_mod);
console.log(hostBase,apiBase)

//未匹配到路由配置,则采用后缀方式
if (route_match_mod === 0)
{
    let host = location.host.split(':')[0];
    // 域名
    if(!isIpAddress(host)){
        apiBase = location.protocol + '//api.' + host;
        hostBase = host;
        staticBase =location.protocol +'//public.' + host;
        helpBase = location.protocol + '//help.' + host;
    }else{
        apiBase = location.protocol + host + ':8801';
        hostBase = host;
        staticBase = location.protocol + host + ':8802';
        helpBase = location.protocol + host + ':8803';
    }


    //后缀模式
    route_match_mod = 2;

    // 前端8800 后端8801 public:8802 help:8803
}

function isIpAddress(url) {
    // 创建一个正则表达式，用于匹配IP地址
    const ipRegex = /^((25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)\.){3}(25[0-5]|2[0-4][0-9]|[01]?[0-9][0-9]?)$/;

    // 检查URL是否与正则表达式匹配
    if (ipRegex.test(url)) {
        return true;
    } else {
        return false;
    }
}

console.log('init.js-2:' + route_match_mod);
console.log(hostBase, apiBase)

export {
    favicon_ico,
    apiBase,
    hostBase,
    staticBase,
    helpBase,
};

export const UrlReg = /^https?:\/\/*/; // 识别 HTTP HTTPS 链接 简易版
export const needSaveFileLimit = 49; // 需要保存为文件的数据量 不包括

export function isIp (ip) {
    const ipre = /\b(?:(?:2(?:[0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9])\.){3}(?:(?:2([0-4][0-9]|5[0-5])|[0-1]?[0-9]?[0-9]))\b/;
    return ipre.test(ip);
}

export function getDomain() {
    let domain = location.hostname;
    if (isIp(domain)) {
        return domain
    } else {
        domain = domain.split('.');
        return [domain[domain.length - 2], domain[domain.length - 1]].join('.');
    }
}
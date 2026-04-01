
// 兼容数字键，包括数字小键盘的键
function normalizeKeyCode (keyCode) {
    if (keyCode > 95 && keyCode < 106) {
        return keyCode - 48;
    }
    return keyCode;
}

// 格式化键盘事件，或者具有 altKey shiftKey metaKey keyCode 属性的对象
export default function (e) {
    return '' + Number(e.ctrlKey) +
        Number(e.altKey) +
        Number(e.shiftKey) +
        Number(e.metaKey) + Number(normalizeKeyCode(e.keyCode));
}

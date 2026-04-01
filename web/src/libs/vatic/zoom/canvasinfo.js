// JavaScript Document
var CanvasInfo = function (common) {
    this.commonObj = common;

    this.width = 0;
    this.height = 0;
    this.squared = []; // 九宫格坐标信息 [{left:0,top:0,right:100,bottom:100},...]
};

/* 方法：初始化 */
CanvasInfo.prototype.init = function () {
    var me = this;
    me.width  = me.commonObj.canvas.width;
    me.height = me.commonObj.canvas.height;
    $(me.commonObj.canvas2).attr('width',me.commonObj.canvas.width);
    $(me.commonObj.canvas2).attr('height',me.commonObj.canvas.height);
};

/* 方法：生成九宫格信息 */
CanvasInfo.prototype.squaredBuild = function () {
    var me = this;
    var _w = Math.ceil(me.width / 3);
    var _h = Math.ceil(me.height / 3);
    var arri = ['上', '中', '下'];
    var arrj = ['左', '中', '右'];
    for (var i = 0; i < 3; i++) {
        // i是高的乘数
        for (var j = 0; j < 3; j++) {
            // j是宽的乘数
            var arr = {};
            arr.name = arri[i] + arrj[j];
            arr.left = j * _w;	// 起点left top
            arr.top = i * _h;
            if (j > 0) { arr.left = (j * _w) + 1; }
            if (i > 0) { arr.top = (i * _h) + 1; }
            arr.right = j * _w + _w;	// 终点right bottom
            arr.bottom = i * _h + _h;
            if (arr.right > me.width) { arr.right = me.width; }
            if (arr.bottom > me.height) { arr.bottom = me.height; }
            me.squared.push(arr);
        }
    }
};

/* 方法：获取鼠标所在哪个九宫格区域 */
CanvasInfo.prototype.getInSquared = function (mouse) {
    var me = this;
    var result;
    for (var i = 0; i < me.squared.length; i++) {
        var tmp = me.squared[i];
        if (tmp.left <= mouse.x && mouse.x <= tmp.right &&
            tmp.top <= mouse.y && mouse.y <= tmp.bottom
        ) {
            result = tmp; break;
        }
    }
    return result;
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
CanvasInfo.prototype.destroy = function () {
    delete this.width;
    delete this.height;
    delete this.squared; // 九宫格坐标信息 [{left:0,top:0,right:100,bottom:100},...]
};

// 方法：创建实例
CanvasInfo.getInstance = (function () {
    var instance = null;
    return function (common) {
        if (!instance) {
            instance = new CanvasInfo(common);
        }
        return instance;
    };
})();

export default CanvasInfo;

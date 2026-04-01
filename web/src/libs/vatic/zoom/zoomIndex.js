/* JavaScript Document zoom-index.js */
var ZoomIndex = function (common, canvasInfo, inputs, imgInfo, mouseEvent) {
    this.commonObj = common;
    this.canvasInfoObj = canvasInfo;
    this.inputsObj = inputs;
    this.imgInfoObj = imgInfo;
    this.mouseEventObj = mouseEvent;
};
// 方法：准备相关信息
ZoomIndex.prototype.ready = function (img) {
    var me = this;
    me.commonObj.img = img;
    me.commonObj.canvas = document.getElementById("canvas");
    me.commonObj.canvas2 = document.getElementById("canvas2");
    me.commonObj.cvsBox = document.getElementById('doodle');
    me.commonObj.ctx = me.commonObj.canvas.getContext("2d");
    me.init();
    me.inputsObj.inputBindChange(function () { me.imgInfoObj.imgDraw(); });
};

// 方法：执行初始化
ZoomIndex.prototype.init = function () {
    var me = this;
    // 1.画布canvas
    // $('#canvasInfo').html(me.commonObj.canvas.width+','+me.commonObj.canvas.height);
    me.mouseEventObj.canvasBindMouseEvent();
    me.canvasInfoObj.init();
    // 2.图片信息
    me.imgInfoObj.init(me.commonObj.img);
    // 3.文本框
    var obj = {
        "sx": me.imgInfoObj.draw.sx,
        "sy": me.imgInfoObj.draw.sy,
        "swidth": me.imgInfoObj.draw.swidth,
        "sheight": me.imgInfoObj.draw.sheight,
        "x": me.imgInfoObj.draw.x,
        "y": me.imgInfoObj.draw.y,
        "width": me.imgInfoObj.draw.width,
        "height": me.imgInfoObj.draw.height
    };
    me.inputsObj.init(me.imgInfoObj);
    me.inputsObj.setInputVal(obj);
    me.imgInfoObj.imgDraw();
    // 4.快捷键,按下
    window.addEventListener('keydown', function (e) {
        var preventDefault = true;
        if (e.keyCode === 18) {
            me.commonObj.canvas2.style.display = 'block';
            document.documentElement.style.overflow = 'hidden';
            me.mouseEventObj.ableIsMove = true; 	// 是否允许拖动
            me.mouseEventObj.isZoom = true; 		// 是否允许缩放
            // if(!$('#J-alt-shortcut').hasClass('cur')){$('#J-alt-shortcut').addClass('cur');}
        } else { preventDefault = false; }
        if (preventDefault) { e.preventDefault(); }
    }, false);
    // 5.快捷键,松开
    window.addEventListener('keyup', function (e) {
        var preventDefault = true;
        if (e.keyCode === 18) {
            me.commonObj.canvas2.style.display = 'none';
            document.documentElement.style.overflow = 'visible';
            me.mouseEventObj.ableIsMove = false; 	// 是否允许拖动
            me.mouseEventObj.isZoom = false; 		// 是否允许缩放
            // me.mouseEventObj.onmouseup();
            // if($('#J-alt-shortcut').hasClass('cur')){$('#J-alt-shortcut').removeClass('cur');}
        } else { preventDefault = false; }
        if (preventDefault) { e.preventDefault(); }
    }, false);
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
ZoomIndex.prototype.destroy = function () {

};

// 方法：创建实例
ZoomIndex.getInstance = (function () {
    var instance = null;
    return function (common, canvasInfo, inputs, imgInfo, mouseEvent) {
        if (!instance) {
            instance = new ZoomIndex(common, canvasInfo, inputs, imgInfo, mouseEvent);
        }
        return instance;
    };
})();
export default ZoomIndex;

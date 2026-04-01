// JavaScript Document
var Common = function () {
    this.mouseEventObj = null;
    this.inputsObj = null;
    this.imgInfoObj = null;

    this.canvas = new Object();
    this.canvas2 = new Object();
    this.cvsBox = new Object();
    this.ctx = new Object();
    this.img = new Object();
};

/* 方法：Alt快捷键:缩放+拖动 */
Common.prototype.altShortcut = function (thisBtn) {
    var me = this;
    var btn = $(thisBtn);
    if (btn.hasClass('cur')) {
        btn.removeClass('cur');
        me.mouseEventObj.ableIsMove = false; 	// 是否允许拖动
        me.mouseEventObj.isZoom = false; 		// 是否允许缩放
        // $('#J-zoom-1811').removeClass('cur');
        // $('#J-move-2109').removeClass('cur');
    } else {
        btn.addClass('cur');
        this.zoomDraw($('#J-zoom-1811'));
        this.toggleAbleMove($('#J-move-2109'));
    }
};

/* 方法：允许缩放 */
Common.prototype.zoomDraw = function (thisBtn) {
    var me = this;
    if (me.mouseEventObj.isZoom) {
        me.mouseEventObj.isZoom = false;
        // $(thisBtn).removeClass('cur');
    } else {
        me.mouseEventObj.isZoom = true;
        // $(thisBtn).addClass('cur');
    }
};

/* 方法：拖动工具 */
Common.prototype.toggleAbleMove = function (thisBtn) {
    var me = this;
    if (me.mouseEventObj.ableIsMove) {
        me.mouseEventObj.ableIsMove = false;
        // $(thisBtn).removeClass('cur');
    } else {
        me.mouseEventObj.ableIsMove = true;
        // $(thisBtn).addClass('cur');
    }
};

/* 方法：显示和隐藏辅助线 */
Common.prototype.toggleGuideLine = function (thisBtn) {
    var me = this;
    if (me.mouseEventObj.guideIsShow) {
        me.mouseEventObj.guideIsShow = false;
        $('#guide-x,#guide-y').hide();
        // $(thisBtn).removeClass('cur');
    } else {
        me.mouseEventObj.guideIsShow = true;
        $('#guide-x,#guide-y').show();
        // $(thisBtn).addClass('cur');
    }
};

/* 方法: 重置绘图 */
Common.prototype.resetDraw = function () {
    var me = this;
    me.inputsObj.initVal(me.imgInfoObj);
    me.imgInfoObj.scale = 0; // 重置：放大和缩小
    me.imgInfoObj.init(this.img);
    me.imgInfoObj.imgDraw();
    me.imgInfoObj.scaleChange();
};

/* 方法: 清空 */
Common.prototype.clearDraw = function () {
    var me = this;
    var obj = {x: 0, y: 0, width: 0, height: 0};
    me.inputsObj.init(me.imgInfoObj);
    me.inputsObj.setInputVal(obj);
    me.imgInfoObj.imgDraw();
};

/* 方法: 判断鼠标是否在图像区域内 */
Common.prototype.mouseIsInImgArea = function (mouse) {
    var me = this;
    if (me.imgInfoObj.imgArea.left <= mouse.x && mouse.x <= me.imgInfoObj.imgArea.right &&
        me.imgInfoObj.imgArea.top <= mouse.y && mouse.y <= me.imgInfoObj.imgArea.bottom) {
        return true;
    } else {
        return false;
    }
};

/* 方法: 计算两点间的距离 */
Common.prototype.getDistance = function (x1, y1, x2, y2) {
    var distance = 0;
    var xdiff = x2 - x1; // 计算两个点的横坐标之差
    var ydiff = y2 - y1; // 计算两个点的纵坐标之差
    distance = Math.pow((xdiff * xdiff + ydiff * ydiff), 0.5); // 计算两点之间的距离
    return Math.ceil(distance);
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
Common.prototype.destroy = function () {
    this.canvas2.removeEventListener('mouseup', this.mouseEventObj.onmouseup, false);
    this.canvas2.removeEventListener('mousemove', this.mouseEventObj.onmousemove, false);
    this.canvas2.removeEventListener('DOMMouseScroll', this.mouseEventObj.scrollFunc, false);

    delete this.canvas;
    delete this.canvas2;
    delete this.ctx;
    delete this.img;
};

// 方法：创建实例
Common.getInstance = (function () {
    var instance = null;
    return function () {
        if (!instance) {
            instance = new Common();
        }
        return instance;
    };
})();
export default Common;

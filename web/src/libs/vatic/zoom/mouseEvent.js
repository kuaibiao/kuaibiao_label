// JavaScript Document
var MouseEvent = function (common, imgInfo, inputs) {
    this.commonObj = common;
    this.imgInfoObj = imgInfo;
    this.inputsObj = inputs;

    this.isStopMove = true; 	// 是否已停止拖动图片
    this.isZoom = false; 		// 是否允许缩放
    this.guideIsShow = false; 	// 是否显示辅助线
    this.ableIsMove = false; 	// 是否允许拖动图片
    this.recInfo = { 			// 矩形区域坐标
        left: 0,
        top: 0, 			// 开始位置
        width: 0,
        height: 0 		// 结束位置
    };
    this.dragPosition = { 		// 鼠标拖动的距离
        left: 0, top: 0
    };
    this.img = { 				// 当前图片在画布绘图的启始位置
        x: 0, y: 0
    };
    this.mousePoint = {			// 鼠标点击在缩放图和真实图中的位置
        clkImgLeft: 0,
        clkImgTop: 0,
        clkImgTrueLeft: 0,
        clkImgTrueTop: 0
    };
    this.mousewheel = 0; 		// 鼠标滚轴滚动的值
    this.scrollFunc = this.scrollFunc.bind(this);
    this.onmouseup = this.onmouseup.bind(this);
    this.onmousemove = this.onmousemove.bind(this);
};

// 方法: 鼠标在图中点击位置
MouseEvent.prototype.setMouseClickImgPosition = function () {
    var me = this;
    var img_left = me.imgInfoObj.draw.x; 	// 1.图left,top
    var img_top = me.imgInfoObj.draw.y;
    var mouse = me.getMouseLeftTop(me.commonObj.canvas2);
    var mouse_left = mouse.x; 		// 2.鼠标点击left,top
    var mouse_top = mouse.y;
    // 3.鼠标点击在图中left,top(有缩放)
    var clkLeft = mouse_left - img_left;
    var clkTop = mouse_top - img_top;
    if (clkLeft < 0) { clkLeft = 0; }
    if (clkTop < 0) { clkTop = 0; }
    me.mousePoint.clkImgLeft = clkLeft;
    me.mousePoint.clkImgTop = clkTop;
    // $('#mouseClickImgPosition').html(clkLeft+','+clkTop); //缩放图
    // 4.实际图片中,鼠标点击在图中left,top(图无缩放,换算后)
    var inImgLeft = clkLeft;
    var inImgTop = clkTop;
    if (me.imgInfoObj.scale < 0) { // 图被缩小了
        inImgLeft = inImgLeft * (me.imgInfoObj.width / me.imgInfoObj.draw.width);
        inImgTop = inImgTop * (me.imgInfoObj.width / me.imgInfoObj.draw.width);
    } else if (me.imgInfoObj.scale > 0) {
        inImgLeft = inImgLeft * (me.imgInfoObj.width / me.imgInfoObj.draw.width);
        inImgTop = inImgTop * (me.imgInfoObj.width / me.imgInfoObj.draw.width);
    }
    inImgLeft = Math.round(inImgLeft);
    inImgTop = Math.round(inImgTop);
    me.mousePoint.clkImgTrueLeft = inImgLeft;
    me.mousePoint.clkImgTrueTop = inImgTop;
    // $('#inImgLeftTop').html(inImgLeft+','+inImgTop); //实际图
};

// 方法: 绑定鼠标事件
MouseEvent.prototype.canvasBindMouseEvent = function () {
    var me = this;
    // 清空监听事件
    me.commonObj.canvas2.removeEventListener('mouseup', me.onmouseup, false);
    me.commonObj.canvas2.removeEventListener('mousemove', me.onmousemove, false);
    // 鼠标按下
    me.commonObj.canvas2.addEventListener('mousedown', function (event) {
        // 1.图片位置
        me.img.x = me.imgInfoObj.draw.x;
        me.img.y = me.imgInfoObj.draw.y;
        // 2.鼠标交互处理
        me.isStopMove = false;
        var mouse = me.getMouseLeftTop(me.commonObj.canvas2);
        if (me.commonObj.mouseIsInImgArea(mouse) && me.isStopMove == false && me.ableIsMove == true) {
            me.commonObj.canvas2.style.cursor = 'pointer';
        }
        /* 鼠标按下:显示信息 */
        me.recInfo.width = 0;
        me.recInfo.height = 0;
        me.saveMoveStartStopInfo('left', 'top', mouse);
        // $('#mouseclickInfo').html(mouse.x+','+mouse.y);
        // $('#mousemoveInfo').html('0,0');
        me.setMouseClickImgPosition();
        // 鼠标释放
        me.commonObj.canvas2.addEventListener('mouseup', me.onmouseup, false);
    }, false);
    // 鼠标移动
    me.commonObj.canvas2.addEventListener('mousemove', me.onmousemove, false);
    // 鼠标移出
    me.commonObj.canvas2.addEventListener('mouseout', function (event) { me.isStopMove = true; }, false);
    // 给画布绑定滑轮滚动事件
    // if (me.commonObj.canvas2.addEventListener) { me.commonObj.canvas2.addEventListener('DOMMouseScroll', me.scrollFunc, false); }
    // me.commonObj.canvas2.onmousewheel = me.scrollFunc;
    if (me.commonObj.cvsBox.addEventListener) { me.commonObj.cvsBox.addEventListener('DOMMouseScroll', me.scrollFunc, false); }
    me.commonObj.cvsBox.onmousewheel = me.scrollFunc;
    // 赋值
    me.commonObj.mouseEventObj = me;
    me.imgInfoObj.mouseEventObj = me;
};

// 方法: 获取鼠标的坐标位置x=left,y=top
MouseEvent.prototype.getMouseLeftTop = function (cvs2) {
    var event = event || window.event;
    var winX = event.clientX + document.body.scrollLeft + document.documentElement.scrollLeft || event.pageX;
    var winY = event.clientY + document.body.scrollTop + document.documentElement.scrollTop || event.pageY;
    var mouse = {x: 0, y: 0};
    var canBox = cvs2.getBoundingClientRect();
    mouse.x = Math.ceil((winX - canBox.left) * (cvs2.width / canBox.width));
    mouse.y = Math.ceil((winY - canBox.top) * (cvs2.height / canBox.height));
    return mouse;
};

// 方法: 保存矩形区域坐标
MouseEvent.prototype.saveMoveStartStopInfo = function (x, y, result) {
    var me = this;
    // 1.记录鼠标拖拽的坐标
    if (x == 'left') { me.recInfo.left = 0 || result.x; }
    if (y == 'top') { me.recInfo.top = 0 || result.y; }
    if (x == 'width') { me.recInfo.width = 0 || result.x; }
    if (y == 'height') { me.recInfo.height = 0 || result.y; }
    me.showRecInfo();
    // 2.记录拖拽的距离
    if (me.recInfo.width > 0 || me.recInfo.height > 0) {
        var left = me.recInfo.width - me.recInfo.left;
        var top = me.recInfo.height - me.recInfo.top;
        me.dragPosition.left = left;
        me.dragPosition.top = top;
    } else {
        me.dragPosition.left = 0;
        me.dragPosition.top = 0;
    }
    // $('#mousedragInfo').html(me.dragPosition.left+','+me.dragPosition.top);
};

// 方法: 显示矩形区域坐标
MouseEvent.prototype.showRecInfo = function () {
    var me = this;
    // $('#rectangleInfo').html(me.recInfo.left+','+me.recInfo.top+' - '+me.recInfo.width+','+me.recInfo.height);
};

// 方法: 移动辅助线
MouseEvent.prototype.guideMove = function (mouse) {
    var me = this;
    if (me.guideIsShow == true) {
        $('#guide-x').css({"top": mouse.y + 'px'});
        $('#guide-y').css({"left": mouse.x + 'px'});
    }
};

// 方法：事件处理,给画布绑定滑轮滚动事件
MouseEvent.prototype.scrollFunc = function (e) {
    e.preventDefault();
    var me = this;
    if (me.isZoom) {
        var wheelInfo = {};
        e = e || window.event;
        if (e.wheelDelta) { // 判断浏览器IE，谷歌滑轮事件
            if (e.wheelDelta > 0) { wheelInfo = {dir: '向上滚动', val: e.wheelDelta}; }
            if (e.wheelDelta < 0) { wheelInfo = {dir: '向下滚动', val: e.wheelDelta}; }
        } else if (e.detail) { // Firefox滑轮事件
            if (e.detail > 0) { wheelInfo = {dir: '向上滚动', val: e.detail}; }
            if (e.detail < 0) { wheelInfo = {dir: '向下滚动', val: e.detail}; }
        }
        if (wheelInfo && wheelInfo.val) {
            if (wheelInfo.val > 0) {
                me.imgInfoObj.step = 0.06; me.imgInfoObj.enlargeDraw({fromWheel: true}); // 放大
            } else {
                me.imgInfoObj.step = 0.06; me.imgInfoObj.narrowDraw({fromWheel: true}); // 缩小
            }
        }
    }
};

// 方法：事件处理,鼠标释放
MouseEvent.prototype.onmouseup = function () {
    var me = this;
    var cvs2 = me.commonObj.canvas2;
    // 1.取消事件监听
    me.isStopMove = true;
    cvs2.removeEventListener('mouseup', me.onmouseup, false);
    // 2.鼠标指针处理
    var mouse = me.getMouseLeftTop(cvs2);
    if (me.commonObj.mouseIsInImgArea(mouse) && me.isStopMove == true && me.ableIsMove == true) {
        cvs2.style.cursor = 'move';
    } else {
        cvs2.style.cursor = 'default';
    }
};

// 方法：事件处理,鼠标移动
MouseEvent.prototype.onmousemove = function () {
    var me = this;
    var cvs2 = me.commonObj.canvas2;
    var mouse = me.getMouseLeftTop(cvs2);
    // $('#mousemoveInfo').html(mouse.x+','+mouse.y);
    // 移动：辅助线
    this.guideMove(mouse);
    // 1.判断鼠标是否在图中,如果在图中则执行'拖动画图'
    if (me.commonObj.mouseIsInImgArea(mouse) && this.ableIsMove === true) {
        cvs2.style.cursor = 'move';
        // 2.拖动画图
        dragImg();
    } else {
        cvs2.style.cursor = 'default';
    }
    // 功能：拖动画图
    function dragImg () {
        if (me.isStopMove == false) {
            cvs2.style.cursor = 'pointer';
            me.saveMoveStartStopInfo('width', 'height', mouse);
            // 1.移动图片
            var obj = {
                x: me.img.x + me.dragPosition.left,
                y: me.img.y + me.dragPosition.top
            };
            me.inputsObj.init(me.imgInfoObj);
            me.inputsObj.setInputVal(obj);
            me.imgInfoObj.imgDraw();
        }
    }
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
MouseEvent.prototype.destroy = function () {
    delete this.isStopMove; 	// 是否已停止拖动图片
    delete this.isZoom; // 是否允许缩放
    delete this.guideIsShow; // 是否显示辅助线
    delete this.ableIsMove; // 是否允许拖动图片
    delete this.recInfo; 		// 矩形区域坐标
    delete this.dragPosition; 	// 鼠标拖动的距离
    delete this.img; 			// 当前图片在画布绘图的启始位置
    delete this.mousePoint;		// 鼠标点击在缩放图和真实图中的位置
    delete this.mousewheel; 	// 鼠标滚轴滚动的值
};

// 方法：创建实例
MouseEvent.getInstance = (function () {
    var instance = null;
    return function (common, imgInfo, inputs) {
        if (!instance) {
            instance = new MouseEvent(common, imgInfo, inputs);
        }
        return instance;
    };
})();
export default MouseEvent;

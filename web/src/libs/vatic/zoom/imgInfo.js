/* JavaScript Document 图片信息 */
var ImgInfo = function (common, canvasInfo, inputs, player) {
    this.commonObj = common;
    this.canvasInfoObj = canvasInfo;
    this.inputsObj = inputs;
    this.mouseEventObj = null;
    this.player = player;
    this.width = 0;
    this.height = 0; 	// 图片宽和高
    this.scale = 0; 	// 当前缩放总量
    this.step = 0.1;	// 每次缩放大小
    this.originX = 0;
    this.originY = 0;	// 缩放前的原点x,y
    this.draw = {		// 绘制图片时的最后坐标
        sx: 0,
        sy: 0,
        swidth: 0,
        sheight: 0,
        x: 0,
        y: 0,
        width: 0,
        height: 0
    };
    /* 图像所在区域 */
    this.imgArea = {
        left: 0,
        top: 0,
        right: 0,
        bottom: 0
    };
};

/* 方法：初始化图片信息 */
ImgInfo.prototype.init = function (img) {
    var me = this;
    // 0.初始化
    me.scale = 0;
    me.step = 0.1;
    me.scaleChange();
    var obj = {
        x: 0,
        y: 0,
        width: img.width,
        height: img.height
    };
    me.inputsObj.init(me);
    me.inputsObj.setInputVal(obj);
    // 1.图片宽高
    me.width = img.width;
    me.height = img.height;
    me.draw.swidth = me.width;
    me.draw.sheight = me.height;
    me.draw.width = me.width;
    me.draw.height = me.height;
    // $('#ImgInfo').html( me.width + ',' + me.height );
    // 2.判断是否需要缩放图片
    // 2.1宽图或正方图
    if (me.width >= me.height) {
        if (me.width >= me.canvasInfoObj.width) { // 情况1.图宽超过画布的宽
            var w = me.canvasInfoObj.width; // 宽 = 画布宽
            var rotate = parseFloat((w / me.width).toFixed(2));
            me.scale = parseFloat((rotate - 1).toFixed(2)); // 缩小的比率
            me.scaleChange();
            var h = Math.ceil(me.height * rotate); // 高
            var obj = {width: w, height: h};
            me.inputsObj.init(me);
            me.inputsObj.setInputVal(obj);

            if (h > me.canvasInfoObj.height) { // 高 = 画布高
                h = me.canvasInfoObj.height;
                rotate = parseFloat((h / me.height).toFixed(2));
                me.scale = parseFloat((rotate - 1).toFixed(2)); // 缩小的比率
                me.scaleChange();
                w = Math.ceil(me.width * rotate); // 宽
                var obj = {width: w, height: h};
                me.inputsObj.init(me);
                me.inputsObj.setInputVal(obj);
            }
        }
    }
    // 2.2高图
    if (me.height > me.width) {
        if (me.height >= me.canvasInfoObj.height) { // 情况2.图高超过画布的高
            var h = me.canvasInfoObj.height; // 高
            var rotate = parseFloat((h / me.height).toFixed(2));
            me.scale = parseFloat((rotate - 1).toFixed(2)); // 缩小的比率
            me.scaleChange();
            var w = Math.ceil(me.width * rotate); // 宽
            var obj = {width: w, height: h};
            me.inputsObj.init(me);
            me.inputsObj.setInputVal(obj);
        }
    }
    // 3.赋值
    me.commonObj.imgInfoObj = me;
    me.commonObj.inputsObj = me.inputsObj;
};

/* 方法：获取图像中心点坐标 */
ImgInfo.prototype.getImgCenter = function () {
    var me = this;
    var result = {};
    result.x = (me.draw.width) / 2 + me.draw.x;
    result.y = (me.draw.height) / 2 + me.draw.y;
    return result;
};

/* 方法：图像信息 */
ImgInfo.prototype.imgAreaShow = function () {
    var me = this;
    // $('#imgArea').html(me.imgArea.left+','+me.imgArea.top+' - '+me.imgArea.right+','+me.imgArea.bottom);
};

/* 方法：缩放检测提示 */
ImgInfo.prototype.scaleChange = function () {
    var me = this;
    // $('#J-scale').html('缩放:' + me.scale);
};

/* 方法： 绘图 */
ImgInfo.prototype.imgDraw = function () {
    var me = this;
    if (me.commonObj.ctx.canvas) {
        me.commonObj.ctx.clearRect(0, 0, me.commonObj.canvas.width, me.commonObj.canvas.height);
        // 绘制：图像信息
        var sx = me.draw.sx; 	// 来源
        var sy = me.draw.sy;
        var swidth = me.draw.swidth;
        var sheight = me.draw.sheight;
        var x = me.draw.x; 		// 目标
        var y = me.draw.y;
        var width = me.draw.width;
        var height = me.draw.height;
        // 记录：图片绘制区域
        me.imgArea.left = x;
        me.imgArea.top = y;
        me.imgArea.right = width + x;
        me.imgArea.bottom = height + y;
        me.imgAreaShow();
        // console.log('commonObj.ctx.drawImage(me.commonObj.img,',sx,',',sy,',',swidth,',',sheight,',',x,',',y,',',width,',',height,');');
        me.commonObj.ctx.drawImage(me.commonObj.img, sx, sy, swidth, sheight, x, y, width, height);
        // 标注框跟随重定位
        me.annotatedProcess('');
    }
};

// 方法：得到可视范围区域
ImgInfo.prototype.getVisibleRange = function (type, value) {
    var me = this;
    var result = value;
    if (type == 'left' && value != 0) {
        var x = value;
        if ((x + me.draw.width) < 100) { // 左边 和 右边
            result = x + me.draw.width;
            result = x - result + 100;
        }
        if ((me.canvasInfoObj.width - x) < 100) { // 右
            var result = me.canvasInfoObj.width - x;
            result = x - (100 - result);
        }
    }
    if (type == 'top' && value != 0) {
        var y = value;
        if (y + me.draw.height < 100) { // 上边 和 下边
            var result = 100 - (y + me.draw.height);
            result = y + result;
        }
        if ((me.canvasInfoObj.height - y) < 100) { // 下
            var result = me.canvasInfoObj.height - y;
            result = y - (100 - result);
        }
    }
    return result;
};

// 方法：放大
ImgInfo.prototype.enlargeDraw = function (params) {
    // 1.计算
    var me = this;
    me.scale = parseFloat((me.scale + me.step).toFixed(2));
    if (me.scale > 2.9) { me.scale = 2.9; }
    var width = me.width;
    var height = me.height;
    var imgWidth = Math.ceil(width + width * me.scale); // 计算要绘制的宽和高width,height
    var imgHeight = Math.ceil(height + height * me.scale);
    var x = me.draw.x; // 计算绘制顶点x,y
    var y = me.draw.y;
    var _w = Math.ceil((imgWidth - me.draw.width) / 2);
    var _h = Math.ceil((imgHeight - me.draw.height) / 2);
    x = x - _w;
    y = y - _h;
    // 2.是否来自鼠标滑轮放大
    var pas = params || undefined;
    if (pas != undefined) {
        if (pas && pas.fromWheel && pas.fromWheel == true) {
            var result = me.wheelEnlarge(x, y, _w, _h);
            x = result.x; y = result.y;
        }
    }
    // 3.改变参数执行放大
    var obj = { x: x, y: y, width: imgWidth, height: imgHeight };
    me.inputsObj.init(me);
    me.inputsObj.setInputVal(obj);
    me.imgDraw();
    me.scaleChange();
};

// 方法：缩小
ImgInfo.prototype.narrowDraw = function (params) {
    // 1.计算
    var me = this;
    me.scale = parseFloat((me.scale - me.step).toFixed(2));
    if (me.scale < -0.9) { me.scale = -0.9; }
    var width = me.width;
    var height = me.height;
    var imgWidth = Math.ceil(width + width * me.scale); // 计算要绘制的宽和高width,height
    var imgHeight = Math.ceil(height + height * me.scale);
    var x = me.draw.x; // 计算绘制顶点x,y
    var y = me.draw.y;
    var _w = Math.ceil((me.draw.width - imgWidth) / 2);
    var _h = Math.ceil((me.draw.height - imgHeight) / 2);
    x = x + _w;
    y = y + _h;
    // 2.是否来自鼠标滑轮缩小
    var pas = params || undefined;
    if (pas != undefined) {
        if (pas && pas.fromWheel && pas.fromWheel == true) {
            var result = me.wheelNarrow(x, y, _w, _h);
            x = result.x; y = result.y;
        }
    }
    // 3.改变参数执行缩小
    var obj = { x: x, y: y, width: imgWidth, height: imgHeight };
    me.inputsObj.init(me);
    me.inputsObj.setInputVal(obj);
    me.imgDraw();
    me.scaleChange();
};

/* 方法：处理当前帧放大缩小后的标注框 */
ImgInfo.prototype.annotatedProcess = function (type) {
    var me = this;
    // 1.得到当前帧号
    var frameNumber = -1;
    frameNumber = this.player.currentFrame;
    // 2.得到要处理的标注对象
    if (frameNumber >= 0) {
        var arr = annotatedObjectsTracker && annotatedObjectsTracker.annotatedObjects;
        if (frameNumber > annotatedObjectsTracker.lastFrame && annotatedObjectsTracker.lastFrame != -1) {
            frameNumber = annotatedObjectsTracker.lastFrame;
        }
        if (arr.length > 0) {
            var currentFrameAnnObjs = [];
            var currentFrameDoms = [];
            for (var i = 0; i < arr.length; i++) {
                for (var j = 0; j < arr[i].frames.length; j++) {
                    var tmp = arr[i].frames[j];
                    if (frameNumber == tmp.frameNumber) {
                        if (tmp.bbox != null) {
                            currentFrameAnnObjs.push(tmp);
                            currentFrameDoms.push(arr[i].dom);
                        }
                    }
                }
            }
            // 3.处理bbox对象 (1.缩放 2.定位)
            for (var i = 0; i < currentFrameAnnObjs.length; i++) {
                var tmp = currentFrameAnnObjs[i].bbox;
                var dom = currentFrameDoms[i];
                // 缩放
                var width = tmp.width;
                var height = tmp.height;
                width = width * (me.draw.width / me.draw.swidth);
                height = height * (me.draw.height / me.draw.sheight);
                $(dom).css({"width": width + 'px', "height": height + 'px'});
                // 定位
                var left = me.draw.x + tmp.x * (me.draw.width / me.draw.swidth);
                var top = me.draw.y + tmp.y * (me.draw.width / me.draw.swidth);
                $(dom).css({"left": left + 'px', "top": top + 'px'});
            }
        }
    }
};

/* 方法：滑轮缩小,计算绘图位置 */
ImgInfo.prototype.wheelNarrow = function (x, y, _w, _h) {
    var me = this;
    var x = x, y = y, _w = _w, _h = _h;
    var result = {x: 0, y: 0};
    // (2.1)获取鼠标在哪个位置
    var position = {};
    var mouse = me.mouseEventObj.getMouseLeftTop(me.commonObj.canvas2);
    var center = me.getImgCenter();
    var distanceDD = 0; // 到角顶点的距离
    var distanceCE = 0; // 到图片中心的距离
    distanceCE = me.commonObj.getDistance(mouse.x, mouse.y, center.x, center.y);
    if (mouse.x < center.x && mouse.y < center.y) {
        position.leftTop = true; // 左上
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.left, me.imgArea.top);
    } else if (mouse.x > center.x && mouse.y < center.y) {
        position.rightTop = true; // 右上
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.right, me.imgArea.top);
    } else if (mouse.y > center.y && mouse.x < center.x) {
        position.leftBottom = true; // 左下
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.left, me.imgArea.bottom);
    } else if (mouse.y > center.y && mouse.x > center.x) {
        position.rightBottom = true; // 右下
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.right, me.imgArea.bottom);
    }
    // 图形内滑轮缩放
    var rotate = parseFloat((distanceDD / distanceCE).toFixed(2));
    if (rotate < 1) {
        if (rotate > 0.6) { rotate = 1 - rotate - 0.1; }
        x = x - _w; y = y - _h;
        var x2 = _w * rotate, y2 = _h * rotate;
        if (position.leftTop == true || position.leftBottom == true) {
            // 在x左加
            x = x - (x2 / 3);
            if (position.leftTop == true) {
                y = y - y2;
            } else {
                y = y - y2 + (_h * 2);
            }
        }
        if (position.rightTop == true || position.rightBottom == true) {
            x = x - x2 + (_w * 2); // 在x右减
            if (position.rightTop == true) {
                y = y - y2;
            } else {
                y = y - y2 + (_h * 2);
            }
        }
    }
    result.x = Math.ceil(x);
    result.y = Math.ceil(y);
    return result;
};

/* 方法：滑轮放大,计算绘图位置 */
ImgInfo.prototype.wheelEnlarge = function (x, y, _w, _h) {
    var me = this;
    var x = x, y = y, _w = _w, _h = _h;
    var result = {x: 0, y: 0};
    // (2.1)获取鼠标在哪个位置
    var position = {};
    var mouse = me.mouseEventObj.getMouseLeftTop(me.commonObj.canvas2);
    var center = me.getImgCenter();
    var distanceDD = 0; // 到角顶点的距离
    var distanceCE = 0; // 到图片中心的距离
    distanceCE = me.commonObj.getDistance(mouse.x, mouse.y, center.x, center.y);
    if (mouse.x < center.x && mouse.y < center.y) {
        position.leftTop = true; // 左上
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.left, me.imgArea.top);
    } else if (mouse.x > center.x && mouse.y < center.y) {
        position.rightTop = true; // 右上
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.right, me.imgArea.top);
    } else if (mouse.y > center.y && mouse.x < center.x) {
        position.leftBottom = true; // 左下
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.left, me.imgArea.bottom);
    } else if (mouse.y > center.y && mouse.x > center.x) {
        position.rightBottom = true; // 右下
        distanceDD = me.commonObj.getDistance(mouse.x, mouse.y, me.imgArea.right, me.imgArea.bottom);
    }
    // 图形内滑轮缩放
    var rotate = parseFloat((distanceDD / distanceCE).toFixed(2));
    if (rotate < 1) {
        if (rotate > 0.6) { rotate = 1 - rotate - 0.1; }
        x = x + _w; y = y + _h;
        var x2 = _w * rotate, y2 = _h * rotate;
        if (position.leftTop == true || position.leftBottom == true) {
            // 在x左加
            x = x + (x2 / 3);
            if (position.leftTop == true) {
                y = y + y2;
            } else {
                y = y + y2 - (_h * 2);
            }
        }
        if (position.rightTop == true || position.rightBottom == true) {
            x = x + x2 - (_w * 2); // 在x右减
            if (position.rightTop == true) {
                y = y + y2;
            } else {
                y = y + y2 - (_h * 2);
            }
        }
    }
    result.x = Math.ceil(x);
    result.y = Math.ceil(y);
    return result;
};

// 方法：调整放大缩小后的bbox的坐标
ImgInfo.prototype.getBoxCoordinate = function (bbox) {
    var me = this;
    var x = Math.round(bbox.x * (me.draw.swidth / me.draw.width) - me.draw.x * (me.draw.swidth / me.draw.width));
    var y = Math.round(bbox.y * (me.draw.swidth / me.draw.width) - me.draw.y * (me.draw.swidth / me.draw.width));
    var width = Math.round(bbox.width * (me.draw.swidth / me.draw.width));
    var height = Math.round(bbox.height * (me.draw.sheight / me.draw.height));
    var result = {x: x, y: y, width: width, height: height};
    return result;
};

// 方法：调整坐标值
ImgInfo.prototype.processBoxCoordinate = function (x, y, width, height) {
    var me = this;
    var x1 = Math.round(x * (me.draw.swidth / me.draw.width) - me.draw.x * (me.draw.swidth / me.draw.width));
    var y1 = Math.round(y * (me.draw.swidth / me.draw.width) - me.draw.y * (me.draw.swidth / me.draw.width));
    var width1 = Math.round(width * (me.draw.swidth / me.draw.width));
    var height1 = Math.round(height * (me.draw.sheight / me.draw.height));
    var result = {x: x1, y: y1, width: width1, height: height1};
    return result;
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
ImgInfo.prototype.destroy = function () {
    delete this.width; // 图片宽和高
    delete this.height;
    delete this.scale; // 当前缩放总量
    delete this.step; // 每次缩放大小
    delete this.originX; // 缩放前的原点x,y
    delete this.originY;
    delete this.draw; // 绘制图片时的最后坐标
    delete this.imgArea;	// 图像所在区域
};

// 方法：创建实例
ImgInfo.getInstance = (function () {
    var instance = null;
    return function (common, canvasInfo, inputs, player) {
        if (!instance) {
            instance = new ImgInfo(common, canvasInfo, inputs, player);
        }
        return instance;
    };
})();
export default ImgInfo;

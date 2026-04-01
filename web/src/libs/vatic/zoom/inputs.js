var Inputs = function () {
    this.imgInfoObj = null;
};

/* 方法：初始化 */
Inputs.prototype.init = function (imgInfo) {
    this.imgInfoObj = imgInfo;
};

/* 方法：重置文本框中的值 */
Inputs.prototype.initVal = function (imgInfo) {
    var me = this;
    me.imgInfoObj = imgInfo;
    width = me.imgInfoObj.width;
    height = me.imgInfoObj.height;
    var obj = {"sx": 0, "sy": 0, "swidth": width, "sheight": height, "x": 0, "y": 0, "width": width, "height": height };
    this.setInputVal(obj);
};

/* 方法：设置文本框的值 */
Inputs.prototype.setInputVal = function (obj) {
    var me = this;
    if (obj.sx != null) {
        // $('#sx').val(obj.sx);
        me.imgInfoObj.draw.sx = obj.sx;
    }
    if (obj.sy != null) {
        // $('#sy').val(obj.sy);
        me.imgInfoObj.draw.sy = obj.sy;
    }
    if (obj.swidth != null) {
        // $('#swidth').val(obj.swidth);
        me.imgInfoObj.draw.swidth = obj.swidth;
    }
    if (obj.sheight != null) {
        // $('#sheight').val(obj.sheight);
        me.imgInfoObj.draw.sheight = obj.sheight;
    }

    if (obj.x != null) {
        obj.x = me.imgInfoObj.getVisibleRange('left', obj.x);
        // $('#x').val(obj.x);
        me.imgInfoObj.draw.x = obj.x;
    }
    if (obj.y != null) {
        obj.y = me.imgInfoObj.getVisibleRange('top', obj.y);
        // $('#y').val(obj.y);
        me.imgInfoObj.draw.y = obj.y;
    }
    if (obj.width != null) {
        // $('#width').val(obj.width);
        me.imgInfoObj.draw.width = obj.width;
    }
    if (obj.height != null) {
        // $('#height').val(obj.height);
        me.imgInfoObj.draw.height = obj.height;
    }
};

/* 方法：文本框绑定change事件 */
Inputs.prototype.inputBindChange = function (cb) {
    var me = this;
    /*
    //$('#sx,#sy,#swidth,#sheight, #x,#y,#width,#height').change(function(){
        var id=$(this).attr('id');
        var obj={};
        if(id=='sx'){obj.sx=parseInt($(this).val());}
        if(id=='sy'){obj.sy=parseInt($(this).val());}
        if(id=='swidth'){obj.swidth=parseInt($(this).val());}
        if(id=='sheight'){obj.sheight=parseInt($(this).val());}

        if(id=='x'){obj.x=parseInt($(this).val());}
        if(id=='y'){obj.y=parseInt($(this).val());}
        if(id=='width'){obj.width=parseInt($(this).val());}
        if(id=='height'){obj.height=parseInt($(this).val());}

        //$('#status').html($(this).attr('title')+':'+$(this).val());
        me.setInputVal(obj);
        cb();
    });
    */
};

// 方法: 销毁临时变量和对象,解除绑定和监听的事件
Inputs.prototype.destroy = function () {

};

// 方法：创建实例
Inputs.getInstance = (function () {
    var instance = null;
    return function () {
        if (!instance) {
            instance = new Inputs();
        }
        return instance;
    };
})();

export default Inputs

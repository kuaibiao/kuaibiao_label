import * as THREE from '../../libs/three.js-r105/build/three.module';

export const PencilTool = (function f(w) {
    //'画笔'工具
    var PENCILTOOL = function(lpc, dataUrl) {
        //this.LookPointCloud=lpc;
        this.dataUrl = dataUrl;

        $(lpc.element).append(
            "<canvas class='pencil-tool-canvas' width='" +
                lpc.width +
                "px' height='" +
                lpc.height +
                "px' style='position: absolute;left:0;top:0;display: none;'></canvas>"
        );

        this.width = lpc.width;
        this.height = lpc.height;

        this.lpc = lpc;

        this.loaded = false;

        this.autoLockPoint=true;
        this.emptyPointCount=0;
        this.minEmptyPointCount=1000;

        //this.lastCameraPos=camera.

        this.$canvas = $(lpc.element).find('.pencil-tool-canvas');
        //this.$canvas.css({width:lpc.width,height:lpc.height});

        this.canvas = this.$canvas.get(0); //当前'画笔'画布
        this.ctx = this.canvas.getContext('2d'); //当前'画笔'2d画布的上下文

        /*      this.btnBrushCancel = document.getElementById("brush-cancel");	//取消选点
                this.btnBrushAdd = document.getElementById("brush-add");	//画笔选点+
                this.btnBrushDec = document.getElementById("brush-dec");	//画笔选点-

                this.btnClear = document.getElementById("clear");			//清空
                this.btnUndo = document.getElementById("undo");				//后退
                this.btnForward = document.getElementById("forward");		//前进
                this.btnPrint = document.getElementById("J-print");			//打印:调试
                */

        this.brushType = ''; //画笔类型：add代表加  dec代表减
        this.activeColor = 'red'; //当前'画笔'的颜色
        this.isClear = false; //橡皮擦'模式'开启 isClear = true
        this.isBrush = false; //画笔'模式'开启 isBrush = true
        this.lWidth = 1; //画笔'线'的宽度
        this.currentPoints = []; //当前画布中正在参与操作的点
        this.brushPoints = []; //画笔'勾划'的所有的点组合-历史数据
        this.brushPointsIndex = -1; //画笔'勾划'的当前点组的编号

        this.defaultPointsColor = null;
        this.points3d = [];
        this.integrity = [];
        this.regionObj = { points2d: [], colors: [] }; //像素'点'工作区域
        this.cutObjs = [];
        this.nowCutObj = null;
        this.isEdit = false;

        this.drawMode = PENCILTOOL.drawMode.pen;
        this.groundMode = PENCILTOOL.groundMode.all;

        this.cr = 0;

        let self = this;

        this.eventsObject = {
            resize: this.onWindowResize.bind(this),
            keyDown: this._onKeyDown.bind(this),
            keyUp:this._onKeyUp.bind(this)
        };

        this.basicMessage = {};

        this.loadComplete = function() {
            self.init();
        };

        if (this.lpc.status.loaded) this.init();
        else this.lpc.addEventListener('loadComplete', this.loadComplete);
    };
    Object.assign(PENCILTOOL.prototype, THREE.EventDispatcher.prototype);

    PENCILTOOL.drawMode = { pen: 1, circle: 2, rect: 3 };
    PENCILTOOL.groundMode = { all: 1, up: 2, down: 3 };

    //功能：'画笔'工具初始化
    PENCILTOOL.prototype.init = function() {
        var self = this;
        //self.ctx = self.canvas.getContext("2d"); //当前'画笔'2d画布的上下文
        this.loaded = true;

        //创建空白点标记组
        this.emptyPointMarks=new THREE.Group();
        this.lpc.scene1.add(this.emptyPointMarks);

        self.ctx.fillStyle = self.activeColor;
        self.ctx.strokeStyle = self.activeColor;

        //存一个指定默认颜色，方便每次重绘时调用
        this.initDefaultPointsAndColor();

        //self.listenBtns();//监听按钮动作
        self.listenToBrushFun(self.canvas); //监听用户的动作
        self.historyBtnState();

        if (this.dataUrl) {
            if (typeof this.dataUrl === 'string') $.get(this.dataUrl).then(this.loadData.bind(this));
            else this.loadData(this.dataUrl);
        }
    };

    //临时角度调整
    PENCILTOOL.prototype._tmpHide=function(){
        if(this.lpc.controls.enabled) return;
        this.lpc.controls.enabled = true;
        this.$canvas.hide();
    };
    PENCILTOOL.prototype._tmpShow=function(){
        this.getAllPoints();
        this.lpc.controls.enabled = false;
        this.$canvas.show();
    };

    //创建
    PENCILTOOL.prototype.createCutObj = function() {
        this.isEdit = false;
        this.unSelectCutObj();
        this.brushReady();

        this._tmpKeyBind();
    };
    PENCILTOOL.prototype.brushReady = function() {
        this.getAllPoints();
        this.$canvas.show();
        this.lpc.clearSelect();
        this.lpc.controls.enabled = false;

        this.brushAdd();
    };
    //追加模型
    PENCILTOOL.prototype.brushAdd = function() {
        this.isClear = false;
        this.isBrush = true;
        this.brushType = 'add';
    };
    //消减模式
    PENCILTOOL.prototype.brushDec = function() {
        this.isClear = false;
        this.isBrush = true;
        this.brushType = 'dec';
    };
    //取消
    PENCILTOOL.prototype.cancel = function(okey) {
        this.brushType = '';
        this.isBrush = false;
        this.$canvas.hide();
        this.lpc.controls.enabled = true;

        if(this.currentPoints.length&&!okey){
            let ps3=this.points3d;
            let l=this.currentPoints.length;
            let cps=this.currentPoints;
            for(let i=0;i<l;i++){
                ps3[cps[i].i].selected=false;
            }
        }
        if(this.nowCutObj){
            let ps3=this.points3d;
            let l = this.nowCutObj.points.length;
            let cps=this.nowCutObj.points;
            for(let i=0;i<l;i++){
                ps3[cps[i].i].selected=true;
            }
        }

        this.currentPoints = [];

        if (this.cutObjs.length === 1) this.nowCutObj = this.cutObjs[0];

        this.updateColor();

        this._tmpKeyUnBind();

        this._findEmptyPoint();
    };
    //完成
    PENCILTOOL.prototype.okey = function() {
        if (!this.nowCutObj) {
            if (!this.currentPoints.length) {
                this.cancel();
                return;
            }

            let id = this.getRandomId();
            let co = Object.assign(
                {
                    index: id,
                    id: id,
                    points: [].concat(this.currentPoints),
                },
                this.basicMessage
            );
            this.cutObjs.push(co);
            this.selectCutObjByIndex(co.index, false);
            this.dispatchEvent({ type: 'cutObjComplete', message: co });
        } else {
            this.nowCutObj.points = [].concat(this.currentPoints);
            if(!this.nowCutObj.points.length){
                this.deleteCutObjByNow();
            }
        }

        this.isEdit = false;
        this.currentPoints = [];

        this.getEmptyPointCount();

        this.cancel(true);
    };
    //编辑
    PENCILTOOL.prototype.editCutObjByIndex = function(index) {
        let co = this.selectCutObjByIndex(index);
        this.editCutObjByNow();
    };
    PENCILTOOL.prototype.editCutObjByNow = function() {
        if (!this.nowCutObj) {
            this.cancel();
            return;
        }
        this.isEdit = true;
        this.currentPoints = this.nowCutObj.points;
        this.brushReady();

        this._tmpKeyBind();
    };
    //删除
    PENCILTOOL.prototype.deleteCutObjByIndex = function(index) {
        if (this.nowCutObj) {
            if (this.nowCutObj.index === index) {
                this.nowCutObj = null;
                this.cancel();
            }
        }
        let rco = this.deleteCutObj(index);
        this.updateColor();
        this.dispatchEvent({ type: 'deleteCutObj', message: rco });
    };
    PENCILTOOL.prototype.deleteAllCutObj = function() {
        this.nowCutObj = null;
        this.cutObjs = [];
        //this.updateColor();

        let ps3 = this.points3d;
        let l = ps3.length;
        for (let i = 0; i < l; i++) {
            ps3[i].selected = false;
        }

        this.cancel();
        this.clearEmptyPointMarks();
        this.getEmptyPointCount();
        this.dispatchEvent({ type: 'deleteAllCutObj', message: '' });
    };
    PENCILTOOL.prototype.deleteCutObjByNow = function() {
        if (!this.nowCutObj) return;

        let rco = this.deleteCutObj(this.nowCutObj.index);
        this.updateColor();
        this.dispatchEvent({ type: 'deleteCutObj', message: rco });
    };
    PENCILTOOL.prototype.deleteCutObj = function(index) {
        //let tmp=this.cutObjs[0];

        if(this.nowCutObj){
            if(this.nowCutObj.index===index){
                this.nowCutObj=null;
            }
        }

        let rco;
        let cutLength = this.cutObjs.length;
        for (let i = 0; i < cutLength; i++) {
            if (index == this.cutObjs[i].index) {
                /*                this.cutObjs[0]=this.cutObjs[i];
                                this.cutObjs[i]=tmp;*/
                rco = this.cutObjs.splice(i, 1);
                break;
            }
        }

        //let rco = this.cutObjs.shift();
        if (this.cutObjs.length === 1) this.nowCutObj = this.cutObjs[0];
        //this.updateColor();

        if (rco) {
            let ps = rco[0].points;
            let ps3 = this.points3d;
            let l = ps.length;
            for (let i = 0; i < l; i++) {
                ps3[ps[i].i].selected = false;
            }

            this.getEmptyPointCount();
            this._findEmptyPoint();

            return rco[0];
        } else return null;
    };

    //内部空白点检测方法
    PENCILTOOL.prototype._findEmptyPoint=function(){
        if(this.emptyPointCount>this.minEmptyPointCount||!this.emptyPointCount)
            this.clearEmptyPointMarks();
        else if(this.emptyPointMarks.children.length)
            this.findEmptyPoint();
    };

    //获取当前剩余多少点没有标记的数目
    PENCILTOOL.prototype.getEmptyPointCount=function(){
        let points = this.lpc.scene1.getObjectByName("points");
        let l = points.geometry.attributes.position.count;

        for(let i=0;i<this.cutObjs.length;i++){
            l-=this.cutObjs[i].points.length;
        }

        this.emptyPointCount=l;
    };

    //获取所有点的3D 2D位置
    PENCILTOOL.prototype.getAllPoints = function() {
        let points = this.lpc.scene1.getObjectByName('points');

        let l = points.geometry.attributes.position.count;
        //let ps=points.geometry.attributes.position.array;
        let c = this.lpc.camera;
        let p = { width: this.width, height: this.height };

        let points2d = [];
        let points3d = this.points3d;

        let frustum = new THREE.Frustum();
        frustum.setFromMatrix(new THREE.Matrix4().multiplyMatrices(c.projectionMatrix, c.matrixWorldInverse));

        for (let i = 0; i < l; i++) {
            let v3 = points3d[i];
            if (frustum.containsPoint(v3)) {
                let v2 = this.toScreenPosition(v3, c, p);
                v2.i = i;
                points2d.push(v2);
            }
        }

        //this.regionObj.points3d=points3d;
        this.regionObj.points2d = points2d;
        //this.regionObj.colors=colors;
    };
    //存一个指定默认颜色，方便每次重绘时调用
    PENCILTOOL.prototype.initDefaultPointsAndColor = function() {
        let points = this.lpc.scene1.getObjectByName('points');
        let position = points.geometry.getAttribute('position');
        //let intensity=points.geometry.getAttribute("intensity");
        let l = position.count;
        let ps = position.array;

        let colors = [];
        let points3d = [];

        this.emptyPointCount=l;

        for (let i = 0; i < l; i++) {
            colors.push(1, 1, 1);
            points3d.push(new THREE.Vector3(ps[i * 3], ps[i * 3 + 1], ps[i * 3 + 2]));
        }

        this.defaultPointsColor = colors;
        this.points3d = points3d;
        //this.integrity=intensity.array;
    };
    //写入颜色
    PENCILTOOL.prototype.setTmpColor = function () {
        let ps=this.lpc.pointSize?this.lpc.pointSize:0.1;

        let ps3=this.points3d;

        let self = this;
        let c = [].concat(this.defaultPointsColor);
        let cutLength = this.cutObjs.length;
        for (let i = 0; i < cutLength; i++) {
            let co = this.cutObjs[i];
            let cs = 1;

            if (this.nowCutObj) {
                if (co.index == this.nowCutObj.index) continue;
                else cs = 0.4
            }

            let cc = [1 * cs, 1 * cs, 0 * cs];
            if (co.attr) {
                if (co.attr.color) {
                    let ct = new THREE.Color(co.attr.color);
                    cc = [ct.r * cs, ct.g * cs, ct.b * cs];
                }
            }
            let jpl = co.points.length;
            let cp = co.points;
            for (let j = 0; j < jpl; j++) {
                let p = cp[j];
                c[p.i * 3] = cc[0];
                c[p.i * 3 + 1] = cc[1];
                c[p.i * 3 + 2] = cc[2];
            }
        }

        let dc = [1, 1, 0];
        if (this.nowCutObj && this.nowCutObj.attr && this.nowCutObj.attr.color) {
            let ct = new THREE.Color(this.nowCutObj.attr.color);
            dc = [ct.r, ct.g, ct.b];
        }

        let cpl = this.currentPoints.length;
        let cp = this.currentPoints;
        for (let i = 0; i < cpl; i++) {
            let p = cp[i];
            c[p.i * 3] = dc[0];
            c[p.i * 3 + 1] = dc[1];
            c[p.i * 3 + 2] = dc[2];
        }
        //临时检测
        this._findEmptyPoint();

        let points = this.lpc.scene1.getObjectByName("points");
        points.geometry.addAttribute('color', new THREE.Float32BufferAttribute(c, 3));
        points.material = new THREE.PointsMaterial({size: ps, vertexColors: THREE.VertexColors});
    };
    //更新全部颜色
    PENCILTOOL.prototype.updateColor = function(ns) {
        let ps=this.lpc.pointSize?this.lpc.pointSize:0.1;

        let self = this;
        let c = [].concat(this.defaultPointsColor);
        let l = this.cutObjs.length;

        let ps3 = this.points3d;

        if(self.nowCutObj){
            for (let i = 0; i < l; i++) {
                let co = this.cutObjs[i];
                if(co.index === self.nowCutObj.index){
                    let tmp=this.cutObjs[this.cutObjs.length-1];
                    this.cutObjs[this.cutObjs.length-1]=this.cutObjs[i];
                    this.cutObjs[i]=tmp;
                }
            }
        }

        for (let i = 0; i < l; i++) {
            let co = this.cutObjs[i];

            let cs = 1;
            if (self.nowCutObj) cs = co.index === self.nowCutObj.index ? 1 : 0.4;

            let cc = [1 * cs, 1 * cs, 0];
            if (co.attr) {
                if (co.attr.color) {
                    let ct = new THREE.Color(co.attr.color);
                    cc = [ct.r * cs, ct.g * cs, ct.b * cs];
                }
            }
            if (co.points) {
                let jpl = co.points.length;
                let cp = co.points;
                for (let j = 0; j < jpl; j++) {
                    let p = cp[j];
                    c[p.i * 3] = cc[0];
                    c[p.i * 3 + 1] = cc[1];
                    c[p.i * 3 + 2] = cc[2];
                    if(ns){
                        ps3[p.i].selected=true;
                    }
                }
            } else if (co.indexs) {
                let kil = co.indexs.length;
                let ci = co.indexs;
                for (let k = 0; k < kil; k++) {
                    let pi = ci[k];
                    c[pi * 3] = cc[0];
                    c[pi * 3 + 1] = cc[1];
                    c[pi * 3 + 2] = cc[2];
                    if(ns){
                        ps3[p.i].selected=true;
                    }
                }
            }
        }

        let points = this.lpc.scene1.getObjectByName('points');
        points.geometry.addAttribute('color', new THREE.Float32BufferAttribute(c, 3));
        points.material = new THREE.PointsMaterial({ size: ps, vertexColors: THREE.VertexColors });
    };

    //选中切块用index
    PENCILTOOL.prototype.selectCutObjByIndex = function(index) {
        let co = this.findCutObjeByIndex(index);
        if (!co) return null;

        this.nowCutObj = co;
        this.currentPoints = co.points;

        this.updateColor();

        this.dispatchEvent({ type: 'selectCutObj', message: co });

        return co;
    };
    //取消选中
    PENCILTOOL.prototype.unSelectCutObj = function() {
        this.nowCutObj = null;
        this.currentPoints = [];
        this.updateColor();
    };
    //获取所有切块数据
    PENCILTOOL.prototype.getAllCutObjs = function() {
        let self = this;

        let l = this.cutObjs.length;
        for (let i = 0; i < l; i++) {
            let co = this.cutObjs[i];
            co.indexs = [];
            let cp = co.points;
            let jpl = cp.length;
            for (let j = 0; j < jpl; j++) {
                co.indexs.push(cp[j].i);
            }
            /*            co.points.map(function (p, i) {
                        })*/
        }
        /*        this.cutObjs.map(function (co) {
                });*/

        return this.cutObjs;
    };
    PENCILTOOL.prototype.getAllCutObjPoints = function() {
        let self = this;
        let p3d = this.points3d;

        let l = this.cutObjs.length;
        for (let i = 0; i < l; i++) {
            let co = this.cutObjs[i];
            co.indexs = [];
            co.points3d = [];
            let cp = co.points;
            let jpl = cp.length;
            for (let j = 0; j < jpl; j++) {
                co.indexs.push(cp[j].i);
                co.points3d.push(p3d[cp[j].i]);
            }
            /*            co.points.map(function (p, i) {
                        })*/
        }
        /*        this.cutObjs.map(function (co) {
                });*/

        return this.cutObjs;
    };
    //获取单个切块数据用index
    PENCILTOOL.prototype.getCutObjByIndex = function(index) {
        let co = this.findCutObjeByIndex(index);
        if (co) {
            co.indexs = [];
            let cp = co.points;
            let jpl = cp.length;
            for (let j = 0; j < jpl; j++) {
                co.indexs.push(cp[j].i);
            }
            /*            co.points.map(function (p, i) {
                        })*/
            return co;
        } else return null;
    };

    PENCILTOOL.prototype.setBasicMessage = function(obj) {
        this.basicMessage = obj;
        if (obj.points) delete obj.points;

        let l = this.cutObjs.length;
        for (let i = 0; i < l; i++) {
            this.cutObjs[i] = Object.assign(this.cutObjs[i], obj);
        }
    };
    PENCILTOOL.prototype.setMessageByIndex = function(obj, index) {
        if (obj.points) delete obj.points;

        let co = this.findCutObjeByIndex(index);
        if (co) {
            co = Object.assign(co, obj);
            this.dispatchEvent({ type: 'setMessage', message: co });
        }
        this.updateColor();
    };
    PENCILTOOL.prototype.setMessageByNow = function(obj) {
        if (!this.nowCutObj) return;

        if (obj.points) delete obj.points;

        let co = this.nowCutObj;
        if (co) {
            co = Object.assign(co, obj);
        }
        this.updateColor();
    };
    PENCILTOOL.prototype.findCutObjeByIndex = function(index) {
        let l = this.cutObjs.length;
        for (let i = 0; i < l; i++) {
            if (index == this.cutObjs[i].index) {
                //this.cutObjs[i]=Object.assign(this.cutObjs[i],obj);
                return this.cutObjs[i];
            }
        }
        return false;
    };

    //按下 空格 键进行临时角度调整相关方法事件代码
    PENCILTOOL.prototype._tmpKeyBind=function(){
        $(window).on("keydown",this.eventsObject.keyDown);
        $(window).on("keyup",this.eventsObject.keyUp);
    };
    PENCILTOOL.prototype._tmpKeyUnBind=function(){
        $(window).off("keydown",this.eventsObject.keyDown).off("keyup",this.eventsObject.keyUp);
    };
    PENCILTOOL.prototype._onKeyDown=function(e){

        if(e.keyCode===32){
            e.stopPropagation();
            e.preventDefault();

            this._tmpHide();
        }
    };
    PENCILTOOL.prototype._onKeyUp=function(e){

        if(e.keyCode===32){
            e.stopPropagation();

            this._tmpShow();
        }
    };

    //功能：监听用户的'画笔'动作
    PENCILTOOL.prototype.listenToBrushFun = function(canvas) {
        var self = this;
        var painting = false; //是否开始'绘画'
        var lastPoint = { x: undefined, y: undefined };

        var tmp = {
            //'画笔'的临时点
            id: 0,
            points: [],
        };
        canvas.onmousedown = function(e) {
            //1.
            painting = true;
            var xy = self.getXYFun(e);
            lastPoint = { x: xy.x, y: xy.y };
            self.ctx.save();
            self.drawCircle(xy.x, xy.y, 0);
            //2.
            tmp = { id: 0, points: [] };
            tmp.points.push(lastPoint);
        };
        canvas.onmousemove = function(e) {
            if (painting) {
                var xy = self.getXYFun(e);
                var newPoint = { x: xy.x, y: xy.y };
                if (self.drawMode === PENCILTOOL.drawMode.pen) {
                    //2.'画笔'画的圈圈
                    self.drawLine(lastPoint.x, lastPoint.y, newPoint.x, newPoint.y);
                    lastPoint = newPoint;
                    tmp.points.push(newPoint);
                } else if (self.drawMode === PENCILTOOL.drawMode.circle) {
                    var r = new THREE.Vector2(newPoint.x, newPoint.y)
                        .sub(new THREE.Vector2(lastPoint.x, lastPoint.y))
                        .length();
                    self.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    self.drawCircle(lastPoint.x, lastPoint.y, r);
                    self.cr = r;
                } else if (self.drawMode === PENCILTOOL.drawMode.rect) {
                    self.ctx.clearRect(0, 0, canvas.width, canvas.height);
                    self.drawRect(lastPoint.x, lastPoint.y, newPoint.x - lastPoint.x, newPoint.y - lastPoint.y);
                    self.rect = [
                        lastPoint,
                        { x: newPoint.x, y: lastPoint.y },
                        newPoint,
                        { x: lastPoint.x, y: newPoint.y },
                    ];
                }
            }
        };
        canvas.onmouseup = function() {
            painting = false;
            if (tmp.points.length > 0 && self.isBrush === true) {
                //2.点变色-处理圈中的点'加|减'
                self.discolorationFun(tmp);
                tmp = null;
                //3.清除'画笔'画的圈圈
                self.ctx.clearRect(0, 0, canvas.width, canvas.height);
                self.cr = 0;
            }
        };
        canvas.mouseleave = function() {
            painting = false;
        };

        window.addEventListener('resize', this.eventsObject.resize);
    };
    //功能：点变色-处理圈中的点'加|减'
    PENCILTOOL.prototype.discolorationFun = function(tmp) {
        //tmp 代表'画笔点组'
        //chPoints 代表'画笔点组'产生的'凸包点'
        //pixPoints 代表所有的'像素点'
        //selectedPoints 代表需要变色的点
        var self = this;
        //var chPoints = self.convexHull(tmp.points); //'凸包点'
        var chPoints = tmp.points;
        if (chPoints != undefined) {
            //1.判断'凸包点'内是否有像素点
            var pixPoints = self.regionObj.points2d; //所有的'像素点'
            var isHavePixelsPoints = false; //是否有像素点
            var selectedPoints = [];
            var pixPointsLen = pixPoints.length;
            for (var i = 0; i < pixPointsLen; i++) {
                var isInside = self.pointInPolygon(pixPoints[i], chPoints);
                if (isInside) {
                    //有像素点
                    isHavePixelsPoints = true;
                    //pixPoints[i].i = i;
                    selectedPoints.push(pixPoints[i]);
                    //selectedPoints.push(i);
                }
            }
            //2.凸包点'图形'内是否有像素点
            if (isHavePixelsPoints === true) {
                //3.'变色点'存储到画笔点中
                if (selectedPoints.length > 0) {
                    //var g = $.extend(true, [], self.currentPoints);
                    if (self.brushType == 'add') {
                        this.currentPoints = this.currentPoints.concat(selectedPoints);
                        this.uniqueCurrentPoints();
                    } else if (self.brushType == 'dec') {
                        this.uniqueDelCurrentPoints(selectedPoints);
                    }
                    //4.当前点存入历史记录中
                    self.savePoints(selectedPoints);
                    selectedPoints = [];
                }
                //4.变色
                //self.currentPointsReDraw();
                self.setTmpColor();
            }
        }
    };

    PENCILTOOL.prototype.uniqueCurrentPoints = function() {
        let ps3=this.points3d;
        let result = [],
            hash = {};
        for (let i = 0, elem; (elem = this.currentPoints[i]) != null; i++) {
            if (!hash[elem.i]) {
                result.push(elem);
                hash[elem.i] = true;
                ps3[elem.i].selected=true;
            }
        }
        this.currentPoints = result;
    };

    PENCILTOOL.prototype.uniqueDelCurrentPoints = function(arr) {
        let ps3=this.points3d;
        let result = [],
            hash = {};
        for (let i = 0, elem; (elem = arr[i]) != null; i++) {
            hash[elem.i] = true;
        }
        for (let i = 0, elem; (elem = this.currentPoints[i]) != null; i++) {
            if (!hash[elem.i])
                result.push(elem);
            else
                ps3[elem.i].selected=false;

        }
        this.currentPoints = result;
    };

    //功能：当前点存入历史记录中
    PENCILTOOL.prototype.savePoints = function(selectedPoints) {
        var self = this;
        if (self.currentPoints.length > 0 || self.brushType == 'dec') {
            self.brushPointsIndex = self.brushPoints.length;
            var id = self.brushPointsIndex;
            var c = $.extend(true, [], self.currentPoints);
            self.brushPoints.push({ id: id, selectedPoints: c });
        }
    };
    //功能：判断一个id是否在已有数组中存在
    PENCILTOOL.prototype.isInBrushPoints = function(id, arr) {
        var self = this;
        var result = false;
        for (var i = 0; i < arr.length; i++) {
            if (id == arr[i].id) {
                result = true;
                break;
            }
        }
        return result;
    };
    //功能：判断一个点是否在另一组点中
    PENCILTOOL.prototype.isInPoints = function(ele, arr) {
        var self = this;
        var result = false;
        for (var i = 0; i < arr.length; i++) {
            //if(ele.x == arr[i].x && ele.y == arr[i].y){
            if (ele.i === arr[i].i) {
                result = true;
                break;
            }
        }
        return result;
    };
    //功能：重新绘制当前点的状态
    PENCILTOOL.prototype.currentPointsReDraw = function() {
        /*        var self = this;
                self.regionObj.initImage();
                self.currentPoints.forEach(function(ele){
                    self.regionObj.drawPoint(self.activeColor,ele,1);
                });
                self.historyBtnState();*/
    };
    //功能：判断一个点是否在一个多边形或圆之内，判断完成后判断通过地面模式是否返回()
    PENCILTOOL.prototype.pointInPolygon = function(point, pointGroup) {
        var self = this;
        var x = point.x,
            y = point.y;
        var inside = false;

        if(this.brushType=="add"){
            let p3=this.points3d;
            let p=p3[point.i];

            if(p.selected) return false;
        }

        if (this.drawMode === PENCILTOOL.drawMode.pen) {
            for (var i = 0, j = pointGroup.length - 1; i < pointGroup.length; j = i++) {
                var xi = pointGroup[i].x,
                    yi = pointGroup[i].y;
                var xj = pointGroup[j].x,
                    yj = pointGroup[j].y;
                var intersect = yi > y != yj > y && x < ((xj - xi) * (y - yi)) / (yj - yi) + xi;
                if (intersect) inside = !inside;
            }
        } else if (this.drawMode === PENCILTOOL.drawMode.circle) {
            var lp = pointGroup[0];
            if (new THREE.Vector2(x, y).sub(new THREE.Vector2(lp.x, lp.y)).length() <= this.cr) {
                inside = true;
            } else {
                inside = false;
            }
        } else if (this.drawMode === PENCILTOOL.drawMode.rect) {
            if (this.IsPointInMatrix(this.rect[0], this.rect[1], this.rect[2], this.rect[3], point)) {
                inside = true;
            } else {
                inside = false;
            }
        }

        inside = this.pointInGround(inside, point);

        return inside;
    };
    //判断地面模式
    PENCILTOOL.prototype.pointInGround = function(inside, point) {
        if (inside) {
            var p3 = this.points3d;

            switch (this.groundMode) {
                case PENCILTOOL.groundMode.all: {
                    break;
                }
                case PENCILTOOL.groundMode.up: {
                    let p = p3[point.i];
                    if (p.z > -this.lpc.groundOffset) {
                        inside = true;
                        break;
                    } else {
                        inside = false;
                        break;
                    }
                }
                case PENCILTOOL.groundMode.down: {
                    let p = p3[point.i];
                    if (p.z <= -this.lpc.groundOffset) {
                        inside = true;
                        break;
                    } else {
                        inside = false;
                        break;
                    }
                }
            }
        }

        return inside;
    };
    //功能：求一组点的凸包点
    PENCILTOOL.prototype.convexHull = function(arr) {
        var self = this;
        const n = arr.length;
        if (n < 3) {
            return;
        }
        const hull = [];
        var l = 0;
        for (var i = 0; i < n; i++) {
            if (arr[i].x < arr[l].x) {
                l = i;
            }
        }
        var p = l,
            q;
        do {
            hull.push(arr[p]);
            q = (p + 1) % n;
            for (var i = 0; i < n; i++) {
                if (self.orientation(arr[p], arr[i], arr[q]) === 2) {
                    q = i;
                }
            }
            p = q;
        } while (p !== l);
        return hull;
    };
    //功能：凸包点的目标比较
    PENCILTOOL.prototype.orientation = function(p, q, r) {
        var self = this;
        const val = (q.y - p.y) * (r.x - q.x) - (q.x - p.x) * (r.y - q.y);
        if (val === 0) {
            return 0;
        }
        return val > 0 ? 1 : 2;
    };
    //功能：获取'鼠标拖动的'矩形坐标
    PENCILTOOL.prototype.getXYFun = function(e) {
        var self = this;
        var _d = { x: 0, y: 0 };
        var e = e || window.event;
        var scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
        var scrollY = document.documentElement.scrollTop || document.body.scrollTop;
        var x = e.pageX || e.clientX + scrollX;
        var y = e.pageY || e.clientY + scrollY;
        /*当前元素离body的左和上距离*/
        var rect = self.canvas.getBoundingClientRect();
        //var rect = $(canvas).position();
        var top = document.documentElement.clientTop; /*IE下元素离上距离*/
        var left = document.documentElement.clientLeft;
        x = x - (rect.left - left);
        y = y - (rect.top - top);
        _d = { x: x, y: y };
        return _d;
    };
    //功能：画笔开始
    PENCILTOOL.prototype.drawCircle = function(x, y, radius) {
        var self = this;
        self.ctx.save();
        self.ctx.beginPath();
        self.ctx.arc(x, y, radius, 0, Math.PI * 2);
        self.ctx.stroke();
        if (self.isClear) {
            self.ctx.clip();
            self.ctx.clearRect(0, 0, self.canvas.width, self.canvas.height);
            self.ctx.restore();
        }
    };
    //功能：画笔绘画中
    PENCILTOOL.prototype.drawLine = function(x1, y1, x2, y2) {
        var self = this;
        self.ctx.lineWidth = self.lWidth;
        self.ctx.lineCap = 'round';
        self.ctx.lineJoin = 'round';
        if (self.isClear) {
            //'像皮擦'模式开启
            self.ctx.save();
            self.ctx.globalCompositeOperation = 'destination-out';
            self.ctx.moveTo(x1, y1);
            self.ctx.lineTo(x2, y2);
            self.ctx.stroke();
            self.ctx.closePath();
            self.ctx.clip();
            self.ctx.clearRect(0, 0, self.canvas.width, self.canvas.height);
            self.ctx.restore();
        } else if (self.isBrush === true) {
            //'画笔'模式开启
            self.ctx.moveTo(x1, y1);
            self.ctx.lineTo(x2, y2);
            self.ctx.stroke();
            self.ctx.closePath();
        }
    };
    PENCILTOOL.prototype.drawRect = function(x, y, w, h) {
        this.ctx.save();
        this.ctx.beginPath();
        this.ctx.rect(x, y, w, h);
        this.ctx.stroke();
    };

    //功能：清空'已选点'
    PENCILTOOL.prototype.clearFun = function() {
        var self = this;
        //原理：使用'画笔选点-'操作
        self.brushType = 'dec';
        self.currentPoints = [];
        if (self.brushPoints.length > 0) {
            if (self.brushPoints[self.brushPointsIndex].selectedPoints.length > 0) {
                self.savePoints([]);
            }
        }
        self.currentPointsReDraw();
    };

    //功能：监听按钮动作
    PENCILTOOL.prototype.listenBtns = function() {
        /*        var self = this;
                //功能：取消选点
                self.btnBrushCancel.onclick = function () {
                    self.brushType = '';
                    self.isBrush = false;
                    $(self.canvas).removeClass('cur-add').removeClass('cur-dec');
                };
                //功能：画笔'加'+
                self.btnBrushAdd.onclick = function () {
                    self.isClear = false;
                    self.isBrush = true;
                    self.brushType = 'add';
                    $(self.canvas).addClass('cur-add').removeClass('cur-dec');
                };
                //功能：画笔'减'-
                self.btnBrushDec.onclick = function () {
                    self.isClear = false;
                    self.isBrush = true;
                    self.brushType = 'dec';
                    $(self.canvas).addClass('cur-dec').removeClass('cur-add');
                };
                //功能：清空
                self.btnClear.onclick = function () {
                    self.clearFun();
                };
                //功能：后退
                self.btnUndo.onclick = function () {
                    self.undoFun();
                };
                //功能：前进
                self.btnForward.onclick = function () {
                    self.forwardFun();
                };
                //功能：打印:调试
                self.btnPrint.onclick = function () {
                    console.log(' - ');
                    console.log("'所有像素点'坐标:",self.regionObj.points2d);
                    console.log("画笔'勾划'的所有的点组:",self.brushPoints);
                    console.log("currentPoints:",self.currentPoints);
                    console.log("brushPointsIndex:",self.brushPointsIndex);
                };*/
    };
    //功能：后退'历史记录'
    PENCILTOOL.prototype.undoFun = function() {
        var self = this;
        self.brushPointsIndex = self.brushPointsIndex - 1;
        if (self.brushPoints.length > 0) {
            var c = [];
            if (self.brushPointsIndex >= 0) {
                //c = JSON.parse(JSON.stringify(self.brushPoints[self.brushPointsIndex].selectedPoints));
                c = $.extend(true, [], self.brushPoints[self.brushPointsIndex].selectedPoints);
            }
            self.currentPoints = c;
            self.currentPointsReDraw();
        }
        if (self.brushPointsIndex < 0) {
            self.brushPointsIndex = -1;
        }
    };
    //功能：前进'历史记录'
    PENCILTOOL.prototype.forwardFun = function() {
        var self = this;
        self.brushPointsIndex = self.brushPointsIndex + 1;
        if (self.brushPointsIndex >= self.brushPoints.length) {
            self.brushPointsIndex = self.brushPoints.length - 1;
        }
        if (self.brushPoints.length > 0) {
            var c = [];
            if (self.brushPointsIndex >= 0) {
                //c = JSON.parse(JSON.stringify(self.brushPoints[self.brushPointsIndex].selectedPoints));
                c = $.extend(true, [], self.brushPoints[self.brushPointsIndex].selectedPoints);
            }
            self.currentPoints = c;
            self.currentPointsReDraw();
        }
    };
    //功能：前进｜后退,按钮状态处理!
    PENCILTOOL.prototype.historyBtnState = function() {
        var self = this;
        //后退
        if (self.brushPointsIndex < 0) {
            $(self.btnUndo).attr('disabled', true);
        } else {
            $(self.btnUndo).attr('disabled', false);
        }
        //前进
        if (self.brushPoints.length > 0 && self.brushPointsIndex < self.brushPoints.length - 1) {
            $(self.btnForward).attr('disabled', false);
        } else {
            $(self.btnForward).attr('disabled', true);
        }
    };
    //功能：消毁释放对象
    PENCILTOOL.prototype.destroy = function() {
        this.deleteAllCutObj();
        this.destroyEmptyPointMarks();

        this._listeners = {};

        this.lpc.removeEventListener('loadComplete', this.loadComplete);

        this.canvas.ontouchstart = null; //移除事件
        this.canvas.ontouchmove = null;
        this.canvas.ontouchend = null;

        this.canvas.onmousedown = null; //移除事件
        this.canvas.onmousemove = null;
        this.canvas.onmouseup = null;
        this.canvas.mouseleave = null;

        this._tmpKeyUnBind();

        window.removeEventListener('resize', this.eventsObject.resize);

        this.canvas = null; //当前'画笔'画布
        this.ctx = null; //当前'画笔'2d画布的上下文

        this.btnBrushAdd = null; //画笔选点+
        this.btnBrushDec = null; //画笔选点-
        this.btnClear = null; //清空
        this.btnUndo = null; //后退

        this.activeColor = null; //当前'画笔'的颜色
        this.isClear = null; //橡皮擦'模式'开启 isClear = true
        this.isBrush = null; //画笔'模式'开启   isBrush = true
        this.lWidth = null; //画笔'线'的宽度
        this.currentPoints = null;
        this.brushPoints = []; //画笔'勾划'的所有的点组
        this.points3d = null;
        this.brushPoints = null;
        this.brushPointsIndex = null; //画笔'勾划'的当前点组的编号

        this.$canvas.remove();
        this.$canvas = null;

        this.nowCutObj = null;
        this.cutObjs = [];
        //this.updateColor();
        this.defaultPointsColor = null;
        this.regionObj = null;
        this.lpc = null;
        this.dataUrl = null;
    };

    //销毁所有空点标识
    PENCILTOOL.prototype.destroyEmptyPointMarks=function(){
        this.clearEmptyPointMarks();
        this.lpc.scene1.remove(this.emptyPointMarks);
    };
    //清空所有空点标识
    PENCILTOOL.prototype.clearEmptyPointMarks=function(){
        let epms=this.emptyPointMarks;

        epms.traverse(function (epm) {
            if(epm.geometry){
                epm.geometry.dispose();
                epm.material.dispose();
            }
        });
        while (epms.children.length){
            epms.remove(epms.children[0]);
        }
    };

    //数据回显
    PENCILTOOL.prototype.loadData = function(res) {
        this.cutObjs = res;
        let l = this.cutObjs.length;
        for (let i = 0; i < l; i++) {
            let co = this.cutObjs[i];
            co.points = [];
            let ci = co.indexs;
            let jil = co.indexs.length;
            for (let j = 0; j < jil; j++) {
                let pi = ci[j];
                co.points.push({ i: pi });
            }
        }
        /*        this.cutObjs.map(function (co) {
                });*/

        this.updateColor(true);

        this.getEmptyPointCount();
    };

    PENCILTOOL.prototype.onWindowResize = function() {
        this.width = this.lpc.width;
        this.height = this.lpc.height;

        this.$canvas.attr('width', this.width).attr('height', this.height);

        this.ctx.fillStyle = this.activeColor;
        this.ctx.strokeStyle = this.activeColor;
    };

    //获得随机ID
    PENCILTOOL.prototype.getRandomId = function(randomLength) {
        return THREE.Math.generateUUID();
        // return Number(Math.random().toString().substr(3, randomLength) + Date.now()).toString(36)
    };
    //三维二维坐标点转换
    //3d世界坐标系转2D坐标系
    PENCILTOOL.prototype.toScreenPosition = function(pos, camera, plane) {
        let position = pos.clone();
        let screenCoord = {};
        position.project(camera);
        screenCoord.x = (0.5 + position.x / 2) * plane.width;
        screenCoord.y = (0.5 - position.y / 2) * plane.height;
        return screenCoord;
    };

    //查找还没有被选择的点
    PENCILTOOL.prototype.findEmptyPoint=function () {
        //this.points3d;
        if(!this.emptyPointCount) return 0;
        if(this.emptyPointCount>this.minEmptyPointCount) return 2;

        this.clearEmptyPointMarks();

        let rm=0;

        let ps3=this.points3d;
        let l=ps3.length;
        for(let i=0;i<l;i++){
            if(!ps3[i].selected){
                let epm=new THREE.Mesh(new THREE.OctahedronBufferGeometry(this.lpc.worldRadius/100),new THREE.MeshBasicMaterial({color:0xff0000,wireframe:true}));
                epm.position.copy(ps3[i])
                this.emptyPointMarks.add(epm);

                rm=1;
            }
        }
        return rm;
    };

    PENCILTOOL.prototype.findOneEmptyPoint=function(){
        let ps3=this.points3d;
        let l=ps3.length;
        for(let i=0;i<l;i++){
            if(!ps3[i].selected){

                this.lpc.controls.target.copy(ps3[i]);
                this.lpc.controls.update();

                return true;
            }
        }
        return false;
    };

    PENCILTOOL.prototype.GetCross = function(p1, p2, p) {
        return (p2.x - p1.x) * (p.y - p1.y) - (p.x - p1.x) * (p2.y - p1.y);
    };
    //判断点p是否在p1p2p3p4的正方形内
    PENCILTOOL.prototype.IsPointInMatrix = function(p1, p2, p3, p4, p) {
        let isPointIn =
            this.GetCross(p1, p2, p) * this.GetCross(p3, p4, p) >= 0 &&
            this.GetCross(p2, p3, p) * this.GetCross(p4, p1, p) >= 0;
        return isPointIn;
    };

    return PENCILTOOL;
})(window);

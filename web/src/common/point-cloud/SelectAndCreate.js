import * as THREE from "../../libs/three.js-r105/build/three.module";
import numeric from "./numeric.min";

export let SelectAndCreate=(function (w) {

    let SAC=function (lpc) {
        $(lpc.element).append("<canvas class='select-and-create-canvas' width='" + lpc.width + "px' height='" + lpc.height + "px' style='position: absolute;left:0;top:0;display: none;background:rgba(0,0,0,0.25)'></canvas>");

        this.width = lpc.width;
        this.height = lpc.height;

        this.lpc = lpc;

        this.loaded=false;

        this.$canvas = $(lpc.element).find(".select-and-create-canvas");

        this.canvas = this.$canvas.get(0);			//当前'画笔'画布
        this.ctx = this.canvas.getContext("2d"); //当前'画笔'2d画布的上下文

        this.brushType = '';		//画笔类型：add代表加  dec代表减
        this.activeColor = 'red'; 	//当前'画笔'的颜色
        this.isClear = false; 		//橡皮擦'模式'开启 isClear = true
        this.isBrush = false; 		//画笔'模式'开启 isBrush = true
        this.lWidth = 1; 			//画笔'线'的宽度
        this.currentPoints = [];	//当前画布中正在参与操作的点
        this.brushPoints = []; 		//画笔'勾划'的所有的点组合-历史数据
        this.brushPointsIndex = -1; //画笔'勾划'的当前点组的编号

        this.defaultPointsColor = null;
        this.points3d = [];
        this.integrity = [];
        this.regionObj = {points2d: [], colors: []}; 		//像素'点'工作区域
        this.cutObjs = [];
        this.nowCutObj = null;
        this.isEdit = false;

        this.painting = false;

        this.eventsObject = {
            resize: this.onWindowResize.bind(this),
            keyup: this.onKeyUp.bind(this),
        };

        let self = this;
        this.loadComplete = function () {
            self.init();
        };

        if (this.lpc.status.loaded)
            this.init();
        else
            this.lpc.addEventListener("loadComplete", this.loadComplete);
    };
    Object.assign(SAC.prototype, THREE.EventDispatcher.prototype);

    //创建
    SAC.prototype.createCutObj = function () {
        this.unSelectCutObj();
        this.brushReady();
    };
    SAC.prototype.brushReady = function () {
        this.getAllPoints();
        this.$canvas.show();
        this.lpc.clearSelect();
        this.lpc.controls.enabled = false;

        this.isBrush = true;
    };
    //取消选中
    SAC.prototype.unSelectCutObj = function () {
        this.nowCutObj = null;
        this.currentPoints = [];
    };

    //获取所有点的3D 2D位置
    SAC.prototype.getAllPoints = function () {
        let points = this.lpc.scene1.getObjectByName("points");

        let l = points.geometry.attributes.position.count;
        //let ps=points.geometry.attributes.position.array;
        let c = this.lpc.camera;
        let p = {width: this.width, height: this.height};

        let points2d = [];
        let points3d = this.points3d;

        let frustum = new THREE.Frustum();
        frustum.setFromMatrix( new THREE.Matrix4().multiplyMatrices( c.projectionMatrix, c.matrixWorldInverse ) );

        for (let i = 0; i < l; i++) {
            let v3 = points3d[i];
            if(frustum.containsPoint(v3)){
                let v2=this.toScreenPosition(v3, c, p);
                v2.i=i;
                points2d.push(v2);
            }
        }

        //this.regionObj.points3d=points3d;
        this.regionObj.points2d = points2d;
        //this.regionObj.colors=colors;
    };

    SAC.prototype.initDefaultPoints = function () {
        let points = this.lpc.scene1.getObjectByName("points");
        let position = points.geometry.getAttribute("position");
        //let intensity=points.geometry.getAttribute("intensity");
        let l = position.count;
        let ps = position.array;

        let points3d = [];

        for (let i = 0; i < l; i++) {
            points3d.push(new THREE.Vector3(ps[i * 3], ps[i * 3 + 1], ps[i * 3 + 2]));
        }

        this.points3d = points3d;
    }

    SAC.prototype.init=function () {
        var self = this;
        this.loaded=true;

        self.ctx.fillStyle = self.activeColor;
        self.ctx.strokeStyle = self.activeColor;

        this.initDefaultPoints();

        self.listenToBrushFun(self.canvas);//监听用户的动作
    };
    SAC.prototype.listenToBrushFun=function (canvas) {

        var self = this;
        this.painting = false; //是否开始'绘画'
        var lastPoint = {x: undefined, y: undefined};

        var tmp = { //'画笔'的临时点
            id: 0, points: []
        };
        canvas.onmousedown = function (e) {
            //1.
            self.painting = true;
            var xy = self.getXYFun(e);
            lastPoint = {"x": xy.x, "y": xy.y};
            self.ctx.save();
            self.drawCircle(xy.x, xy.y, 0);
            //2.
            tmp = {id: 0, points: []};
            tmp.points.push(lastPoint);
        };
        canvas.onmousemove = function (e) {
            if (self.painting) {
                var xy = self.getXYFun(e);
                var newPoint = {"x": xy.x, "y": xy.y};

                self.ctx.clearRect(0,0,canvas.width,canvas.height);
                self.drawRect(lastPoint.x,lastPoint.y,newPoint.x-lastPoint.x,newPoint.y-lastPoint.y);
                self.rect=[lastPoint,{x:newPoint.x,y:lastPoint.y},newPoint,{x:lastPoint.x,y:newPoint.y}]


            }
        };
        canvas.onmouseup = function () {
            self.painting = false;
            if (tmp.points.length > 0 && self.isBrush === true) {
                //2.点变色-处理圈中的点'加|减'
                self.discolorationFun(tmp);
                self.cancel();
                tmp = null;
                //3.清除'画笔'画的圈圈
                self.ctx.clearRect(0, 0, canvas.width, canvas.height);
                self.cr=0;
            }
        };
        canvas.mouseleave = function () {
            self.painting = false;
        };


        window.addEventListener("resize", this.eventsObject.resize);
        window.addEventListener("keyup",this.eventsObject.keyup);

    };

    SAC.prototype.discolorationFun = function (tmp) {
        //tmp 代表'画笔点组'
        //chPoints 代表'画笔点组'产生的'凸包点'
        //pixPoints 代表所有的'像素点'
        //selectedPoints 代表需要变色的点
        var self = this;
        var chPoints=tmp.points;
        if (chPoints != undefined) {
            //1.判断'凸包点'内是否有像素点
            var pixPoints = self.regionObj.points2d;//所有的'像素点'
            var isHavePixelsPoints = false; //是否有像素点
            var selectedPoints = [];
            var pixPointsLen = pixPoints.length;
            for (var i = 0; i < pixPointsLen; i++) {
                var isInside = self.pointInPolygon(pixPoints[i], chPoints);
                if (isInside) {//有像素点
                    isHavePixelsPoints = true;
                    //pixPoints[i].i = i;
                    selectedPoints.push(pixPoints[i]);
                    //selectedPoints.push(i);
                }
            }

            console.log(selectedPoints);

            this.getRectInPoints(selectedPoints);
        }
    };

    //功能：判断一个点是否在一个多边形或圆之内，判断完成后判断通过地面模式是否返回()
    SAC.prototype.pointInPolygon = function (point, pointGroup) {
        var self = this;
        var x = point.x, y = point.y;
        var inside = false;


        if(this.IsPointInMatrix(this.rect[0],this.rect[1],this.rect[2],this.rect[3],point)){
            inside=true;
        }else{
            inside=false;
        }


        inside=this.pointInGround(inside,point);

        return inside;

    }
    //判断地面模式
    SAC.prototype.pointInGround=function(inside,point){
        if(inside){
            var p3=this.points3d;

            let p=p3[point.i];
            if(p.z>=(-this.lpc.groundOffset)){
                inside=true;
            }else{
                inside=false;
            }
        }

        return inside
    };

    SAC.prototype.drawCircle = function (x, y, radius) {
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
    SAC.prototype.drawRect=function(x,y,w,h){
        this.ctx.save();
        this.ctx.beginPath();
        this.ctx.rect(x,y,w,h);
        this.ctx.stroke();
    };

    //取消
    SAC.prototype.cancel = function () {
        this.brushType = '';
        this.isBrush = false;
        this.$canvas.hide();
        this.lpc.controls.enabled = true;

        this.currentPoints = [];

        this.painting = false;

        this.ctx.clearRect(0, 0, this.canvas.width, this.canvas.height);

    };

    //3d世界坐标系转2D坐标系
    SAC.prototype.toScreenPosition = function (pos, camera, plane) {
        let position = pos.clone();
        let screenCoord = {};
        position.project(camera);
        screenCoord.x = (0.5 + position.x / 2) * plane.width;
        screenCoord.y = (0.5 - position.y / 2) * plane.height;
        return screenCoord;
    };

    SAC.prototype.getXYFun = function (e) {
        var self = this;
        var _d = {'x': 0, 'y': 0};
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
        _d = {'x': x, 'y': y};
        return _d;
    };

    SAC.prototype.onWindowResize = function () {
        this.width = this.lpc.width;
        this.height = this.lpc.height;

        this.$canvas.attr("width", this.width).attr("height", this.height);

        this.ctx.fillStyle = this.activeColor;
        this.ctx.strokeStyle = this.activeColor;

    };

    SAC.prototype.onKeyUp = function(event) {
        event.preventDefault();
        event.stopPropagation();

        switch ( event.keyCode ) {
            case 27:{//Esc
                this.cancel();
                break;
            }
        }
    };


    SAC.prototype.getRectInPoints=function(selectPoints){
        let l=selectPoints.length;
        if(l<=1) return;

        if(l>30){
            this.doPoints2(selectPoints);
        }else{
            this.doPoints1(selectPoints);
        }
    };

    SAC.prototype.doPoints1=function(selectPoints){
        let p3=this.points3d;
        let l=selectPoints.length;

        let maxX=-Infinity;
        let minX=Infinity;
        let maxY=-Infinity;
        let minY=Infinity;
        let maxZ=-Infinity;
        let minZ=Infinity;

        let v3s=[];

        for(let i=0;i<l;i++){

            let v3=p3[selectPoints[i].i];

            maxX=v3.x>maxX?v3.x:maxX;
            maxY=v3.y>maxY?v3.y:maxY;
            maxZ=v3.z>maxZ?v3.z:maxZ;

            minX=v3.x<minX?v3.x:minX;
            minY=v3.y<minY?v3.y:minY;
            minZ=v3.z<minZ?v3.z:minZ;

            v3s.push(v3);
        }

        let obb1 = findOBB(v3s);

        let center=new THREE.Vector3(minX+(maxX-minX)/2,minY+(maxY-minY)/2,minZ+(maxZ-minZ)/2);
        let v3l=v3s.length;
        for(let i=0;i<v3l;i++){
            let v=v3s[i].clone().sub(center);
            let v2=center.clone().sub(v);
            v3s.push(v2);
        }

        let obb = findOBB(v3s);
        let rect={
            "3Dcenter":{
                x:minX+(maxX-minX)/2,
                y:minY+(maxY-minY)/2,
                z:minZ+(maxZ-minZ)/2
            },
            "3Dsize":{
                width:obb1.size[1][1]-obb1.size[1][0],
                height:obb1.size[0][1]-obb1.size[0][0],
                deep:maxZ-minZ,
                alpha:Math.atan2(obb.axes[0][1],obb.axes[0][0])
            }
        };

        this.lpc.rectDone(rect);
    };
    SAC.prototype.doPoints2=function(selectPoints){
        let p3=this.points3d;
        let l=selectPoints.length;

        let maxX=-Infinity;
        let minX=Infinity;
        let maxY=-Infinity;
        let minY=Infinity;
        let maxZ=-Infinity;
        let minZ=Infinity;

        let v3s=[];

        for(let i=0;i<l;i++){

            let v3=p3[selectPoints[i].i];

            maxX=v3.x>maxX?v3.x:maxX;
            maxY=v3.y>maxY?v3.y:maxY;
            maxZ=v3.z>maxZ?v3.z:maxZ;

            minX=v3.x<minX?v3.x:minX;
            minY=v3.y<minY?v3.y:minY;
            minZ=v3.z<minZ?v3.z:minZ;

            v3s.push(v3);
        }

        let obb = findOBB2(v3s);

        let rect={
            "3Dcenter":{
                x:obb.center[0],
                y:obb.center[1],
                z:minZ+(maxZ-minZ)/2
            },
            "3Dsize":{
                width:Math.abs(obb.size.maxX-obb.size.minX),
                height:Math.abs(obb.size.maxY-obb.size.minY),
                deep:maxZ-minZ,
                alpha:obb.axes
            }
        };

        this.lpc.rectDone(rect);
    };

    SAC.prototype.destroy=function () {

        this.lpc.removeEventListener("loadComplete", this.loadComplete);

        this.canvas.ontouchstart = null; //移除事件
        this.canvas.ontouchmove = null;
        this.canvas.ontouchend = null;

        this.canvas.onmousedown = null; //移除事件
        this.canvas.onmousemove = null;
        this.canvas.onmouseup = null;
        this.canvas.mouseleave = null;

        window.removeEventListener("resize", this.eventsObject.resize);
        window.removeEventListener("keyup",this.eventsObject.keyup);

        this.canvas = null;			//当前'画笔'画布
        this.ctx = null;			//当前'画笔'2d画布的上下文

        this.activeColor = null; 	//当前'画笔'的颜色
        this.isClear = null; 		//橡皮擦'模式'开启 isClear = true
        this.isBrush = null; 		//画笔'模式'开启   isBrush = true
        this.points3d=null;

        this.$canvas.remove();
        this.$canvas=null;

        this.regionObj = null;
        this.lpc = null;
        this.dataUrl = null;

        this.eventsObject=null;
        this.loadComplete=null;

        for(var a in this){
            delete this[a];
        }
    };

    function findOBB(dataPoints) {
        // copy the [x,y] array
        // input: dataPoints is an Object3D (group)

        var xyArray = [];
        for (var i = 0; i < dataPoints.length; i++) {
            xyArray.push([dataPoints[i].x, dataPoints[i].y]);
        }

        // find mean
        var xbar = 0,
            ybar = 0;
        for (var i = 0; i < xyArray.length; i++) {
            xbar += xyArray[i][0];
            ybar += xyArray[i][1];
        }
        xbar /= xyArray.length;
        ybar /= xyArray.length;

        // adjust data
        for (var i = 0; i < xyArray.length; i++) {
            xyArray[i][0] -= xbar;
            xyArray[i][1] -= ybar;
        }

        // covariance matrix
        var xx = 0,xy = 0,yy = 0;
        for (var i = 0; i < xyArray.length; i++) {
            xx += xyArray[i][0] * xyArray[i][0];
            xy += xyArray[i][0] * xyArray[i][1];
            yy += xyArray[i][1] * xyArray[i][1];
        }

        // solve eigenvectors
        var cM = [
            [xx, xy],
            [xy, yy]
        ];
        var ev = numeric.eig(cM);

        // pick PC1 as +x
        var PC1 = [ev.E.x[0][0], ev.E.x[1][0]];
        console.log(PC1);

        // rotate 90 CCW as +y}
        var PC2 = [-PC1[1], PC1[0]];

        // change basis
        for (var i = 0; i < xyArray.length; i++) {
            var xp = dot(xyArray[i], PC1);
            var yp = dot(xyArray[i], PC2);
            xyArray[i][0] = xp;
            xyArray[i][1] = yp;
        }

        // find xy extreme values
        var xMin, xMax, yMin, yMax;
        xMin = yMin = 1e10;
        xMax = yMax = -1e10;

        for (var i = 0; i < xyArray.length; i++) {
            if (xyArray[i][0] < xMin) xMin = xyArray[i][0];
            if (xyArray[i][0] > xMax) xMax = xyArray[i][0];
            if (xyArray[i][1] < yMin) yMin = xyArray[i][1];
            if (xyArray[i][1] > yMax) yMax = xyArray[i][1];
        }

        // get 4 corners
        return {
            center: [xbar, ybar],
            axes: [PC1, PC2],
            size: [
                [xMin, xMax],
                [yMin, yMax]
            ]
        };
    }

    function findOBB2(dataPoints){
        var xyArray = [];
        let xa=[];
        let ya=[];
        let xl=0;
        let yl=0;
        for (let i = 0; i < dataPoints.length; i++) {
            //xyArray.push([dataPoints[i].x, dataPoints[i].y]);
            xa.push(dataPoints[i].x);
            ya.push(dataPoints[i].y);

            xl+=dataPoints[i].x;
            yl+=dataPoints[i].y;
        }
        let xm=xl/dataPoints.length;
        let ym=yl/dataPoints.length;

        for (let i = 0; i < dataPoints.length; i++) {
            xa[i]-=xm;
            ya[i]-=ym;
        }

        let theta=[];
        let rots=[];
        let rots_data=[];
        for(let i=0;i<=180;i++){
            let t=i/180*Math.PI;
            //theta.push(t);
            let cost=Math.cos(t);
            let sint=Math.sin(t);

            let ta = [cost,sint,-sint,cost];
            ta.t=t;
            rots.push(ta);
        }

        let dx=[];
        let d=[];
        //let dy=[];
        for(let i=0;i<rots.length;i++){
            let r = rots[i];
            let drx = [];
            let dr = [];
            //let dry = [];
            drx.t = r.t;
            dr.t = r.t;
            //dry.t = r.t;

            for(let j=0;j<dataPoints.length;j++){
                let x=xa[j];
                let y=ya[j];
                let rx=x*r[0]+y*r[1];
                let ry=x*r[2]+y*r[3];
                drx.push(rx);
                dr.push({x:rx,y:ry});
            }
            dx.push(drx);
            d.push(dr);
            //dy.push(dry);
        }

        for(let i=0;i<dx.length;i++){
            let drx=dx[i];
            let dr=d[i];

            let maxDrx = Math.max.apply(null,drx);
            let minDrx = Math.min.apply(null,drx);

            let dxBins=[];
            for(let i=minDrx;i<maxDrx;i+=0.1){
                let s=i;
                let e=(i+0.1>maxDrx)?maxDrx:(i+0.1);
                dxBins.push({
                    start:s,
                    end:e,
                    count:0
                });
            }

            for(let j=0;j<dr.length;j++){
                let x=drx[j];
                for(let m=0;m<dxBins.length;m++){
                    let b = dxBins[m];
                    if(x>=b.start&&x<=b.end){
                        b.count++;
                        break;
                    }
                }
            }

            dxBins.sort(function (a,b) {
                return b.count-a.count;
            });

            dr.maxCount=dxBins[0].count;
        }

        d.sort(function (a,b) {
            return b.maxCount-a.maxCount;
        });

        let rd = d[0];

        let maxX=-Infinity;
        let minX=Infinity;
        let maxY=-Infinity;
        let minY=Infinity;

        for(let i=0;i<rd.length;i++){
            let v3=rd[i];
            maxX=v3.x>maxX?v3.x:maxX;
            maxY=v3.y>maxY?v3.y:maxY;

            minX=v3.x<minX?v3.x:minX;
            minY=v3.y<minY?v3.y:minY;
        }

        /*        let ct = Math.cos(-rd.t);
                let st = Math.sin(-rd.t);

                let maxRX = (maxX*ct+maxY*st)+xm;
                let maxRY = (-maxX*st+maxY*ct)+ym;

                let minRX = (minX*ct+minY*st)+xm;
                let minRY = (-minX*st+minY*ct)+ym;*/

        let maxSX = maxX+xm;
        let maxSY = maxY+ym;

        let minSX = minX+xm;
        let minSY = minY+ym;

        let ct = Math.cos(-rd.t);
        let st = Math.sin(-rd.t);

        let maxRX = (maxX*ct+maxY*st)+xm;
        let maxRY = (-maxX*st+maxY*ct)+ym;

        let minRX = (minX*ct+minY*st)+xm;
        let minRY = (-minX*st+minY*ct)+ym;

        return {
            center: [(maxRX+minRX)/2, (maxRY+minRY)/2],
            axes: rd.t+Math.PI/2,
            size: {
                minX:minSX,
                minY:minSY,
                maxX:maxSX,
                maxY:maxSY
            }
        };
    }



    SAC.prototype.GetCross=function(p1, p2, p) {
        return (p2.x - p1.x) * (p.y - p1.y) - (p.x - p1.x) * (p2.y - p1.y);
    };
    //判断点p是否在p1p2p3p4的正方形内
    SAC.prototype.IsPointInMatrix = function(p1, p2, p3, p4, p) {
        let isPointIn = this.GetCross(p1, p2, p) * this.GetCross(p3, p4, p) >= 0 && this.GetCross(p2, p3, p) * this.GetCross(p4, p1, p) >= 0;
        return isPointIn;
    };


    function dot(v1, v2) {
        return v1[0] * v2[0] + v1[1] * v2[1];
    }

    return SAC;

})(window);

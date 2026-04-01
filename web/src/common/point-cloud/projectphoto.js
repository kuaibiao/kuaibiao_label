// 此js依赖于
// three.js 104
// PCDLoader.js
// OrbitControls.js
// DragControls.js
// TransformControls.js
// jquery.js 3.4.1
// panzoom.js  --todo https://github.com/anvaka/panzoom

// ========================================================================================================
// element:网页元素
// imgUrl:照片路径
// pcdUrl:pcd文件路径，可以没有
// imgWidth:照片宽度
// imgHeight:照片高度
// focusLength:照片焦距
// mat4Array:相机四维矩阵
// dataUrl:数据参数
// version
import * as THREE from "../../libs/three.js-r105/build/three.module";
import panzoom from './panzoom.min';

export let PhotoProject =(function f(w) {
    let PhotoProject = function (optional) {

        let self = this;

        this.element = optional.element;
        this.imgUrl = optional.imgUrl;
        this.pcdUrl = optional.pcdUrl;
        this.imgWidth = optional.imgWidth;
        this.imgHeight = optional.imgHeight;
        this.fl = optional.focusLength;
        this.dataUrl = optional.dataUrl;
        this.index = optional.index;
        this.params=optional.params;

        this.translation=optional.translation;

        if(typeof optional.enabled=="boolean")
            this.enabled=optional.enabled;
        else
            this.enabled = true;

        if(typeof optional.isRectShow=="boolean")
            this.isRectShow=optional.isRectShow;
        else
            this.isRectShow = true;

        this.modes = optional.modes ? optional.modes : PhotoProject.modes.box;
        this.box_type = optional.box_type ? optional.box_type : "box";


        this.jd = 2 * Math.atan(this.imgHeight / 2 / this.fl) * 180 / Math.PI;

        let nm = new THREE.Matrix4();
        nm.set(
            1, 0, 0, 0,
            0, -1, 0, 0,
            0, 0, -1, 0,
            0, 0, 0, 1
        );

        this.mat4 = new THREE.Matrix4();
        //this.mat4.elements=optional.mat4Array;
        if(!this.translation){
            this.mat4.set.apply(this.mat4, optional.mat4Array);
        }else{
            let t=this.translation;
            let p=new THREE.Vector3(t.position.x,t.position.y,t.position.z);
            let q=new THREE.Quaternion(t.quaternion.x,t.quaternion.y,t.quaternion.z,t.quaternion.w);
            let s=new THREE.Quaternion(t.scale.x,t.scale.y,t.scale.z);
            this.mat4.compose(p,q,s);
        }

        this.mat4.multiply(nm);

        if(this.params&&!optional.mat4Array){
            this.pm4=new THREE.Matrix4()
/*            this.pm4.set.apply(this.pm4, [
                2*optional.params.fx/this.imgWidth,0,0,0,
                0,-2*optional.params.fy/this.imgHeight,0,0,
                1-2*optional.params.cx/this.imgWidth,2*optional.params.cy/this.imgHeight-1,(0.1+optional.params.fx)/(0.1-optional.params.fx),-1.0,
                0,0,2*optional.params.fx*0.1/(0.1-optional.params.fx),0
            ]);*/
            this.pm4.elements=[
                2*optional.params.fx/this.imgWidth,0,0,0,
                0,2*optional.params.fy/this.imgHeight,0,0,
                1-2*optional.params.cx/this.imgWidth,2*optional.params.cy/this.imgHeight-1,(0.1+1000000)/(0.1-1000000),-1.0,
                0,0,2*1000000*0.1/(0.1-1000000),0
            ];
        }




        this.meshMaterial = new THREE.MeshBasicMaterial({
            color: 0xffff00,
            opacity: 0.2,
            transparent: true,
            side: THREE.FrontSide,
            flatShading: true
        });

        this.tl = new THREE.TextureLoader();
        let pl = new THREE.PCDLoader();

        this.width = $(this.element).width();
        this.height = $(this.element).height();

        this.offset = $(this.element).offset();

        this.background = new THREE.Texture();
        this.renderer;
        this.camera;
        this.scene;
        this.rects;

        this.frustum;

        this.findMeshs = [];

        this.panzoom;

        this.mouse = new THREE.Vector2(0, 0);
        this.raycaster = new THREE.Raycaster();

        this.isRectDomDraging=false;
        this.rectDomStatus={
            x:0,y:0,l:0,t:0
        };

        this.init();
        this.animate()

    }

    Object.assign( PhotoProject.prototype, THREE.EventDispatcher.prototype );

    PhotoProject.prototype.init = function () {
        this.initDom();
        this.initPanZoom();
        this.initBasic();
        this.initTranslateControl();
        this.initDragControls();
        this.initEvents();

        if (this.dataUrl) {
            if (typeof this.dataUrl === "string")
                $.get(this.dataUrl).then(this.loadData.bind(this));
            else
                this.loadData(this.dataUrl);
        }

        if(!this.enabled)
            this.pause();
    };

    PhotoProject.prototype.load = function(img,data){
        let self = this;

        //this.clearAllRectDom();

        if(this.translateControl){
            this.translateControl.detach();
            this.scene.remove(this.translateControl);
            this.translateControl.dispose();
            this.initTranslateControl();
        }


        this.clearRects();

        if (typeof img === "string") {
            this.imgUrl = img;
        } else if (img.image) {
            this.background = img;
            this.imgUrl = img.image.src;
        } else {
            this.background = new THREE.TextureLoader().load(img.basicSrc);
            this.imgUrl = img.basicSrc;
        }

        this.dataUrl = data;


        if (this.dataUrl) {
            if (typeof this.dataUrl === "string")
                $.get(this.dataUrl).then(this.loadData.bind(this));
            else
                this.loadData(this.dataUrl);
        }

        //$(self.element).find(".photo-project-plane").get(0).style.backgroundImage = "url(" + this.imgUrl + ")";
        if(img.image){
            img.image.style="position: absolute;width: 100%;height:100%;left:0;top:0;";
            $(this.element).find(".photo-project-background").empty().get(0).appendChild(img.image)
        }else{
            $(this.element).find(".photo-project-background").html("<img style='position: absolute;width: 100%;height:100%;left:0;top:0;' src='"+(this.imgUrl.basicSrc?this.imgUrl.basicSrc:this.imgUrl)+"'>");
            //$(this.element).find(".photo-project-background").get(0).src=this.imgUrl.basicSrc?this.imgUrl.basicSrc:this.imgUrl;
        }
    };
    PhotoProject.prototype.showSceneBackground = function(){
        if(this.background)
            this.scene.background = this.background;
    };
    PhotoProject.prototype.hideSceneBackground=function(){
        this.scene.background = null;
        this.background = null;
    };

//创建相关HTML元素
    PhotoProject.prototype.initDom = function () {
        let self = this;

        $(this.element).append(
            /*        "<div class='photo-project-line-1' style='z-index: 1;'></div>"+
                    "<div class='photo-project-line-2' style='z-index: 1;'></div>"+*/
            "<div class='photo-project-plane'>" +
                "<div class='photo-project-background' style='position: absolute;width: 100%;height:100%;left:0;top:0;'></div>" +
                "<div class='photo-project-stage'></div>" +
                "<div class='photo-project-drag' ></div>" +
            "</div>"
        );

        this.resizeDom();

        this.offset = $(this.element).find(".photo-project-plane").offset();

        if(this.imgUrl.image){
            this.imgUrl.image.style="position: absolute;width: 100%;height:100%;left:0;top:0;";
            $(this.element).find(".photo-project-background").get(0).appendChild(this.imgUrl.image)
        }else{
            //$(this.element).find(".photo-project-background").get(0).src=this.imgUrl.basicSrc?this.imgUrl.basicSrc:this.imgUrl;
            $(this.element).find(".photo-project-background").html("<img style='position: absolute;width: 100%;height:100%;left:0;top:0;' src='"+(this.imgUrl.basicSrc?this.imgUrl.basicSrc:this.imgUrl)+"'>");
        }
    };
//resize DOM元素
    PhotoProject.prototype.resizeDom = function () {

        //let scale=this.panzoom?this.panzoom.getTransform().scale:1;

        let w = $(this.element).width();
        let h = $(this.element).height();
        if (this.imgWidth >= this.imgHeight) {
            this.height = this.imgHeight / this.imgWidth * w;
            this.width = w;
        } else {
            this.height = h;
            this.width = h / (this.imgHeight / this.imgWidth);
        }
        $(this.element).find(".photo-project-plane").css({
            width: this.width,
            height: this.height,
            marginLeft: -this.width / 2,
            marginTop: -this.height / 2
        });

        if(this.panzoom){
            this.panzoom.zoomAbs(this.width/2,this.height/2,1);
            this.panzoom.moveTo(0,0);
        }

    };
//创建拖动缩放对象
    PhotoProject.prototype.initPanZoom = function () {
        let self = this;

        this.panzoom = panzoom($(this.element).find(".photo-project-plane").get(0),
            {
                bounds: true,
                smoothScroll: false,
                zoomDoubleClickSpeed: 1,
                minZoom:0.8,
                /*autocenter:true,*/
            });
        this.panzoom.on("transform", function () {
            //$("#drag-test").css("transform",$(self.element).find(".photo-project-plane").css("transform"))
        });
        this.panzoom.on('zoom', function (e) {
            let s = e.getTransform().scale;
            //self.renderer.setSize(self.width*s,self.height*s);


            self.offset = $(self.element).find(".photo-project-plane").offset();

            //$("#drag-test").css("transform",$(self.element).find(".photo-project-plane").css("transform"))
            //self.dragControls.enabled=true;
        });
        this.panzoom.on('panend', function (e) {
            self.offset = $(self.element).find(".photo-project-plane").offset();

            //$("#drag-test").css("transform",$(self.element).find(".photo-project-plane").css("transform"))
            //self.dragControls.enabled=true;
        });

    }

//创建渲染器，场景和摄像机 并写入摄像机矩阵
    PhotoProject.prototype.initBasic = function () {
        this.renderer = new THREE.WebGLRenderer({antialias: true, alpha: true});
        this.renderer.setPixelRatio(window.devicePixelRatio);
        this.renderer.setSize(this.width, this.height);
        $(this.element).find(".photo-project-stage").get(0).appendChild(this.renderer.domElement);

        this.scene = new THREE.Scene();
        this.camera = new THREE.PerspectiveCamera(this.jd, this.imgWidth / this.imgHeight, 0.000001, this.fl);
        this.frustum = new THREE.Frustum();

        this.rects = new THREE.Group();
        this.scene.add(this.rects);
        //this.camera.up.set(0,0,1);

        let p = new THREE.Vector3();
        let s = new THREE.Vector3();
        let q = new THREE.Quaternion();
       // let r = new THREE.Euler(0,0,0,'YXZ');

        //if(!this.translation){
            this.mat4.decompose(p, q, s);
            this.camera.quaternion.copy(q);
/*        }
        else{
            p.copy(this.translation.position);
            q.copy(this.translation.quaternion);
            r.setFromQuaternion(q);
            //q.set(this.translation.quaternion.x,this.translation.quaternion.y,this.translation.quaternion.z,this.translation.quaternion.w);
            s.copy(this.translation.scale);
            this.camera.up.set(0,0,1);
            this.camera.rotation.set(r.x,-r.y,r.z);
        }*/
        this.camera.position.copy(p);
        this.camera.scale.copy(s);
        //this.camera.updateProjectionMatrix();

        if(this.pm4){
            this.camera.projectionMatrix=this.pm4;
        }



/*        if(this.translation){
            let nm = new THREE.Matrix4();
            nm.set(
                -1, 0, 0, 0,
                0, -1, 0, 0,
                0, 0, -1, 0,
                0, 0, 0, -1
            );
            this.camera.matrixWorld.multiply(nm);
            this.camera.updateMatrixWorld();
        }*/


        this.frustum.setFromMatrix(new THREE.Matrix4().multiplyMatrices(this.camera.projectionMatrix, this.camera.matrixWorldInverse));

        this.eventsObject = {
            resize: this.onWindowResize.bind(this),
            //mousemove: this.onMouseMove.bind(this),
            rectDomMouseDown:this.onRectDomMouseDown.bind(this),
            rectDomMouseMove:this.onRectDomMouseMove.bind(this),
            rectDomMouseUp:this.onRectDomMouseUp.bind(this),

            rectDomSizeMouseDown:this.onRectDomSizeMouseDown.bind(this),
            rectDomSizeMouseMove:this.onRectDomSizeMouseMove.bind(this),
            rectDomSizeMouseUp:this.onRectDomSizeMouseUp.bind(this),
        };

        this.findMeshs = [];
        this.nowRect = null;

        this.animateObject = null;
    };
    PhotoProject.prototype.getTransform = function () {
        return {p: this.camera.position.clone(), q: this.camera.quaternion.clone()}
    };
    //创建位移工具
    PhotoProject.prototype.initTranslateControl = function () {
        this.translateControl = new THREE.TransformControls(this.camera, $(this.element).find(".photo-project-drag").get(0));
        //this.translateControl.showY = false;
        this.scene.add(this.translateControl);
        this.translateControl.visible=false;
        this.translateControl.setSize(2);
        this.translateControl.enabled=this.enabled;
        this.translateControl.setMode("scale");
        this.translateControl.isAttach=false;
        let self = this;

        this.translateControl.addEventListener("objectChange",function (event) {
            self.emitPhotoMeshUpdate();
        });

/*        this.translateControl.addEventListener("mouseDown",function () {
            if(!self.enabled) return;
            if (self.panzoom)
                self.panzoom.pause();
        });

        this.translateControl.addEventListener("mouseUp",function () {
            if(!self.enabled) return;

            if (self.panzoom)
                self.panzoom.resume();
        });
        this.translateControl.addEventListener("dragging-changed",function () {
            if(!self.enabled) return;

            if (self.panzoom)
                self.panzoom.resume();
        });*/
        //this.setSize()
    };
    //创建拖动工具
    PhotoProject.prototype.initDragControls = function () {
        let self = this;

        this.dragControls = new THREE.DragControls(this.findMeshs, this.camera, $(this.element).find(".photo-project-drag").get(0));
        this.dragControls.addEventListener("dragstart", function (res) {
            if(!self.enabled||self.translateControl.isAttach) return;

            self.nowRect = res.object;
            self.updateNowRectColor();
            if (self.panzoom)
                self.panzoom.pause();
        });
        this.dragControls.addEventListener("dragend", function (res) {
            if(!self.enabled||self.translateControl.isAttach) return;

            if (self.panzoom)
                self.panzoom.resume();

            self.emitPhotoMeshUpdate();
        });
    };

    //大小
    PhotoProject.prototype.toggleTC=function(isAttach){
        if(!this.translateControl||!this.nowRect||!this.enabled) return;

        if(typeof isAttach == "boolean")
            this.translateControl.isAttach=isAttach;
        else
            this.translateControl.isAttach=!this.translateControl.isAttach;

        if(this.translateControl.isAttach){
            this.translateControl.attach(this.nowRect);
            this.translateControl.enabled=this.enabled;
            if(this.panzoom)
                this.panzoom.pause();
            if(this.dragControls)
                this.dragControls.enabled=false;
        }else{
            this.translateControl.detach();
            this.translateControl.enabled=this.enabled;
            if(this.panzoom)
                this.panzoom.resume();
            if(this.dragControls)
                this.dragControls.enabled=true;
        }
    }
//绑定事件
    PhotoProject.prototype.initEvents = function () {
        this.disposeEvents();
        window.addEventListener("resize", this.eventsObject.resize);
        //this.element.addEventListener("mousemove", this.eventsObject.mousemove)
    };
//解绑事件
    PhotoProject.prototype.disposeEvents = function () {
        window.removeEventListener("resize", this.eventsObject.resize);
        //this.element.removeEventListener("mousemove", this.eventsObject.mousemove, false);
    };
//渲染
    PhotoProject.prototype.animate = function () {
        let self = this;

        window.requestAnimationFrame(function step(timestamp) {
            self.animateObject = window.requestAnimationFrame(step);

            self.renderer.render(self.scene, self.camera)
        });
    };
//选中或创建Rect
    PhotoProject.prototype.createRect = function (rect) {

        let tm = this.rects.getObjectByName("select-" + rect.index);
        if (tm) {
            this.nowRect = tm.children[0];
            this.updateNowRectColor();
            this.selectRectDom(this.nowRect);
            if(this.translateControl.isAttach)
                this.translateControl.attach(tm.children[0]);
            /*if(this.isRectShow)
                this.translateControl.attach(tm.children[0]);*/
        } else {
            this.createARealRect(rect);
        }
    };
    //选中RectDom
    PhotoProject.prototype.selectRectDom=function(m){
        $(this.element).find(".photo-project-rect-select").removeClass("photo-project-rect-select");
        if(!m) return;
        if(m.userData.$dom){
            m.userData.$dom.addClass("photo-project-rect-select");
        }


    }
//更新Rect
    PhotoProject.prototype.updateRect = function (rect) {
        let s = rect["3Dsize"];
        let p = rect["3Dcenter"];

        let mo = this.rects.getObjectByName("select-" + rect.index);
        let m = mo.children[0];

        if (m.position.length() > 0 && (rect.updateType === 0 || rect.updateType === 2)) return;
        else if (m.position.length() > 0 && rect.updateType === 1) {
            //mo.rotation.z=s.alpha;
            m.rotation.z = s.alpha;
            mo.userData.nowData["3Dsize"].alpha=s.alpha;
        } else {
            switch (this.modes) {
                case PhotoProject.modes.box:
                default: {
                    this.updateBox(m, s)
                    break;
                }
                case PhotoProject.modes.plane: {
                    this.updatePlane(m, s, p);
                    break;
                }
            }

            mo.userData.nowData = $.extend(true,{},rect);
            //m.geometry=new THREE.BoxGeometry(s.height,s.width,s.deep);
            mo.position.set(p.x, p.y, p.z);
            //mo.rotation.z=s.alpha;
            m.rotation.z = s.alpha;
        }
    };
    PhotoProject.prototype.updateBox = function (m, s) {
        m.geometry = new THREE.BoxGeometry(s.height, s.width, s.deep);
        m.children[0].geometry= new THREE.EdgesGeometry( m.geometry );
    };
    PhotoProject.prototype.updatePlane = function (m, s) {
        /*let pp = new THREE.Vector3(p.x,p.y,p.z).project(this.camera);
        pp.x=(0.5 + pp.x / 2);*/

        /*if(pp.x>=0.5){*/
        var p1 = new THREE.Vector3(+s.height / 2, +s.width / 2, +s.deep / 2);
        var p2 = new THREE.Vector3(+s.height / 2, +s.width / 2, -s.deep / 2);
        var p3 = new THREE.Vector3(-s.height / 2, -s.width / 2, +s.deep / 2);
        var p4 = new THREE.Vector3(-s.height / 2, -s.width / 2, -s.deep / 2);
        /* }else {
             var p1=new THREE.Vector3(+s.height/2,-s.width/2,+s.deep/2);
             var p2=new THREE.Vector3(+s.height/2,-s.width/2,-s.deep/2);
             var p3=new THREE.Vector3(-s.height/2,+s.width/2,+s.deep/2);
             var p4=new THREE.Vector3(-s.height/2,+s.width/2,-s.deep/2);
         }*/

        let vs = new Float32Array([
            p1.x, p1.y, p1.z,
            p2.x, p2.y, p2.z,
            p3.x, p3.y, p3.z,

            p3.x, p3.y, p3.z,
            p4.x, p4.y, p4.z,
            p2.x, p2.y, p2.z,
        ]);
        let bg = new THREE.BufferGeometry();
        bg.addAttribute("position", new THREE.BufferAttribute(vs, 3));

        m.geometry = bg;
        m.children[0].geometry= new THREE.EdgesGeometry( m.geometry );
    };

//创建一个rect
    PhotoProject.prototype.createARealRect = function (rect,isReLook) {
        //let rect

        let s = rect["3Dsize"];
        let p = rect["3Dcenter"];

        let im;
        if(rect["imageMap"]){
            im=rect["imageMap"][this.index];
        }
        if(im){
            s=im["3Dsize"]||s;
            p=im["3Dcenter"]||p;
        }

        let mo = new THREE.Group();
        mo.userData.type = "rect";
        mo.userData.index = rect.index;

        let m;

        //let m = new THREE.Mesh(new THREE.BoxGeometry(s.height,s.width,s.deep),this.meshMaterial.clone());
        switch (this.modes) {
            case PhotoProject.modes.box:
            default: {
                m = this.createABox(s);
                break;
            }
            case PhotoProject.modes.plane: {
                m = this.createAPlane(s, p);
                break;
            }
        }
        m.name = "m-" + rect.index;
        m.visible=this.isRectShow;


        if (rect.attr) {
            if (rect.attr.color)
                m.material.color = new THREE.Color(rect.attr.color);
        }

        mo.position.set(p.x, p.y, p.z);
        //mo.rotation.z=s.alpha;
        m.rotation.z = s.alpha;

        if(im) {
            let ss=im["3Dscale"];
            let p2=im["3Dcenter2"];
            m.scale.set(ss.y,ss.x,ss.z);
            m.position.set(p2.x,p2.y,p2.z);
        }

        mo.userData.index = rect.index;
        mo.userData.nowData = $.extend(true,{},rect);
        mo.name = "select-" + rect.index;
        mo.add(m);

        this.findMeshs.push(m);

        this.rects.add(mo);

        this.nowRect = m;
        this.updateNowRectColor();

        m.add(new THREE.LineSegments( new THREE.EdgesGeometry( m.geometry ), new THREE.LineBasicMaterial( { color:0xff0000} ) ))

        if (rect["cubeMap"] && isReLook) {
            let bbox = rect["cubeMap"][this.index] && rect["cubeMap"][this.index].bbox;
            bbox&&this.createRectDom(m, bbox);
           /* if(bbox)
                this.createRectDom(m, bbox);*/
        }

        if(this.translateControl.isAttach)
            this.translateControl.attach(m);

/*        if(this.isRectShow)
            this.translateControl.attach(m);*/
    };
    //创建或重置矩形映射
    PhotoProject.prototype.createRectDom=function(m,bbox){
        let d=$(this.element).find(".photo-project-rect-"+m.parent.userData.nowData.index);
        if(!d.length){
            $(this.element).find(".photo-project-drag").append("<div data-index='"+m.parent.userData.nowData.index+"' class='photo-project-rect photo-project-rect-"+m.parent.userData.nowData.index+"'><div data-index='"+m.parent.userData.nowData.index+"' class='photo-project-rect-size'></div></div>");
            d=$(this.element).find(".photo-project-rect-"+m.parent.userData.nowData.index);
            d.on("mousedown",this.eventsObject.rectDomMouseDown);
            d.find(".photo-project-rect-size").on("mousedown",this.eventsObject.rectDomSizeMouseDown);

            this.selectRectDom(m);
        }
        m.userData.$dom=d;
        let $ppd=$(this.element).find(".photo-project-drag");

        if(bbox){
            d.css({"left":bbox.l,"top":bbox.t,"width":bbox.w,"height":bbox.h});
        }else{
            let rd=this.getRectData(m,{width:this.width,height:this.height});
            let ps2dx=[];
            let ps2dy=[];

            if(rd){
                rd.boxRectData.map(function (p2) {
                    ps2dx.push(p2.x);
                    ps2dy.push(p2.y);
                });

                ps2dx.sort(function (a,b) {
                    return a-b;
                });
                ps2dy.sort(function (a,b) {
                    return a-b;
                });

                let left=ps2dx[0];
                let top=ps2dy[0];
                let width=ps2dx[ps2dx.length-1]-ps2dx[0];
                let height=ps2dy[ps2dy.length-1]-ps2dy[0];

                d.css({top:top/$ppd.height()*100+"%",left:left/$ppd.width()*100+"%",width:width/$ppd.width()*100+"%",height:height/$ppd.height()*100+"%"})
            }else{
                d.off("mousedown").remove();
            }
        }




    };

    PhotoProject.prototype.createAllRectDom=function(resetAll){
        let self=this;
        this.findMeshs.map(function (m) {
            if(!resetAll){
                let d=$(self.element).find(".photo-project-rect-"+m.parent.userData.nowData.index);
                if(!d.length)
                    self.createRectDom(m);
            }else
                self.createRectDom(m);

        });
        $(".photo-project-rect-select").removeClass("photo-project-rect-select");
    };

    //重读数据时清空所有4点边框
    PhotoProject.prototype.clearAllRectDom=function(){
        $(this.element).find(".photo-project-rect").unbind().remove();
    };


    //返回所有矩形映射数据
    PhotoProject.prototype.getAllRectDomData=function(){
        let rd=[];
        let self=this;
        $(this.element).find(".photo-project-rect").each(function () {
            let d={
                l:this.style.left,
                t:this.style.top,
                w:this.style.width,
                h:this.style.height,
                rl:self.imgWidth*(+(this.style.left.split("%")[0]))/100,
                rt:self.imgHeight*(+(this.style.top.split("%")[0]))/100,
                rw:self.imgWidth*(+(this.style.width.split("%")[0]))/100,
                rh:self.imgHeight*(+(this.style.height.split("%")[0]))/100,
                index:$(this).data("index"),
                isBbox:true
            }
            rd.push(d);
        });

        return rd;
    };
    PhotoProject.prototype.getRectDomDataByIndex=function(index){
        let rdd=$(this.element).find(".photo-project-rect-"+index).get(0);

        return rdd && {
            l:rdd.style.left,
            t:rdd.style.top,
            w:rdd.style.width,
            h:rdd.style.height,
            rl:this.imgWidth*(+(rdd.style.left.split("%")[0]))/100,
            rt:this.imgHeight*(+(rdd.style.top.split("%")[0]))/100,
            rw:this.imgWidth*(+(rdd.style.width.split("%")[0]))/100,
            rh:this.imgHeight*(+(rdd.style.height.split("%")[0]))/100,
            index:index,
            isBbox:true
        }
    };

    PhotoProject.prototype.createABox = function (s) {
        let m = new THREE.Mesh(new THREE.BoxGeometry(s.height, s.width, s.deep), this.meshMaterial.clone());
        return m;
    };
    PhotoProject.prototype.createAPlane = function (s, p) {

        let pp = new THREE.Vector3(p.x, p.y, p.z).project(this.camera);
        pp.x = (0.5 + pp.x / 2);
        /*if(pp.x>=0.5){*/
        var p1 = new THREE.Vector3(+s.height / 2, +s.width / 2, +s.deep / 2);
        var p2 = new THREE.Vector3(+s.height / 2, +s.width / 2, -s.deep / 2);
        var p3 = new THREE.Vector3(-s.height / 2, -s.width / 2, +s.deep / 2);
        var p4 = new THREE.Vector3(-s.height / 2, -s.width / 2, -s.deep / 2);
        /*}else {
            var p1=new THREE.Vector3(+s.height/2,-s.width/2,+s.deep/2);
            var p2=new THREE.Vector3(+s.height/2,-s.width/2,-s.deep/2);
            var p3=new THREE.Vector3(-s.height/2,+s.width/2,+s.deep/2);
            var p4=new THREE.Vector3(-s.height/2,+s.width/2,-s.deep/2);
        }*/

        let vs = new Float32Array([
            p1.x, p1.y, p1.z,
            p2.x, p2.y, p2.z,
            p3.x, p3.y, p3.z,

            p3.x, p3.y, p3.z,
            p4.x, p4.y, p4.z,
            p2.x, p2.y, p2.z,
        ]);
        let bg = new THREE.BufferGeometry();
        bg.addAttribute("position", new THREE.BufferAttribute(vs, 3));
        let m = new THREE.Mesh(bg, this.meshMaterial.clone());
        m.material.side = THREE.DoubleSide;

        //m.lookAt(this.camera);

        return m;
    };

    //获取数据
    PhotoProject.prototype.getAllData=function(){
        let rds=[]
        for(let i=0;i<this.findMeshs.length;i++){
            rds.push(this.getData(this.findMeshs[i]));
        }
        return rds;
    };
    PhotoProject.prototype.getDataByIndex=function(index){
        let tm=this.scene.getObjectByName("select-"+index);
        return this.getData(tm.children[0]);
    }
    PhotoProject.prototype.getDataByNow=function(){
        if (!this.nowRect) return;
        return this.getData(this.nowRect)
    };
    PhotoProject.prototype.getData=function(m){
        let p=m.parent.position;
        let s=m.geometry.parameters;
        let rd=Object.assign({"3Dscale":m.scale,"3Dcenter2":m.position},m.parent.userData.nowData);
        rd["3Dcenter"]={x:p.x,y:p.y,z:p.z};
        rd["3Dsize"]={width:s.height,height:s.width,deep:s.depth,alpha: m.rotation.z};
        rd["3Dscale"]={x:m.scale.y,y:m.scale.x,z:m.scale.z};

        if(m.userData.bbox)
            rd.bbox=m.userData.bbox;

        return JSON.parse(JSON.stringify(rd));
    };

    //获取最终数据
    PhotoProject.prototype.getRealDataByNow=function(){
        if (!this.nowRect) return;
        return this.getRealData(this.nowRect)
    }
    PhotoProject.prototype.getRealData=function(m){
        let rrd={};

        let rd=this.getData(m);
        let rdp=rd["3Dcenter"];
        let rdp2=rd["3Dcenter2"];
        let p=new THREE.Vector3();
        p.set(rdp.x,rdp.y,rdp.z);
        p.add(rdp2);

        let s=rd["3Dsize"];
        let ss=rd["3Dscale"];

        rrd["3Dcenter"]=p;
        rrd["3Dsize"]={width:s.width*ss.x,height:s.height*ss.y,deep:s.deep*ss.z,alpha:s.alpha};
        rrd.index=rd.index;
        return rrd;
    }

    PhotoProject.prototype.updateNowRectColor=function(){
        if(!this.nowRect) return;

        this.findMeshs.map(function (m) {
            m.material.opacity=0.2;
        });

        this.nowRect.material.opacity=0.35;
    }

//删除当前立方体
    PhotoProject.prototype.deleteRectByNow = function () {
        if (!this.nowRect) return;

        let tm = this.nowRect.parent;
        this.deleteRectToFindMeshs(tm);
        this.deleteRect(tm);
        this.rects.remove(tm);
    }
//删除立方体用index
    PhotoProject.prototype.deleteRectByIndex = function (index) {
        let tm = this.rects.getObjectByName("select-" + index);
        this.deleteRectToFindMeshs(tm);
        this.deleteRect(tm);
        this.rects.remove(tm);
    };
//删除所有立方体
    PhotoProject.prototype.deleteRectAllRect = function () {
        let self = this;
        if(this.translateControl)
            this.translateControl.detach();

        this.rects.children.map(function (c) {
            if (c.userData.type == "rect") {
                self.deleteRect(c);
            }
        });
        while (this.rects.children.length) {
            this.rects.remove(this.rects.children[0]);
        }
        while (this.findMeshs.length){
            this.findMeshs.shift();
        }

    };
//删除立方体
    PhotoProject.prototype.deleteRect = function (tm) {
        let m = tm.children[0];

        this.deleteRectDom(m);

        if(this.nowRect){
            if (m.uuid == this.nowRect.uuid)
                this.nowRect = null;
        }


        m.geometry.dispose();
        m.material.dispose();

        tm.remove(m);
    };
    //删除立方体相关dom元素
    PhotoProject.prototype.deleteRectDom=function(m){
        if(!m.userData.$dom) return;

        m.userData.$dom.off("mousedown").find(".photo-project-rect-size").off("mousedown");
        m.userData.$dom.remove();
        m.userData.$dom=null;

        this.emitPhotoMeshUpdate();
    };
    //删除当前立方体dom元素
    PhotoProject.prototype.deleteRectDomByNow=function(){
        //if(!this.nowRect) return;

        let index= $(this.element).find(".photo-project-rect-select").data("index");
        if(index){
            let m=this.scene.getObjectByName("select-"+index).children[0];
            this.deleteRectDom(m);
        }
    };
    //删除所有立方体dom元素
    PhotoProject.prototype.deleteAllRectDom=function(){
        for (let i = 0; i < this.findMeshs.length; i++) {
            this.deleteRectDom(this.findMeshs[i]);
        }
    }

//删除数组中的立方体对象
    PhotoProject.prototype.deleteRectToFindMeshs = function (tm) {

        let m = tm.children[0];
        //let tmpR = this.findMeshs[0];

        for (let i = 0; i < this.findMeshs.length; i++) {
            if (this.findMeshs[i].uuid == m.uuid) {
/*                this.findMeshs[0] = this.findMeshs[i];
                this.findMeshs[i] = tmpR;*/
                this.findMeshs.splice(i,1);
                break;
            }
        }
        //this.findMeshs.shift();
    };

//根据index找到rect然后放大或缩小
    PhotoProject.prototype.addRectScaleByIndex = function (index, step) {
        if(!this.enabled) return;

        let tm = this.rects.getObjectByName("select-" + index);
        if (tm) {
            tm.children[0].scale.add(new THREE.Vector3(step, step, step));
        }

    };
//当前rect的放大或缩小
    PhotoProject.prototype.addRectScaleByNow = function (step) {
        if(!this.enabled) return;
        if (!this.nowRect) return;
        this.nowRect.scale.add(new THREE.Vector3(step, step, step));
    };

//通过各种方式获取8个点信息
//通过index获取8个点信息
    PhotoProject.prototype.getRectDataByIndex = function (index) {
        let tm = this.rects.getObjectByName("select-" + index);
        if (tm) {
            return this.getRectData(tm.children[0]);
        }
    };
//获取当前Rect的8个点信息
    PhotoProject.prototype.getRectDataByNow = function () {
        if (!this.nowRect) return;
        return this.getRectData(this.nowRect);
    };
//获取所有Rect的8个点信息
    PhotoProject.prototype.getAllRectData = function () {
        let rpss = [];
        let self = this;
        this.findMeshs.map(function (m) {
            let rps = self.getRectData(m);
            if (rps)
                rpss.push(rps);
        });

        return rpss;
    };
//获取8个点信息
    PhotoProject.prototype.getRectData = function (m,plane) {
        let s = m.parent.userData.nowData["3Dsize"];
        //let ss = m.scale.x;

        let p1 = new THREE.Vector3(+s.height / 2, +s.width / 2, +s.deep / 2);
        let p2 = new THREE.Vector3(-s.height / 2, +s.width / 2, +s.deep / 2);
        let p3 = new THREE.Vector3(-s.height / 2, -s.width / 2, +s.deep / 2);
        let p4 = new THREE.Vector3(+s.height / 2, -s.width / 2, +s.deep / 2);
        let p5 = new THREE.Vector3(+s.height / 2, +s.width / 2, -s.deep / 2);
        let p6 = new THREE.Vector3(-s.height / 2, +s.width / 2, -s.deep / 2);
        let p7 = new THREE.Vector3(-s.height / 2, -s.width / 2, -s.deep / 2);
        let p8 = new THREE.Vector3(+s.height / 2, -s.width / 2, -s.deep / 2);
        let ps=[];

        switch (this.modes) {
            case PhotoProject.modes.box:
            default:{
                ps = [p1, p2, p3, p4, p5, p6, p7, p8];
                break;
            }
            case PhotoProject.modes.plane:{
                ps=[p1,p5,p3,p7];
                break;
            }
        }


        let rps = [];
        let needReturn = false;

        let frustum = new THREE.Frustum();
        frustum.setFromMatrix(new THREE.Matrix4().multiplyMatrices(this.camera.projectionMatrix, this.camera.matrixWorldInverse));

        for (let i = 0; i < ps.length; i++) {
            m.localToWorld(ps[i]);

/*            if(i==7){
                let mt=new THREE.Mesh(new THREE.BoxGeometry(0.5,0.5,0.5),new THREE.MeshBasicMaterial({color:0x0000ff}));
                mt.position.copy(ps[i]);
                this.scene.add(mt);
            }*/


            rps.push(this.toScreenPosition(ps[i], this.camera, plane?plane:{width: this.imgWidth, height: this.imgHeight}));

            if (frustum.containsPoint(ps[i])) {
                needReturn = true;
            }


        }
        rps = needReturn ? rps : undefined;


        return rps ? {data: this.getData(m), boxRectData: rps} : undefined;
    };
//3d世界坐标系转2D坐标系
    PhotoProject.prototype.toScreenPosition = function (pos, camera, plane) {
        let position = pos.clone();
        let screenCoord = {};
        position.project(camera);
        screenCoord.x = (0.5 + position.x / 2) * plane.width;
        screenCoord.y = (0.5 - position.y / 2) * plane.height;
        return screenCoord;
    };

//通过index重置Rect
    PhotoProject.prototype.resetRectByIndex = function (index) {
        let mo = this.rects.getObjectByName("select-" + index);
        if (mo) {
            this.resetRect(mo);
        }
    };
//重置当前Rect
    PhotoProject.prototype.resetRectByNow = function () {
        if (!this.nowRect) return;
        this.resetRect(this.nowRect.parent);
    };
//重置所有Rect
    PhotoProject.prototype.resetAllRect = function () {
        for (let i = 0; i < this.findMeshs.length; i++) {
            this.resetRect(this.findMeshs[i].parent);
        }
    };
//重置rect
    PhotoProject.prototype.resetRect = function (mo) {
        let rect = mo.userData.nowData;
        let s = rect["3Dsize"];
        let p = rect["3Dcenter"];

        let m = mo.children[0];
        /*m.geometry=new THREE.BoxGeometry(s.height,s.width,s.deep);*/
        switch (this.modes) {
            case PhotoProject.modes.box:
            default: {
                this.updateBox(m, s);
                break;
            }
            case PhotoProject.modes.plane: {
                this.updatePlane(m, s);
                break;
            }
        }
        m.position.set(0, 0, 0);
        m.scale.set(1,1,1);
        mo.position.set(p.x, p.y, p.z);
        //mo.rotation.z=s.alpha;
        m.rotation.z = s.alpha;
    };

//写入信息
    PhotoProject.prototype.setMessage = function (msg) {
        let mo = this.rects.getObjectByName("select-" + msg.index);
        if (mo) {
            mo.userData.nowData = $.extend(true,mo.userData.nowData,msg);
            if (msg.attr) {
                if (msg.attr.color)
                    mo.children[0].material.color = new THREE.Color(msg.attr.color);
            }
        }
    }

//resize事件
    PhotoProject.prototype.onWindowResize = function () {
        this.resizeDom();
        this.renderer.setSize(this.width, this.height);
    };
    //空的mousemove事件
    PhotoProject.prototype.onMouseMove = function (event) {
    }
    //RectDom的mouse事件用来处理RectDom的拖动
    PhotoProject.prototype.onRectDomMouseDown=function(event){
        if(!this.enabled) return;

        let $dom=$(event.target);
        if(!$dom.hasClass("photo-project-rect")) {
            return;
        }

        if(this.panzoom)
            this.panzoom.pause();


        event.preventDefault();
        event.stopPropagation();



        $dom.parent().on("mousemove",this.eventsObject.rectDomMouseMove)
            .on("mouseup",this.eventsObject.rectDomMouseUp);

        this.isRectDomDraging=true;

        //let s=this.panzoom?this.panzoom.getTransform().scale:1;
        this.rectDomStatus={
            x:event.clientX,
            y:event.clientY,
            l:event.target.offsetLeft,
            t:event.target.offsetTop
        }

        let index=$dom.data("index");
        this.nowRect=this.scene.getObjectByName("select-"+index).children[0];
        this.updateNowRectColor();
        this.selectRectDom(this.nowRect);

    };
    PhotoProject.prototype.onRectDomMouseMove=function(event){
        if(!this.isRectDomDraging)
            return;

        //let tf=this.panzoom?this.panzoom.getTransform():{x:0,y:0,scale:1};

        let rds=this.rectDomStatus;
        var nx = event.clientX;
        var ny = event.clientY;
        //计算移动后的左偏移量和顶部的偏移量
        var nl = (nx - (rds.x - rds.l));
        var nt = (ny - (rds.y - rds.t));

        let $ppd=$(this.element).find(".photo-project-drag");

        let $dom=this.nowRect.userData.$dom;
        $dom.css({left:nl/$ppd.width()*100+"%",top:nt/$ppd.height()*100+"%"});
    };
    PhotoProject.prototype.onRectDomMouseUp=function(event){
        event.preventDefault();
        //event.stopPropagation();
        let $dom=this.nowRect.userData.$dom;
        $dom.parent().off("mousemove").off("mouseup");

        this.isRectDomDraging=false;

        if(this.panzoom)
            this.panzoom.resume();

        this.emitPhotoMeshUpdate();
    };

    PhotoProject.prototype.onRectDomSizeMouseDown=function(event){
        if(!this.enabled) return;

        let $dom=$(event.target);
        if(!$dom.hasClass("photo-project-rect-size")) {
            return;
        }

        if(this.panzoom)
            this.panzoom.pause();


        event.preventDefault();
        event.stopPropagation();

        $dom.parent().parent().on("mousemove",this.eventsObject.rectDomSizeMouseMove)
            .on("mouseup",this.eventsObject.rectDomSizeMouseUp);

        this.rectDomStatus={
            x:event.clientX,
            y:event.clientY,
            w:$dom.parent().width(),
            h:$dom.parent().height()
        }

        this.isRectDomDraging=true;

        let index=$dom.data("index");
        this.nowRect=this.scene.getObjectByName("select-"+index).children[0];
        this.selectRectDom(this.nowRect);
    };
    PhotoProject.prototype.onRectDomSizeMouseMove=function(event){
        if(!this.isRectDomDraging)
            return;

        let rds=this.rectDomStatus;

        var w = event.clientX - rds.x + rds.w;
        var h = event.clientY - rds.y + rds.h;

        let $ppd=$(this.element).find(".photo-project-drag");

        let $dom=this.nowRect.userData.$dom;
        $dom.css({width:w/$ppd.width()*100+"%",height:h/$ppd.height()*100+"%"});
    };
    PhotoProject.prototype.onRectDomSizeMouseUp=function(event){
        event.preventDefault();
        //event.stopPropagation();
        let $dom=this.nowRect.userData.$dom;
        $dom.parent().off("mousemove").off("mouseup");

        this.isRectDomDraging=false;

        if(this.panzoom)
            this.panzoom.resume();

        this.emitPhotoMeshUpdate();
    };



    //清除所有rect
    PhotoProject.prototype.clearRects = function(){
        this.nowRect = null;

        this.rects.traverse(function (c) {
            if (c.geometry) {
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (this.rects.children.length)
            this.rects.remove(this.rects.children[0]);

        this.findMeshs = [];
    };

//清除和销毁当前场景
    PhotoProject.prototype.clearScene = function () {
        this.nowRect = null;

        this.scene.traverse(function (c) {
            if (c.geometry) {
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (this.scene.children.length)
            this.scene.remove(this.scene.children[0]);

        this.findMeshs = [];
    };
    PhotoProject.prototype.clearRects = function(){
        this.nowRect = null;

        this.rects.traverse(function (c) {
            if (c.geometry) {
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (this.rects.children.length)
            this.rects.remove(this.rects.children[0]);

        this.findMeshs = [];
    };
//结束播放，清除当前画布
    PhotoProject.prototype.playEnd = function () {
        if (this.animateObject) {
            cancelAnimationFrame(this.animateObject);
            this.renderer.setClearColor(0x000000);
            this.renderer.clear();
            this.renderer.dispose();
        }
    };
//销毁当前对象
    PhotoProject.prototype.dispose = function () {
        if (this.panzoom)
            this.panzoom.dispose();

        this.disposeEvents();
        this.clearScene();
        this.playEnd();

        $(this.element).find(".photo-project-plane").empty().remove();

        if(this.dragControls){
            this.dragControls.dispose();
        }

        if(this.translateControl){
            this.translateControl.dispose();
        }

        this._listeners={};

        //$(this.element).find(".photo-project-plane");
    };
    //读取数据
    PhotoProject.prototype.loadData = function (res) {
        this.clearRects();
        for (let i = 0; i < res.length; i++) {
            this.createARealRect(res[i],true);
        }

        if(this.dragControls){
            this.dragControls.dispose();
        }
        this.initDragControls();
    };
    PhotoProject.prototype.pause=function(){
        this.toggleTC(false);

        this.panzoom.zoomAbs(this.width,this.height,1);
        this.panzoom.moveTo(0,0);
        this.panzoom.pause();

        if(this.dragControls)
            this.dragControls.enabled=false;
        this.translateControl.enabled=false;

        this.enabled=false;
    };
    PhotoProject.prototype.play=function(){
        this.panzoom.resume();

        if(this.dragControls)
            this.dragControls.enabled=true;
        this.translateControl.enabled=true;

        this.enabled=true;
    };

    PhotoProject.prototype.moveFrontByNow=function (speed) {
        if (!this.nowRect || !this.enabled) return;

        this.nowRect.position.add(new THREE.Vector3(speed,0,0).applyEuler(this.nowRect.rotation));
        this.emitPhotoMeshUpdate();
    };
    PhotoProject.prototype.moveLeftByNow=function (speed) {
        if (!this.nowRect || !this.enabled) return;

        this.nowRect.position.add(new THREE.Vector3(0,speed,0).applyEuler(this.nowRect.rotation));
        this.emitPhotoMeshUpdate();
    };
    PhotoProject.prototype.moveUpByNow=function (speed) {
        if (!this.nowRect || !this.enabled) return;

        this.nowRect.position.add(new THREE.Vector3(0,0,speed).applyEuler(this.nowRect.rotation));
        this.emitPhotoMeshUpdate();
    };

    PhotoProject.prototype.emitPhotoMeshUpdate=function(){
        if(!this.nowRect) return;
        let id = this.nowRect.name.split('m-')[1];
        this.dispatchEvent({type:"photoUpdateMesh",message:id});
    };

    PhotoProject.modes = {box: 0, plane: 1};

    return PhotoProject;
})(window);

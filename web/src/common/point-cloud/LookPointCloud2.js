// 此js依赖于
// three.js 104
// PCDLoader.js
// OrbitControls.js
// DragControls.js
// TransformControls.js
// jquery.js 3.4.1

// 绑定自定义事件
// selectMesh:鼠标点击方块选中返回的事件
// createBoxComplete:一个方框创建好时返回的事件

// version
import * as THREE from "../../libs/three.js-r105/build/three.module";
import {OrbitControls} from "../../libs/three.js-r105/examples/jsm/controls/OrbitControls.js";
import {PCDLoader} from "../../libs/three.js-r105/examples/jsm/loaders/PCDLoader.js";
import {DragControls} from "../../libs/three.js-r105/examples/jsm/controls/DragControls.js";
import {TransformControls} from "../../libs/three.js-r105/examples/jsm/controls/TransformControls.js";

THREE.DragControls = DragControls;
THREE.PCDLoader = PCDLoader;
THREE.TransformControls = TransformControls;
THREE.OrbitControls = OrbitControls;

export const PointCloud = (function f(w) {

    let /*camera, */controls, /*scene1,scene2, */renderer;

    let translateControl;

    let nowM;

    let rectGroup,rectGroup2;
    let threeCamera;
    let threeSize={
        t:[0,0],
        l:[0,0],
        f:[0,0]
    }

    let dragControlsTop,dragControlsLeft,dragControlsFront;
    let topEditorPoints,leftEditorPoints,frontEditorPoints=[];


    let raycaster;
    let mouse = new THREE.Vector2(0,0);
    let mouseTR;
    let findMeshs=[];

    let lineMaterial = new THREE.LineBasicMaterial( { color: 0x00ff00, transparent: true, opacity: 1.0 } );
    let meshMaterial = new THREE.MeshBasicMaterial( { color: 0xffff00,opacity:0.2,transparent:true, side: THREE.FrontSide, flatShading: true } );

    let createRect={
        fPoint:null,
        sPoint:null,
        tPoint:null,
        width:0,
        height:0,
        deep:0
    };
    let tmpIndex=0;
    let touchPlaneX;
    let touchPlaneZ;
    //let touchPoint;
    let tmpBox=new THREE.Mesh(new THREE.BoxGeometry(0,0,0),new THREE.MeshBasicMaterial( { color: 0x00ff00,opacity:0.5,side: THREE.DoubleSide, flatShading: true/*transparent:true*/}));
    tmpBox.visible=false;
    let tmpBoxHelper=new THREE.BoxHelper(tmpBox,0x00ff00);
    tmpBoxHelper.name="tmp-box";
    let tpg=new THREE.Geometry();
    tpg.vertices.push(new THREE.Vector3());
    let touchPoint=new THREE.Points(tpg,new THREE.PointsMaterial( {size:6, color: 0xff0000,sizeAttenuation:false } ));
    touchPoint.visible=false;

    let modes={default:1,car:2,car2:3};
    let occlusionMode={all:1,inside:2,outside:3};

    //tmpBox.renderOrder=1000;

    //==================================================================================================================
    //element:网页元素
    //pcdUrl:pcd文件URL
    //dataUrl:json数据URL
    //isTCEditorOpen:是否打开编辑功能
    //carDeep:默认立方体高度，只有mod为car1时有效
    //groundOffset:地面偏移量
    //alphaInherit:是否继承上一个立方体的旋转角度
    //labelRangeRaidus:是否绘制一个用来标识范围的圆以及这个圆的半径
    function LookPointCloud(optional) {
        this.element=optional.element;
        this.pcdUrl=optional.pcdUrl;
        this.dataUrl=optional.dataUrl;
        this.isTCEditorOpen=optional.isTCEditorOpen;

        this.carDeep=optional.carDeep?optional.carDeep:2.4;

        this.groundOffset=optional.groundOffset||optional.groundOffset==0?optional.groundOffset:2.2;
        this.groundColor=optional.groundColor?optional.groundColor:[0,0.39411764705882354,0.8686274509803921];

        this.labelRangeRaidus=optional.labelRangeRaidus?optional.labelRangeRaidus:0;

        this.alphaInherit=optional.alphaInherit?optional.alphaInherit:false;
        this.lastAlpha=0;

        this.showOneEnabled=false;

        this.domStage=null;
        this.$doPlaneTop=null;
        this.$doPlaneLeft=null;
        this.$doPlaneFront=null;

        this.planeTopDrag=null;
        this.planeLeftDrag=null;
        this.planeFrontDrag=null;

        this.isPlaneTopRotate=true;

        //背景颜色
        this.background = new THREE.Color("#000000");
        //遮挡模式
        this.occlusionMode = occlusionMode.all;
        this.occlusionSize = -1;

        //scene1中点云大小
        this.pointSize = 0.1;

        this.width=$(this.element).width();
        this.height=$(this.element).height();

        this.offset=$(this.element).offset();

        if(typeof this.pcdUrl === "string" ){
            let sa=this.pcdUrl.split("/");
            this.name=sa[sa.length-1];
        }else
            this.name=this.pcdUrl.name;

        this.loader=null;

        this.scene1=null;
        this.scene2=null;
        this.camera=null;

        this.status={
            main:0,
            mode:optional.mode?optional.mode:modes.default,
            loaded:false,

            tc:{
                t:{min:0.6,max:2.0,val:1.0},
                l:{min:0.6,max:2.0,val:1.0},
                f:{min:0.6,max:2.0,val:1.0}
            }
        };

        this.basicMessage={};
        this.worldRadius=0;

        this.eventsObject={
            resize:this.onWindowResize.bind(this),
            threeClick:this.threeClick.bind(this),
            keyup:onKeyUp.bind(this),

            topPlaneMouseDown:onPlaneTopMouseDown.bind(this),
            topPlaneMouseMove:onPlaneTopMouseMove.bind(this),
            topPlaneMouseUp:onPlaneTopMouseUp.bind(this),
            topPlaneMouseWheel:onPlaneTopMouseWheel.bind(this),

            leftPlaneMouseDown:onPlaneLeftMouseDown.bind(this),
            leftPlaneMouseWheel:onPlaneLeftMouseWheel.bind(this),
            frontPlaneMouseDown:onPlaneFrontMouseDown.bind(this),
            frontPlaneMouseWheel:onPlaneFrontMouseWheel.bind(this)
        };
        this.animateObject=null;

        switch (this.status.mode) {
            default:
            case modes.default: {
                this.eventsObject.mousedown=onMouseDown.bind(this);
                this.eventsObject.mousemove=onMouseMove.bind(this);
                this.eventsObject.mouseup=onMouseUp.bind(this);
                break;
            }
            case modes.car:{
                this.eventsObject.mousedown=onCarMouseDown.bind(this);
                this.eventsObject.mousemove=onCarMouseMove.bind(this);
                this.eventsObject.mouseup=onCarMouseUp.bind(this);
                break;
            }
            case modes.car2:{
                this.eventsObject.mousedown=onCar2MouseDown.bind(this);
                this.eventsObject.mousemove=onCar2MouseMove.bind(this);
                this.eventsObject.mouseup=onCar2MouseUp.bind(this);
                break;
            }
        }

        this.init();
        this.animate();

        //this.camera=camera;
        this.threeSize=threeSize;

        this.controls=controls;

        this.touchPoint=touchPoint;
    }
    Object.assign( LookPointCloud.prototype, THREE.EventDispatcher.prototype );

    //写入基础信息
    LookPointCloud.prototype.setBasicMessage=function(obj){
        this.basicMessage=obj;

        findMeshs.map(function (m) {
            for(let a in obj){
                if(a!="3Dsize"||a!="3Dcenter"||a!="cBy")
                    m.userData.nowData[a]=obj[a];
            }
        })
    };
    //写入额外数据
    LookPointCloud.prototype.setMessageByIndex=function(obj,index){
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].userData.index==index){
                for(var a in obj){
                    if(a!="3Dsize"||a!="3Dcenter"){
                        this.isMessageHasColor(obj,a,findMeshs[i]);
                        findMeshs[i].userData.nowData[a]=obj[a];
                    }
                }
                this.setPointsColorByMeshInThreeCamera(findMeshs[i]);
                this.dispatchEvent({type:"setMessage",message:findMeshs[i].userData.nowData});
                return;
            }
        }
    };
    LookPointCloud.prototype.setMessageByNow=function(obj){
        if(!nowM) return;

        for(var a in obj){
            if(a!="3Dsize"||a!="3Dcenter"){
                this.isMessageHasColor(obj,a,nowM);
                nowM.userData.nowData[a]=obj[a];
            }
        }
        this.setPointsColorByMeshInThreeCamera(nowM);
        this.dispatchEvent({type:"setMessage",message:nowM.userData.nowData});
    };
    //判断是否有color写入
    LookPointCloud.prototype.isMessageHasColor=function(obj,a,m){
        if(a=="attr"){
            if(obj[a]){
                if(obj[a].color){
                    this.setMeshColor(m,obj[a].color)
                }
            }
        }
    };

    //读取新场景
    LookPointCloud.prototype.load=function(pcdUrl,dataUrl,notUpdateTarget){
        this.status.loaded=false;
        this.dispatchEvent({type:"loadStart",message:""});

        this.createBoxEnd();
        this.clearSelect();
        this.clearScene();

        if(translateControl)
            translateControl.detach();

        this.loadPoints(pcdUrl,dataUrl,notUpdateTarget);

        this.initThreeCamera();

        renderer.dispose();
    };
    //销毁场景中三维对象
    LookPointCloud.prototype.clearScene=function(){
        nowM=null;

        findMeshs=[];
        topEditorPoints=[];
        leftEditorPoints=[];
        frontEditorPoints=[];

        this.scene1.traverse(function (c) {
            if(c.geometry){
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (this.scene1.children.length)
            this.scene1.remove(this.scene1.children[0]);

        this.scene2.traverse(function (c) {
            if(c.geometry){
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (this.scene2.children.length)
            this.scene2.remove(this.scene2.children[0]);

    };
    //结束播放，清除当前画布
    LookPointCloud.prototype.playEnd=function(){

        if(this.animateObject){
            cancelAnimationFrame(this.animateObject);
            renderer.setViewport(0,0,self.width,self.height);
            renderer.setScissor(0,0,self.width,self.height);
            renderer.setScissorTest( true );
            renderer.setClearColor(0x000000);
            renderer.clear();
            renderer.dispose();
        }

    }
    //销毁当前操作对象
    LookPointCloud.prototype.clearControls=function(){
        if(this.planeTopDrag){
            this.planeTopDrag.dispose();
            this.planeLeftDrag.dispose();
            this.planeFrontDrag.dispose();
        }
        if(controls)
            controls.dispose();
        if(translateControl)
            translateControl.dispose();

    };
    //销毁当前所有
    LookPointCloud.prototype.dispose=function(){
        this.clearScene();
        // this.clearUI();
        this.clearControls();
        this.playEnd();
        this.disposeBasic();
        this.disposeEvents();

        $(this.element).empty();

        this._listeners={};
    };
    LookPointCloud.prototype.disposeBasic=function(){
        this.scene1=null;
        this.scene2=null;
        this.camera=null;
        renderer=null;
        raycaster=null;
        rectGroup=null;
        rectGroup2=null;
        this.loader=null;

    };

    //仅销毁所有数据
    LookPointCloud.prototype.clearRects=function(){
        findMeshs=[];
        topEditorPoints=[];
        leftEditorPoints=[];
        frontEditorPoints=[];

        rectGroup.traverse(function (c) {
            if(c.geometry){
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (rectGroup.children.length)
            rectGroup.remove(rectGroup.children[0]);

        rectGroup2.traverse(function (c) {
            if(c.geometry){
                c.geometry.dispose();
                c.material.dispose();
            }
        });
        while (rectGroup2.children.length)
            rectGroup2.remove(rectGroup2.children[0]);
    };


    //读取点云文件
    LookPointCloud.prototype.loadPoints=function(pu,du,notUpdateTarget){
        let loader=this.loader;
        let self=this;

        if(typeof pu === "string" ){
            loader.load( pu,
                function(points){
                    if(self.scene1)
                        self.doPoints(points,du,notUpdateTarget);
                },
                function ( xhr ) {
                    // console.log( ( xhr.loaded / xhr.total * 100 ) + '% loaded' );
                    this.dispatchEvent({type:"loadTotal",message:( xhr.loaded / xhr.total * 100 )});
                }.bind(this),
                // called when loading has errors
                function ( error ) {
                    //console.log( 'An error happened' );
                    this.dispatchEvent({type:"loadError",message:error})
                }.bind(this)
            );
        }
        else{
            if(self.scene1)
                this.doPoints(pu,du,notUpdateTarget);
        }


    };
    //读取点云完成后处理
    LookPointCloud.prototype.doPoints=function(points,du,notUpdateTarget){
        let self=this;

        points.name="points";
        this.scene1.add( points );

        points.geometry.computeBoundingSphere();
        points.geometry.computeBoundingBox();
        self.worldRadius=points.geometry.boundingSphere.radius;
        self.worldRect=points.geometry.boundingBox.getSize();

        if(self.occlusionSize<0)
            self.occlusionSize=Math.ceil(self.worldRadius/4);

        if(!notUpdateTarget)
            this.camera.position.set(-self.worldRadius,-self.worldRadius,self.worldRadius);

        //this.setPointsColorByMesh(true);
        //controls.update();

        rectGroup=new THREE.Group();
        this.scene1.add(rectGroup);
        rectGroup2=new THREE.Group();
        this.scene2.add(rectGroup2);

        let p2=points.clone();
        p2.geometry=points.geometry.clone();
        p2.material=points.material.clone();
        p2.material.size=3;
        p2.renderOrder=0;
        //p2.name="points";
        let pg=new THREE.Group();
        pg.name="points-offset";
        pg.add(p2);
        this.scene2.add( pg );


        let center = points.geometry.boundingSphere.center;
        //this.camera.lookAt(center.x, center.y, center.z);
        if(!notUpdateTarget){
            controls.target.set( center.x, center.y, center.z );
            controls.update();
            controls.maxDistance=self.worldRadius*8;
        }




        touchPlaneX=new THREE.Mesh(new THREE.PlaneGeometry(points.geometry.boundingSphere.radius*6,points.geometry.boundingSphere.radius*4),new THREE.MeshBasicMaterial({opacity:0,color:0xff0000,transparent:true,side:THREE.DoubleSide}));
        touchPlaneZ=new THREE.Mesh(new THREE.PlaneGeometry(points.geometry.boundingSphere.radius*6,points.geometry.boundingSphere.radius*4),new THREE.MeshBasicMaterial({opacity:0,transparent:true,side:THREE.DoubleSide}));
        touchPlaneZ.rotation.set(0,Math.PI/2,0);

        //touchPoint.geometry=new THREE.SphereGeometry(points.geometry.boundingSphere.radius/200,16,16);

        this.scene1.add(touchPlaneX);
        this.scene1.add(touchPlaneZ);
        this.scene1.add(touchPoint);

        this.setPointsColor();

        if(!du) {
            this.status.loaded=true;
            this.dispatchEvent({type:"loadComplete",message:""});
            this.dispatchEvent({type:"loadDataComplete",message:[]});
        } else if(typeof du === "string") {
            $.get(du).done(this.loadPointsData).fail(function () {
                self.status.loaded=true;
            })
        } else  {
            this.loadPointsData(du);
        }

        this._initLabelRange();
    }
    LookPointCloud.prototype._initLabelRange=function(){
        let lr=new THREE.Mesh( new THREE.TorusGeometry( Math.round(this.worldRadius/4), 0.1, 2, 100,6.3 ), new THREE.MeshBasicMaterial({ color: 0x2d8cf0 }) );
        lr.renderOrder=4;
        lr.position.set(0,0,0);
        lr.visible=false;
        this.scene1.add(lr);

        this.lr=lr;
    };
    LookPointCloud.prototype.setLRRaidus=function(r){
        if(!this.lr)return;
        if(!(+r)) return;

        this.lr.visible=true;
        this.lr.geometry=new THREE.TorusGeometry( r, 0.1, 2, 100,6.3 );
    };
    LookPointCloud.prototype.showLR=function(){
        if(!this.lr)return;

        this.lr.visible=true;
    };
    LookPointCloud.prototype.hideLR=function(){
        if(!this.lr)return;

        this.lr.visible=false;
    };
    //读取点云相关json数据
    LookPointCloud.prototype.loadPointsData=function(res){
        this.clearSelect();
        this.clearRects();
        this.dispatchEvent({type:"loadComplete",message:res});
        this.status.loaded=true;

        let self=this;
        res.map(createARealBox.bind(this));

        this.dispatchEvent({type:"loadDataComplete",message:res});
    };

    //根据外部传入的摄像机修改点颜色
    /*LookPointCloud.prototype.setPointsColorByCamera=function(camera){
        this.clearSelect();

        let points = this.scene1.getObjectByName("points");

        let rc=this.worldRadius;
        let rz=this.worldRect.z;

        let l = points.geometry.attributes.position.count;
        let ps=points.geometry.attributes.position.array;
        let colors=[];

        //this.scene1.add(new THREE.CameraHelper(camera));

        let frustum = new THREE.Frustum();
        frustum.setFromMatrix( new THREE.Matrix4().multiplyMatrices( camera.projectionMatrix, camera.matrixWorldInverse ) );
        for(let i=0;i<l;i++){
            let tc=this.groundColor;

            let p=new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
            if(p.z>(-this.groundOffset)){
                tc=[(p.z/rz+0.5)*1.8,(1.0-(p.z/rz+0.5))*1.8,0];
            }

            if(frustum.containsPoint(p))
                tc=[1,1,0];


            colors.push(tc[0],tc[1],tc[2])
        }
        points.geometry.addAttribute( 'color', new THREE.Float32BufferAttribute( colors, 3 ) );
        points.material = new THREE.PointsMaterial( { size: 0.1, vertexColors: THREE.VertexColors } );
    };*/

    //LookPointCloud.prototype.getA

    //创建用来拖动大小和位置的8个点
    function createEditorPoints(m3,rect){
        let s=rect["3Dsize"];
        let p=rect["3Dcenter"];

        let p1=new THREE.Vector3(+s.height/2,+s.width/2,+s.deep/2);
        let p2=new THREE.Vector3(-s.height/2,+s.width/2,+s.deep/2);
        let p3=new THREE.Vector3(-s.height/2,-s.width/2,+s.deep/2);
        let p4=new THREE.Vector3(+s.height/2,-s.width/2,+s.deep/2);
        let p5=new THREE.Vector3(+s.height/2,+s.width/2,-s.deep/2);
        let p6=new THREE.Vector3(-s.height/2,+s.width/2,-s.deep/2);
        let p7=new THREE.Vector3(-s.height/2,-s.width/2,-s.deep/2);
        let p8=new THREE.Vector3(+s.height/2,-s.width/2,-s.deep/2);

        createEditorPoint(m3,p1,[1,1,1]);createEditorPoint(m3,p2,[-1,1,1]);
        createEditorPoint(m3,p3,[-1,-1,1]);createEditorPoint(m3,p4,[1,-1,1]);
        createEditorPoint(m3,p5,[1,1,-1]);createEditorPoint(m3,p6,[-1,1,-1]);
        createEditorPoint(m3,p7,[-1,-1,-1]);createEditorPoint(m3,p8,[1,-1,-1]);
    }
    //创建8个点中的某一个
    function createEditorPoint(m3,p,data){
        let s=m3.userData.nowData["3Dsize"];
        let ss=(s.width+s.height+s.deep)/3/8;
        let mt1=new THREE.Mesh(new THREE.BoxGeometry(ss,ss,ss),new THREE.MeshBasicMaterial({color:0x2c8cf0,depthTest:false,transparent:true, side: THREE.DoubleSide, flatShading: true}));
        mt1.userData.data=data;
        mt1.userData.size=ss;
        mt1.position.copy(p);
        mt1.renderOrder=4;
        m3.add(mt1);
    }
    //更新点大小
    function updateEditorPointSize(m3){
        let s=m3.userData.nowData["3Dsize"];
        let ss=(s.width+s.height+s.deep)/3/8;
        m3.children[0].setLength(s.height);

        m3.children.map(function (p) {
            if(p.userData.data){
                p.geometry=new THREE.BoxGeometry(ss,ss,ss);
                p.userData.size=ss;
                p.userData.data[0]=p.position.x<0?-1:1;
                p.userData.data[1]=p.position.y<0?-1:1;
                p.userData.data[2]=p.position.z<0?-1:1;
            }
        })
    }

    //根据立方体修改点颜色
    LookPointCloud.prototype.setPointsColorByMesh=function(isColor){

        let points = this.scene1.getObjectByName("points");
        //let points2= scene2.getObjectByName("points");

        let l = points.geometry.attributes.position.count;
        let ps=points.geometry.attributes.position.array;

        let colors;
        let isFirst=true;

        if(points.geometry.getAttribute("color")){
            colors=points.geometry.getAttribute("color").array;
            isFirst=false
        }else
            colors=[];

        findMeshs.map(function (m) {
            m.userData.nowData.pointLength=0;
        });

        //let m=mesh?mesh:nowM;
        for(let i=0;i<l;i++){
            let tc=[1,1,1];
            findMeshs.map(function (m) {
                if(m.geometry.boundingBox.containsPoint(new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]).applyMatrix4(new THREE.Matrix4().getInverse(m.matrixWorld)))){
                    //tc=[0.8,0,1];
                    m.userData.nowData.pointLength++;
                }

            });
            if(colors.length/3==l){
                colors[i*3]=tc[0];
                colors[i*3+1]=tc[1];
                colors[i*3+2]=tc[2];
            }
            else
                colors.push(tc[0],tc[1],tc[2]);
        }

/*        if(isColor){
            points.geometry.addAttribute( 'color', new THREE.Float32BufferAttribute( colors, 3 ) );
            points.material = new THREE.PointsMaterial( { size: 0.1, vertexColors: THREE.VertexColors } );
        }*/

    }

    //写入基础颜色
    LookPointCloud.prototype.setPointsColor=function(camera){
        let points = this.scene1.getObjectByName("points");
        if(!points) return;

        let frustum;
        if(camera){
            frustum = new THREE.Frustum();
            frustum.setFromMatrix( new THREE.Matrix4().multiplyMatrices( camera.projectionMatrix, camera.matrixWorldInverse ) );
        }



        let rz = this.worldRect.z;

        let l = points.geometry.attributes.position.count;
        let ps = points.geometry.attributes.position.array;
        let colors=[];

        let size=this.occlusionSize;
        let mode=this.occlusionMode;
        let bgColor=this.background;
        let tc=[bgColor.r,bgColor.g,bgColor.b];
        let gc=this.groundColor;

        switch (mode) {
            case occlusionMode.all:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    let tc = this.checkColor(p,frustum,rz,gc);
                    colors.push(tc[0],tc[1],tc[2]);
                }
                break;
            }
            case occlusionMode.inside:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    if(p.length()>size)
                        colors.push(tc[0],tc[1],tc[2]);
                    else{
                        let tc = this.checkColor(p,frustum,rz,gc);
                        colors.push(tc[0],tc[1],tc[2]);
                    }
                }
                break;
            }
            case occlusionMode.outside:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    if(p.length()<=size)
                        colors.push(tc[0],tc[1],tc[2]);
                    else{
                        let tc = this.checkColor(p,frustum,rz,gc);
                        colors.push(tc[0],tc[1],tc[2]);
                    }
                }
                break;
            }
        }

        points.geometry.addAttribute( 'color', new THREE.Float32BufferAttribute( colors, 3 ) );
        points.material = new THREE.PointsMaterial( { size: this.pointSize, vertexColors: THREE.VertexColors } );
    };
    LookPointCloud.prototype.checkColor=function(p,frustum,rz,gc){
        if(frustum){
            let m=1.6;
            if(frustum.containsPoint(p)){
                m=1
            }

            if(p.z>(-this.groundOffset))
                return [(p.z/rz+0.5)*1.8/m,(1.0-(p.z/rz+0.5))*1.8/m,0/m];
            else
                return [gc[0]/m,gc[1]/m,gc[2]/m];
        }else{
            if(p.z>(-this.groundOffset))
                return [(p.z/rz+0.5)*1.8,(1.0-(p.z/rz+0.5))*1.8,0];
            else
                return [gc[0],gc[1],gc[2]];
        }
    };

    //根据mesh修改三视图（scene2）中的点云颜色
    LookPointCloud.prototype.setPointsColorByMeshInThreeCamera=function(mesh){
        let points = this.scene2.getObjectByName("points");

        mesh.geometry.computeBoundingBox();

        let l = points.geometry.attributes.position.count;
        let ps=points.geometry.attributes.position.array;

        let colors;
        if(points.geometry.getAttribute("color"))
            colors=points.geometry.getAttribute("color").array;
        else
            colors=[];

        let c;
        let rect=mesh.userData.nowData;
        if(rect.attr){
            if(rect.attr.color)
                c=new THREE.Color(rect.attr.color);
            else
                c=new THREE.Color(0xffff00);
        }else{
            c=new THREE.Color(0xffff00);
        }

        for(let i=0;i<l;i++){
            let tc=[1,1,1];
            if(mesh.geometry.boundingBox.containsPoint(new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]).applyMatrix4(new THREE.Matrix4().getInverse(mesh.matrixWorld)))){
                tc=[c.r,c.g,c.b];
            }

            if(colors.length/3==l){
                colors[i*3]=tc[0];
                colors[i*3+1]=tc[1];
                colors[i*3+2]=tc[2];
            }
            else
                colors.push(tc[0],tc[1],tc[2]);
        }

        points.geometry.addAttribute( 'color', new THREE.Float32BufferAttribute( colors, 3 ) );
        points.material = new THREE.PointsMaterial( { size: 1.5, vertexColors: THREE.VertexColors } );
    }

    //只显示一个立方体的模式
    LookPointCloud.prototype.showOne=function(){
        this.showOneEnabled=true;
        for(let i=0;i<findMeshs.length;i++){
            let m=findMeshs[i];
            m.visible=false;
            m.userData.helper.visible=false;
        }

        if(nowM){
            nowM.visible=true;
            nowM.userData.helper.visible=true;
            return true;
        }else
            return false;
    };
    LookPointCloud.prototype.unShowOne=function(){
        this.showOneEnabled=false;
        for(let i=0;i<findMeshs.length;i++){
            let m=findMeshs[i];
            m.visible=true;
            //m.userData.helper.visible=true;
        }

        if(nowM){
            nowM.visible=true;
            nowM.userData.helper.visible=true;
        }
    };

    //写入groundOffset并重新渲染
    LookPointCloud.prototype.setGroundOffset=function(offset=2.2){
        this.groundOffset=offset;
        //this.setPointsColor();
        this.setPointsColor();
    };




    LookPointCloud.prototype.init=function(){
        this.initDom();
        this.initBasic();
        this.loadPoints(this.pcdUrl,this.dataUrl);
        this.initThreeCamera();

        this.initEvents();
    };
    LookPointCloud.prototype.initDom=function(){
        $(this.element).empty();
        $(this.element).html("" +
            "<div class='look-point-cloud-stage' style='position: relative;height: 100%;width: 100%;left:0;top:0'></div>" +
            "<div class='look-point-cloud-do-plane-top look-point-cloud-do-plane' style='position: absolute;display: none'>" +
                "<div class='look-point-cloud-rotation-add1' style='position: absolute;left:0;bottom:0;color:#fff;cursor: pointer;background: deepskyblue;height: 18px;line-height: 18px;width: 18px;text-align: center'>&lt;</div>"+
                "<div class='look-point-cloud-rotation-sub1' style='position: absolute;right:0;bottom:0;color:#fff;cursor: pointer;background: deepskyblue;height: 18px;line-height: 18px;width: 18px;text-align: center;'>&gt;</div>"+
            "</div>"+
            "<div class='look-point-cloud-do-plane-left look-point-cloud-do-plane' style='position: absolute;display: none'>" +
            "</div>"+
            "<div class='look-point-cloud-do-plane-front look-point-cloud-do-plane' style='position: absolute;display: none'>" +
            "</div>"+
            "");

        this.domStage=$(this.element).find(".look-point-cloud-stage").get(0);

        this.$doPlaneTop=$(".look-point-cloud-do-plane-top");
        this.$doPlaneLeft=$(".look-point-cloud-do-plane-left");
        this.$doPlaneFront=$(".look-point-cloud-do-plane-front");
        this.$doPlaneTop.css({width:this.width/4,height:this.height/3,right:0,top:0});
        this.$doPlaneLeft.css({width:this.width/4,height:this.height/3-2,right:0,top:this.height/3+1});
        this.$doPlaneFront.css({width:this.width/4,height:this.height/3,right:0,bottom:0});
    };
    LookPointCloud.prototype.initBasic=function(){

        this.scene1 = new THREE.Scene();
        this.scene2 = new THREE.Scene();

        this.scene1.background=this.background;

        this.camera = new THREE.PerspectiveCamera( 15, this.width / this.height, 0.01, 400000 );
        this.camera.up.set( 0, 0, 1 );

        controls = new THREE.OrbitControls(this.camera,this.domStage );


        renderer = new THREE.WebGLRenderer( { antialias: true } );
        renderer.setPixelRatio( window.devicePixelRatio?window.devicePixelRatio:1);
        renderer.setSize( this.width, this.height );
        renderer.sortObjects=false;
        this.domStage.appendChild( renderer.domElement );

        raycaster=new THREE.Raycaster();

        this.loader = new THREE.PCDLoader();

        //this.scene1.add(new THREE.AxesHelper( 5 ));


    };
    //创建三视图的拖动对象（PlaneDragControls）
    LookPointCloud.prototype.initPlaneDragControls=function(objs){
        let self=this;

        if(this.planeFrontDrag) this.planeFrontDrag.dispose();
        if(this.planeLeftDrag) this.planeLeftDrag.dispose();
        if(this.planeTopDrag) this.planeTopDrag.dispose();

        this.planeTopDrag=new THREE.DragControls(objs,threeCamera.children[0],this.$doPlaneTop.get(0));
        this.planeLeftDrag=new THREE.DragControls(objs,threeCamera.children[1],this.$doPlaneLeft.get(0));
        this.planeFrontDrag=new THREE.DragControls(objs,threeCamera.children[2],this.$doPlaneFront.get(0));

        this.planeTopDrag.addEventListener("dragstart",onPlaneDragStart.bind(this));
        this.planeTopDrag.addEventListener('drag',function (res) {
            if(!res.object.userData.lastPos) return;

            let w=res.object.userData.lastPos.x>0?(res.object.position.x-res.object.userData.lastPos.x):-(res.object.position.x-res.object.userData.lastPos.x);
            let h=res.object.userData.lastPos.y>0?(res.object.position.y-res.object.userData.lastPos.y):-(res.object.position.y-res.object.userData.lastPos.y);

            let gw=res.object.userData.lastSize.width+w;
            let gh=res.object.userData.lastSize.height+h;
            let gd=res.object.userData.lastSize.depth;

            self.updateFrontCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});
            self.updateLeftCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});

            planeDragDoIt.apply(self,[res,gw,gh,gd]);
        });
        this.planeTopDrag.addEventListener("dragend",onPlaneDragEnd.bind(this));

        this.planeLeftDrag.addEventListener("dragstart",onPlaneDragStart.bind(this));
        this.planeLeftDrag.addEventListener('drag',function (res) {
            if(!res.object.userData.lastPos) return;

            let w=res.object.userData.lastPos.x>0?(res.object.position.x-res.object.userData.lastPos.x):-(res.object.position.x-res.object.userData.lastPos.x);
            let d=res.object.userData.lastPos.z>0?(res.object.position.z-res.object.userData.lastPos.z):-(res.object.position.z-res.object.userData.lastPos.z);

            let gw=res.object.userData.lastSize.width+w;
            let gh=res.object.userData.lastSize.height;
            let gd=res.object.userData.lastSize.depth+d;

            self.updateTopCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});
            self.updateFrontCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});

            planeDragDoIt.apply(self,[res,gw,gh,gd]);
        });
        this.planeLeftDrag.addEventListener("dragend",onPlaneDragEnd.bind(this));

        this.planeFrontDrag.addEventListener("dragstart",onPlaneDragStart.bind(this));
        this.planeFrontDrag.addEventListener('drag',function (res) {
            if(!res.object.userData.lastPos) return;

            let d=res.object.userData.lastPos.z>0?(res.object.position.z-res.object.userData.lastPos.z):-(res.object.position.z-res.object.userData.lastPos.z);
            let h=res.object.userData.lastPos.y>0?(res.object.position.y-res.object.userData.lastPos.y):-(res.object.position.y-res.object.userData.lastPos.y);

            let gw=res.object.userData.lastSize.width;
            let gh=res.object.userData.lastSize.height+h;
            let gd=res.object.userData.lastSize.depth+d;

            self.updateTopCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});
            self.updateLeftCamera({width:Math.abs(gh),height:Math.abs(gw),deep:Math.abs(gd)});

            planeDragDoIt.apply(self,[res,gw,gh,gd]);
        });
        this.planeFrontDrag.addEventListener("dragend",onPlaneDragEnd.bind(this));
    };
    //创建点云场景中的TransformControls对象
    LookPointCloud.prototype.initTransformControls=function(obj){
        let self=this;

        if(translateControl){
            this.scene1.remove(translateControl);
            translateControl.dispose();
        }

        translateControl=new THREE.TransformControls(this.camera,this.domStage);
        translateControl.attach(obj);
        this.changeTransformControls("translate");

        this.scene1.add(translateControl);
    };
    //将TransformControls切换到移动或旋转模式
    LookPointCloud.prototype.changeTransformControls=function(type){
        if(!translateControl||!nowM) return;

        let self=this;

        if(type==="translate"){
            //nowM.children[0].visible=false;
            translateControl.setMode(type);
            translateControl.setSize(this.worldRadius/800);
            translateControl.showX=true;
            translateControl.showY=true;
            translateControl.showZ=true;
            translateControl.addEventListener( 'dragging-changed', function ( event ) {
                controls.enabled = ! event.value;
                //self.setPointsColorByMesh();
            });
            translateControl.addEventListener("objectChange",function (event) {
                if(!event.target.object) return;

                self.updateMeshPosition();
                self.setPointsColorByMeshInThreeCamera(nowM);
            });
        }else if(type==="rotate"){
            //nowM.children[0].visible=true;
            translateControl.setMode(type);
            translateControl.setSize(this.worldRadius/800);
            translateControl.showX=false;
            translateControl.showY=false;
            translateControl.showZ=true;
            translateControl.addEventListener( 'dragging-changed', function ( event ) {
                self.planeTopDrag.enabled = ! event.value;
                //self.setPointsColorByMesh();
            });
            translateControl.addEventListener("objectChange",function (event) {
                if(!event.target.object) return;

                let r=event.target.object.rotation;
                //nowM.visible=true;
                event.target.object.userData.nowData["3Dsize"].alpha=r.z;
                event.target.object.userData.mTime=(+Date.now().toString());
                self.scene2.getObjectByName("points-offset").rotation.set(-r.x,-r.y,-r.z);

                self.updateThreeCamera(event.target.object.userData.nowData);

                event.target.object.userData.helper.rotation.set(r.x,r.y,r.z);

                self.saveLastAlpha(r.z);

                //self.setPointsColorByMesh();

                self.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:1},nowM.userData.nowData)});

                self.setPointsColorByMeshInThreeCamera(nowM);
            });
        }
    };
    LookPointCloud.prototype.initEvents=function(){
        this.disposeEvents();
        let self=this

        window.addEventListener("resize",this.eventsObject.resize);
        this.domStage.addEventListener("click",this.eventsObject.threeClick);
        this.domStage.addEventListener("mousedown",this.eventsObject.mousedown);
        this.domStage.addEventListener("mousemove",this.eventsObject.mousemove);
        this.domStage.addEventListener("mouseup",this.eventsObject.mouseup);
        window.addEventListener("keyup",this.eventsObject.keyup);

        this.$doPlaneTop.get(0).addEventListener("mousedown",this.eventsObject.topPlaneMouseDown);
        this.$doPlaneTop.get(0).addEventListener("mousemove",this.eventsObject.topPlaneMouseMove);
        this.$doPlaneTop.get(0).addEventListener("mouseup",this.eventsObject.topPlaneMouseUp);
        this.$doPlaneTop.get(0).addEventListener("mouseleave",this.eventsObject.topPlaneMouseUp);
        this.$doPlaneTop.get(0).addEventListener("mousewheel",this.eventsObject.topPlaneMouseWheel);

        this.$doPlaneLeft.get(0).addEventListener("mousedown",this.eventsObject.leftPlaneMouseDown);
        this.$doPlaneLeft.get(0).addEventListener("mousewheel",this.eventsObject.leftPlaneMouseWheel);
        this.$doPlaneFront.get(0).addEventListener("mousedown",this.eventsObject.frontPlaneMouseDown);
        this.$doPlaneFront.get(0).addEventListener("mousewheel",this.eventsObject.frontPlaneMouseWheel);

        $(".look-point-cloud-rotation-add1").on("click",function () {
            self.rotationAdd1(true)
        });
        $(".look-point-cloud-rotation-sub1").on("click",function () {
            self.rotationAdd1()
        });
    };
    LookPointCloud.prototype.disposeEvents=function(){
        window.removeEventListener("resize",this.eventsObject.resize);
        this.domStage.removeEventListener("click",this.eventsObject.threeClick);
        this.domStage.removeEventListener("mousedown",this.eventsObject.mousedown);
        this.domStage.removeEventListener("mousemove",this.eventsObject.mousemove);
        this.domStage.removeEventListener("mouseup",this.eventsObject.mouseup);
        window.removeEventListener("keyup",this.eventsObject.keyup);

        this.$doPlaneTop.get(0).removeEventListener("mousedown",this.eventsObject.topPlaneMouseDown);
        this.$doPlaneTop.get(0).removeEventListener("mousemove",this.eventsObject.topPlaneMouseMove);
        this.$doPlaneTop.get(0).removeEventListener("mouseup",this.eventsObject.topPlaneMouseUp);
        this.$doPlaneTop.get(0).removeEventListener("mouseleave",this.eventsObject.topPlaneMouseUp);
        this.$doPlaneTop.get(0).removeEventListener("mousewheel",this.eventsObject.topPlaneMouseWheel);

        this.$doPlaneLeft.get(0).removeEventListener("mousedown",this.eventsObject.leftPlaneMouseDown);
        this.$doPlaneLeft.get(0).removeEventListener("mousewheel",this.eventsObject.leftPlaneMouseWheel);
        this.$doPlaneFront.get(0).removeEventListener("mousedown",this.eventsObject.frontPlaneMouseDown);
        this.$doPlaneFront.get(0).removeEventListener("mousewheel",this.eventsObject.frontPlaneMouseWheel);


        $(".look-point-cloud-rotation-add1").off("click");
        $(".look-point-cloud-rotation-sub1").off("click");
    };

    LookPointCloud.prototype.planeSelect=function($target) {
        this.$doPlaneTop.css("outline",0);
        this.$doPlaneLeft.css("outline",0);
        this.$doPlaneFront.css("outline",0);

        $target.css("outline","2px solid #2c8cf0");
    };

    //修改背景颜色
    LookPointCloud.prototype.changeBackground=function(color="#000000"){
        this.background.set(color);

        this.setPointsColor();
    };
    //修改遮挡范围
    LookPointCloud.prototype.changeOcclusionSize=function(size){
        if(size===this.occlusionSize) return;

        this.occlusionSize=size;

        this.setPointsColor();
    };
    //修改遮挡模式
    LookPointCloud.prototype.changeOcclusionMode=function(mode){
        if(mode===this.occlusionMode) return;

        this.occlusionMode=mode;

        this.setPointsColor()
    };
    /*LookPointCloud.prototype.changeOcclusionColor=function(){
        let points = this.scene1.getObjectByName("points");

        let rc = this.worldRadius;
        let rz = this.worldRect.z;

        let l = points.geometry.attributes.position.count;
        let ps = points.geometry.attributes.position.array;
        let colors=[];

        let size=this.occlusionSize;
        let mode=this.occlusionMode;
        let bgColor=this.background;
        let tc=[bgColor.r,bgColor.g,bgColor.b];
        let gc=this.groundColor;

        switch (mode) {
            case occlusionMode.all:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    if(p.z>(-this.groundOffset))
                        colors.push((p.z/rz+0.5)*1.8,(1.0-(p.z/rz+0.5))*1.8,0);
                    else
                        colors.push(gc[0],gc[1],gc[2])
                }
                break;
            }
            case occlusionMode.inside:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    if(p.length()>size)
                        colors.push(tc[0],tc[1],tc[2]);
                    else{
                        if(p.z>(-this.groundOffset))
                            colors.push((p.z/rz+0.5)*1.8,(1.0-(p.z/rz+0.5))*1.8,0);
                        else
                            colors.push(gc[0],gc[1],gc[2])
                    }

                }
                break;
            }
            case occlusionMode.outside:{
                for(let i=0;i<l;i++){
                    let p = new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
                    if(p.length()<=size)
                        colors.push(tc[0],tc[1],tc[2]);
                    else{
                        if(p.z>(-this.groundOffset))
                            colors.push((p.z/rz+0.5)*1.8,(1.0-(p.z/rz+0.5))*1.8,0);
                        else
                            colors.push(gc[0],gc[1],gc[2]);
                    }

                }
                break;
            }
        }

        points.geometry.addAttribute( 'color', new THREE.Float32BufferAttribute( colors, 3 ) );
        points.material = new THREE.PointsMaterial( { size: this.pointSize, vertexColors: THREE.VertexColors } );

    };*/

    //修改点大小
    LookPointCloud.prototype.changePointSize=function(size=0.1){
        if(size===this.pointSize) return;

        this.pointSize=size;

        let points = this.scene1.getObjectByName("points");
        points.material.size=this.pointSize;
    };

    //创建3个角度的摄像机
    LookPointCloud.prototype.initThreeCamera=function(){
        threeCamera=new THREE.Group();
        this.scene2.add(threeCamera);

        let cameraTop = new THREE.OrthographicCamera( -1,1,1,-1, 1, 5 );
        let cameraLeft = new THREE.OrthographicCamera( -1,1,1,-1, 1, 5 );
        let cameraFront = new THREE.OrthographicCamera( -1,1,1,-1, 1, 5 );

        cameraTop.up.set( 0, 0, 1);
        cameraLeft.up.set( 0, 0 ,1);
        cameraFront.up.set( 0, 0, 1);

        let CTHelper=new THREE.CameraHelper( cameraTop );
        CTHelper.name="ct";
        let CLHelper=new THREE.CameraHelper( cameraLeft );
        CLHelper.name="cl";
        let CFHelper=new THREE.CameraHelper( cameraFront );
        CFHelper.name="cf";

        cameraTop.rotation.set(2*Math.PI,0,Math.PI/2);
        cameraLeft.rotation.set(Math.PI/2,0,0);
        cameraFront.rotation.set(Math.PI/2,Math.PI/2,0);

        CTHelper.visible=false;
        CLHelper.visible=false;
        CFHelper.visible=false;

        threeCamera.add(cameraTop);
        threeCamera.add(cameraLeft);
        threeCamera.add(cameraFront);

        this.scene1.add(CTHelper);
        this.scene1.add(CLHelper);
        this.scene1.add(CFHelper);
    };
    LookPointCloud.prototype.updateCameraZoom=function(type,add){
        let max=this.status.tc[type].max;
        let min=this.status.tc[type].min;
        if(add)
            this.status.tc[type].val=this.status.tc[type].val+0.1>max?max:this.status.tc[type].val+0.1;
        else
            this.status.tc[type].val=this.status.tc[type].val-0.1<min?min:this.status.tc[type].val-0.1;
    }
    //更新3个角度的摄像机
    LookPointCloud.prototype.updateThreeCamera=function(data){

        let p=data["3Dcenter"];
        let s=data["3Dsize"];
        let pv=new THREE.Vector3(p.x,p.y,p.z);

        threeCamera.position.set(p.x,p.y,p.z);

        this.updateTopCamera(s);
        this.updateLeftCamera(s);
        this.updateFrontCamera(s);

    };
    //更新顶视图摄像机
    LookPointCloud.prototype.updateTopCamera=function(s){

        let ct=threeCamera.children[0];

        let cs=getSizeSort(s);

        let v=s.deep<(s.width+s.height+s.deep)/3/8*2?(s.width+s.height+s.deep)/3/8*2:s.deep;
        ct.position.set(0,0,v);
        ct.near=0;
        ct.far=v*2.5;

        let zoom=this.status.tc.t.val;
        ct.left=-cs[0]*zoom;
        ct.right=cs[0]*zoom;
        ct.top=cs[0]*zoom;
        ct.bottom=-cs[0]*zoom;

        ct.updateProjectionMatrix();
        this.scene1.getObjectByName("ct").update();

        this.threeSize.t=[s.height,s.width];
    }
    //更新侧视图摄像机
    LookPointCloud.prototype.updateLeftCamera=function(s){

        let cl=threeCamera.children[1];

        let cs=getSizeSort(s);

        let v=s.width<(s.width+s.height+s.deep)/3/8*2?(s.width+s.height+s.deep)/3/8*2:s.width;
        cl.position.set(0,-v,0);
        cl.near=0;
        cl.far=v*2.5;

        let zoom=this.status.tc.l.val;
        cl.left=-cs[0]*zoom;
        cl.right=cs[0]*zoom;
        cl.top=cs[0]*zoom;
        cl.bottom=-cs[0]*zoom;

        cl.updateProjectionMatrix();
        this.scene1.getObjectByName("cl").update();

        this.threeSize.l=[s.height,s.deep]
    }
    //更新前试图摄像机
    LookPointCloud.prototype.updateFrontCamera=function(s){

        let cf=threeCamera.children[2];

        let cs=getSizeSort(s);

        let v=s.height<(s.width+s.height+s.deep)/3/8*2?(s.width+s.height+s.deep)/3/8*2:s.height;
        cf.position.set(v,0,0);
        cf.near=0;
        cf.far=v*2.5;

        let zoom=this.status.tc.f.val;
        cf.left=-cs[0]*zoom;
        cf.right=cs[0]*zoom;
        cf.top=cs[0]*zoom;
        cf.bottom=-cs[0]*zoom;

        cf.updateProjectionMatrix();
        this.scene1.getObjectByName("cf").update();

        this.threeSize.f=[s.width,s.deep];
    }

    //开始创建立方体
    LookPointCloud.prototype.createBox=function(){
        if(!this.isTCEditorOpen) return;

        this.status.main=1;
        this.clearSelect();
        controls.enabled=false;

        if(this.status.mode===modes.car||this.status.mode===modes.car2){
            let points=this.scene1.getObjectByName("points");
            touchPlaneX.position.copy(new THREE.Vector3(0,0,-this.groundOffset));
        }
    };
    //结束创建立方体
    LookPointCloud.prototype.createBoxEnd=function(){
        this.status.main=0;
        controls.enabled=true;

        this.scene1.remove(tmpBox);
        this.scene1.remove(tmpBoxHelper);

        createRect={
            fPoint:null,
            sPoint:null,
            tPoint:null,
            width:0,height:0,deep:0
        }

        touchPoint.visible=false;
    };

    //窗口变化时候执行的代码
    LookPointCloud.prototype.onWindowResize=function (){
        this.width=$(this.element).width();
        this.height=$(this.element).height();

        this.offset=$(this.element).offset();

        this.camera.aspect = this.width / this.height;
        this.camera.updateProjectionMatrix();
        renderer.setSize( this.width, this.height );
        //controls.handleResize();

        this.$doPlaneTop.css({width:this.width/4,height:this.height/3-1,right:0,top:0});
        this.$doPlaneLeft.css({width:this.width/4,height:this.height/3-2,right:0,top:this.height/3+2});
        this.$doPlaneFront.css({width:this.width/4,height:this.height/3-1,right:0,bottom:0});
    };
    //点击立方体选中立方体
    LookPointCloud.prototype.threeClick=function(event) {
        if(this.status.main) return;

        event.preventDefault();
        event.stopPropagation();

        //let ol=nowM?this.offset.left+this.width/4:this.offset.left;
/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        raycaster.setFromCamera( mouse, this.camera );
        let intersects = raycaster.intersectObjects( findMeshs );

        if(intersects.length){
            this.selectMesh(intersects[ 0 ].object);

        }/*else
            this.clearSelect();*/
    };

    //查找并选中立方体用index
    LookPointCloud.prototype.findMeshByIndex=function(index){
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].userData.index==index){
                this.selectMesh(findMeshs[i]);
                return;
            }
        }
    };
    //选中立方体
    LookPointCloud.prototype.selectMesh=function(obj){
        if(nowM){
            if(obj.uuid==nowM.uuid) return;

            nowM.userData.helper.visible=false;
            nowM.visible=true;
            nowM.children[0].visible=true;
            this.scene2.getObjectByName("select-"+nowM.userData.data.index).visible=false;

            if(translateControl)
                translateControl.detach();
        }

        obj.userData.helper.visible=true;
        obj.visible=true;
        let op=obj.position;
        let or=obj.rotation;
        this.scene2.getObjectByName("points-offset").position.set(op.x,op.y,op.z);
        this.scene2.getObjectByName("points").position.set(-op.x,-op.y,-op.z);
        this.scene2.getObjectByName("points-offset").rotation.set(-or.x,-or.y,-or.z);
        this.scene2.getObjectByName("select-"+obj.userData.data.index).visible=true;

        this.setPointsColorByMeshInThreeCamera(obj);

        nowM=obj;

        if (this.showOneEnabled){
            this.showOne();
        }

        //self.setPointsColorByMesh(scene1.getObjectByName("points"));



        if(this.isTCEditorOpen){
            this.initPlaneDragControls(this.scene2.getObjectByName("select-"+obj.userData.data.index).children);
            this.initTransformControls(nowM);
        }
        this.updateThreeCamera(nowM.userData.nowData,nowM);
        this.openSelectUI();
        this.dispatchEvent( { type: 'selectMesh', message: nowM.userData.nowData } );
    };
    //取消选中
    LookPointCloud.prototype.clearSelect=function(){
        this.clearSelectMesh();
        this.clearSelectUI();
    };
    //取消选中立方体
    LookPointCloud.prototype.clearSelectMesh=function(){
        if(nowM){
            nowM.userData.helper.visible=false;
            nowM.visible=true;
            nowM.children[0].visible=true;
            this.scene2.getObjectByName("select-"+nowM.userData.data.index).visible=false;

            if(translateControl){
                this.scene1.remove(translateControl);
                translateControl.dispose();
            }

            nowM=null;

        }
    };

    //当前立方体旋转1°
    LookPointCloud.prototype.rotationAdd1=function(isAdd){
        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        let d=isAdd?1:-1;
        let rt=nowM.rotation.clone();

        nowM.rotation.set(rt.x,rt.y,rt.z+d*Math.PI/180);

        let r=nowM.rotation;

        nowM.userData.nowData["3Dsize"].alpha=r.z;
        nowM.userData.mTime=(+Date.now().toString());
        this.scene2.getObjectByName("points-offset").rotation.set(-r.x,-r.y,-r.z);

        this.updateThreeCamera(nowM.userData.nowData);

        nowM.userData.helper.rotation.set(r.x,r.y,r.z);

        this.saveLastAlpha(r.z);

        this.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:1},nowM.userData.nowData)});

        this.setPointsColorByMeshInThreeCamera(nowM);
    }
    //隐藏选中UI
    LookPointCloud.prototype.clearSelectUI=function(){
        this.$doPlaneTop.hide();
        this.$doPlaneLeft.hide();
        this.$doPlaneFront.hide();
    };
    //显示选中UI
    LookPointCloud.prototype.openSelectUI=function(){
        this.$doPlaneTop.show();
        this.$doPlaneLeft.show();
        this.$doPlaneFront.show();
    };

    //通过ID删除立方体
    LookPointCloud.prototype.deleteMeshByIndex=function(index){
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].userData.index==index){
                let rd=findMeshs[i].userData.nowData;
                this.deleteMesh(findMeshs[i],i);

                this.dispatchEvent({type:"deleteMesh",message:rd});
                //this.setPointsColorByMesh();
                return rd;
            }
        }
    };
    //删除所有立方体
    LookPointCloud.prototype.deleteAllMesh=function(){
        while (findMeshs.length){
            this.deleteMesh(findMeshs[0],0);
        }
        //this.setPointsColorByMesh();
        this.dispatchEvent({type:"deleteAllMesh",message:""});
    };
    //删除当前立方体
    LookPointCloud.prototype.deleteMeshByNow=function(){
        if(!nowM) return;
        for(let i=0;i<findMeshs.length;i++){
            if(nowM.uuid==findMeshs[i].uuid){
                let rd=findMeshs[i].userData.nowData;
                this.deleteMesh(findMeshs[i],i);

                //this.setPointsColorByMesh();
                this.dispatchEvent({type:"deleteMesh",message:rd});
                return rd;
            }
        }
    };
    //删除立方体
    LookPointCloud.prototype.deleteMesh=function(obj,i){
        if(obj==nowM)
            this.clearSelect();

        let m2=obj;
        let m3=this.scene2.getObjectByName(obj.name);

        let tm = findMeshs[0];
        findMeshs[0]=findMeshs[i];
        findMeshs[i]=tm;
        findMeshs.shift();

        m3.traverse(function (mp) {
            if(mp.geometry){
                mp.geometry.dispose();
                mp.material.dispose();
            }
        });
        while (m3.children.length){
            m3.remove(m3.children[0]);
        }
        rectGroup2.remove(m3);

        rectGroup.remove(m2);
        rectGroup.remove(m2.userData.helper);

        m2.userData.helper.geometry.dispose();
        m2.userData.helper.material.dispose();
        m2.userData.helper=null;

        m2.remove(m2.children[0]);
        m2.geometry.dispose();
        m2.material.dispose();

    };

    //获取所有立方体信息
    LookPointCloud.prototype.getAllRects=function(){
        let ra=[];
        let self=this;

        //计算每个立方体内点数量
        //this.setPointsColorByMesh();

        findMeshs.map(function (m) {
            let ps=self.get8Points(m);
            m.userData.nowData.points=ps;
            ra.push(JSON.parse(JSON.stringify(m.userData.nowData)));
        });

        return ra;
    };
    //通过id获取立方体信息
    LookPointCloud.prototype.getRectByIndex=function(index){
        let self=this;
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].userData.index==index){
                let ps=self.get8Points(findMeshs[i])
                findMeshs[i].userData.nowData.points=ps;
                return JSON.parse(JSON.stringify(findMeshs[i].userData.nowData));
            }
        }
    };
    LookPointCloud.prototype.get8Points=function(m){
        let ps=[];
        let m3=this.scene2.getObjectByName(m.name);
        m.updateWorldMatrix();
        m3.children.map(function (p) {
            if(p.userData.data){

                let pp=m.localToWorld(p.position.clone())
                ps.push(pp);

/*                let mt=new THREE.Mesh(new THREE.BoxGeometry(0.5,0.5,0.5),new THREE.MeshBasicMaterial({color:0x0000ff}));
                mt.position.copy(pp);
                scene1.add(mt);*/
            }
        })

        return ps;
    }

    //通过id写入立方体颜色
    LookPointCloud.prototype.setMeshColorByIndex=function(index,colorString){
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].userData.index==index){
                this.setMeshColor(findMeshs[i],colorString);
                return;
            }
        }
    };
    //写入当前立方体颜色
    LookPointCloud.prototype.setMeshColorByNow=function(colorString){
        if(!nowM) return;
        for(let i=0;i<findMeshs.length;i++){
            if(nowM.uuid==findMeshs[i].uuid){
                this.setMeshColor(findMeshs[i],colorString);
                return;
            }
        }
    };
    //写入立方体颜色
    LookPointCloud.prototype.setMeshColor=function(obj,colorString){
        let c=new THREE.Color(colorString);
        obj.material.color=c;
        obj.userData.helper.material.color=c;
        this.scene2.getObjectByName(obj.name).material.color=c;
    };

    //外部设置摄像机视角
    LookPointCloud.prototype.moveCamera=function(object){
        this.camera.position.copy(object.p);
        this.camera.quaternion.copy(object.q);
    };

    //渲染方法
    LookPointCloud.prototype.animate=function(){
        let self=this;

        window.requestAnimationFrame(function step(timestamp) {
            if(!renderer) return;
            self.animateObject = window.requestAnimationFrame(step);

            if(!nowM){
                renderer.setViewport(0,0,self.width,self.height);
                renderer.setScissor(0,0,self.width,self.height);
                renderer.setScissorTest( true );
                renderer.setClearColor(0x000000);
                renderer.render( self.scene1, self.camera );
            }
            else{
                renderer.setViewport(0,0,self.width,self.height);
                renderer.setScissor(0,0,self.width,self.height);
                renderer.setScissorTest( true );
                renderer.setClearColor(0x000000);
                renderer.render(self.scene1,self.camera);

                renderer.setViewport(self.width/4*3,0,self.width/4,self.height/3);
                renderer.setScissor(self.width/4*3,0,self.width/4,self.height/3);
                renderer.setClearColor(0x222222);
                renderer.render(self.scene2,threeCamera.children[2]);

                renderer.setViewport(self.width/4*3,self.height/3+1,self.width/4,self.height/3-1);
                renderer.setScissor(self.width/4*3,self.height/3+1,self.width/4,self.height/3-1);
                renderer.setScissorTest( true );
                renderer.setClearColor(0x222222);
                renderer.render(self.scene2,threeCamera.children[1]);

                renderer.setViewport(self.width/4*3,self.height/3*2+1,self.width/4,self.height/3);
                renderer.setScissor(self.width/4*3,self.height/3*2+1,self.width/4,self.height/3);
                renderer.setScissorTest( true );
                renderer.setClearColor(0x222222);
                renderer.render(self.scene2,threeCamera.children[0]);

            }
        });
    };

    //拖动8个点改变大小和位置时，更新其他点的位置
    //拖动8个点改变大小和位置时，更新其他点的位置
    function updateOtherPoints(point) {
        updateAllPoints(point.parent);
    }
    //更新所有8个点
    function updateAllPoints(obj) {
        let rect=obj.userData.nowData;
        let s=rect["3Dsize"];
        let p=rect["3Dcenter"];

        obj.children.map(function (p) {
            if(p.userData.data){
                let d=p.userData.data;
                p.position.set(s.height/2*d[0],s.width/2*d[1],s.deep/2*d[2]);
            }
        })
    }
    function findMeshByName(name) {
        for(let i=0;i<findMeshs.length;i++){
            if(findMeshs[i].name==name)
                return findMeshs[i];
        }
    }

    //更新主体区域内立方体
    function updateMesh(name,s,p) {
        let m = findMeshByName(name);
        m.geometry=new THREE.BoxGeometry(s.height,s.width,s.deep);
        m.position.set(p.x,p.y,p.z);
        let r=m.rotation;


        m.userData.nowData["3Dcenter"]=p;
        m.userData.nowData["3Dsize"]=s;
        m.userData.nowData.mTime=(+Date.now().toString());

        m.geometry.computeBoundingBox();

        m.userData.helper.geometry=new THREE.EdgesGeometry( m.geometry );
        m.userData.helper.rotation.copy(r);
        m.userData.helper.position.set(p.x,p.y,p.z);

        m.children[0].setLength(s.height);

    }

    function getSizeSort(s){
        let ss=[+s.width,+s.height,+s.deep];
        ss.sort(function(a,b){
            return b-a;
        });
        return ss;
    };
/*    function toScreenPosition(pos, camera,plane) {
        let position=pos.clone();
        let screenCoord = {};
        position.project(camera);
        screenCoord.x = (0.5 + position.x / 2) * plane.width;
        screenCoord.y = (0.5 - position.y / 2) * plane.height;
        return screenCoord;
    };
    function toScreenPosition2(pos,camera,plane) {
        camera.updateProjectionMatrix();
        let projScreenMat=new THREE.Matrix4();

        projScreenMat.multiplyMatrices( camera.projectionMatrix, camera.matrixWorldInverse );
        pos.applyMatrix4(projScreenMat);

        return { x: ( pos.x + 1 ) * plane.width /2,
            y: ( -pos.y + 1) * plane.height / 2 };
    };*/

    //8个点拖动时需要的方法
    function onPlaneDragStart(res) {
        res.object.userData.lastPos=res.object.position.clone();
        res.object.userData.lastSize=res.object.parent.geometry.parameters;
        res.object.userData.lastMPos=res.object.parent.position.clone();
        res.object.userData.lastMMPos=nowM.position.clone();

        this.isPlaneTopRotate=false;
    }
    function planeDragDoIt(res,gw,gh,gd){
        let r=nowM.rotation;

        let pd=res.object.userData.data;
        if(Math.abs(gw)>this.worldRadius){
            res.object.position.x=this.worldRadius/2*pd[0];
            gw=this.worldRadius;
        }else gw=Math.abs(gw);
        if(Math.abs(gh)>this.worldRadius){
            res.object.position.y=this.worldRadius/2*pd[1];
            gh=this.worldRadius;
        }else gh=Math.abs(gh);
        if(Math.abs(gd)>this.worldRadius){
            res.object.position.z=this.worldRadius/2*pd[2];
            gd=this.worldRadius;
        }else gd=Math.abs(gd);


        let lmp2=(res.object.position.clone().sub(res.object.userData.lastPos));
        let vt=res.object.userData.lastMPos.clone().add(lmp2.clone().multiplyScalar(0.5));

        let tv=new THREE.Vector3(lmp2.x*Math.cos(r.z)-lmp2.y*Math.sin(r.z),lmp2.y*Math.cos(r.z)+lmp2.x*Math.sin(r.z),lmp2.z);
        let vt2=res.object.userData.lastMPos.clone().add(tv.clone().multiplyScalar(0.5));

        res.object.parent.geometry=new THREE.BoxGeometry(gw,gh,gd);
        res.object.parent.position.set(vt.x,vt.y,vt.z);

        let s={width:gh,height:gw,deep:gd,alpha:r.z};
        res.object.parent.userData.nowData["3Dcenter"]=vt2;
        res.object.parent.userData.nowData["3Dsize"]=s;
        res.object.parent.userData.nowData.mTime=(+Date.now().toString());

        updateOtherPoints(res.object);
        updateMesh(res.object.parent.name,s,vt2);

        this.threeSize.f=[s.width,s.deep];
        this.threeSize.l=[s.height,s.deep];
        this.threeSize.t=[s.height,s.width];

        this.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:2},nowM.userData.nowData)});

        this.setPointsColorByMeshInThreeCamera(nowM);
    }
    function onPlaneDragEnd(res) {
        this.updateThreeCamera(res.object.parent.userData.nowData);

        let vt=nowM.position;

        res.object.parent.position.set(vt.x,vt.y,vt.z);
        this.scene2.getObjectByName("points-offset").position.set(vt.x,vt.y,vt.z);
        this.scene2.getObjectByName("points").position.set(-vt.x,-vt.y,-vt.z);

        updateEditorPointSize(res.object.parent);

        this.isPlaneTopRotate=true;
        mouseTR=null;

        let r=nowM.rotation;
        let lmp2=(res.object.position.clone().sub(res.object.userData.lastPos));
        let tv=new THREE.Vector3(lmp2.x*Math.cos(r.z)-lmp2.y*Math.sin(r.z),lmp2.y*Math.cos(r.z)+lmp2.x*Math.sin(r.z),lmp2.z);

        this.dispatchEvent({type:"updateMeshSize",message:Object.assign({updateType:2,tv:tv.clone()},nowM.userData.nowData)});
    }

    //默认的立方体创建方式
    function onMouseDown(event) {
        if(!this.status.main) return;
        if(this.status.main==2) return;
        if(this.status.main==3) return;

        event.preventDefault();
        event.stopPropagation();

        /*mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        mouse.last=new THREE.Vector2(mouse.x,mouse.y);

        raycaster.setFromCamera( mouse, this.camera );
        this.scene1.add(tmpBox);
        this.scene1.add(tmpBoxHelper);
        //tmpBoxHelper.update();

        let intersects = raycaster.intersectObjects( [this.scene1.getObjectByName("points")] );

        if(intersects.length){
            createRect.fPoint=intersects[0].point;
            touchPlaneX.position.copy(createRect.fPoint);
            this.status.main=2;
            touchPoint.visible=false;
        }
    }
    function onMouseMove(event) {
        if(!this.status.main) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        raycaster.setFromCamera( mouse, this.camera );
        if(this.status.main==1){

            let intersects = raycaster.intersectObjects( [this.scene1.getObjectByName("points")] );

            if(intersects.length){
                touchPoint.visible=true;
                touchPoint.position.copy(intersects[0].point);
            }//else touchPoint.visible=false;
        }
        else if(this.status.main==2){
            let r=this.mouseMoveStateWH();
            if(r){
                createRect["3Dcenter"]={x:r.p.x,y:r.p.y,z:r.p.z};
                createRect["3Dsize"]={width:Math.abs(r.h),height:Math.abs(r.w),deep:0,alpha:0};
            }
        }else if(this.status.main==3){
            let r=this.mouseMoveStateD();
            if(r){
                createRect["3Dcenter"]={x:createRect.p.x,y:createRect.p.y,z:r.p.z};
                createRect["3Dsize"]={width:Math.abs(createRect.height),height:Math.abs(createRect.width),deep:Math.abs(r.d),alpha:0};
            }
        }
    }
    function onMouseUp(event) {
        if(!this.status.main||!createRect.fPoint) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        raycaster.setFromCamera( mouse, this.camera );

        if(this.status.main==2){
            if(!createRect.sPoint||mouse.clone().sub(mouse.last).length()<=0) return;

            touchPlaneZ.position.copy(createRect.sPoint);
            this.status.main=3;

        }
        else if(this.status.main==3){
            this.boxDone();
        }
    }

    //规定了地面和默认高度的创建方式
    function onCarMouseDown(event){
        if(!this.status.main) return;
        if(this.status.main==2) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        mouse.last=new THREE.Vector2(mouse.x,mouse.y);

        //touchPlaneX.position.copy(new THREE.Vector3());
        this.scene1.add(tmpBox);
        this.scene1.add(tmpBoxHelper);

        let intersects = raycaster.intersectObjects( [touchPlaneX] );
        if(intersects.length){
            createRect.fPoint=intersects[0].point;
            this.status.main=2;
            touchPoint.visible=false;
        }
    }
    function onCarMouseMove(event) {
        if(!this.status.main) return;

        event.preventDefault();
        event.stopPropagation();

        //console.log($(window).scrollTop())
/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);
        //console.log(mouse);

        raycaster.setFromCamera( mouse, this.camera );

        if(this.status.main==1){
            let intersects = raycaster.intersectObjects( [touchPlaneX] );

            if(intersects.length){
                touchPoint.visible=true;
                touchPoint.position.copy(intersects[0].point);
            }
        }
        else if(this.status.main==2){
            let r=this.mouseMoveStateWH();
            if(r){
                createRect["3Dcenter"]={x:r.p.x,y:r.p.y,z:r.p.z+(this.carDeep/2)};
                createRect["3Dsize"]={width:Math.abs(r.h),height:Math.abs(r.w),deep:this.carDeep,alpha:0};
            }
        }
    }
    function onCarMouseUp(event) {
        if(!this.status.main||!createRect.fPoint) return;

        event.preventDefault();
        event.stopPropagation();

        getXYFun(event,this.width,this.height);

        if(this.status.main==2){
            if(!createRect.sPoint||mouse.clone().sub(mouse.last).length()<=0) return;
            this.boxDone();
        }
    }

    //规定了地面高度但需要调整高度的方法
    function onCar2MouseDown(event) {
        if(!this.status.main) return;
        if(this.status.main==2) return;
        if(this.status.main==3) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        mouse.last=new THREE.Vector2(mouse.x,mouse.y);

        //touchPlaneX.position.copy(new THREE.Vector3());
        this.scene1.add(tmpBox);
        this.scene1.add(tmpBoxHelper);

        let intersects = raycaster.intersectObjects( [touchPlaneX] );
        if(intersects.length){
            createRect.fPoint=intersects[0].point;
            this.status.main=2;
            touchPoint.visible=false;
        }
    }
    function onCar2MouseMove(event) {
        if(!this.status.main) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        raycaster.setFromCamera( mouse, this.camera );
        if(this.status.main==1){

            let intersects = raycaster.intersectObjects( [touchPlaneX] );

            if(intersects.length){
                touchPoint.visible=true;
                touchPoint.position.copy(intersects[0].point);
            }//else touchPoint.visible=false;
        }
        else if(this.status.main==2){
            let r=this.mouseMoveStateWH();
            if(r){
                createRect["3Dcenter"]={x:r.p.x,y:r.p.y,z:r.p.z};
                createRect["3Dsize"]={width:Math.abs(r.h),height:Math.abs(r.w),deep:0,alpha:0};
            }
        }else if(this.status.main==3){
            let r=this.mouseMoveStateD();
            if(r){
                createRect["3Dcenter"]={x:createRect.p.x,y:createRect.p.y,z:r.p.z};
                createRect["3Dsize"]={width:Math.abs(createRect.height),height:Math.abs(createRect.width),deep:Math.abs(r.d),alpha:0};
            }
        }
    }
    function onCar2MouseUp(event) {
        if(!this.status.main||!createRect.fPoint) return;

        event.preventDefault();
        event.stopPropagation();

/*        mouse.x = ( (event.clientX-this.offset.left) / this.width ) * 2 - 1;
        mouse.y = - ( (event.clientY-this.offset.top) / this.height ) * 2 + 1;*/
        getXYFun(event,this.width,this.height);

        raycaster.setFromCamera( mouse, this.camera );

        if(this.status.main==2){
            if(!createRect.sPoint||mouse.clone().sub(mouse.last).length()<=0) return;

            touchPlaneZ.position.copy(createRect.sPoint);
            this.status.main=3;

        }
        else if(this.status.main==3){
            this.boxDone();
        }
    }

    //绘制立方体时获得WH
    LookPointCloud.prototype.mouseMoveStateWH=function() {
        if(!createRect.fPoint) return;

        let intersects = raycaster.intersectObjects( [touchPlaneX] );

        if(intersects.length){
            createRect.sPoint=intersects[0].point;

            let w=createRect.sPoint.x-createRect.fPoint.x;
            let h=createRect.sPoint.y-createRect.fPoint.y;
            let dw=w<0?-1:1;
            let dh=h<0?-1:1;

            if(w>this.worldRadius){
                w=this.worldRadius*dw;
                createRect.sPoint.x=createRect.fPoint.x+w;
            }
            if(h>this.worldRadius){
                h=this.worldRadius*dh;
                createRect.sPoint.y=createRect.fPoint.y+h;
            }

            //createRect.sPoint.x=createRect.fPoint.x+

            let p=createRect.fPoint.clone().add((createRect.sPoint.clone().sub(createRect.fPoint)).multiplyScalar(0.5));

            tmpBox.geometry=new THREE.BoxGeometry(w,h,0);
            tmpBox.position.copy(p);
            tmpBoxHelper.update();

            createRect.width=w;
            createRect.height=h;

            createRect.p=p;

            return {p:p,w:w,h:h};
        }
    }
    //绘制立方体时获得D
    LookPointCloud.prototype.mouseMoveStateD=function() {
        if(!createRect.fPoint) return;

        let intersects = raycaster.intersectObjects( [touchPlaneZ] );
        if(intersects.length){
            createRect.tPoint=intersects[0].point;

            let d=createRect.tPoint.z-createRect.sPoint.z;
            let dd=d<0?-1:1;

            if(d>this.worldRadius){
                d=this.worldRadius*dd;
                createRect.tPoint.z=createRect.sPoint.z+d;
            }

            let p=createRect.fPoint.clone().add((createRect.tPoint.clone().sub(createRect.fPoint)).multiplyScalar(0.5));

            tmpBox.geometry=new THREE.BoxGeometry(createRect.width,createRect.height,Math.abs(d));
            tmpBox.position.set(createRect.p.x,createRect.p.y,p.z);
            tmpBoxHelper.update();

            return {p:p,d:d};
        }
    }
    //立方体绘制完成
    LookPointCloud.prototype.boxDone=function () {
        this.useLastAlpha();
        let m=createARealBox.call(this,createRect);
        this.createBoxEnd();
        this.selectMesh(m);
        this.changeTransformControls("rotate");
        this.dispatchEvent( { type: 'createBoxComplete', message: m.userData.nowData } );
    };
    //外部绘制立方体
    LookPointCloud.prototype.rectDone=function(rect){
        let m=createARealBox.call(this,rect);
        this.createBoxEnd();
        this.selectMesh(m);
        this.changeTransformControls("rotate");
        this.dispatchEvent( { type: 'createBoxComplete', message: m.userData.nowData } );
    };

    function getXYFun(e,w,h) {
        var self = this;
        var _d = { x: 0, y: 0 };
        var e = e || window.event;
        var scrollX = document.documentElement.scrollLeft || document.body.scrollLeft;
        var scrollY = document.documentElement.scrollTop || document.body.scrollTop;
        var x = e.pageX || e.clientX + scrollX;
        var y = e.pageY || e.clientY + scrollY;
        /*当前元素离body的左和上距离*/
        var rect = renderer.getContext().canvas.getBoundingClientRect();
        //var rect = $(canvas).position();
        var top = document.documentElement.clientTop; /*IE下元素离上距离*/
        var left = document.documentElement.clientLeft;
        x = x - (rect.left - left);
        y = y - (rect.top - top);
        _d = new THREE.Vector2(x,y);
        mouse.x=(_d.x/w)*2-1;
        mouse.y=1-(_d.y/h)*2;
        //return _d;
    };

    //如有需要，存储上一个角度
    LookPointCloud.prototype.saveLastAlpha=function(z){
        if(this.alphaInherit)
            this.lastAlpha=z
    };
    //如有需要，使用上一个角度
    LookPointCloud.prototype.useLastAlpha=function(){
        if(this.alphaInherit)
            createRect["3Dsize"].alpha=this.lastAlpha;
    }

    LookPointCloud.prototype.getRectPointsByNow=function () {
        if(!nowM) return;

        let points = this.scene1.getObjectByName("points");

        let l = points.geometry.attributes.position.count;
        let ps=points.geometry.attributes.position.array;

        let rps=[];

        let m=nowM;

        for(let i=0;i<l;i++){
            let tv=new THREE.Vector3(ps[i*3],ps[i*3+1],ps[i*3+2]);
            if(m.geometry.boundingBox.containsPoint(tv.applyMatrix4(new THREE.Matrix4().getInverse(m.matrixWorld)))){
                rps.push(tv);
            }
        }

        return rps;
    }

    //鼠标再$doPlaneTop中拖动
    function onPlaneTopMouseDown(event) {
        if(!this.isTCEditorOpen) return;
        if(!nowM) return;
        if(!this.isPlaneTopRotate) return;

        this.planeSelect(this.$doPlaneTop);

        mouseTR=new THREE.Vector2(event.clientX,event.clientY);
    }
    function onPlaneTopMouseMove(event) {
        if(!this.isTCEditorOpen) return;
        if(!mouseTR) return;
        if(!nowM) return;
        if(!this.isPlaneTopRotate) return;

        let tm=new THREE.Vector2(event.clientX,event.clientY);

        let l=mouseTR.distanceTo(tm)/50;
        let d=mouseTR.length()>tm.length()?-1:1
        nowM.rotation.z+=l*d;
        let r=nowM.rotation;
        //nowM.visible=true;
        nowM.userData.nowData["3Dsize"].alpha=r.z;
        nowM.userData.mTime=(+Date.now().toString());
        this.scene2.getObjectByName("points-offset").rotation.set(-r.x,-r.y,-r.z);

        this.updateThreeCamera(nowM.userData.nowData);

        nowM.userData.helper.rotation.set(r.x,r.y,r.z);

        this.saveLastAlpha(r.z);

        this.setPointsColorByMeshInThreeCamera(nowM);

        this.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:1},nowM.userData.nowData)});

        mouseTR.x=event.clientX;
        mouseTR.y=event.clientY;
    }
    function onPlaneTopMouseUp() {
        if(!this.isTCEditorOpen) return;
        if(!this.isPlaneTopRotate) return;

        mouseTR=null;

        this.setPointsColorByMeshInThreeCamera(nowM);
    }
    function onPlaneTopMouseWheel(event) {

        event.preventDefault();
        event.stopPropagation();

        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        this.planeSelect(this.$doPlaneTop);

        if(event.deltaY>0)
            this.updateCameraZoom("t",true);
        else
            this.updateCameraZoom("t",false);

        let s=nowM.userData.nowData["3Dsize"];

        this.updateTopCamera(s);
    }

        //鼠标在$doPlaneLeft中操作
    function onPlaneLeftMouseDown(event) {
        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        this.planeSelect(this.$doPlaneLeft);
    }
    function onPlaneLeftMouseWheel(event) {

        event.preventDefault();
        event.stopPropagation();

        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        this.planeSelect(this.$doPlaneLeft);

        if(event.deltaY>0)
            this.updateCameraZoom("l",true);
        else
            this.updateCameraZoom("l",false);

        let s=nowM.userData.nowData["3Dsize"];

        this.updateLeftCamera(s);
    }

    //鼠标在$doPlaneFront中操作
    function onPlaneFrontMouseDown(event) {
        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        this.planeSelect(this.$doPlaneFront);
    }
    function onPlaneFrontMouseWheel(argument) {

        event.preventDefault();
        event.stopPropagation();

        if(!this.isTCEditorOpen) return;
        if(!nowM) return;

        this.planeSelect(this.$doPlaneFront);

        if(event.deltaY>0)
            this.updateCameraZoom("f",true);
        else
            this.updateCameraZoom("f",false);

        let s=nowM.userData.nowData["3Dsize"];

        this.updateFrontCamera(s);
    }

    //快捷键，暂时只有ESC时需求创建立方体
    function onKeyUp(event) {
        event.preventDefault();
        event.stopPropagation();

        switch ( event.keyCode ) {
            case 27:{//Esc
                if(this.status.main){
                    this.createBoxEnd();
                }
                break;
            }
            case 81:{
                this.moveRotationByNow();
                break;
            }
            case 69:{
                this.moveRotationByNow(true);
                break;
            }
        }
    }

    //创建立方体
    function createARealBox(rect) {
        let s=rect["3Dsize"];
        let p=rect["3Dcenter"];

        if(!s.width||!s.height||!s.deep) return;

        rect.index=(rect.index||rect.index==0)?rect.index:getRandomId();
        rect.id=rect.index;
        // rect.cTime=rect.cTime?rect.cTime:(+Date.now().toString());
        // rect.mTime=rect.mTime?rect.mTime:"";

        let mm=meshMaterial.clone();
        if(rect.attr){
            if(rect.attr.color)
                mm.color=new THREE.Color(rect.attr.color);
        }


        let m2=new THREE.Mesh(new THREE.BoxGeometry(s.height,s.width,s.deep),mm);
        let m3=new THREE.Mesh(new THREE.BoxGeometry(s.height,s.width,s.deep),mm.clone());

        m2.geometry.computeBoundingSphere();

        let m4=new THREE.ArrowHelper(new THREE.Vector3(1,0,0,),new THREE.Vector3(),s.height,0xffff00);
        m4.setLength(s.height);
        m2.add(m4);
        m3.add(m4.clone());

        let m5=new THREE.LineSegments( new THREE.EdgesGeometry( m2.geometry ), new THREE.LineBasicMaterial( { color: mm.color.getHex()} ) );
        m5.visible=false;

        let m1=new THREE.BoxHelper( m2,mm.color.getHex() );

        //let m4=new THREE.BoxHelper(m3,0x0dc3b4);
        m2.name="select-"+rect.index;
        m2.userData.helper=m5;
        m3.name="select-"+rect.index;
        m3.position.set(p.x,p.y,p.z);
        m3.material.depthTest=false;
        m3.material.color.setHex(0x2c8cf0);
        m3.renderOrder=1;

        m1.position.set(p.x,p.y,p.z);
        m2.position.set(p.x,p.y,p.z);
        m5.position.set(p.x,p.y,p.z);
        m2.rotation.set(0,0,s.alpha?s.alpha:0);
        m5.rotation.set(0,0,s.alpha?s.alpha:0);
        m1.update();

        let m6=createMeshError(m2.geometry.boundingSphere.radius);
        m2.userData.error=m6;
        m2.add(m6);

        m2.geometry.computeBoundingBox();

        m1.visible=false;
        m3.visible=false;

        rectGroup2.add(m3);
        rectGroup.add(m1);
        rectGroup.add(m2);
        rectGroup.add(m5);
        //rectGroup2.add(m4);

        m2.userData.data=rect;
        m2.userData.index=rect.index;
        m3.userData.data=rect;
        m3.userData.index=rect.index;
        //m2.userData.status=0;

        findMeshs.push(m2);

        m2.userData.nowData = {
            '3Dcenter': p,
            '3Dsize': s,
            index: rect.index,
            cBy: rect.cBy,
            cTime: rect.cTime,
            cStep: rect.cStep,
            mBy: rect.mBy,
            mTime: rect.mTime,
            mStep: rect.mStep,
            attr: rect.attr,
            color: rect.color
        };
        m3.userData.nowData = {
            '3Dcenter': p,
            '3Dsize': s,
            index: rect.index,
            cBy: rect.cBy,
            cTime: rect.cTime,
            cStep: rect.cStep,
            mBy: rect.mBy,
            mTime: rect.mTime,
            mStep: rect.mStep,
            attr: rect.attr,
            color: rect.color
        };

        for(var a in this.basicMessage) {
            m2.userData.nowData[a]=this.basicMessage[a];
            m3.userData.nowData[a]=this.basicMessage[a];
        }

        createEditorPoints(m3,rect);

        return m2;
    }


    //创建感叹号的模型并隐藏
    function createMeshError(radius){
        let m6=new THREE.Group();
        let r=radius;
        //let r=m2.geometry.boundingSphere.radius
        let r1=new THREE.Mesh(new THREE.BoxGeometry(r/15,r/15,r/15),new THREE.MeshBasicMaterial({color:0xff0000}));
        let r2=new THREE.Mesh(new THREE.BoxGeometry(r/15,r/15,r/2),new THREE.MeshBasicMaterial({color:0xff0000}));
        let lg=new THREE.Geometry();
        lg.vertices.push(new THREE.Vector3(),new THREE.Vector3(0,0,-r));
        let l1=new THREE.Line(lg,new THREE.MeshBasicMaterial({color:0xff0000}));
        r2.position.set(0,0,r/2);
        m6.add(l1);
        m6.add(r1);
        m6.add(r2);
        m6.position.set(0,0,r);
        m6.visible=false;

        return m6;
    }
    //更新感叹号大小
    function updateMeshError(m){
        if(!m.userData.error) return;
        if(!m.userData.error.visible) return;

        m.geometry.computeBoundingSphere();
        let r=m.geometry.boundingSphere.radius;

        let m6=m.userData.error;
        let c=m6.children;
        c[0].geometry.vertices=[new THREE.Vector3(),new THREE.Vector3(0,0,-r)];
        c[0].geometry.verticesNeedUpdate=true;
        c[1].geometry=new THREE.BoxGeometry(r/15,r/15,r/15);
        c[2].geometry=new THREE.BoxGeometry(r/15,r/15,r/2);
        c[2].position.set(0,0,r/2);
        m6.position.set(0,0,r);
    }
    LookPointCloud.prototype.setErrorByNow=function(){
        if(!nowM) return;
        let m=nowM;
        updateMeshError(m);
        m.userData.error.visible=true;
    };
    LookPointCloud.prototype.setErrorByIndex=function(index){
        let m=this.scene1.getObjectByName("select-"+index);
        if(m){
            updateMeshError(m);
            m.userData.error.visible=true;
        }
    };
    //清楚感叹号
    LookPointCloud.prototype.clearErrorByNow=function(){
        if(!nowM) return;
        let m=nowM;
        m.userData.error.visible=false;
    };
    LookPointCloud.prototype.clearErrorByIndex=function(index){
        let m=this.scene1.getObjectByName("select-"+index);
        if(m)
            m.userData.error.visible=false;
    };

    LookPointCloud.prototype.updateMeshPosition=function(){
        let p=nowM.position.clone();
        nowM.userData.nowData["3Dcenter"]=p;
        nowM.userData.nowData.mTime=(+Date.now().toString());
        this.updateThreeCamera(nowM.userData.nowData);
        this.scene2.getObjectByName(nowM.name).position.set(p.x,p.y,p.z);
        this.scene2.getObjectByName("points-offset").position.set(p.x,p.y,p.z);
        this.scene2.getObjectByName("points").position.set(-p.x,-p.y,-p.z);

        nowM.userData.helper.position.set(p.x,p.y,p.z);

        this.setPointsColorByMeshInThreeCamera(nowM);

        this.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:0},nowM.userData.nowData)});
    };

    LookPointCloud.prototype.moveFrontByNow=function (speed) {
        if(!nowM||!this.isTCEditorOpen) return;

        nowM.position.add(new THREE.Vector3(speed,0,0).applyEuler(nowM.rotation));
        this.updateMeshPosition();
    };
    LookPointCloud.prototype.moveLeftByNow=function (speed) {
        if(!nowM||!this.isTCEditorOpen) return;

        nowM.position.add(new THREE.Vector3(0,speed,0).applyEuler(nowM.rotation));
        this.updateMeshPosition();
    };
    LookPointCloud.prototype.moveUpByNow=function (speed) {
        if(!nowM||!this.isTCEditorOpen) return;

        nowM.position.add(new THREE.Vector3(0,0,speed).applyEuler(nowM.rotation));
        this.updateMeshPosition();
    };

    LookPointCloud.prototype.moveRotationByNow=function (wise) {
        if(!nowM) return;

        let d=wise?1:-1;
        let rt=nowM.rotation.clone();
        nowM.rotation.set(rt.x,rt.y,rt.z+Math.PI/2*d);
        let r=nowM.rotation;

        let size=nowM.userData.nowData["3Dsize"];
        let s={width:size.height,height:size.width,deep:size.deep,alpha:r.z};
        let m3=this.scene2.getObjectByName(nowM.name);
        m3.geometry=new THREE.BoxGeometry(s.height,s.width,s.deep);
        m3.userData.nowData["3Dsize"]=s;
        m3.children[0].setLength(s.height);

        updateAllPoints(m3);
        updateMesh(nowM.name,s,nowM.position);

        nowM.userData.mTime=(+Date.now().toString());
        this.scene2.getObjectByName("points-offset").rotation.set(-r.x,-r.y,-r.z);

        this.updateThreeCamera(nowM.userData.nowData);

        this.saveLastAlpha(r.z);

        this.dispatchEvent({type:"updateMesh",message:Object.assign({updateType:1},nowM.userData.nowData)});
    };

    //返回一个随机ID根据时间
    function getRandomId(randomLength){
        return THREE.Math.generateUUID();
        // return Number(Math.random().toString().substr(3,randomLength) + Date.now()).toString(36)
    }


    //w.LookPointCloud=LookPointCloud;

    return{
        LookPointCloud:LookPointCloud,
        MeshModes:modes,
        OcclusionMode:occlusionMode,
        nowM:nowM
/*        scene1:function () {
            return scene1;
        },
        scene2:function () {
            return scene2;
        }*/
    }

})(window);













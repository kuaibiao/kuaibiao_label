import * as THREE from '../../libs/three.js-r105/build/three.module';
import { PointCloud } from './LookPointCloud2';
import { PhotoProject } from './projectphoto';

export const LookPointCloudList = (function f(w) {
    let LookPointCloudList = function(pcdUrls, dataUrls, config,imgUrls,imgConfigs) {
        let self = this;

        this.index = 0;
        this.lastIndex = 0;
        this.config = config;
        this.config.pcdUrl = pcdUrls[0];
        this.config.dataUrl = dataUrls[0];

        this.unSyncMeshSize = this.config.unSyncMeshSize;

        this.pcdUrls = pcdUrls;
        this.dataUrls = dataUrls;

        this.imgUrls=imgUrls;
        this.imgConfigs=imgConfigs;

        if(this.imgUrls){
            for(let i=0;i<this.imgUrls.length;i++){
                this.imgUrls[i].loadIndex=0;
                this.preImageLoad(this.imgUrls[i]);
                /*for(let j=0;j<this.imgUrls[i].length;j++){
                    let src=this.imgUrls[i][j];
                    this.imgUrls[i][j]=new THREE.TextureLoader().load(src);
                    this.imgUrls[i][j].basicSrc=src;
                }*/
            }
        }

        this.lpc = new PointCloud.LookPointCloud(config);
        this.pps = null;

        this.createPhotoProjects();
        this.bindEventsPhotoProjects();

        this.loader = new THREE.PCDLoader();
        this.loadIndex = this.index + 1;

        this.animateObject = null;
        this.clock = new THREE.Clock();
        this.needPlay = false;
        this.needPlayNext = 150;
        this.needPlayNextNum = 0;

        this.lineObjects = {};
        for (let i = 0; i < dataUrls.length; i++) {
            if (dataUrls[i]) this.saveLineObject(i, dataUrls[i]);
        }

        this.needShowLine = false;
        this.lineMaterial = new THREE.LineBasicMaterial({
            color: 0x00000ff,
        });

        this.moveMode = LookPointCloudList.moveMode.move;
        this._tmpMoveMode = LookPointCloudList.moveMode.move;

        this.loadComplete = function() {
            let ps = self.lpc.scene1.getObjectByName('points');
            if (self.pcdUrls[self.index].uuid !== ps.uuid) self.pcdUrls[self.index] = ps;
        };
        this.deleteMesh = function(res) {
            let rd = res.message;
            if (self.lineObjects[rd.index]) {
                self.lineObjects[rd.index][self.index] = undefined;
            }
            //self.updateLine();
        };
        this.deleteAllMesh=function () {
            for(let index in self.lineObjects){
                self.lineObjects[index][self.index] = undefined;
            }
            //self.updateLine();
        };
        this.updateMeshSize=function (res) {
            if(self.unSyncMeshSize) return;

            let rd = res.message;
            let sco={size:rd["3Dsize"],pos:rd["3Dcenter"],index:rd.index};

            sco.tv=rd.tv;

            let d=self.dataUrls[self.index];
            if(!d) return;
            for(let i=0;i<d.length;i++){
                if(d[i].index===sco.index){
                    let p1 = d[i]["3Dcenter"];
                    let p2 = sco.pos;
                    let vp1=new THREE.Vector3(p1.x,p1.y,p1.z);
                    let vp2=new THREE.Vector3(p2.x,p2.y,p2.z);
                    vp2.sub(vp1);

                    sco.dur={
                        xd:vp2.x>0?1:-1,
                        yd:vp2.y>0?1:-1,
                        zd:vp2.z>0?1:-1
                    }
                }
            }

            if(sco.dur)
                self.synchronizeRect(sco);
        };
        /*        this.createBoxComplete=function (res) {
            let rd = res.message;
        };*/
        this.updateMesh=function (res) {
            //self.updateLine();
        };

        this.lpc.addEventListener('loadComplete', this.loadComplete);
        this.lpc.addEventListener('deleteMesh', this.deleteMesh);
        this.lpc.addEventListener("deleteAllMesh",this.deleteAllMesh);
        this.lpc.addEventListener("updateMeshSize",this.updateMeshSize);
        this.lpc.addEventListener("updateMesh",this.updateMesh);
        //this.lpc.addEventListener("createBoxComplete",this.createBoxComplete)

        this.preLoad();

        this.animate();
    };
    Object.assign(LookPointCloudList.prototype, THREE.EventDispatcher.prototype);

    LookPointCloudList.moveMode = { move: 1, aod: 2, cover: 3 };

    //创建图片映射对象
    LookPointCloudList.prototype.createPhotoProjects=function() {
        if(!this.imgUrls||!this.imgConfigs) return;
        console.log(this.imgUrls,this.imgConfigs);

        this.pps=[];
        let data=this.dataUrls?this.dataUrls[0]:undefined;
        for(let i=0;i<this.imgConfigs.length;i++){
            Object.assign(this.imgConfigs[i],{imgUrl:this.imgUrls[0][i],dataUrl:data,index:i});
            //Object.assign(this.imgConfigs[i],{imgUrl:typeof this.imgUrls[0][i] === "string" ? this.imgUrls[0][i]:this.imgUrls[0][i].basicSrc,dataUrl:data,index:i});
            this.pps[i]=new PhotoProject(this.imgConfigs[i]);
        }

    }
    //建立pps和lpc的操作关联
    LookPointCloudList.prototype.bindEventsPhotoProjects=function() {
        if(!this.pps) return;

        let self=this;
        this.lpc.addEventListener("selectMesh",function(res){
            for(let i=0;i<self.pps.length;i++){
                self.pps[i].createRect(res.message);
            }
        });

        this.lpc.addEventListener("updateMesh",function(res) {
            for(let i=0;i<self.pps.length;i++){
                self.pps[i].updateRect(res.message);
            }
        });

        this.lpc.addEventListener("deleteMesh",function(res) {
            for(let i=0;i<self.pps.length;i++){
                self.pps[i].deleteRectByIndex(res.message.index);
            }
        });

        this.lpc.addEventListener("deleteAllMesh",function(res) {
            for(let i=0;i<self.pps.length;i++){
                self.pps[i].deleteRectAllRect();
            }
        });

        this.lpc.addEventListener("setMessage",function(res) {
            for(let i=0;i<self.pps.length;i++){
                self.pps[i].setMessage(res.message);
            }
        });
    }

    //预加载点云方法
    LookPointCloudList.prototype.preLoad = function() {
        let self = this;

        if (this.pcdUrls[this.loadIndex] && typeof this.pcdUrls[this.loadIndex] === 'string') {
            this.loader.load(this.pcdUrls[this.loadIndex], function(points) {
                if (!self.pcdUrls[self.loadIndex]) return;

                if (self.pcdUrls[self.loadIndex].uuid !== points.uuid) {
                    self.pcdUrls[self.loadIndex] = points;
                }

                //console.log(self.pcdUrls);

                if (self.pcdUrls[self.loadIndex + 1]) {
                    self.loadIndex++;
                    self.preLoad();
                    self.dispatchEvent({
                        type: 'preLoadTotal',
                        message: (self.loadIndex / (self.pcdUrls.length - 1)) * 100,
                    });
                } else {
                    self.dispatchEvent({ type: 'preLoadComplete', message: '' });
                    return;
                }
            });
        } else if (this.pcdUrls[this.loadIndex + 1]) {
            this.loadIndex++;
            this.preLoad();
            this.dispatchEvent({ type: 'preLoadTotal', message: (self.loadIndex / (self.pcdUrls.length - 1)) * 100 });
        } else {
            this.dispatchEvent({ type: 'preLoadComplete', message: ''});
            return;
        }
    };
    //预加载图片方法
    LookPointCloudList.prototype.preImageLoad=function(urls){
        if(!this.imgUrls) return;

        let self=this;

        if(urls[urls.loadIndex] && typeof urls[urls.loadIndex] === 'string'){
            let src=urls[urls.loadIndex];
            urls[urls.loadIndex]=new THREE.TextureLoader().load(src,function (texture) {
                if(urls[urls.loadIndex+1]){
                    urls.loadIndex++;
                    self.preImageLoad(urls);
                }else return;
            });
            urls[urls.loadIndex].basicSrc=src;
        }else if(urls[urls.loadIndex+1]){
            urls.loadIndex++;
            this.preImageLoad(urls);
        }else{
            return;
        }

    };


    //加载下一个
    LookPointCloudList.prototype.next = function() {
        if (!this.lpc.status.loaded) return;

        this.lastIndex = this.index;
        this.index = this.index + 1 >= this.pcdUrls.length ? 0 : this.index + 1;

        this.save(this.lastIndex);
        this.moveMove();

        this.isShowLine();

        this.dispatchEvent({type:"indexChange",message:this.index})
    };
    //加载下一个并copy当前数据到下一帧
    LookPointCloudList.prototype.nextCopy = function() {
        if (!this.lpc.status.loaded) return;

        this.lastIndex = this.index;
        this.index = this.index + 1 >= this.pcdUrls.length ? 0 : this.index + 1;

        this.save(this.lastIndex);

        this.moveAod();

        this.isShowLine();

        this.dispatchEvent({type:"indexChange",message:this.index});
    }
    LookPointCloudList.prototype.nextPlay=function () {
        if(!this.lpc.status.loaded) return;

        this.lastIndex=this.index;
        this.index=(this.index+1>=this.pcdUrls.length)?0:this.index+1;

        this.moveMove();

        this.isShowLine();

        if(this.pps){
            for(let i=0;i<this.pps.length;i++){
                this.pps[i].showSceneBackground();
            }
        }

        this.dispatchEvent({type:"indexChange",message:this.index});
    }
    //加载上一个
    LookPointCloudList.prototype.prev = function() {
        if (!this.lpc.status.loaded) return;

        this.lastIndex = this.index;
        this.index = this.index - 1 < 0 ? this.pcdUrls.length - 1 : this.index - 1;

        this.save(this.lastIndex);
        this.moveMove();

        this.isShowLine();

        this.dispatchEvent({type:"indexChange",message:this.index});
    };
    //加载某一个
    LookPointCloudList.prototype.moveTo = function(index) {
        if (!this.lpc.status.loaded) return;
        if (!+index&&index!=0) return;
        if (+index === this.index) return;

        this.lastIndex = this.index;
        if((index-1)==this.lastIndex){
            this.save(this.lastIndex);
        }

        if (+index >= this.pcdUrls.length) this.index = this.pcdUrls.length - 1;
        else if (+index < 0) this.index = 0;
        else this.index = +index;

        this.save(this.lastIndex);
        this.moveMove();

        this.dispatchEvent({type:"indexChange",message:this.index});
    };

    //本地保存当前数据
    LookPointCloudList.prototype.save = function(index) {
        let d = this.lpc.getAllRects();

        if(this.dataUrls[index-1]){
            let dl=this.dataUrls[index-1];
            for(let i=0;i<d.length;i++){
                for(let j=0;j<dl.length;j++){
                    if(d[i].index===dl[j].index){
                        let v1 = d[i]["3Dcenter"];
                        let v2 = dl[j]["3Dcenter"];
                        d[i].lastOffset = new THREE.Vector3(v1.x-v2.x,v1.y-v2.y,v1.z-v2.z);
                    }
                }
            }
        }

        if(this.pps){
/*            for(let i=0;i<this.pps.length;i++){
                this.pps[i].getAllRectDomData();
            }*/

            for(let i=0;i<d.length;i++){
                let di=d[i];
                di.cubeMap=[];
                di.imageMap=[];

                for(let j=0;j<this.pps.length;j++){
                    let pp=this.pps[j];
                    let pd=pp.getRectDataByIndex(di.index);
                    let bbox=pp.getRectDomDataByIndex(di.index);

                    if(pd){
                        let vd1=pd.data["3Dcenter"];
                        let vd2=pd.data["3Dcenter2"];
                        let v1=new THREE.Vector3(vd1.x,vd1.y,vd1.z);
                        let v2=new THREE.Vector3(vd2.x,vd2.y,vd2.z);
                        v1.add(v2);


                        di.cubeMap[j]={
                            imageUrl:pp.imgUrl,
                            cubePoints:pd.boxRectData?pd.boxRectData:undefined,
                            bbox:bbox
                        };
                        di.imageMap[j]={
                            imageUrl:pp.imgUrl,
                            "3Dcenter":v1,
                            "3Dcenter2":new THREE.Vector3(),
                            "3Dscale":pd.data["3Dscale"],
                            "3Dsize":pd.data["3Dsize"],
                            box_type:pp.box_type
                        };
                    }


                }

            }
        }

        this.dataUrls[index] = d;
        this.saveLineObject(index, d);
    };

    //同步修改过的立方体大小
    LookPointCloudList.prototype.synchronizeRect=function(sco){

        let dus=this.dataUrls;
        for(let i=0;i<dus.length;i++){
            let du=dus[i];
            if(!du) return;
            for(let j=0;j<du.length;j++){
                if(du[j].index===sco.index){

                    let s1 = du[j]["3Dsize"];
                    let s2 = sco.size;

                    let p1=du[j]["3Dcenter"];
                    let p2=sco.pos;

                    let dur=sco.dur;

                    let lx=(s2.width-s1.width)/2;let ly=(s2.height-s1.height)/2;let lz=(s2.deep-s1.deep)/2;

                    let lv=new THREE.Vector3(Math.abs(ly)*dur.xd,Math.abs(lx)*dur.yd,Math.abs(lz)*dur.zd);
                    lv.applyEuler(new THREE.Euler(0,0,s2.alpha));


                    du[j]["3Dcenter"].x+=lv.x;
                    du[j]["3Dcenter"].y+=lv.y;
                    du[j]["3Dcenter"].z+=lv.z;

                    du[j]["3Dsize"].width=s2.width;
                    du[j]["3Dsize"].height=s2.height;
                    du[j]["3Dsize"].deep=s2.deep;
                }
            }
        }
    }

    //判断模式进行处理
    LookPointCloudList.prototype.switchMoveMode = function() {

        if(this.index<this.lastIndex||(Math.abs(this.index-this.lastIndex)>1)){
            if(this.dataUrls[this.index]){
                if(this.dataUrls[this.index].length)
                    this.moveMode=LookPointCloudList.moveMode.move;
            }
            if(this.dataUrls[this.lastIndex]){
                if(this.dataUrls[this.lastIndex].length)
                    this.moveMode=LookPointCloudList.moveMode.move;
            }
            this.save(this.lastIndex);
            this.moveMove();
        }else{
            switch (this.moveMode) {
                case LookPointCloudList.moveMode.aod: {
                    this.moveAod();
                    break;
                }
                case LookPointCloudList.moveMode.move: {
                    this.moveMove();
                    break;
                }
                case LookPointCloudList.moveMode.cover: {
                    this.moveCover();
                    break;
                }
            }
        }

        this.isShowLine();

        this.dispatchEvent({ type: 'indexChange', message: this.index });
    };
    LookPointCloudList.prototype.isShowLine = function() {
        if (this.needShowLine) {
            this.showLine();
        } else {
            this.hideLine();
        }
    };
    LookPointCloudList.prototype.moveAod = function() {
        this.save(this.lastIndex);
        let id=this._copyLastData();

        this.lpc.load(this.pcdUrls[this.index],id,true);
        this.dataUrls[this.index]=id;
        this.loadPhotoProjects(this.index);
    };

    //如果下一帧有数据，略过，补齐这一帧有而下一帧没有的数据,且以某种方式自动跟踪
    LookPointCloudList.prototype._copyLastData=function(){
        let td = this.dataUrls[this.index]?JSON.parse(JSON.stringify(this.dataUrls[this.index])):[];
        let id = JSON.parse(JSON.stringify(this.dataUrls[this.lastIndex]));

        for(let i=0;i<td.length;i++){
            for(let j=0;j<id.length;j++){
                if(td[i].index===id[j].index) {
                    id[j]["3Dcenter"] = td[i]["3Dcenter"];
                    id[j]["3Dsize"] = td[i]["3Dsize"];
                    console.log(td[i]);
                    if(td[i].imageMap)
                        id[j].imageMap = td[i].imageMap;
                    if(td[i].cubeMap)
                        id[j].cubeMap = td[i].cubeMap;



                    id[j].lastOffset=null;
                    td[i].notNeedAddNext=true;
                }
            }
        }

        for(let i=0;i<td.length;i++){
            if(!td[i].notNeedAddNext)
                id.push(td[i]);
        }

        for(let i=0;i<id.length;i++){
            if(id[i].lastOffset){
                let lo = id[i].lastOffset;
                id[i]["3Dcenter"].x+=lo.x;
                id[i]["3Dcenter"].y+=lo.y;
                id[i]["3Dcenter"].z+=lo.z;
                if(this.pps){
                    for(let j=0;j<this.pps.length;j++){
                        if(id[i].imageMap[j]){
                            id[i].imageMap[j]["3Dcenter"].x+=lo.x;
                            id[i].imageMap[j]["3Dcenter"].y+=lo.y;
                            id[i].imageMap[j]["3Dcenter"].z+=lo.z;
                        }
                    }
                }
            }
        }

        return id;
    };

    LookPointCloudList.prototype.moveMove = function() {
        this.lpc.load(this.pcdUrls[this.index], this.dataUrls[this.index], true);
        this.loadPhotoProjects(this.index);
    };
    LookPointCloudList.prototype.moveCover = function() {
        this.save(this.lastIndex);
        this.lpc.load(this.pcdUrls[this.index], JSON.parse(JSON.stringify(this.dataUrls[this.lastIndex])), true);
        this.loadPhotoProjects(this.index);
    };

    LookPointCloudList.prototype.loadPhotoProjects=function(index){
        if(!this.pps) return;

        for(let i=0;i<this.pps.length;i++){
            this.pps[i].load(this.imgUrls[index][i],this.dataUrls[index]);
        }
    };

    //保存到lineObject
    LookPointCloudList.prototype.saveLineObject = function(index, d) {
        for (let i = 0; i < d.length; i++) {
            let v3 = d[i]['3Dcenter'];
            if (!this.lineObjects[d[i].index]) {
                this.lineObjects[d[i].index] = [new THREE.Vector3(v3.x, v3.y, v3.z)];
            } else {
                this.lineObjects[d[i].index][index] = new THREE.Vector3(v3.x, v3.y, v3.z);
            }
        }
    };

    //本地保存现有数据
    LookPointCloudList.prototype.saveNow = function() {
        //this.dataUrls[this.index]=this.lpc.getAllRects();
        this.save(this.index);
    };
    //返回当前所有数据
    LookPointCloudList.prototype.getData = function() {
        this.saveNow();
        return this.dataUrls;
    };
    LookPointCloudList.prototype.getLineData = function() {
        let rd = [];

        for (var a in this.lineObjects) {
            rd.push({
                index: a,
                data: this.lineObjects[a].filter(function(s) {
                    return s;
                }),
            });
        }
        return rd;
    };

    //销毁
    LookPointCloudList.prototype.destroy = function() {
        this.lpc.removeEventListener('loadComplete', this.loadComplete);
        this.lpc.dispose();
        this.lpc = null;

        if(this.pps){
            for(let i=0;i<this.pps.length;i++){
                this.pps[i].dispose();
            }
            this.pps=null;
        }

        this.loader = null;
        this.config = {};
        this.pcdUrls = [];
        this.dataUrls = [];
        this.playEnd();

        this._listeners = {};
    };
    LookPointCloudList.prototype.playEnd = function() {
        if (this.animateObject) {
            cancelAnimationFrame(this.animateObject);
        }
    };

    //序列帧播放和暂停
    LookPointCloudList.prototype.play = function() {
        if (this.loadIndex < this.pcdUrls.length - 1) return;

        //if(!this.dataUrls[this.index])
        this.save(this.index);

        this.needPlay = true;

        this.dispatchEvent({ type: 'play', message: '' });
    };
    LookPointCloudList.prototype.pause = function() {
        this.needPlay = false;
        this.needPlayNextNum = 0;

        if(this.pps){
            for(let i=0;i<this.pps.length;i++){
                this.pps[i].hideSceneBackground();
            }
        }

        this.dispatchEvent({ type: 'pause', message: '' });
    };

    //生成线
    LookPointCloudList.prototype.updateLine = function() {
        //if(!this.dataUrls[this.index])
        this.save(this.index);

        let d = this.dataUrls[this.index];

        //for(let i=0;i<d.length;i++){
        for (let a in this.lineObjects) {
            if (this.lineObjects[a] instanceof Array) {
                let g = new THREE.Geometry();
                g.vertices = this.lineObjects[a].filter(function(s) {
                    return s;
                });

                let line = new THREE.Line(g, this.lineMaterial.clone());
                line.name = 'line-' + a;
                let lastLine = this.lpc.scene1.getObjectByName(line.name);
                if(lastLine){
                    lastLine.geometry.dispose();
                    lastLine.material.dispose();
                    this.lpc.scene1.remove(lastLine);
                }
                this.lpc.scene1.add(line);
            }
        }
        //}
    };
    //销毁线
    LookPointCloudList.prototype.clearLine = function() {
        for (let a in this.lineObjects) {
            let l = this.lpc.scene1.getObjectByName('line-' + a);
            if (l) {
                l.geometry.dispose();
                l.material.dispose();
                this.lpc.scene1.remove(l);
                l = null;
            }
        }
    };
    //显示线
    LookPointCloudList.prototype.showLine = function() {
        this.needShowLine = true;
        this.clearLine();
        this.updateLine();
    };
    //隐藏线
    LookPointCloudList.prototype.hideLine = function() {
        this.needShowLine = false;
        this.clearLine();
    };

    //删除全部数据
    LookPointCloudList.prototype.clearData = function() {
        this.clearLine();
        this.lineObjects = {};
        this.dataUrls = [];
        this.lpc.deleteAllMesh();
    };

    //删除当前在所有针的数据
    LookPointCloudList.prototype.deleteMeshInLineByNow=function(){
        let rd=this.lpc.deleteMeshByNow();

        if(rd){
            this.lineObjects[rd.index]=null;
            delete this.lineObjects[rd.index];

            let lastLine = this.lpc.scene1.getObjectByName("line-"+rd.index);
            if(lastLine){
                lastLine.geometry.dispose();
                lastLine.material.dispose();
                this.lpc.scene1.remove(lastLine);
            }

            let dus=this.dataUrls;

            for(let i=0;i<dus.length;i++){
                let du=dus[i];
                if(du){
                    for(let j=0;j<du.length;j++){
                        if(rd.index===du[j].index){
                            du.splice(j,1);
                        }
                    }
                }
            }
        }
    };


    LookPointCloudList.prototype.animate = function() {
        let self = this;

        window.requestAnimationFrame(function step(timestamp) {
            self.animateObject = window.requestAnimationFrame(step);

            if (self.needPlay) {
                let d = self.clock.getDelta() * 1000;
                self.needPlayNextNum += d;
                self.moveMode = LookPointCloudList.moveMode.move;

                if (self.needPlayNextNum >= self.needPlayNext) {
                    self.nextPlay();
                    /*                    if(self.needShowLine)
                        self.updateLine();*/

                    self.needPlayNextNum = 0;
                }
            }
        });
    };

    return LookPointCloudList;
})(window);

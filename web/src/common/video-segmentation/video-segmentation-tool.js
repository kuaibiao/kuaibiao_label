//视频分割标注工具
import EventDispatcher from './EventDispatcher.js';
import videojs from 'video.js'
import 'video.js/dist/video-js.css'
import enJson from 'video.js/dist/lang/en.json'
import zhJson from 'video.js/dist/lang/zh-CN.json'
import '../../libs/jquery-ui/jquery-ui.min.js'

var VideoSegmentationTool = (function (w) {
    let VideoSegmentationTool = function (optional) {        
        this.btnSaveModifyObj=null;     //按钮:'保存修改'
        this.newLabelObj = null;        //按钮：新建
        this.createLabelObj = null;     //按钮：保存
        this.cancelSaveObj = null;      //按钮：取消
        this.currentTimeObj=null;       //当前进度时间
        this.totalTimeObj=null;         //视频总时长
        this.resultListObj=null;        //结果列表         
        this.nowData=null;              //当前选中的结果1条        
        this.element=null;              //视频区域
        this.el=null;
        this.isNew=false;               //点击过'新建'等待'保存'        
        this.setTimesObj=null;          //设置时间外框
        this.src=optional.src;        
        this.width=optional.width;
        this.height=optional.height;
        this.step=optional.step?optional.step:0.1;        
        this.data={}; //用于'回显'的数据
        if(optional.data){
            for(let i=0;i<optional.data.length;i++){
                this.data[String(optional.data[i].id)]=optional.data[i];
            }
        }
        this.is_edit = true; //是否允许使用编辑功能'新建,保存修改,删除,清空标注结果等..'
        if(optional && typeof(optional.is_edit) != 'undefined'){
            this.is_edit = optional.is_edit;
        }
        this.user_id=''; //当前用户id
        if(optional.user_id){
            this.user_id=optional.user_id;
        }
        this.startTimeDom;  //设为开始时间
        this.endTimeDom;    //设为结束时间
        this.startTimeTmp=0;
        this.endTimeTmp=0;
        this.dataPlaying=false;
        this.basicMessage={};
        this.objectEvents={
            onSeeked:this.onSeeked.bind(this),
            onTSSelect:this.onTSSelect.bind(this),
            onTESelect:this.onTESelect.bind(this)
        }
        if(typeof optional.element === "string"){
            this.element=document.getElementById(optional.element);
        }else{
            this.element=optional.element;
        }
        this.el = $(this.element).find('#J-video-container')[0];
        this.video=null;

        this.lang = 'en-US'; //语言-本地化处理-默认'英文'
        this.langJson = {'en-US':enJson};
        if(optional.lang){
            let tmp = {};
            if(optional.lang=='en-US'){
                this.lang = optional.lang;
                tmp['en-US'] = enJson; //英文翻译
                this.langJson = tmp;
            }else if(optional.lang=='zh-CN'){
                this.lang = optional.lang;
                tmp['zh-CN'] = zhJson; //中文翻译
                this.langJson = tmp;
            }
        }

        this.getSources();
        if(this.sources){
            var _box = $(this.element).find('#J-video-container')[0];            
            this.video=videojs(_box,{
                languages: this.langJson,
                width:this.width,
                height:this.height,
                sources: this.sources,
                preload:true,
                controlBar: {
                    children: [
                        "playToggle",                        
                        "durationDisplay",
                        "timeDivider",
                        "currentTimeDisplay",
                        "progressControl",
                        "audioTrackButton",
                        "fullscreenToggle",
                        {
                            name: 'volumePanel',
                            inline: false,
                        }
                    ]
                }
            });
            this.init();            
            this.video.playbackRate(1); //设置默认播放速度            
        }
    }
    
    Object.assign(VideoSegmentationTool.prototype, EventDispatcher.prototype);

    //功能：设置新的视频地址
    VideoSegmentationTool.prototype.setVideoSrcFun = function (obj) {
        var self = this;
        //1.清空上一个视频的所有数据
        self.clearAllLabelFun();
        //2.接收:新视频数据
        var _src = "";
        if(obj && obj.src){_src = obj.src;}
        if(obj.data){ //用于'回显'
            self.data = {};
            for(let i=0; i<obj.data.length; i++){
                self.data[String(obj.data[i].id)] = obj.data[i];
            }
        }
        //3.新视频:初始化
        if(self.video != null){            
            self.src = _src;
            self.getSources();
            self.video.dispose();
            $(self.element).html('<video id="J-video-container" class="video-js new-2039" controls preload="none"></video>');            
            if(self.sources){                
                var _box = $(self.element).find('#J-video-container')[0];                
                self.video=videojs(_box,{
                    languages: self.langJson,
                    width:self.width,
                    height:self.height,
                    sources: self.sources,
                    preload:true,
                    controlBar: {
                        children: [
                            "playToggle",                            
                            "durationDisplay",
                            "timeDivider",
                            "currentTimeDisplay",
                            "progressControl",
                            "audioTrackButton",
                            "fullscreenToggle",
                            {
                                name: 'volumePanel',
                                inline: false,
                            }
                        ]
                    }
                });
                self.init();
                self.video.playbackRate(1); //设置默认播放速度
            }
        }
    };

    //功能：判断播放文件格式
    VideoSegmentationTool.prototype.getSources=function () {
        let ln=this.src.split(".")[this.src.split(".").length-1];
        switch (ln) {
            case "mp4":{
                this.sources=[{
                    src: this.src,
                    type: 'video/mp4'
                }];
                break;
            }
            case "webm":{
                this.sources=[{
                    src: this.src,
                    type: 'video/webm'
                }];
                break;
            }
            case "ogg":{
                this.sources=[{
                    src: this.src,
                    type: 'video/ogg'
                }];
                break;
            }
            default:{
                this.sources=[{
                    src: this.src,
                    type: 'video/mp4'
                }];                
                break;
            }
        }
    };

    //功能：初始化
    VideoSegmentationTool.prototype.init = function () {
        var self = this;
        self.initDom();        
        self.initEvents();        
        self.isShowHideSaveModify();        
        self.showCurrentTime(0);
    };

    //功能：新建
    VideoSegmentationTool.prototype.newLabelFun = function(){
        var self = this;        
        //1.不选中任务结果
        self.isNew = true;
        self.unSelectLabel();
        //2.通知更新数据
        self.dispatchEvent({type:"updateDataEvent",message:null});
        //3.'新建,保存修改,取消等按钮'是否显示
        self.isShowHideSaveModify();
    };

    //功能：'新建,保存修改,取消等按钮'是否显示
    VideoSegmentationTool.prototype.isShowHideSaveModify=function () {
        var self = this;
        setTimeout(function(){
            if(self.is_edit === false){
                $(self.newLabelObj).hide();         //新建
                $(self.btnSaveModifyObj).hide();    //保存修改
                $(self.setTimesObj).hide();         //设为'开始时间' - 设为'结束时间' 的外框                
                $(self.createLabelObj).hide();      //保存'新建'
                $(self.cancelSaveObj).hide();       //取消
            }else{            
                if(self.nowData && self.nowData.id){
                    $(self.btnSaveModifyObj).show();    //保存修改
                    $(self.cancelSaveObj).show();       //取消
                    $(self.newLabelObj).show();         //新建
                    $(self.createLabelObj).hide();      //保存'新建'
                    $(self.setTimesObj).show();         //设为'开始时间' - 设为'结束时间' 的外框                    
                }else{
                    $(self.btnSaveModifyObj).hide();    //保存修改
                    $(self.cancelSaveObj).hide();       //取消
                    if(self.isNew === true){
                        $(self.createLabelObj).show();  //保存'新建'
                        $(self.cancelSaveObj).show();   //取消
                        $(self.setTimesObj).show();         //设为'开始时间' - 设为'结束时间' 的外框                        
                    }else{
                        $(self.newLabelObj).show();     //新建
                        $(self.cancelSaveObj).hide();   //取消
                        $(self.createLabelObj).hide();  //保存'新建'
                        $(self.setTimesObj).hide();         //设为'开始时间' - 设为'结束时间' 的外框                        
                    }
                }
            }
        },100);
    };
    
    //功能：删除当前结果中的'标签'    
    VideoSegmentationTool.prototype.delCurrentDataTag=function (text) {
        var self = this;        
        if(text!=''){
            if(self.nowData && self.nowData.attr && self.nowData.attr.text){
                var textArr = self.nowData.attr.text.slice(0);
                if(textArr.length > 0){
                    textArr.splice(textArr.indexOf(text),1);
                }
                self.nowData.attr.text = textArr.slice(0);                
                self.dispatchEvent({type:"setTagEvent",message:self.nowData});//更新结果中的'标签'和'色条'
            }
        }
    };
    
    //功能：删除当前结果中的'标签'
    VideoSegmentationTool.prototype.delCurrentDataTagByIndex = function (index) {
        var self = this;        
        if(index >= 0){
            if(self.nowData && self.nowData.attr && self.nowData.attr.text){
                var textArr = self.nowData.attr.text.slice(0);
                var codeArr = self.nowData.attr.code.slice(0);
                var categoryArr = self.nowData.attr.category.slice(0);
                if(textArr.length > 0){
                    textArr.splice(index,1);
                }
                if(codeArr.length > 0){
                    codeArr.splice(index,1);
                }
                if(categoryArr.length > 0){
                    categoryArr.splice(index,1);
                }
                self.nowData.attr.text = textArr.slice(0);
                self.nowData.attr.code = codeArr.slice(0);
                self.nowData.attr.category = categoryArr.slice(0);
                self.dispatchEvent({type:"setTagEvent",message:self.nowData});//更新结果中的'标签'和'色条'
            }
        }
    };

    //创建相关UI
    VideoSegmentationTool.prototype.initDom=function () {
        var self = this;
        //1.操作按钮
        let $el=$(this.video.el());
        setTimeout(function(){
            self.startTimeDom = $(self.setTimesObj).find('.set-time-start');  //设置时间-开始时间
            self.endTimeDom = $(self.setTimesObj).find('.set-time-end');    //设置时间-结束时间            
            $(self.startTimeDom).html('00:00:00.00');
            $(self.endTimeDom).html('00:00:00.00');
        },100);

        //2.标尺
        setTimeout(function(){
            $el.append(
                "<div class='video-label-select-length-plane' data='Staff gauge'>" +
                    "<div class='video-label-select-length-plane2'>" +
                        "<div class='video-label-select-length-plane3'>" +
                            "<div class='video-label-select-length'></div>" +
                        "</div>" +
                        "<div class='video-label-select-length-bar1'>" +
                            "<div class='video-label-select-length-bar-time'></div>"+
                        "</div>"+
                        "<div class='video-label-select-length-bar2'>" +
                            "<div class='video-label-select-length-bar-time'></div>"+
                        "</div>"+
                    "</div>"+
                "</div>"
            );
            self.$vlslP3=$el.find(".video-label-select-length-plane3");
            self.$vlsl=$el.find(".video-label-select-length");
            self.$vlslBar1=$el.find(".video-label-select-length-bar1");
            self.$vlslBar2=$el.find(".video-label-select-length-bar2");

            self.$vlslBar1.draggable({ axis: "x",containment: "parent",
                drag:function () {
                    let $a=$(this);
                    let $b=self.$vlslBar2;
                    let a = (parseFloat($a.css("left")))/(self.$vlslP3.width());
                    let b = (parseFloat($b.css("left")))/(self.$vlslP3.width());                    
                    self.updateDataAndUi(a,b);
                    let s=self.video.duration()*a;
                    self.video.currentTime(s);
                    $a.find(".video-label-select-length-bar-time").text(formatTime(s)).show();
                },
                stop:function () {
                    let $a=$(this);
                    let $b=self.$vlslBar2;
                    let a = (parseFloat($a.css("left")))/(self.$vlslP3.width());
                    let b = (parseFloat($b.css("left")))/(self.$vlslP3.width());
                    self.updateDataAndUi(a,b);
                    let s=self.video.duration()*a;
                    self.video.currentTime(s);
                    $a.find(".video-label-select-length-bar-time").text(formatTime(s)).hide();
                }
            });
            self.$vlslBar2.draggable({ axis: "x",containment: "parent",
                drag:function () {
                    let $b=$(this);
                    let $a=self.$vlslBar1;
                    let a = (parseFloat($a.css("left")))/(self.$vlslP3.width());
                    let b = (parseFloat($b.css("left")))/(self.$vlslP3.width());
                    self.updateDataAndUi(a,b);
                    let s=self.video.duration()*b;
                    self.video.currentTime(s);
                    $b.find(".video-label-select-length-bar-time").text(formatTime(s)).show();
                },
                stop:function () {
                    let $b=$(this);
                    let $a=self.$vlslBar1;
                    let a = (parseFloat($a.css("left")))/(self.$vlslP3.width());
                    let b = (parseFloat($b.css("left")))/(self.$vlslP3.width());
                    self.updateDataAndUi(a,b);
                    let s=self.video.duration()*b;
                    self.video.currentTime(s);
                    $b.find(".video-label-select-length-bar-time").text(formatTime(s)).hide();
                }
            });
        },100);
    };

    //功能：初始化,绑定各种事件.
    VideoSegmentationTool.prototype.initEvents=function () {
        let self=this;
        self.video.on("durationchange",function () {
            //1.视频暂停播放
            self.video.pause();
            //2.显示视频时长            
            $(self.totalTimeObj).find('.time').html(formatTime(self.video.duration()));
        });
        
        //点击进度条时触发此事件
        this.video.on("seeked",function(res){                        
            let ct = self.video.currentTime();            
            self.showCurrentTime(ct);
            //self.video.pause();
        });

        //播放中
        this.video.on("timeupdate",function (res) {
            let ct = self.video.currentTime();
            self.showCurrentTime(ct,'00');
            if(self.nowData&&self.dataPlaying){
                if(ct>=self.nowData.endTime){
                    self.video.currentTime(self.nowData.endTime);
                    self.video.pause();
                    self.dataPlaying=false;
                }
            }
            //判断播放结束时再次更新'当前进度'的时间
            if(self.video.paused()){
                self.showCurrentTime(ct);
            }            
        });

        //暂停
        this.video.on("pause",function(res){
             let ct = self.video.currentTime();
             self.showCurrentTime(ct);
        });        

        //3.设为开始-设为结束:绑定事件.
        setTimeout(function(){
            self.startTimeDom.on("click",self.objectEvents.onTSSelect);
            self.endTimeDom.on("click",self.objectEvents.onTESelect);
        },200);
        //4.回显结果
        setTimeout(function(){
            for(let index in self.data) {
                self.dispatchEvent({type:"createLabelEvent",message:self.data[index]});
            }
        },200);
    };

    //功能：显示'当前进度'的时间
    VideoSegmentationTool.prototype.showCurrentTime = function(time,sec){
        var self = this;
        //1.显示'当前进度时间'
        var s = formatTime(time);
        if(sec){ //sec='00'时代表播放中毫秒数始终显示为00
            if(s.indexOf('.') != -1){
                s = s.slice(0, s.indexOf('.'))+'.00';
            }
        }        
        $(self.currentTimeObj).find('.time').html(s);
        //2.移除设置开始结束时间按钮样式
        $(self.endTimeDom).removeClass('video-label-time-select');
        $(self.startTimeDom).removeClass('video-label-time-select');
    }
    
    VideoSegmentationTool.prototype.removeEvents=function(){
        this.startTimeDom.off("click",this.objectEvents.onTSSelect);
        this.endTimeDom.off("click",this.objectEvents.onTESelect);
    }

    VideoSegmentationTool.prototype.onSeeked=function(){
        if(this.startTimeDom.hasClass("video-label-time-select")){
            this.startTimeTmp=this.video.currentTime();
            this.startTimeDom.text(formatTime(this.startTimeTmp));
        }
        if(this.endTimeDom.hasClass("video-label-time-select")){
            this.endTimeTmp=this.video.currentTime();
            this.endTimeDom.text(formatTime(this.endTimeTmp));
        }
    }

    //功能：设置开始时间
    VideoSegmentationTool.prototype.onTSSelect=function(){
        this.unSelectTimeDom();
        this.startTimeDom.addClass("video-label-time-select");
        this.startTimeTmp=this.video.currentTime();
        this.startTimeDom.text(formatTime(this.startTimeTmp));
    }

    //功能：设置结束时间
    VideoSegmentationTool.prototype.onTESelect=function(){
        this.unSelectTimeDom();
        this.endTimeDom.addClass("video-label-time-select");
        this.endTimeTmp=this.video.currentTime();
        this.endTimeDom.text(formatTime(this.endTimeTmp));
    }

    //功能：创建一个新的分割结果:lable对象
    VideoSegmentationTool.prototype.createLabelFun=function () {
        var self = this;
        if(!self.video.duration()) return;
        //1.保存新建
        let id = generateUUID();
        self.doLabel(id);
        self.unSelectTimeDom();
        self.unSelectLabel(); //不选中任务结果
        self.dispatchEvent({type:"createLabelEvent",message:this.data[id]});
        //2.新建为'假'
        self.isNew = false; 
    };

    //功能：新的分割结果:数据结构
    VideoSegmentationTool.prototype.doLabel=function(id){
        if(this.endTimeTmp<this.startTimeTmp){
            let tmpTime=this.startTimeTmp;
            this.startTimeTmp=this.endTimeTmp;
            this.endTimeTmp=tmpTime;
        }
        this.data[id]={
            id:id,
            isLabel:true,
            startTime:this.startTimeTmp,
            endTime:this.endTimeTmp,
            length:this.endTimeTmp-this.startTimeTmp,
            duration:this.video.duration(),
            startTimeRatio:((this.startTimeTmp/this.video.duration())*100?(this.startTimeTmp/this.video.duration())*100:0)+"%",
            endTimeRatio:((this.endTimeTmp/this.video.duration())*100?(this.endTimeTmp/this.video.duration())*100:0)+"%",
            lengthRatio:(((this.endTimeTmp-this.startTimeTmp)/this.video.duration())*100?((this.endTimeTmp-this.startTimeTmp)/this.video.duration())*100:0)+"%",
            cTime:Date.now(), // 创建时间
            cBy:this.user_id, // 创建人
            mTime:'', // 修改时间
            mBy:'', // 修改人
            type:'video-segmentation', //标注类型
        };        
    }

    //功能：选中一个lable对象
    VideoSegmentationTool.prototype.selectLabel=function (id) {
        var self = this;
        if(self.data[id]){
            self.nowData=self.data[id];
            self.startTimeTmp=self.nowData.startTime;
            self.endTimeTmp=self.nowData.endTime;
            self.startTimeDom.text(formatTime(self.startTimeTmp));
            self.endTimeDom.text(formatTime(self.endTimeTmp));
            self.video.currentTime(self.startTimeTmp);
            let c=self.nowData.attr?self.nowData.attr.color:"#ffff00";
            //let w=$(self.video.el()).find(".vjs-progress-control").width()*(+(self.nowData.lengthRatio.split("%")[0])/100)<1?"1px":self.nowData.lengthRatio;
            if(this.$vlsl){
                let w=this.$vlslP3.width()*(+(this.nowData.lengthRatio.split("%")[0])/100)<1?"1px":this.nowData.lengthRatio;
                let ml=-(this.$vlslBar1.width()/2);
                self.$vlsl.css({width:w,left:self.nowData.startTimeRatio,background:c});
                this.$vlslBar1.css({left:this.$vlslP3.width()*(this.nowData.startTimeRatio.split("%")[0]/100),background:c,marginLeft:ml});
                this.$vlslBar2.css({left:this.$vlslP3.width()*(this.nowData.endTimeRatio.split("%")[0]/100),background:c,marginLeft:ml})
            }            
            //1.更新记录的值为选中
            self.updateDataSetSelectedById(id);                        
            //2.与tag.vue组件通信
            self.dispatchEvent({type:"showTextInTagVueEvent",message:self.nowData});
        }
        self.isShowHideSaveModify();
    };
    //功能：更新label信息在UI上的展示
    VideoSegmentationTool.prototype.updateDataAndUi=function(a,b){
        a=this.clamp(0,1,a);
        b=this.clamp(0,1,b);
        if(a>b){
            let tmp=a;
            a=b;
            b=tmp;
        }
        let d=this.video.duration();
        let data=this.nowData;
        this.startTimeTmp=d*a;
        this.endTimeTmp=d*b;
        /*      
        data.startTime=this.startTimeTmp;
        data.endTime=this.endTimeTmp;
        data.length=(this.endTimeTmp-this.startTimeTmp);
        data.duration=d;
        data.startTimeRatio=a*100+"%";
        data.endTimeRatio=b*100+"%";
        data.lengthRatio=(((this.endTimeTmp-this.startTimeTmp)/d)*100?((this.endTimeTmp-this.startTimeTmp)/d)*100:0)+"%";
        */
        let lr=(((this.endTimeTmp-this.startTimeTmp)/d)*100?((this.endTimeTmp-this.startTimeTmp)/d)*100:0)+"%";
        let c=data.attr?data.attr.color:"#ffff00";
        let w=$(this.video.el()).find(".video-label-select-length-plane3").width()*(+(lr.split("%")[0])/100)<1?"1px":lr;
        this.$vlsl.css({width:w,left:a*100+"%",background:c});
        this.startTimeDom.text(formatTime(this.startTimeTmp));
        this.endTimeDom.text(formatTime(this.endTimeTmp));
        this.video.pause();
    };
    //功能：清空标注
    VideoSegmentationTool.prototype.clearAllLabelFun = function(){
        var self = this;
        self.data = {};
        self.isNew = false;        
        self.nowData=null;
        self.dispatchEvent({type:"updateDataEvent",message:null});
        self.isShowHideSaveModify();        
        self.unSelectTimeDom();
        self.unSelectLabel();
    };
    VideoSegmentationTool.prototype.clamp=function(min,max,v){
        v=v<min?min:v;
        v=v>max?max:v;
        return v;
    };
    //功能：当前id记录selected为true,其它的记录selected为false.
    VideoSegmentationTool.prototype.updateDataSetSelectedById=function (id) {
        var self = this;        
        if(self.data[String(id)]){        
            self.data[id]['selected'] = true;
            for(let i in self.data) {
                if(self.data[i].id != id){
                    self.data[i]['selected'] = false;
                }
            }
        }else{
            for(let i in self.data) {
                if(self.data[i].id != id){
                    self.data[i]['selected'] = false;
                }
            }
        }
    }

    //功能：当前选中高亮显示
    VideoSegmentationTool.prototype.selectLabelUpdateCss=function (id) {
        var self = this;
        let className = '.vl-item-'+id; //例：.vl-item-0D14A817-98D9-45D8-B57B-2E7486B2E5EF
        let curObj = $(self.resultListObj).find(className);
        if(curObj){
            if(curObj.length>0){
                curObj.siblings().removeClass('cur');
                curObj.addClass('cur');
            }
        }
    }

    //功能：取消选中
    VideoSegmentationTool.prototype.unSelectLabel=function(){
        var self = this;
        //1.参数初始化
        self.nowData=null;
        self.startTimeTmp=0; //临时分割开始点
        self.endTimeTmp=0; //临时分割结束点
        self.startTimeDom.text("00:00:00.00");
        self.endTimeDom.text("00:00:00.00");
        self.unSelectTimeDom();
        //self.video.currentTime(0);
        self.$vlsl.css({width:0,left:0,background:"#ffff00"});
        self.$vlslBar1.css({left:-99999});
        self.$vlslBar2.css({left:-99999});
        //2.取消选中的数据标识
        self.updateDataSetSelectedById('-1');
        //3.显示隐藏相应html标签
        self.isShowHideSaveModify();
        //4.通知tag.vue清空已选标签
        self.dispatchEvent({type:"showTextInTagVueEvent",message:[]});
    };

    //功能：取消选中状态'开始时间和结束时间'
    VideoSegmentationTool.prototype.unSelectTimeDom=function(){
        $(this.video.el()).find(".video-label-time-select").removeClass("video-label-time-select");
    };

    //功能：更新一个label对象
    VideoSegmentationTool.prototype.updateLabelById=function(id){
        if(!this.data[id]) return;
        this.updateLabel(id);
        this.unSelectTimeDom();        
    };

    //功能：保存当前修改
    VideoSegmentationTool.prototype.updateLabelByNowFun=function(){
        var self = this;
        if(!self.nowData) return;
        self.isNew=false;
        var curTime = self.endTimeTmp; //记忆上次结束时间

        self.updateLabel(this.nowData.id); //更新数据
        self.unSelectTimeDom(); //取消选中状态'开始时间和结束时间'
        self.unSelectLabel(); //不选中任务结果
        
        self.video.currentTime(curTime); //设置当前时间为记忆的'上次结束时间'
        //this.dispatchEvent({type:"updateLabelEvent",message:this.nowData});
    }
    //功能：更新当前标注结果
    VideoSegmentationTool.prototype.updateLabel=function(id){
        if(this.endTimeTmp<this.startTimeTmp){
            let tmpTime=this.startTimeTmp;
            this.startTimeTmp=this.endTimeTmp;
            this.endTimeTmp=tmpTime;
        }
        this.data[id].isLabel=true;
        this.data[id].startTime=this.startTimeTmp;
        this.data[id].endTime=this.endTimeTmp;
        this.data[id].length=(this.endTimeTmp-this.startTimeTmp);
        this.data[id].duration=this.video.duration();
        this.data[id].startTimeRatio=((this.startTimeTmp/this.video.duration())*100?(this.startTimeTmp/this.video.duration())*100:0)+"%";
        this.data[id].endTimeRatio=((this.endTimeTmp/this.video.duration())*100?(this.endTimeTmp/this.video.duration())*100:0)+"%";
        this.data[id].lengthRatio=(((this.endTimeTmp-this.startTimeTmp)/this.video.duration())*100?((this.endTimeTmp-this.startTimeTmp)/this.video.duration())*100:0)+"%";        
        //this.data[id].cTime = Date.now(); // 创建时间
        //this.data[id].cBy = this.user_id; // 创建人
        this.data[id].mTime = Date.now(); // 修改时间
        this.data[id].mBy = this.user_id; // 修改人
        //this.data[id].type = 'video-segmentation'; //标注类型
        this.selectLabel(id);
    }

    //功能：删除一个结果：label对象
    VideoSegmentationTool.prototype.deleteLabelByIndex=function (id) {
        if(!this.data[id]) return;
        if(this.nowData){
            if(this.nowData.id===id){
                this.unSelectLabel();
            }
        }
        delete this.data[id];
        this.unSelectTimeDom();
        this.dispatchEvent({type:"deleteLabelEvent",message:id});
    };

    VideoSegmentationTool.prototype.deleteLabelByNow=function(){
        if(!this.nowData) return;
        let id=this.nowData.id;
        delete this.data[id];
        this.unSelectLabel();
        this.dispatchEvent({type:"deleteLabelEvent",message:id});
    };

    //功能：调试打印
    VideoSegmentationTool.prototype.debugPrintFun=function(){
        let res = this.getData();
        this.dispatchEvent({type:"debugPrintEvent",res});
    };

    //功能：播放当前:lable对象
    VideoSegmentationTool.prototype.playByNow=function () {
        if(!this.nowData) return;
        this.dataPlaying=true;
        this.video.currentTime(this.nowData.startTime);
        this.video.play();
    };

    VideoSegmentationTool.prototype.getData=function () {
        let rd=[];
        for(let a in this.data){
            if(this.data[a].isLabel){
                rd.push(this.data[a]);
            }
        }
        return rd;
    };

    //功能：写入各种信息
    VideoSegmentationTool.prototype.setBasicMessage=function(msg){
        this.basicMessage=this.checkMessage(msg);
        for(var a in this.data){
            Object.assign(this.data[a],this.basicMessage);
        }
    };

    //功能：处理'可以有多个标签'
    VideoSegmentationTool.prototype.processMultiLabel = function(m,isAppend){
        var self = this;
        var result = null;
        let newText = m.attr ? m.attr.text : '';         //新'标签'
        let newCategory = m.attr ? m.attr.category : ''; //新'分类'
        let newCode = m.attr ? m.attr.shortValue : '';   //新'code'
        let textArr = self.nowData.attr ? self.nowData.attr.text : [];          //旧'标签'
        let categoryArr = self.nowData.attr ? self.nowData.attr.category : [];  //旧'分类'
        let codeArr = self.nowData.attr ? self.nowData.attr.code : [];          //旧'code'
        let _color = m.attr ? m.attr.color : '#ff0000';
        if(isAppend === true){ //追加
            if(m && m.attr.localTagIsUnique && isAppend === true){ //.组内追加
                let index = categoryArr.indexOf(newCategory || newText);
                if (~index) {
                    let labelIndex = -1;
                    for (let i = 0; i < textArr.length; i++) {
                        if (categoryArr[i] === newCategory && textArr[i] === newText) {
                            labelIndex = i;break;
                        }
                    }
                    if (~labelIndex) {
                        textArr.splice(labelIndex, 1, newText);
                        codeArr.splice(labelIndex, 1, newCode || '');
                        categoryArr.splice(labelIndex, 1, newCategory || newText);
                    } else {
                        textArr.push(newText || '');
                        codeArr.push(newCode || '');
                        categoryArr.push(newCategory || newText);
                    }
                    if (index === 0) {_color = m.attr.color;}
                } else {
                    textArr.push(newText || '');
                    codeArr.push(newCode || '');
                    categoryArr.push(newCategory || newText);
                }
            }else{ //.组内替换                
                let index = categoryArr.indexOf(newCategory || newText);
                if (~index) {
                    textArr.splice(index, 1, newText);
                    codeArr.splice(index, 1, newCode || '');
                    categoryArr.splice(index, 1, newCategory || newText);
                    if (index === 0) {
                        _color = m.attr.color;
                    }
                } else {
                    textArr.push(newText || '');
                    codeArr.push(newCode || '');
                    categoryArr.push(newCategory || newText);
                }
            }
        }else{ //替换
            textArr = [];
            codeArr = [];
            categoryArr = [];             
            _color = m.attr.color;
            textArr.push(newText || '');
            codeArr.push(newCode || '');
            categoryArr.push(newCategory || newText);
        }
        result = {
            attr:{
                color:_color,
                text:textArr,
                category:categoryArr,
                code:codeArr
            }
        }        
        return result;
    };

    //功能:设置标签
    VideoSegmentationTool.prototype.setTagFun=function (data) {
        var self = this;
        if(!self.nowData) return;
        //1.处理传过来的标签是'追加'还是'替换'
        var _checked = false;
        if(data && data.attr && data.attr['appendTag']){
            _checked = data.attr['appendTag'];
        }
        let m = this.checkMessage(data);        
            m = self.processMultiLabel(m,_checked);
            Object.assign(this.nowData,m);
        //2.设置颜色和通知标签更新事件
        let _color = this.nowData.attr?this.nowData.attr.color:"#ffff00";
        this.$vlsl.css({'background-color':_color});
        this.dispatchEvent({type:"setTagEvent",message:this.nowData});        
        //3.与tag.vue组件通信
        self.dispatchEvent({type:"showTextInTagVueEvent",message:self.nowData});
    };

    //功能：取消保存
    VideoSegmentationTool.prototype.cancelSaveFun = function(){
        var self = this;        
            self.isNew=false;
            self.unSelectLabel();
            //取消选中的数据标识
            self.updateDataSetSelectedById('-1');
    };

    VideoSegmentationTool.prototype.setMessageById=function (msg,id) {
        if(!this.data[id]) return;
        let m=this.checkMessage(msg);
        Object.assign(this.nowData,m);
        if(this.nowData){
            if(this.nowData.id===id){
                let c=this.nowData.attr?this.nowData.attr.color:"#ffff00";
                this.$vlsl.css({background:c});
                this.$vlslBar1.css({background:c});
                this.$vlslBar2.css({background:c});
            }
        }
        this.dispatchEvent({type:"setTagEvent",message:this.data[id]})
    };

    VideoSegmentationTool.prototype.checkMessage=function (msg) {
        if(msg.id) delete msg.id;
        if(msg.isLabel) delete msg.isLabel;
        if(msg.startTimeDom) delete msg.startTimeDom;
        if(msg.endTime) delete msg.endTime;
        if(msg.length) delete msg.length;
        if(msg.duration) delete msg.duration;
        if(msg.startTimeRatio) delete msg.startTimeRatio;
        if(msg.endTimeRatio) delete msg.endTimeRatio;
        if(msg.lengthRatio) delete msg.lengthRatio;
        return msg;
    }

    //功能：通过步长进行时间的微调
    VideoSegmentationTool.prototype.addTimeByStepFun=function () {
        let ct = this.video.currentTime();
        this.video.currentTime(ct+this.step);
        //显示当前进度时间
        ct = this.video.currentTime();
        this.showCurrentTime(ct);
        //this.onSeeked();
    }
    VideoSegmentationTool.prototype.desTimeByStepFun=function () {
        let ct = this.video.currentTime();
        this.video.currentTime(ct-this.step);
        //显示当前进度时间
        ct = this.video.currentTime();
        this.showCurrentTime(ct);
        //this.onSeeked();
    }
    VideoSegmentationTool.prototype.destroy=function () {
        this.video.dispose();
        this.removeEvents();
        this.nowData=null;
        this._listeners=null;
        this.data={};
        $(this.el).find(".video-label-select-length-plane").empty().remove();
        this.el=null;        
        for(var a in this){
            delete this[a]
        }
    }
    VideoSegmentationTool.prototype.formatTime=function (_seconds) {
        let rs=_seconds
        _seconds = parseInt(_seconds);
        let hours, mins, seconds;
        let result = '';
        seconds = (rs % 60).toFixed(2);
        mins = parseInt(_seconds % 3600 / 60);
        hours = parseInt(_seconds / 3600);
        if (hours){
            result = `${PadZero(hours)}:${PadZero(mins)}:${PadZero(seconds)}`;
        }else{
            result = `${PadZero(mins)}:${PadZero(seconds)}`;
        }
        return result;
    }
    //补零
    function PadZero(str) {
        var s = '';
        if(str < 10 && str > 0){
            s = '0' + String(str);
        }else if(str == 0){
            s = '00';
        }else{
            s = str;
        }        
        return s;
    }
    function formatTime(_seconds) {
        let rs=_seconds
        _seconds = parseInt(_seconds);
        let hours, mins, seconds;
        let result = '';       
        seconds = (rs % 60).toFixed(2);        
        mins = parseInt(_seconds % 3600 / 60);
        hours = parseInt(_seconds / 3600);
        if (hours){
            result = `${PadZero(hours)}:${PadZero(mins)}:${PadZero(seconds)}`;
        }else{
            result = `${PadZero(mins)}:${PadZero(seconds)}`;
        }
        return result;
    }
    function generateUUID(){
        var lut = [];
        for ( var i = 0; i < 256; i ++ ) {
            lut[ i ] = ( i < 16 ? '0' : '' ) + ( i ).toString( 16 );
        }
        var d0 = Math.random() * 0xffffffff | 0;
        var d1 = Math.random() * 0xffffffff | 0;
        var d2 = Math.random() * 0xffffffff | 0;
        var d3 = Math.random() * 0xffffffff | 0;
        var uuid = lut[ d0 & 0xff ] + lut[ d0 >> 8 & 0xff ] + lut[ d0 >> 16 & 0xff ] + lut[ d0 >> 24 & 0xff ] + '-' +
            lut[ d1 & 0xff ] + lut[ d1 >> 8 & 0xff ] + '-' + lut[ d1 >> 16 & 0x0f | 0x40 ] + lut[ d1 >> 24 & 0xff ] + '-' +
            lut[ d2 & 0x3f | 0x80 ] + lut[ d2 >> 8 & 0xff ] + '-' + lut[ d2 >> 16 & 0xff ] + lut[ d2 >> 24 & 0xff ] +
            lut[ d3 & 0xff ] + lut[ d3 >> 8 & 0xff ] + lut[ d3 >> 16 & 0xff ] + lut[ d3 >> 24 & 0xff ];
        // .toUpperCase() here flattens concatenated strings to save heap memory space.
        return uuid.toUpperCase();
    }
    return VideoSegmentationTool;
})(window);
export default VideoSegmentationTool;
<!--视频分割-->
<template>
    <div class="video-segmentation-box">
        <div id="stage" ref="stage">
            <!--按钮操作区-->
            <div class="btns-operator">
                <div class="col c-box-1432">
                    <!--新建：时间段-->
                    <Button class="btn-2143"
                     :class="newLabelBtnIsDisabledCss"
                     :disabled="newLabelBtnIsDisabled" size="small"
                      @click.native="newLabel()" ref="newLabel">{{$t('video_s_create')}}</Button>
                </div>
                <div class="col c-box-1433">
                    <!--开始时间 - 结束时间-->
                    <span ref="setTimes" id="J-setTimes" class="set-times">
                        <!--设为开始时间-->
                        <span class="set-time-start time-btn btn-2143" :title="$t('video_s_set_start_time')">00:00.00</span> - 
                        <span class="set-time-end time-btn btn-2143" :title="$t('video_s_set_end_time')">00:00.00</span><!--设为结束时间-->
                    </span>
                </div>
                <div class="col">
                    <!--保存修改-->
                    <Button class="btn-2143" size="small" @click.native="updateLabelByNow()" ref="btnUpdateLabel">{{$t('video_s_save_modifications')}}</Button>
                    <!--'保存'新建时间段-->
                    <Button class="btn-2143" size="small" @click.native="createLabel()" ref="createLabel">{{$t('tool_save_temp')}}</Button><!--保存-->
                    <!--'取消' - 新建保存 - 保存修改 -->
                    <Button class="btn-2143" size="small" @click.native="cancelSave()" ref="cancelSave">{{$t('tool_cancel')}}</Button><!--取消-->
                </div>                
                
                <!--调试:打印
                <div class="btns-operator-one" style="position: absolute;top: -200px;z-index: 9;">
                    <Button type="primary" size="small" @click.native="debugPrint()">调试:打印</Button>
                </div>
                -->
                <div class="btns-operator-two">
                    <!--按钮:保存,新建-->
                    <div class="right">
                        <div class="col">
                            <!-- 时间进度: '+加'  '-减'  -->
                            <Button class="btn-1122" size="small" @click.native="desTimeByStep()" :title="$t('video_s_forward')"><!--向前-->
                                <Icon type="ios-remove" class="split-left"/>
                                <Icon type="ios-rewind" />
                            </Button>
                            <span class="sec">                                
                                <InputNumber class="sec-step" :max="100" :min="0.1" :step="stepNumber" v-model="secStep" size="small"></InputNumber>
                                <em class="tool_s">{{$t('tool_s')}}</em>
                            </span><!--快进|快退秒数-->
                            <Button class="btn-1122" size="small" @click.native="addTimeByStep()" :title="$t('video_s_backward')"><!--向后-->
                                <Icon type="ios-fastforward" />
                                <Icon type="ios-remove" class="split-right"/>
                            </Button>
                        </div>                        
                    </div>

                    <div class="center-time">
                        <!--当前进度时间-->
                        <span ref="videoCurrentTime" id="J-video-current-time" class="video-current-time" :title="$t('video_s_current_progress')">
                            <span class="time">00:00.00</span>
                        </span> / 
                        <!--视频总时长-->
                        <span ref="videoTotalTime" id="J-video-total-time" class="video-total-time" :title="$t('video_s_total_video_time')">
                            <span class="time">00:00.00</span>
                        </span>
                    </div>

                </div>
            </div>
            <!--视频播放区-->
            <div id="J-vc-box" ref="vcBox" class="vc-box">
                <video ref="videoContainerBox" id="J-video-container" class="video-js" controls preload="none"></video>
            </div>
        </div>
        
        <!--结果区-->
        <div ref="srlBox" class="stage-result-list-Box" :data-tips="$t('video_s_result_list')"><!--结果列表-->
            <!--顶-->
            <div class="box-top">
                <span class="title">{{$t('video_s_result_list')}}</span><!--结果列表-->
                <em class="num">{{$t('video_s_totals')}}<b>{{resultData.length}}</b>{{$t('video_s_items')}}</em><!--共?条-->
            </div>
            <!--中:列表-->
            <div ref="stageResultList" id="stage-result-list">
                <div 
                    :class='["J-item-row","vl-item", "vl-item-"+item.id, getCurFun(item)]' 
                    :data-id='item.id' 
                    :key='item.id' 
                    @click='selectLabelFun($event, item.id)' 
                    v-for="(item,index) in resultData" 
                    >
                    <div class="row-1">
                        <!--暂时保留
                        <div class='vl-item-line bg' title='时间段色条' 
                            :style='"background-color:"+formatAttrColor(item)+";left:"+item.startTimeRatio+";width:"+formatAttrWidth(item)+""'></div>
                        -->
                        
                        <!--时间段-->
                        <span class='time' :title='$t("video_s_time_slot")'>{{index+1}}、{{formatTimeFun(item.startTime)}} - {{formatTimeFun(item.endTime)}}</span>
                    </div>
                    <div class="row-2">
                        <span class="btns-2211">
                            <Icon class="del" type="md-remove-circle" :title='$t("tool_delete")' v-if="is_edit === true"/><!--删除-->
                            <Icon class='play' type="logo-youtube" :title='$t("tool_play")'/><!--播放-->
                        </span>
                        <span class='text' v-if="item.attr">
                            <Tag v-for="(obj,i) in item.attr.text" :key='i'>{{obj}}</Tag>
                        </span><!--标签-->
                        <div style='clear:both;'></div>
                    </div>
                </div>
            </div>
            <!--底-->
            <div class="box-bottom">
                <!--清空标注列表-->
                <Button class="btn-clear" @click.native="clearAllLabel()" v-if="is_edit === true">{{$t('video_s_clear_annotated_list')}}</Button>
            </div>
        </div>
        <div style='clear:both;'></div>
    </div>
</template>

<script>
import Vue from 'vue';
import api from '@/api';
import EventBus from '@/common/event-bus';
import '@/common/video-segmentation/video-segmentation-tool.css';
import VideoSegmentationTool from './video-segmentation-tool.js';
export default {
  name: 'video-segmentation',
  vl:null,
  data () {
    return {
        id:'',
        type:'',
        video_url:'',
        isMultiLabel:false,
        resultData:[], //作业结果列表
        rowWidth:0,    //作业结果列表的宽度
        audit_view_box_height:0, //审核界面上容器的高度
        is_edit:true, //当前组件是否允许使用编辑功能'新建,保存修改,删除,清空标注结果等..'
        newLabelBtnIsDisabledCss:'', //按钮：新建(是否可用)
        newLabelBtnIsDisabled:false,
        secStep:0.1, //快进|快退 的秒数
        stepNumber:1, //InputNumber控件的步长 
    }
  },
  components: {
    
  },  
  watch: {
      secStep(a,b){ //监测快进|快退秒数的变化          
          if(a!=b){
              this.vl.step = a;
          }
          if(a<2){
              this.stepNumber = 0.1;
          }else if(a>1){
              this.stepNumber = 1;
          }          
      }
  },
  computed: {
   
  },
  filters: {
     
  },
  created(){
  
  },
  mounted(){
          
  },
  methods:{
      //功能：新建(按钮是否可用)
      newLabelBtnFun(isDisabled){
          var self = this;          
          if(isDisabled === true){
              self.newLabelBtnIsDisabledCss = 'disabled';
              self.newLabelBtnIsDisabled = true;
          }else{
              self.newLabelBtnIsDisabledCss = '';
              self.newLabelBtnIsDisabled = false;
          }
      },
      //功能：接收事件
      receiveEventFun(){
            var self = this;
            EventBus.$off('setLabel', self.setLabelFun); //接收tag.vue设置'标签'
            EventBus.$off('appendLabel', self.appendLabelFun); //接收tag.vue设置'追加标签'
            EventBus.$off('deleteLabel', self.delTagFun); //接收tag.vue设置'删除标签'

            EventBus.$on('setLabel', self.setLabelFun); //接收tag.vue设置'标签'
            EventBus.$on('appendLabel', self.appendLabelFun); //接收tag.vue设置'追加标签'
            EventBus.$on('deleteLabel', self.delTagFun); //接收tag.vue设置'删除标签'
      },
      //功能：接收tag.vue设置'删除标签'
      delTagFun(index){
          var self = this;
          if(index >= 0){
            var fun = self.vl && self.vl.delCurrentDataTagByIndex;                
            if(typeof(fun) == 'function'){
                self.vl.delCurrentDataTagByIndex(index); //删除当前结果中的'标签'
            }              
          }
      },
      //功能：接收设置'追加标签'
      appendLabelFun(params){
          var self = this;
          var _text = typeof(params.label)!='undefined' ? params.label:'';
          var _color = typeof(params.color)!='undefined' ? params.color:'';
          var _category = typeof(params.category)!='undefined' ? params.category:'';
          var _shortValue = typeof(params.shortValue)!='undefined' ? params.shortValue:'';
          var _localTagIsUnique = typeof(params.localTagIsUnique)!='undefined' ? params.localTagIsUnique:0;
          if(_text.length > 0){
              var fun = self.vl && self.vl.setTagFun;                
                if(typeof(fun) == 'function'){
                    self.vl.setTagFun({attr:{
                        color:_color,
                        category:_category,
                        text:_text,
                        shortValue:_shortValue,
                        localTagIsUnique:_localTagIsUnique,
                        'appendTag':true
                    }});
                }
          }
      },
      //功能：接收设置'标签'
      setLabelFun(params){
          var self = this;
          var _text = typeof(params.label)!='undefined' ? params.label:'';
          var _color = typeof(params.color)!='undefined' ? params.color:'';
          var _category = typeof(params.category)!='undefined' ? params.category:'';
          var _shortValue = typeof(params.shortValue)!='undefined' ? params.shortValue:'';
          var _localTagIsUnique = typeof(params.localTagIsUnique)!='undefined' ? params.localTagIsUnique:0;                    
          if(_text.length > 0){
                var fun = self.vl && self.vl.setTagFun;
                if(typeof(fun) == 'function'){
                    self.vl.setTagFun({attr:{
                        color:_color,
                        category:_category,
                        text:_text,
                        shortValue:_shortValue,
                        localTagIsUnique:_localTagIsUnique,
                        'appendTag':false
                    }});
                }
          }
      },
      //功能：计算设置video的高和宽
      getWidthHeight(){
          var self = this;
          //1.计算设置video的高和宽
            var objStage = self.$refs.stage;
            var top = self.$refs.stage.getBoundingClientRect().top;
            let height = window.innerHeight - top;
                height = height - 30;
            var objStageWidth = parseInt($(self.$refs.stage).width()); //外框:宽和高
            // var objStageHeight = parseInt($(self.$refs.stage).height());
            var videoWidth = objStageWidth; //video标签:宽和高
            // var videoHeight = objStageHeight;
            //2.设置video的高为父级元素的高
            // var objParent = $(objStage).parents('.template-preview');
            // var _height = $(objParent).height() - 6;
            // videoHeight = _height;
          return {width:videoWidth, height:height};
      },
      //功能：初始化
      init(params){
          var self = this;
            self.newLabelBtnFun(false);
            self.video_url='';
            self.type='';
            self.data=[];
            self.is_edit=false;
            //1.作业结果列表的宽度
            self.rowWidth = parseInt($(self.$refs.stageResultList).width()) - 20;
            //2.获取视频文件的key
            if(params && params.video_url){ self.video_url = params.video_url; }            
            if(params && params.type){ self.type = params.type; }
            if(params && params.data){ self.data = params.data; }
            if(params && params.audit_view_box_height){ self.audit_view_box_height = params.audit_view_box_height; }                       
            if(params && typeof(params.is_edit) != 'undefined'){ 
                self.is_edit = params.is_edit;
            }
            if(self.is_edit === true){
                self.receiveEventFun();
            }            
            let data = self.data;//数据初始化和回显            
            let videoContainer = self.$refs.vcBox;
            var boxInfo = self.getWidthHeight();
            if(self.audit_view_box_height > 0){boxInfo.height = self.audit_view_box_height;}
            $(self.$refs.stageResultList).css({height:(boxInfo.height - 75)+'px'});
            $(self.$refs.stage).css({height:boxInfo.height+'px'});
            var _src = self.video_url; //视频文件的地址                                
            //2.视频分割工具:初始化
            if(self.vl){
                self.vl.setVideoSrcFun({src:_src,data:data});                              
            }else{
                self.vl = new VideoSegmentationTool({
                        element:videoContainer,
                        src:_src,
                        width:boxInfo.width,
                        height:boxInfo.height,
                        step:0.1,
                        data:data,
                        is_edit:self.is_edit,
                        user_id:self.$store.state.user.userInfo.id,
                        lang:self.$store.state.app.lang
                    });
            }
            self.vl.currentTimeObj = $(self.$refs.videoCurrentTime); //当前进度时间
            self.vl.totalTimeObj = $(self.$refs.videoTotalTime); //视频总时长
            self.vl.btnSaveModifyObj = self.$refs.btnUpdateLabel.$el; //按钮:'保存修改'
            self.vl.resultListObj = $(self.$refs.stageResultList); //结果列表
            self.vl.newLabelObj = self.$refs.newLabel.$el; //按钮：新建
            self.vl.createLabelObj = self.$refs.createLabel.$el; //按钮：保存
            self.vl.cancelSaveObj = self.$refs.cancelSave.$el; //按钮：取消
            self.vl.setTimesObj = self.$refs.setTimes;      //设置时间外框            
            //3.初始化接收'视频分割'事件
            self.removeAllEvent();
            self.initEvent();
      },
      //功能：新建时间段
      createLabel(){
          var self = this;          
          if(self.vl.endTimeTmp <= 0){
            $(self.vl.endTimeDom).addClass('heighlight');
            self.$Message.destroy();
            self.$Message.warning({
                content: self.$t('video_s_end_time_not_be_empty'),
                top: 150,
                duration: 2
            });
            return;
          }else{
              $(self.vl.endTimeDom).removeClass('heighlight');
              self.vl.createLabelFun();
              self.newLabelBtnFun(false); //新建(按钮可用)
          }
      },      
      //功能：调试打印
      debugPrint(){
          var self = this;
          self.vl.debugPrintFun();
      },
      //功能：'移除'事件
      removeAllEvent(){
          var self = this;
          self.vl._listeners=[];
      },
      //功能：初始化接收相关'视频分割'事件
      initEvent(){
        var self = this;
        //调试:打印
        self.vl.addEventListener("debugPrintEvent",function (res) {
            console.log('调试:打印');
            console.log('vl.data:',self.vl.data);
            console.log('res:',res);
            console.log('vl.nowData:',self.vl.nowData);
            console.log('vl.startTimeTmp:',self.vl.startTimeTmp);
            console.log('vl.endTimeTmp:',self.vl.endTimeTmp);
        });

        //功能：创建作业新结果
        self.vl.addEventListener("createLabelEvent",function (res) {
            //1.更新数据
            self.updateData();
            //2.选中当前
            // let item = res.message;
            // if(item.id){
            //     self.vl.selectLabel(item.id);
            // }
        });

        //功能：删除一个结果
        self.vl.addEventListener("deleteLabelEvent",function (res) {            
            self.updateData();
        });        

        //功能：设置'时间段的色条'和'标签'
        self.vl.addEventListener("setTagEvent",function (res) {            
            self.updateData();
        });

        //功能：监听事件更新数据
        self.vl.addEventListener("updateDataEvent",function (res) {            
            self.updateData();
        });
                
        //功能：通知tag.vue当前选中结果的标签
        self.vl.addEventListener("showTextInTagVueEvent",function (res) {
            var result = [];
            var categoryArr = [];
            if(res && res.message && res.message.attr && res.message.attr.category){
                categoryArr =  res.message.attr.category;
            }
            if(res && res.message && res.message.attr && res.message.attr.text){
                var arr =  res.message.attr.text;
                for(var i = 0; i<arr.length; i++){
                    result.push({categoryText:categoryArr[i] || arr[i], shortValue:"", text:arr[i]});
                }
            }            
            EventBus.$emit('renderLabelList', result);//通知tag.vue当前标签
        });
      },
      //功能：更新数据
      updateData(){
          var self = this;
          self.resultData.splice(0, self.resultData.length);
          self.resultData = self.vl.getData();
      },
      //功能：格式化时间
      formatTimeFun(time){
          var self = this;
          return self.vl.formatTime(time);
      },      
      //功能：格式化'颜色'
      formatAttrColor(m){
          return m.attr?m.attr.color:"#ffff00";
      },
      //功能：格式化'宽度'
      formatAttrWidth(m){
          var self = this;
          var _width = self.rowWidth*(+(m.lengthRatio.split("%")[0])/100)<1?"1px":m.lengthRatio;
          return _width;
      },
      //功能：获取当前结果行是否'高亮'
      getCurFun(m){
          let _className = '';
          if(m && m.selected){
              if(m.selected === true){
                  _className = 'cur';
              }
          }
          return _className;
      },
      //功能：监听'结果'中的点击事件
      selectLabelFun(e,id){
          var self = this;
          var targetObj = e.target;//当前点中的目标          
          if($(targetObj).hasClass('del')){
                //1.删除 - 当前选中
                if(confirm(self.$t('video_s_tip_del'))){ // 提示：确定删除此时间段吗？
                    self.vl.deleteLabelByIndex(id);
                }
          }else if($(targetObj).hasClass('play')){
                //2.播放 - 当前选中
                self.vl.selectLabel(id);
                self.vl.playByNow();
                self.newLabelBtnFun(false);
          }else{
                //3.选中 - 当前选中
                self.vl.selectLabel(id);
                self.newLabelBtnFun(false);
          }
      },
      //功能：保存修改
      updateLabelByNow(){
          var self = this;
          if(self.vl.endTimeTmp <= 0){
              $(self.vl.endTimeDom).addClass('heighlight');
              self.$Message.destroy();
              self.$Message.warning({
                    content: self.$t('video_s_end_time_not_be_empty'),
                    top: 150,
                    duration: 2
              });
              return;
          }else{
            $(self.vl.endTimeDom).removeClass('heighlight');
            self.vl.updateLabelByNowFun();
            self.newLabelBtnFun(false); //新建(按钮可用)
          }          
      },
      //功能：当前进度时间'减'
      desTimeByStep(){
          var self = this;
          self.vl.desTimeByStepFun();
      },
      //功能：当前进度时间'加'
      addTimeByStep(){
          var self = this;
          self.vl.addTimeByStepFun();
      },      
      //功能：新建
      newLabel(){
          var self = this;
          self.vl.newLabelFun();
          $(self.vl.endTimeDom).removeClass('heighlight');
          //2.新建(按钮不可用)
          self.newLabelBtnFun(true);
      },
      //功能：清空标注
      clearAllLabel(){
          var self = this;
          if(confirm(self.$t('video_s_tip_emptying_all'))){ // 提示：确定清空标注结果吗？
            self.vl.clearAllLabelFun();
            self.newLabelBtnFun(false); //新建(按钮可用)
          }
      },
      //功能：取消保存
      cancelSave(){
          var self = this;
          self.vl.cancelSaveFun();
          $(self.vl.endTimeDom).removeClass('heighlight');
          //2.新建(按钮可用)
          self.newLabelBtnFun(false);
      },
      //功能：获取结果数据
      getResultDataFun(){
          var self = this;
          return {
              type : self.type, 
              value : self.vl.getData()
          };
      }
  },
  updated(){
    
  },
  beforeDestroy(){
      var self = this;
      EventBus.$off('setLabel', self.setLabelFun); //接收tag.vue设置'标签'
      EventBus.$off('appendLabel', self.appendLabelFun); //接收tag.vue设置'追加标签'
      EventBus.$off('deleteLabel', self.delTagFun); //接收tag.vue设置'删除标签'
      self.removeAllEvent();
      self.vl.destroy();
      self.vl = null;
  },
  destroyed(){
      
  }
}
</script>
<style scoped>
.c-box-1432{width:auto;padding-left: 18px;}
.c-box-1433{width:220px;text-align: center;}
.heighlight{border-color: #ff0000 !important;}
</style>
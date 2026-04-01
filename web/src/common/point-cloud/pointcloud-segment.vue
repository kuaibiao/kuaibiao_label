<template>
    <div class="point-cloud-wrapper">
        <div class="point-cloud-tools">
            <div class="left-btns" v-if="editable">
                <Button type="primary" size="small" @click="createSegment" :disabled="editStatus==2">{{$t('cloud_create')}}(N)</Button>
                <Button type="primary" size="small" @click="editSegment" :disabled="editStatus==1">{{$t('cloud_edit')}}(E)</Button>
                <ButtonGroup v-show="editing">
                    <Button :type="brushType === 'dec' ? 'primary': 'default'" size="small" @click="segmentDec">{{$t('cloud_subtract')}}</Button>
                    <Button :type="brushType === 'add' ? 'primary': 'default'" size="small" @click="segmentAdd">{{$t('cloud_add')}}</Button>
                    <Button type="primary" size="small" @click="cancelSegmentation">{{$t('cloud_cancel')}}(ESC)</Button>
                    <Button type="primary" size="small" @click="completeSegmentation">{{$t('cloud_complete')}}(Enter)</Button>
                </ButtonGroup>
            </div>
            <Button type="primary" size="small" style="margin-left:15px;" @click="checkUnmarkerPoint">{{$t('cloud_check')}}</Button>
            <div class="right-btns" v-if="editable">
                <Button type="error" size="small" :disabled="creating" @click="needDeleteItem=true">{{$t('cloud_delete')}}</Button>
                <Button type="error" size="small" :disabled="creating" @click="needDeleteAll=true">{{$t('cloud_delete_all')}}</Button>
                <span>{{$t("cloud_drawing_mode")}}</span>
                <Select v-model="drawMode" size="small" style="width: 40px" @on-change="changeDrawMode" placeholder="">
                    <Option value=1>{{$t("cloud_brush")}}</Option>
                    <Option value=2>{{$t("cloud_round")}}</Option>
                    <Option value=3>{{$t("cloud_square")}}</Option>
                </Select>

                <Modal width="360"
                        v-model="needDeleteItem"
                        :title="$t('cloud_delete')">
                    <div slot="footer">
                        <i-button size="large" @click="needDeleteItem=false">{{$t('cloud_cancel')}}</i-button>
                        <i-button type="error" size="large" @click="deleteItem">{{$t('cloud_ok')}}</i-button>
                    </div>
                    <p>{{$t('delete_data_text')}}</p>
                </Modal>

                <Modal  width="360"
                        v-model="needDeleteAll"
                        :title="$t('cloud_delete_all')">
                    <div slot="footer">
                        <i-button size="large" @click="needDeleteAll=false">{{$t('cloud_cancel')}}</i-button>
                        <i-button type="error" size="large" @click="deleteAll">{{$t('cloud_ok')}}</i-button>
                    </div>
                    <p>{{$t('delete_data_all_text')}}</p>
                </Modal>
            </div>

            <span class="other-text">{{$t("cloud_point_size")}}:</span>
            <Select v-model="pointSize" size="small" style="width: 40px" @on-change="changePointSize" placeholder="">
                <Option value=0.1>{{$t("cloud_small")}}</Option>
                <Option value=0.3>{{$t("cloud_middle")}}</Option>
                <Option value=0.5>{{$t("cloud_large")}}</Option>
            </Select>
            <ColorPicker style="margin-left:10px" size="small" v-model="background" @on-change="changeBackground" />

            <Checkbox class="other-text" @on-change="toggleLR" v-model="showLR">{{$t("cloud_visibility")}}</Checkbox>
            <InputNumber :min="1" v-model="lrSize" :disabled="!showLR" size="small" style="width: 50px" @on-change="setLRRaidus"></InputNumber>

            <Button style="margin-left: 10px" size="small" @click="fullScreen">{{$t('cloud_full')}}</Button>

        </div>
        <div class="point-cloud-content" :class="has2dImage ? 'has-2d-image':''" ref="renderTarget">
            <div class="point-cloud-3d"></div>
            <div class="point-cloud-2d">
                <div class="p-2d-item" v-for="(item, index) in image2dList" :key="index"
                     :class="editIndex === index? 'editing': ''" style="height: 24%">
                    <ButtonGroup class="point-cloud--2d-tools">
                        <Button type="primary" size="small" @click="editCube(index)">{{editIndex > -1 ? $t('cloud_close') : $t('cloud_view')}}
                        </Button>
                    </ButtonGroup>


                    <div class="p-2d-item-content">

                    </div>
                </div>
            </div>

            <div class="point-cloud-other-tools">
                <!--<Checkbox v-model="autoLockPoint" @on-change="changeAutoLockPoint">{{$t("cloud_lock_labeled_points")}}</Checkbox>-->
            </div>
        </div>
    </div>
</template>

<script>
    import PointCloud from "./pointcloud";
    import mixin from './mixin';
    import cloneDeep from 'lodash.clonedeep';
    import EventBus from '../../common/event-bus';
    import elementResizeDetectorMaker from "element-resize-detector";

    let erd = elementResizeDetectorMaker();

    const brushType = {
        add: 'add',
        dec: 'dec'
    };
    export default {
        name: "pointcloud-segment",
        mixins: [mixin],
        pointCloud: null,
        pointCloudSegment: null,
        imageControls: [],
        resultList: {},
        watch: {
            /*'$store.state.app.shrink' () {
                // 导航栏变动更新工具大小
                if (this.pointCloud) {
                    setTimeout(() => {
                        this.pointCloud.onWindowResize();
                        this.pointCloudSegment.onWindowResize();
                    }, 300);
                }
            }*/
        },
        data () {
            return {
                isReady: false,
                currentShapeIndex: void 0,
                editable: false,
                editing: false,
                options: {},
                brushType: brushType.add, // 'dec'

                creating: false,

                needDeleteItem: false,
                needDeleteAll: false,

                pointSize:0.1,
                background:"#000000",
                showLR:false,
                lrSize:1,

                drawMode:1,

                has2dImage: false,
                image2dList: [],
                editIndex:-1,

                editStatus:0,


                //autoLockPoint:true
            };
        },
        computed: {
            userId () {
                return this.gmixin_userInfo.id;
            }
        },
        mounted () {
            this.imageControls = [];

            this.bindDomEvent = this.bindDomEvent.bind(this);
            this.resize = this.resize.bind(this);
            this.resize();
            window.addEventListener('resize', this.resize);
            EventBus.$on('setDefaultLabel', this.setDefaultLabel);
            EventBus.$on('setLabel', this.setLabel);
            EventBus.$on('appendLabel', this.appendLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('selectShape', this.selectItem);
            EventBus.$on('removeShape', this.removeItemById);

            let self = this;
            erd.listenTo(this.$el.querySelector(".point-cloud-content"), function(element) {
                if (self.pointCloud) {
                    self.resize();
                }
            });
        },
        methods: {

            setDefaultLabel (label) {
                this.defaultLabel = label;
            },
            resize () {
                if (this.resizing) {
                    return;
                }
                this.resizing = true;
                setTimeout(() => {
                    let target = this.$el.querySelector('.point-cloud-3d');
                    let target2d = this.$el.querySelector('.point-cloud-2d');
                    let bbox = target.getBoundingClientRect();
                    let height = window.innerHeight - bbox.top - 40;
                    if (height < 520) {
                        height = 520;
                    }
                    target.style.height = height + 'px';
                    if (target2d) {
                        target2d.style.height = height + 'px';
                    }
                    if (this.pointCloud) {
                        this.pointCloud.onWindowResize();
                        this.pointCloudSegment.onWindowResize();

                        if (this.imageControls) {
                            this.imageControls.forEach((pp, index) => {
                                this.editIndex === index && this.updateControlSize(index);
                                pp.onWindowResize();
                            });
                        }
                    }
                    this.resizing = false;
                }, 10);
            },
            init (options) {
                this.options = options;

                this.has2dImage = !!(options.image2dList && options.image2dList.length);
                this.image2dList = (options.image2dList || []).map((item) => {
                    return {
                        ...item,
                        adjustIsOpen: false
                    };
                });

                let result = options.result;
                this.$nextTick(() => {
                    if (this.pointCloud) {
                        this.isReady = false;
                        if (this.pointCloudSegment) {
                            this.pointCloudSegment.destroy();
                            this.pointCloudSegment._listeners = {};
                            this.pointCloud._listeners = {};
                            this.pointCloud.load(options.src);
                            this.pointCloudSegment = new PointCloud.PencilTool(this.pointCloud, result);
                            // this.pointCloudSegment.setBasicMessage({
                            //     mBy: this.userId,
                            //     step: this.taskInfo?this.taskInfo.step_id:null,
                            //     cStep: this.taskInfo?this.taskInfo.step_id:null,
                            //     mStep: this.taskInfo?this.taskInfo.step_id:null
                            // });

                            this.createImageControls(options, result);

                            this.bindEvent();
                        }
                        return;
                    }
                    let ele = this.$el.querySelector('.point-cloud-3d');
                    this.pointCloud = new PointCloud.LookPointCloud({
                        element: ele,
                        pcdUrl: options.src,
                        isTCEditorOpen: options.allowEditing,
                        mode: PointCloud.MeshModes.car,
                        groundOffset:-Infinity,
                        groundColor:[1,1,1]
                    });
                    this.pointCloudSegment = new PointCloud.PencilTool(this.pointCloud, result);
                    this.editable = options.allowEditing;
                    // this.pointCloudSegment.setBasicMessage({
                    //     mBy: this.userId,
                    //     step: this.taskInfo?this.taskInfo.step_id:null,
                    //     cStep: this.taskInfo?this.taskInfo.step_id:null,
                    //     mStep: this.taskInfo?this.taskInfo.step_id:null
                    // });

                    this.createImageControls(options, result);

                    this.bindEvent();
                });
            },
            handleKeyDown (e) {
                if (!this.editable) {
                    return;
                }
                let keyCode = e.keyCode || e.which;
                let target = e.target;
                if (~['input', 'textarea'].indexOf(target.tagName.toLowerCase())) {
                    return;
                }
                switch (keyCode) {
                    case 78: { // N 新增
                        this.createSegment();
                        e.preventDefault();
                        break;
                    }
                    case 69: { // E 编辑
                        this.editSegment();
                        e.preventDefault();
                        break;
                    }
                    // case 8:
                    // case 46: { // backspace delete
                    //     this.deleteItem();
                    //     e.preventDefault();
                    //     break;
                    // }
                    case 27: { // ESC 取消
                        this.cancelSegmentation();
                        e.preventDefault();
                        break;
                    }
                    case 13: { // Enter 完成
                        this.completeSegmentation();
                        e.preventDefault();
                        break;
                    }
                    case 107:
                    case 187:{
                        this.segmentAdd();
                        e.preventDefault();
                        break;
                    }
                    case 109:
                    case 189:{
                        this.segmentDec();
                        e.preventDefault();
                        break;
                    }
                }
            },
            bindDomEvent () {
                this.unbindDomEvent();
                window.addEventListener('keydown', this.handleKeyDown, false);
            },
            unbindDomEvent () {
                window.removeEventListener('keydown', this.handleKeyDown, false);
            },
            bindEvent () {
                this.bindDomEvent();
                let userId = this.gmixin_userInfo.id;
                let step = this.taskInfo?this.taskInfo.step_id: '';
                this.pointCloud.addEventListener('loadComplete', (e) => {
                    this.isReady = true;
                    this.resultChange({
                        type: 'load',
                        message: this.options.result || [],
                    });
                    this.$emit('ready');

                    this.lrSize=Math.round(this.pointCloud.worldRadius/4);
                });
                this.pointCloud.addEventListener('loadTotal', (e) => {
                    this.$emit('progress', e);
                });
                this.pointCloud.addEventListener('loadError', (e) => {
                    this.$emit('error', e);
                    this.isReady = false;
                });
                // 创建完成 或编辑结束
                this.pointCloudSegment.addEventListener('cutObjComplete', (e) => {
                    this.currentShapeIndex = (e.message && e.message.index) || void 0;
                    let attr;
                    let target = e.message;

                    // 默认标签
                    if (this.defaultLabel) {
                        attr = {
                            category: [this.defaultLabel.category || ''],
                            color: this.defaultLabel.color,
                            label: [this.defaultLabel.label || ''],
                            code: [this.defaultLabel.shortValue || ''],
                        };
                    }
                    // 作业绩效信息
                    if (!target.cBy) { // 新创建的
                        target.cBy = this.userId;
                        target.cTime = (+Date.now()).toString();
                        target.cStep = step;
                    } else { // 修改的
                        let stat = this.getWorkerInfo(target);
                        Object.assign(target, stat);
                    }
                    // 没打过标签的区域 使用默认标签
                    if (attr && !target.attr) {
                        this.pointCloudSegment.setMessageByIndex({attr}, this.currentShapeIndex);
                    }
                    this.resultChange(e);
                    // if(userId!=e.message.mBy){
                    //     e.message.cBy=e.message.mBy;
                    //     e.message.cTime=e.message.mTime;
                    //     e.message.cStep=e.message.mStep;
                    // }
                    // e.message.mBy=userId;
                    // e.message.mTime=(+Date.now().toString());
                    // e.message.mStep=this.taskInfo?this.taskInfo.step_id:null;
                });
                this.pointCloudSegment.addEventListener('deleteCutObj', (e) => {
                    this.resultChange(e);
                });
                this.pointCloudSegment.addEventListener('deleteAllCutObj', (e) => {
                    this.resultChange(e);
                });
                this.pointCloudSegment.addEventListener('selectCutObj', (e) => {
                    this.currentShapeIndex = (e.message && e.message.index) || void 0;
                    this.resultChange(e);
                });
                this.pointCloudSegment.addEventListener('setMessage', (e) => {
                    let target = e.message;
                    if (target) {
                        let stat = this.getWorkerInfo(target);
                        Object.assign(target, stat);
                    }
                    this.resultChange(e);
                });
            },
            selectItem (id) {
                if (this.pointCloudSegment) {
                    this.pointCloudSegment.selectCutObjByIndex(id);
                }
            },
            deleteItem () {
                if (this.currentShapeIndex) {
                    this.removeItemById(this.currentShapeIndex);
                    this.currentShapeIndex = 0;
                }
                this.needDeleteItem = false;
            },
            removeItemById (index) {
                if (this.pointCloudSegment) {
                    this.pointCloudSegment.deleteCutObjByIndex(index);
                }
            },
            deleteAll () {
                if (this.pointCloudSegment) {
                    this.pointCloudSegment.deleteAllCutObj();
                    this.currentShapeIndex=0;
                }
                this.needDeleteAll = false;
            },
            createSegment () {
                if (this.isReady && this.pointCloudSegment) {
                    this.pointCloudSegment.createCutObj();
                    this.editing = true;
                    this.brushType = brushType.add;
                    this.creating = true;

                    this.editStatus=1;
                }
            },
            checkUnmarkerPoint () {
                if (this.isReady && this.pointCloudSegment) {
                    let rm=this.pointCloudSegment.findEmptyPoint();
                    switch (rm) {
                        case 2:{
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: this.$t('cloud_empty_points_message1'),
                                duration: 1,
                            });
                            break;
                        }
                        case 1:{
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: this.$t('cloud_leak_mark'),
                                duration: 1,
                            });
                            break;
                        }
                        case 0:{
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: this.$t('cloud_all_marked'),
                                duration: 1,
                            });
                            break;
                        }
                    }
                }
            },
            editSegment () {
                if (this.isReady && this.pointCloudSegment) {
                    this.pointCloudSegment.editCutObjByNow();
                    this.editing = this.pointCloudSegment.isEdit;
                    if (this.editing) {
                        this.brushType = brushType.add;
                    } else {
                        this.cancelSegmentation();
                    }

                    this.creating = true;

                    this.editStatus=2;
                }
            },
            segmentAdd () {
                if (this.isReady && this.pointCloudSegment) {
                    this.pointCloudSegment.brushAdd();
                    this.brushType = brushType.add;
                }
            },
            segmentDec () {
                if (this.isReady && this.pointCloudSegment) {
                    this.pointCloudSegment.brushDec();
                    this.brushType = brushType.dec;
                }
            },
            cancelSegmentation () {
                if (this.isReady && this.pointCloudSegment && this.editing) {
                    this.pointCloudSegment.cancel();
                    this.editing = false;
                }
                this.creating = false;

                this.editStatus = 0;
            },
            completeSegmentation () {
                if (this.isReady && this.pointCloudSegment && this.editing) {
                    this.pointCloudSegment.okey();
                    this.editing = false;
                }
                this.creating = false;

                this.editStatus = 0;
            },
            getResult (save = false) {
                if (this.pointCloudSegment) {
                    let cubeList = {};
                    let segments = this.pointCloudSegment.getAllCutObjs();
                    segments.forEach((item) => {
                        cubeList[item.index] = {
                            ...item,
                            type: 'pcl_segment',
                        };
                        delete cubeList[item.index].points;
                    });
                    return Object.values(cubeList);
                } else {
                    return [];
                }
            },
            itemLabelChange (attr) {
                if (!attr) {
                    attr = {
                        category: [],
                        code: [],
                        label: []
                    };
                }
                EventBus.$emit('renderLabelList', attr.label.map((item, i) => {
                    return {
                        categoryText: attr.category[i],
                        shortValue: attr.code[i],
                        text: item
                    };
                }));
            },
            resultChange (e) {
                //if (!e.message) return;

                if(e)
                    switch (e.type) {
                    case 'setMessage':
                    case 'selectCutObj':
                    case 'cutObjComplete': {
                        this.resultList[e.message.index] = e.message;
                        break;
                    }
                    case 'deleteAllCutObj': {
                        this.resultList = {};
                        break;
                    }
                    case 'deleteCutObj': {
                        delete this.resultList[e.message.index];
                        break;
                    }
                    case 'load': {
                        this.resultList = {};
                        e.message.forEach(item => {
                            this.resultList[item.index] = item;
                        });
                        break;
                    }
                }

                let resultList = Object.values(this.resultList);
                let currentItem = this.pointCloudSegment.getCutObjByIndex(this.currentShapeIndex);
                if (currentItem) {
                    currentItem = {
                        attr: currentItem.attr,
                        id: currentItem.index,
                    };
                }
                EventBus.$emit('renderResultList', {
                    currentItem,
                    resultList: resultList.map((item) => {
                        return {
                            ...item.attr,
                            id: item.index
                        };
                    })
                });
                this.itemLabelChange(currentItem && currentItem.attr);
            },
            setLabel (label) {
                this.defaultLabel = label;
                if (this.currentShapeIndex) {
                    let attr = {
                        category: [label.category || ''],
                        color: label.color,
                        label: [label.label || ''],
                        code: [label.shortValue || ''],
                    };
                    this.pointCloudSegment.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            appendLabel (obj) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloudSegment.getCutObjByIndex(this.currentShapeIndex);
                    let attr = (target && target.attr) || {
                        category: [],
                        label: [],
                        code: [],
                        color: obj.color,
                    };
                    attr = cloneDeep(attr);
                    if (obj.localTagIsUnique) {
                        let index = attr.category.indexOf(obj.category || obj.label);
                        if (~index) {
                            let labelIndex = -1;
                            for (let i = 0; i < attr.label.length; i++) {
                                if (attr.category[i] === obj.category && attr.label[i] === obj.label) {
                                    labelIndex = i;
                                    break;
                                }
                            }
                            if (~labelIndex) {
                                attr.label.splice(labelIndex, 1, obj.label);
                                attr.code.splice(labelIndex, 1, obj.shortValue || '');
                                attr.category.splice(labelIndex, 1, obj.category || obj.label);
                            } else {
                                attr.label.push(obj.label || '');
                                attr.code.push(obj.shortValue || '');
                                attr.category.push(obj.category || obj.label);
                            }
                            if (index === 0) {
                                attr.color = obj.color;
                            }
                        } else {
                            attr.label.push(obj.label || '');
                            attr.code.push(obj.shortValue || '');
                            attr.category.push(obj.category || obj.label);
                        }
                    } else { // 标签所在组至多一个标签
                        let index = attr.category.indexOf(obj.category || obj.label);
                        if (~index) {
                            attr.label.splice(index, 1, obj.label);
                            attr.code.splice(index, 1, obj.shortValue || '');
                            attr.category.splice(index, 1, obj.category || obj.label);
                            if (index === 0) {
                                attr.color = obj.color;
                            }
                        } else {
                            attr.label.push(obj.label || '');
                            attr.code.push(obj.shortValue || '');
                            attr.category.push(obj.category || obj.label);
                        }
                    }
                    if (attr.label.length === 1) {
                        attr.color = obj.color;
                    }
                    this.pointCloudSegment.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            deleteLabel (index) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloudSegment.getCutObjByIndex(this.currentShapeIndex);
                    if (~index && target.attr) {
                        let attr = cloneDeep(target.attr);
                        attr.label.splice(index, 1);
                        attr.code.splice(index, 1);
                        attr.category.splice(index, 1);
                        if (attr.label.length === 0) {
                            delete attr.color;
                        }
                        this.pointCloudSegment.setMessageByIndex({attr}, this.currentShapeIndex);
                    }
                }
            },

            editCube (index,callback) {
                if (this.editIndex === index) {
                    this.editIndex = -1;
                } else {
                    this.editIndex = index;
                }
                this.$nextTick(() => {
                    let pp = this.imageControls[index];
                    this.updateControlSize(index);
                    pp.onWindowResize();
                    if (this.editIndex === -1) {
                        pp.pause();
                    } else {
                        pp.play();
                    }
                    this.image2dList.splice(index, 1, {
                        ...this.image2dList[index],
                        adjustIsOpen: false
                    });

                    if(callback)
                        callback();
                });
            },


            //修改点大小
            changePointSize(){
                if(!this.pointCloud) return;

                this.pointCloud.changePointSize(this.pointSize);
            },
            //修改背景颜色
            changeBackground(){
                if(!this.pointCloud) return;
                if(this.background==="")
                    this.background = "#000000";

                this.pointCloud.changeBackground(this.background);
                this.pointCloudSegment.updateColor();
            },

            toggleLR(){
                if(!this.pointCloud) return;

                if(this.showLR)
                    this.pointCloud.showLR();
                else
                    this.pointCloud.hideLR();
            },

            setLRRaidus(){
                if(!this.pointCloud) return;

                this.pointCloud.setLRRaidus(this.lrSize);
            },

            isFullScreen () {
                return document.isFullScreen || document.mozIsFullScreen || document.webkitIsFullScreen
            },

            fullScreen(){
                if(!this.isFullScreen()){
                    this.$el.requestFullscreen();
                    //this.full(document.body);
                }
                else{
                    //document.exitFullscreen();
                    this.exitFullscreen();
                }
            },
            full(ele) {
                if (ele.requestFullscreen) {
                    ele.requestFullscreen();
                } else if (ele.mozRequestFullScreen) {
                    ele.mozRequestFullScreen();
                } else if (ele.webkitRequestFullscreen) {
                    ele.webkitRequestFullscreen();
                } else if (ele.msRequestFullscreen) {
                    ele.msRequestFullscreen();
                }
            },
            exitFullscreen() {
                if(document.exitFullScreen) {
                    document.exitFullScreen();
                } else if(document.mozCancelFullScreen) {
                    document.mozCancelFullScreen();
                } else if(document.webkitExitFullscreen) {
                    document.webkitExitFullscreen();
                } else if(document.msExitFullscreen) {
                    document.msExitFullscreen();
                }
            },

            changeDrawMode(){
                if(!this.pointCloud) return;

                this.pointCloudSegment.drawMode=(+this.drawMode);
            },

/*            changeAutoLockPoint(){
                if(!this.pointCloud) return;

                this.pointCloudSegment.autoLockPoint=this.autoLockPoint
            },*/

            createImageControls (options, result) {
                let image2dContainer = this.$el.querySelector('.point-cloud-2d');
                if (this.imageControls.length) {
                    this.imageControls.forEach(control => {
                        control.dispose();
                    });
                }
                this.imageControls = [];
                let itemEleList = image2dContainer.querySelectorAll('.p-2d-item-content');
                options.image2dList.forEach((item, index) => {
                    let boxType = ['box', 'plane'].indexOf(item.box_type); // box  8个点 plane 4个点
                    this.boxType = boxType !== -1 ? boxType : 0;
                    let pp = new PointCloud.PhotoProject({
                        element: itemEleList[index],
                        imgUrl: item.url,
                        // pcdUrl: options.src,
                        imgWidth: item.width || item.camera_internal.cx * 2,
                        imgHeight: item.height || item.camera_internal.cy * 2,
                        focusLength: item.camera_internal.fx,
                        mat4Array: item.camera_external && item.camera_external.map(ele => parseFloat(ele)),
                        enabled: this.editIndex === index, // 处于放大状态的 默认开启放缩功能
                        index: index,
                        isRectShow: this.boxType === 0,
                        // modes: this.boxType,
                        params: item.camera_internal,
                        translation: item.camera_translation,
                    });
                    this.imageControls.push(pp);
                });
            },

            updateControlSize (index) {
                let target = this.$el.querySelector(`.p-2d-item:nth-of-type(${index + 1})`);
                let box = target.getBoundingClientRect();
                if (this.editIndex === index) {
                    let width = box.width + (window.innerWidth - box.right - 20) + 'px';
                    target.style.width = box.width + (window.innerWidth - box.right - 20) + 'px';
                } else {
                    target.style.width = 'unset';
                }
            },

            //有用重置修改的读取数据
            loadData(d){
                if(!this.pointCloudSegment) return;
                //this.pointCloudSegment.loadPointsData(d);
                this.pointCloudSegment.deleteAllCutObj();
                this.pointCloudSegment.loadData(d);
                this.resultChange({
                    type: 'load',
                    message: d || [],
                });
            }
        },
        destroyed () {
            EventBus.$off('setDefaultLabel', this.setDefaultLabel);
            EventBus.$off('setLabel', this.setLabel);
            EventBus.$off('appendLabel', this.appendLabel);
            EventBus.$off('deleteLabel', this.deleteLabel);
            EventBus.$off('selectShape', this.selectItem);
            EventBus.$off('removeShape', this.removeItemById);
            window.removeEventListener('resize', this.resize);
            this.unbindDomEvent();
            if (this.pointCloudSegment) {
                this.pointCloudSegment._listeners = {};
                this.pointCloudSegment.destroy();
                this.pointCloudSegment = null;
            }
            if (this.pointCloud) {
                erd.uninstall(this.$el.querySelector(".point-cloud-content"))
                this.pointCloud._listeners = {};
                this.pointCloud.dispose();
                this.pointCloud = null;
            }
        }
    };
</script>

<style lang="scss">

    .other-text{
        line-height:24px;margin-left: 5px;margin-right: 5px;
    }

    .point-cloud-wrapper {
        position: relative;
    }

    .point-cloud-content {
        min-height: 520px;
        position: relative;
        width: 100%;

        .point-cloud-2d {
            display: none;
        }

        &.has-2d-image {
            .point-cloud-3d {
                display: inline-block;
                position: relative;
                width: 75.5%;
            }

            .point-cloud-2d {
                display: inline-block;
                width: 24%;
                vertical-align: top;
                overflow-y: auto;

                .point-cloud-2d-move{
                    display: none;
                    position: absolute;
                    width: 0px;
                    height: 0px;
                    right:16px;
                    bottom:16px;

                    z-index: 10000;

                    .front{
                        position: absolute;
                        left: -54px;
                        top:-50px;
                    }
                    .back{
                        position: absolute;
                        left: -54px;
                        top:-24px;
                    }
                    .left{
                        position: absolute;
                        left:-84px;
                        top:-24px;
                    }
                    .right{
                        position: absolute;
                        left:-24px;
                        top:-24px;
                    }
                    .up{
                        position: absolute;
                        left:-128px;
                        top:-50px;
                    }
                    .down{
                        position: absolute;
                        left:-128px;
                        top:-24px;
                    }
                }

                .p-2d-item {
                    overflow: hidden;
                    margin: 5px 0;
                    background-color: #eee;

                    .p-2d-item-content {
                        position: relative;
                        height: 80%;
                        outline: none;
                    }

                    &.editing {
                        position: absolute;
                        top: 0;
                        height: 100% !important;
                        z-index: 3;
                        margin: 0;

                        .point-cloud-2d-move{
                            display: block;
                        }

                        .point-cloud-2d-page{
                            display: block;
                        }
                    }
                }

                .point-cloud-2d-page{
                    position: absolute;
                    left: 16px;
                    bottom: 16px;
                    display: none;
                    z-index: 10000;

                    height: 30px;
                    line-height: 30px;
                    background: #fff;
                    padding-left: 5px;
                    border-radius: 3px;
                }
            }
        }

        .point-cloud-other-tools{
            position:absolute;
            height: 30px;
            left:16px;
            top:16px;
            line-height: 30px;
            color:#fff;
        }

        .point-cloud-message{
            position: absolute;
            left:16px;
            top:46px;
            color:#fff;
        }

        .point-cloud-controls{
            position: absolute;
            left:16px;
            bottom:16px;

            .front{
                position: absolute;
                left: 30px;
                top:-55px;
            }
            .back{
                position: absolute;
                left: 30px;
                top:-29px;
            }
            .left{
                position: absolute;
                left:0px;
                top:-29px;
            }
            .right{
                position: absolute;
                left:60px;
                top:-29px;
            }
            .up{
                position: absolute;
                left:102px;
                top:-54px;
            }
            .down{
                position: absolute;
                left:102px;
                top:-29px;
            }
        }

        .point-cloud--2d-tools {
            position: relative;
            z-index: 2;
        }

        .photo-project-plane {
            position: absolute;
            left: 50%;
            top: 50%;
        }

        .photo-project-stage, .photo-project-drag, .photo-project-rects {
            position: absolute;
            width: 100%;
            height: 100%;
            left: 0;
            top: 0;
        }

        .photo-project-line-1 {
            position: absolute;
            border-top: 1px solid #000;
            /* margin-top: 0.5px;*/
            width: 100%;
            height: 0;
            opacity: 0;
            left: 0;
        }

        .photo-project-line-2 {
            position: absolute;
            border-left: 1px solid #000;
            /*margin-left: 0.5px;*/
            width: 0;
            height: 100%;
            opacity: 0;
            top: 0
        }

        .photo-project-rect {
            position: absolute;
            background: rgba(0, 0, 0, 0.2);
            cursor: pointer;
        }

        .photo-project-rect-select {
            outline: 1px dashed rgba(241, 3, 13, 0.5);
            z-index: 1;
        }

        .photo-project-rect-size {
            position: absolute;
            right: 0;
            bottom: 0;
            width: 0;
            height: 0;
            border-top: 3px solid transparent;
            border-left: 3px solid transparent;
            border-right: 3px solid #ff0000;
            border-bottom: 3px solid #ff0000;
            cursor: se-resize;
        }

        .cube-size-info {
            position: absolute;
            top: 0;
            right: 5px;
            font-size: 12px;
            color: #fff;
            pointer-events: none;
            background-color:rgba(0,0,0,0.8);
        }
    }

    .point-cloud-tools {
        padding: 5px;
        display: flex;
        .right-btns {
            margin-left: 48px;
        }
    }

    .point-cloud-other-tools{
        position:absolute;
        left:0;
        top:0;
    }
</style>

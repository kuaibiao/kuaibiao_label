<template>
    <div class="point-cloud-wrapper">
        <div class="point-cloud-tools" v-if="editable">
            <div class="left-btns">
                <Tooltip placement="top-start">
                    <Button type="primary" size="small" @click="startCreate" icon="md-add"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_create')}}(N)</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="primary" size="small" @click="rotateItem" icon="md-repeat"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_totate')}}(R)</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="primary" size="small" @click="translateItem" icon="md-move"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_move')}}(T)</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="primary" size="small" @click="createCutObj" icon="ios-crop-outline"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_ai_create')}}</p>
                    </div>
                </Tooltip>

                <span :class="textBg">{{$t("cloud_visibility")}}:</span>
                <Select v-model="occlusionMode" size="small" style="width: 80px" @on-change="changeOcclusionMode" placeholder="">
                    <Option value=1>{{$t("cloud_all")}}</Option>
                    <Option value=2>{{$t("cloud_inside")}}</Option>
                    <Option value=3>{{$t("cloud_outside")}}</Option>
                </Select>
                <InputNumber :min="1" v-model="occlusionSize" :disabled="occlusionMode==1" size="small" style="width: 50px" @on-change="changeOcclusionSize"></InputNumber>
                <span :class="textBg">{{$t("cloud_point_size")}}:</span>
                <Select v-model="pointSize" size="small" style="width: 40px" @on-change="changePointSize" placeholder="">
                    <Option value=0.1>{{$t("cloud_small")}}</Option>
                    <Option value=1.0>{{$t("cloud_middle")}}</Option>
                    <Option value=1.5>{{$t("cloud_large")}}</Option>
                </Select>
                <ColorPicker size="small" v-model="background" @on-change="changeBackground" />
            </div>
            <div class="right-btns">
<!--                <Button type="error" size="small" @click="needDeleteItem=true">{{$t('cloud_delete')}}(Del)</Button>
                <Button type="error" size="small" @click="needDeleteAll=true">{{$t('cloud_delete_all')}}</Button>-->

                <Tooltip placement="top">
                    <Button type="error" size="small" @click="needDeleteItem=true" icon="md-backspace"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_delete')}}</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="error" size="small" @click="needDeleteAll=true" icon="md-trash"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_delete_all')}}</p>
                    </div>
                </Tooltip>

                <Checkbox @on-change="toggleShowOne" v-model="showOne">{{$t('show_one')}}</Checkbox>

                <Tooltip placement="top">
                    <Button size="small" icon="md-expand" @click="fullScreen"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_full')}}</p>
                    </div>
                </Tooltip>

                <Modal  width="360"
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
        </div>
        <div class="point-cloud-content" :class="has2dImage ? 'has-2d-image':''" ref="renderTarget">
            <div class="point-cloud-3d"></div>
            <div class="point-cloud-2d">
                <div class="p-2d-item" v-for="(item, index) in image2dList" :key="index"
                     :class="editIndex === index? 'editing': ''" style="height: 24%">
                    <!-- 四点映射-->
                    <ButtonGroup class="point-cloud--2d-tools" v-if="boxType">
                        <Button type="primary" size="small" @click="viewByCamera(index)">{{$t('cloud_angle')}}</Button>
                        <Button type="primary" size="small" @click="createRectDom(index)"
                                v-show="editable">{{$t('cloud_rect_current')}}</Button>
                        <Button type="primary" size="small" @click="createAllRectDom(index)"
                                v-show="editable">{{$t('cloud_rect_all')}}</Button>
                        <Button type="primary" size="small" @click="deleteRectDom(index)" v-show="editable">{{$t('cloud_delete_rect_dom_now')}}</Button>
                        <Button type="primary" size="small" @click="deleteAllRectDom(index)" v-show="editable">{{$t('cloud_delete_rect_dom_all')}}</Button>
                        <Button type="primary" size="small" @click="editCube(index)">{{editIndex > -1 ? $t('cloud_close') : $t('cloud_view')}}

                        </Button>
                    </ButtonGroup>
                    <!--八点映射-->
                    <ButtonGroup class="point-cloud--2d-tools" v-if="!boxType">
                        <Tooltip placement="bottom-start">
                            <Button type="primary" size="small" @click="viewByCamera(index)" icon="md-videocam"></Button>
                            <div slot="content">
                                <p>{{$t('cloud_angle')}}</p>
                            </div>
                        </Tooltip>
                        <Tooltip placement="bottom">
                            <Button type="primary"
                                    size="small" @click="toggleMapSizeAdjust(index)"
                                    v-show="editable" icon="md-build"></Button>

                            <div slot="content">
                                <p>{{$t('cloud_adjust')}}(C)</p>
                            </div>
                        </Tooltip>
                        <Tooltip placement="bottom">
                            <Button type="primary" size="small" @click="resetCube(index)"
                                    v-show="editable" icon="md-refresh-circle"></Button>
                            <div slot="content">
                                <p>{{$t('cloud_reset')}}</p>
                            </div>
                        </Tooltip>

                        <Tooltip placement="bottom">
                            <Button type="primary" size="small" @click="editCube(index)"icon="md-expand"></Button>
                            <div slot="content">
                                <p>{{editIndex > -1 ? $t('cloud_close') : $t('cloud_view')}}</p>
                            </div>
                        </Tooltip>

                    </ButtonGroup>

                    <div class="point-cloud-2d-move" v-if="!boxType">
                        <Button type="primary"  icon="md-arrow-up" size="small" class="front" @click="moveFront(index)"></Button>
                        <Button type="primary"  icon="md-arrow-down" size="small" class="back" @click="moveBack(index)"></Button>
                        <Button type="primary"  icon="md-arrow-back" size="small" class="left" @click="moveLeft(index)"></Button>
                        <Button type="primary"  icon="md-arrow-forward" size="small" class="right" @click="moveRight(index)"></Button>
                        <Button type="primary" shape="circle" icon="ios-arrow-up" size="small" class="up" @click="moveUp(index)"></Button>
                        <Button type="primary" shape="circle" icon="ios-arrow-down" size="small" class="down" @click="moveDown(index)"></Button>
                    </div>

                    <div class="point-cloud-2d-page">
                        <span style="margin-right: 5px">{{$t("cloud_toggle_2D_images")}}</span>
                        <Page style="display: inline" :current="editIndex+1" :total="image2dList.length" :page-size=1 simple @on-change="changeEditCube" />
                    </div>

                    <div class="p-2d-item-content">

                    </div>
                </div>
            </div>
            <div class="point-cloud-other-tools">
                {{$t('cloud_ground_offset')}}<InputNumber v-model="groundOffset" :step="0.1" size="small" @on-change="setGroundOffset"></InputNumber>
            </div>
            <div class="point-cloud-message">
                <div v-html="$t('cloud_item_label')+itemMessage.label"></div>
                <div v-html="$t('cloud_item_height')+itemMessage.minWidth+$t('cloud_unit')+'—'+itemMessage.maxWidth+$t('cloud_unit')+'; '+itemMessage.width+$t('cloud_unit')"></div>
                <div v-html="$t('cloud_item_width')+itemMessage.minHeight+$t('cloud_unit')+'—'+itemMessage.maxHeight+$t('cloud_unit')+'; '+itemMessage.height+$t('cloud_unit')"></div>
                <div v-html="$t('cloud_item_deep')+itemMessage.minDeep+$t('cloud_unit')+'—'+itemMessage.maxDeep+$t('cloud_unit')+'; '+itemMessage.deep+$t('cloud_unit')"></div>
                <div v-html="$t('cloud_item_position')+itemMessage.position+$t('cloud_unit')"></div>
            </div>
            <div class="point-cloud-controls" v-if="editable">
                <Button type="primary"  icon="md-arrow-up" size="small" class="front" @click="rectMoveFront()"></Button>
                <Button type="primary"  icon="md-arrow-down" size="small" class="back" @click="rectMoveBack()"></Button>
                <Button type="primary"  icon="md-arrow-back" size="small" class="left" @click="rectMoveLeft()"></Button>
                <Button type="primary"  icon="md-arrow-forward" size="small" class="right" @click="rectMoveRight()"></Button>
                <Button type="primary" shape="circle"  icon="ios-arrow-up" size="small" class="up" @click="rectMoveUp()"></Button>
                <Button type="primary" shape="circle"  icon="ios-arrow-down" size="small" class="down" @click="rectMoveDown()"></Button>
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

    export default {
        name: "point-cloud",
        mixins: [mixin],
        pointCloud: null,
        SelectAndCreate:null,
        imageControls: [],
        resultList: {},
        watch: {
/*            '$store.state.app.shrink' () {
                // 导航栏变动更新工具大小
                if (this.pointCloud) {
                    setTimeout(() => {
                        this.resize();
                    }, 300);
                }
            }*/
            'editable'(val){
                if(!this.pointCloud) return;

                this.pointCloud.isTCEditorOpen=val;
            }
        },
        data () {
            return {
                isReady: false,
                currentShapeIndex: void 0,
                editable: false,
                has2dImage: false,
                image2dList: [],
                editIndex: -1,
                boxType: 0,
                defaultLabel: null,

                speed: 0.1,
                showOne: false,

                needDeleteItem: false,
                needDeleteAll: false,

                itemMessage: {
                    label: "",

                    maxWidth: "∞",
                    minWidth: "0",

                    maxHeight: "∞",
                    minHeight: "0",

                    maxDeep: "∞",
                    minDeep: "0",

                    width: "0",
                    height: "0",
                    deep: "0",

                    position:"(0,0,0)"
                },

                occlusionMode:1,
                occlusionSize:50,
                pointSize:0.1,
                background:"#000000",

                groundOffset:2.2,

                textBg:""
            };
        },
/*        props:{
            taskInfo: {
                type: Object,
                required: true,
            },
        },*/
        mounted () {
            this.bindDomEvent = this.bindDomEvent.bind(this);
            this.resize = this.resize.bind(this);
            this.resize();
            this.imageControls = [];
            window.addEventListener('resize', this.resize);
            EventBus.$on('setDefaultLabel', this.setDefaultLabel);
            EventBus.$on('setLabel', this.setLabel);
            EventBus.$on('appendLabel', this.appendLabel);
            EventBus.$on('deleteLabel', this.deleteLabel);
            EventBus.$on('selectShape', this.selectItem);
            EventBus.$on('removeShape', this.removeItemById);

            this.textBg = this.fullTextBg();

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

                    this.textBg = this.fullTextBg();

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
                        if (this.imageControls) {
                            this.imageControls.forEach((pp, index) => {
                                this.editIndex === index && this.updateControlSize(index);
                                pp.onWindowResize();
                            });
                        }

                        if(this.SelectAndCreate){
                            this.SelectAndCreate.onWindowResize();
                        }
                    }
                    this.resizing = false;
                }, 10);
            },
            init (options) {
                this.has2dImage = !!(options.image2dList && options.image2dList.length);
                this.image2dList = (options.image2dList || []).map((item) => {
                    return {
                        ...item,
                        adjustIsOpen: false
                    };
                });
                let result = options.result.map((r) => {
                    let occluded = r.bf_occluded;
                    let label = typeof occluded === 'undefined' ? [r.keyText] : [r.keyText, occluded.toString()];
                    if (r.keyText) {
                        return {
                            '3Dcenter': r['3Dcenter'],
                            '3Dsize': r['3Dsize'],
                            cBy: r.cBy,
                            cTime: r.cTime,
                            color: r.color,
                            index: r.index,
                            attr: {
                                label: cloneDeep(label),
                                code: new Array(label.length).fill(''),
                                category: cloneDeep(label),
                            }
                        };
                    } else {
                        return r;
                    }
                });
                this.$nextTick(() => {
                    if(this.SelectAndCreate){
                        this.SelectAndCreate.destroy();
                    }
                    if (this.pointCloud) {
                        this.isReady = false;
                        this.pointCloud.load(options.src, result);
                        this.createImageControls(options, result);
                        this.SelectAndCreate=new PointCloud.SelectAndCreate(this.pointCloud);
                        return;
                    }
                    let ele = this.$el.querySelector('.point-cloud-3d');
                    this.pointCloud = new PointCloud.LookPointCloud({
                        element: ele,
                        pcdUrl: options.src,
                        dataUrl: result,
                        isTCEditorOpen: options.allowEditing,
                        mode: PointCloud.MeshModes.car,
                        alphaInherit: false,
                        groundOffset: options.groundOffset,
                        labelRangeRaidus: options.labelRangeRaidus && parseFloat(options.labelRangeRaidus)
                    });
                    this.SelectAndCreate=new PointCloud.SelectAndCreate(this.pointCloud);
                    this.createImageControls(options, result);
                    this.editable = options.allowEditing;
                    // let userId = this.gmixin_userInfo.id;
                    // this.pointCloud.setBasicMessage({
                    //     mBy: userId,
                    //     step: this.taskInfo?this.taskInfo.step_id:null,
                    //     cStep: this.taskInfo?this.taskInfo.step_id:null,
                    //     mStep: this.taskInfo?this.taskInfo.step_id:null
                    // });
                    this.bindEvent();
                });
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

            fullTextBg(){
                if(this.isFullScreen())
                    return "text-bg2";
                else
                    return "text-bg";
            },

            createCutObj(){
                if(this.SelectAndCreate){
                    this.SelectAndCreate.createCutObj();
                }
            },

            //修改遮罩模式
            changeOcclusionMode(){
                if(!this.pointCloud) return;

                this.pointCloud.changeOcclusionMode(+this.occlusionMode);
            },
            //修改遮罩大小
            changeOcclusionSize(){
                if(!this.pointCloud||!(+this.occlusionSize)) return;

                this.pointCloud.changeOcclusionSize(+this.occlusionSize);
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
            },
            //修改地面偏移
            setGroundOffset(){
                if(!this.pointCloud) return;

                this.pointCloud.setGroundOffset(+this.groundOffset);
            },

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
                    //console.log(item.box_type);
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
                        dataUrl: result,
                        enabled: this.editIndex === index, // 处于放大状态的 默认开启放缩功能
                        index: index,
                        isRectShow: this.boxType === 0,
                        // modes: this.boxType,
                        box_type: item.box_type,
                        params: item.camera_internal,
                        translation: item.camera_translation,
                    });
                    this.imageControls.push(pp);
                });
            },

            toggleShowOne (){
                if(!this.pointCloud) return;

                if(this.showOne) {
                    this.pointCloud.showOne();
                } else {
                    this.pointCloud.unShowOne();
                }
            },

            viewByCamera (index) {
                let pp = this.imageControls[index];
                this.pointCloud.setPointsColor(pp.camera);
            },
            createRectDom (index) {
                let pp = this.imageControls[index];
                pp.createRectDom(pp.nowRect);
            },
            createAllRectDom (index) {
                let pp = this.imageControls[index];
                pp.createAllRectDom();
            },
            deleteRectDom (index) {
                let pp = this.imageControls[index];
                pp.deleteRectDomByNow();
            },
            deleteAllRectDom (index) {
                let pp = this.imageControls[index];
                pp.deleteAllRectDom();
            },
            // scaleCube (index, delta) {
            //     let pp = this.imageControls[index];
            //     pp.addRectScaleByNow(delta);
            // },
            resetCube (index) {
                let pp = this.imageControls[index];
                pp.resetRectByNow();
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
                    case 78: { // N 新增框
                        this.pointCloud.createBox();
                        e.preventDefault();
                        break;
                    }
                    case 82: { // R 开启旋转
                        this.rotateItem();
                        e.preventDefault();
                        break;
                    }
                    case 84: { // T 移动
                        this.translateItem();
                        e.preventDefault();
                        break;
                    }
                    case 8:
                    case 46: { // backspace delete
                        this.pointCloud.deleteMeshByNow();
                        e.preventDefault();
                        break;
                    }
                    case 67 : { // C
                        if (this.imageControls.length && this.editIndex !== -1) {
                            this.toggleMapSizeAdjust(this.editIndex);
                            e.preventDefault();
                            break;
                        }
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
                this.pointCloud.addEventListener('selectMesh', (e) => {
                    this.currentShapeIndex = (e.message && e.message.index) || void 0;
                    this.resultChange(e);
                    this.updateItemMessage(e);
                    this.updateCubeSizeInfo(e);
                    if (this.imageControls) {
                        this.imageControls.forEach((pp) => {
                            pp.createRect(e.message);
                        });
                    }
                });

                this.pointCloud.addEventListener('deleteMesh', (e) => {
                    this.resultChange(e);
                    this.clearItemMessage();
                    if (this.imageControls) {
                        this.imageControls.forEach((pp) => {
                            pp.deleteRectByIndex(e.message.index);
                        });
                    }
                });
                this.pointCloud.addEventListener('updateMesh', (e) => {
                    this.resultChange(e);
                    this.updateItemMessage(e);
                    this.updateCubeSizeInfo(e);
                    if (this.imageControls) {
                        this.imageControls.forEach((pp) => {
                            pp.updateRect(e.message);
                        });
                    }
                    // if(userId!=e.message.mBy) {
                    //     e.message.cBy=e.message.mBy;
                    //     e.message.cTime=e.message.mTime;
                    //     e.message.cStep=e.message.mStep;
                    // }
                    // e.message.mBy=userId;
                    // e.message.mTime=(+Date.now().toString());
                    // e.message.mStep=this.taskInfo?this.taskInfo.step_id:null;
                });

                this.pointCloud.addEventListener('createBoxComplete', (e) => {
                    // 更新创建人信息
                    e.message.cBy = this.gmixin_userInfo.id;
                    e.message.cTime = Date.now();
                    e.message.cStep = this.taskInfo?this.taskInfo.step_id: '';
                    this.currentShapeIndex = e.message.index;
                    let attr;
                    if (this.defaultLabel) {
                        attr = {
                            category: [this.defaultLabel.category || ''],
                            color: this.defaultLabel.color,
                            label: [this.defaultLabel.label || ''],
                            code: [this.defaultLabel.shortValue || ''],
                            maxDepth: this.defaultLabel.maxDepth || undefined,
                            minDepth: this.defaultLabel.minDepth || undefined,
                            maxWidth: this.defaultLabel.maxWidth || undefined,
                            minWidth: this.defaultLabel.minWidth || undefined,
                            maxHeight: this.defaultLabel.maxHeight || undefined,
                            minHeight: this.defaultLabel.minHeight || undefined,
                        };
                    }
                    this.pointCloud.setMessageByIndex({attr,}, e.message.index);
                    this.resultChange(e);
                });
                this.pointCloud.addEventListener('deleteAllMesh', (e) => {
                    this.resultChange(e);
                    this.clearItemMessage();
                    if (this.imageControls) {
                        this.imageControls.forEach((pp) => {
                            pp.deleteRectAllRect(e.message);
                        });
                    }
                });
                this.pointCloud.addEventListener("setMessage", (e) => {
                    this.resultChange(e);
                    this.updateItemMessage(e);
                    if (this.imageControls) {
                        this.imageControls.forEach((pp) => {
                            pp.setMessage(e.message);
                        });
                    }
                });
                this.pointCloud.addEventListener('loadComplete', (e) => {
                    this.isReady = true;
                    this.resultChange({
                        type: 'load',
                        message: e.message || [],
                    });

                    this.$emit('ready');
/*                    if (this.pointCloud) {
                        this.pointCloud.onWindowResize();
                    }*/
                    this.occlusionSize=this.pointCloud.occlusionSize;
                });
                this.pointCloud.addEventListener("loadDataComplete", (e) =>{
                    for(let i=0;i<e.message.length;i++){
                        let so = this.checkSize(e.message[i]);

                        if(so.check){
                            this.pointCloud.clearErrorByIndex(e.message[i].index);
                        }else{
                            this.pointCloud.setErrorByIndex(e.message[i].index);
                        }
                    }

                    this.resultChange({
                        type: 'load',
                        message: e.message || [],
                    });
                });
                this.pointCloud.addEventListener('loadTotal', (e) => {
                    this.$emit('progress', e);
                });
                this.pointCloud.addEventListener('loadError', (e) => {
                    this.$emit('error', e);
                    this.isReady = false;
                });
            },

            changeEditCube(index){
                this.editCube(this.editIndex, ()=> {
                    this.editCube(index-1);
                });
            },

            rectMoveFront(){
                if(this.pointCloud){
                    this.pointCloud.moveFrontByNow(this.speed);
                }
            },
            rectMoveBack(){
                if(this.pointCloud){
                    this.pointCloud.moveFrontByNow(-this.speed);
                }
            },
            rectMoveLeft(){
                if(this.pointCloud){
                    this.pointCloud.moveLeftByNow(this.speed);
                }
            },
            rectMoveRight(){
                if(this.pointCloud){
                    this.pointCloud.moveLeftByNow(-this.speed);
                }
            },
            rectMoveUp(){
                if (this.pointCloud) {
                    this.pointCloud.moveUpByNow(this.speed);
                }
            },
            rectMoveDown(){
                if (this.pointCloud) {
                    this.pointCloud.moveUpByNow(-this.speed);
                }
            },

            moveFront (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveFrontByNow(this.speed);
                }
            },
            moveBack (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveFrontByNow(-this.speed);
                }
            },
            moveLeft (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveLeftByNow(this.speed);
                }
            },
            moveRight (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveLeftByNow(-this.speed);
                }
            },
            moveUp (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveUpByNow(this.speed);
                }
            },
            moveDown (index) {
                let pp = this.imageControls[index];
                if (pp) {
                    pp.moveUpByNow(-this.speed);
                }
            },

            toggleMapSizeAdjust (index) {
                let pp = this.imageControls[index];
                this.image2dList.splice(index, 1, {
                    ...this.image2dList[index],
                    adjustIsOpen: !this.image2dList[index].adjustIsOpen
                });
                pp.toggleTC();
            },
            updateCubeSizeInfo (e) {
                if (this.pointCloud) {

                    let ro = this.checkSize(e.message);

                    let sizeInfo = this.pointCloud.threeSize;
                    let domObj = {
                        t: {
                            ele: this.pointCloud.$doPlaneTop,
                            format: this.$t('cloud_lw'),
                            st:[[ro.wt1,ro.wt2],[ro.ht1,ro.ht2]]
                        },
                        l: {
                            ele: this.pointCloud.$doPlaneLeft,
                            format: this.$t('cloud_lh'),
                            st:[[ro.wt1,ro.wt2],[ro.dt1,ro.dt2]]
                        },
                        f:
                        {
                            ele: this.pointCloud.$doPlaneFront,
                            format: this.$t('cloud_wh'),
                            st:[[ro.ht1,ro.ht2],[ro.dt1,ro.dt2]]
                        }
                    };
                    for (let k in sizeInfo) {
                        if (sizeInfo.hasOwnProperty(k)) {
                            let size = sizeInfo[k];
                            let sizeInfoEle = domObj[k].ele.find('.cube-size-info');
                            let sizeInfoStr = size.reduce((pre, cur, index) => {
                                let st = domObj[k].st[index];
                                return pre.replace('X', st[0] + cur.toFixed(2) + st[1] + this.$t('cloud_unit'));
                            }, domObj[k].format);
                            if (sizeInfoEle.length) {
                                sizeInfoEle.html(sizeInfoStr);
                            } else {
                                domObj[k].ele.append(`<span class="cube-size-info"> ${sizeInfoStr} </span>`);
                            }
                        }
                    }
                }
            },

            updateItemMessage (e) {
                let m = e.message;
                if (m.attr) {
                    this.itemMessage.label = m.attr.label.toString().replace("," , ";");

                    this.itemMessage.minWidth = m.attr.minWidth ? (+m.attr.minWidth).toFixed(2) : 0;
                    this.itemMessage.maxWidth = m.attr.maxWidth ? (+m.attr.maxWidth).toFixed(2) : "∞";
                    this.itemMessage.minHeight = m.attr.minHeight ? (+m.attr.minHeight).toFixed(2) : 0;
                    this.itemMessage.maxHeight = m.attr.maxHeight ? (+m.attr.maxHeight).toFixed(2) : "∞";
                    this.itemMessage.minDeep = m.attr.minDepth ? (+m.attr.minDepth).toFixed(2) : 0;
                    this.itemMessage.maxDeep = m.attr.maxDepth ? (+m.attr.maxDepth).toFixed(2) : "∞";
                } else {
                    this.clearItemMessage();
                }


                let size = m["3Dsize"];
                let pos = m["3Dcenter"];
                let so = this.checkSize(m);

                this.itemMessage.width = so.wt1 + (+size.height).toFixed(2) + so.wt2;
                this.itemMessage.height = so.ht1 + (+size.width).toFixed(2) + so.ht2;
                this.itemMessage.deep = so.dt1 + (+size.deep).toFixed(2) + so.dt2;

                this.itemMessage.position="("+pos.x.toFixed(2)+","+pos.y.toFixed(2)+","+pos.z.toFixed(2)+")";

                if(so.check){
                    this.pointCloud.clearErrorByIndex(m.index);
                }else{
                    this.pointCloud.setErrorByIndex(m.index);
                }
                if (e.type !== 'selectMesh') {
                    let stat = this.getWorkerInfo(m);
                    Object.assign(m, stat);
                }
            },
            clearItemMessage () {
                this.itemMessage.label = "";
                this.itemMessage.minWidth = 0;
                this.itemMessage.maxWidth = '∞';
                this.itemMessage.minHeight = 0;
                this.itemMessage.maxHeight = '∞';
                this.itemMessage.minDeep = 0;
                this.itemMessage.maxDeep = '∞';

                this.itemMessage.width = 0;
                this.itemMessage.height = 0;
                this.itemMessage.deep = 0;
            },
            checkSize (message) {
                let m = message;
                let t1="<span style='color:Red'>";
                let t2="</span>";
                let ro = {
                    wt1: "",
                    wt2: "",
                    ht1: "",
                    ht2: "",
                    dt1: "",
                    dt2: "",
                    check: true
                };

                let size = m["3Dsize"];

                if(m.attr){
                    let attr = m.attr;
                    if(!this.checkNum(attr.minWidth,attr.maxWidth,size.height)){
                        ro.wt1=t1;
                        ro.wt2=t2;
                        ro.check=false;
                    }
                    if(!this.checkNum(attr.minHeight,attr.maxHeight,size.width)){
                        ro.ht1=t1;
                        ro.ht2=t2;
                        ro.check=false;
                    }
                    if(!this.checkNum(attr.minDepth,attr.maxDepth,size.deep)){
                        ro.dt1=t1;
                        ro.dt2=t2;
                        ro.check=false;
                    }
                }

                return ro;
            },
            checkNum(min,max,num){
                let maxTrue=false;
                let minTrue=false;

                if(!min && min!=0){
                    minTrue=true;
                }else{
                    if(num<min)
                        minTrue=false;
                    else
                        minTrue=true;
                }

                if(!max && max!=0){
                    maxTrue=true;
                }else{
                    if(num>max)
                        maxTrue=false;
                    else
                        maxTrue=true;
                }

                if(maxTrue&&minTrue)
                    return true;
                else return false;
            },

            rotateItem () {
                if (this.pointCloud) {
                    this.pointCloud.changeTransformControls('rotate');
                }
            },
            translateItem () {
                if (this.pointCloud) {
                    this.pointCloud.changeTransformControls('translate');
                }
            },
            selectItem (id) {
                if (this.pointCloud) {
                    this.pointCloud.findMeshByIndex(id);
                }
            },
            deleteItem () {
                if (this.currentShapeIndex) {
                    this.removeItemById(this.currentShapeIndex);
                }

                this.needDeleteItem = false;
            },
            removeItemById (index) {
                if (this.pointCloud) {
                    this.pointCloud.deleteMeshByIndex(index);
                }
            },
            deleteAll () {
                if (this.pointCloud) {
                    this.pointCloud.deleteAllMesh();
                }

                this.needDeleteAll = false;
            },
            startCreate () {
                if (this.isReady && this.pointCloud) {
                    this.pointCloud.createBox();
                }
            },
            getResult (save = false) {
                if (this.pointCloud) {
                    let cubeList = {};
                    this.pointCloud.getAllRects().forEach((item) => {
                        cubeList[item.index] = {
                            ...item,
                            type: 'd3d_cube',
                        };
                    });
                    if (this.imageControls && save) {
                        Object.keys(cubeList).forEach((index) => {
                            cubeList[index].cubeMap = [];
                            for (let i = 0; i < this.imageControls.length; i++) {
                                let pp = this.imageControls[i];
                                let data = pp.getDataByIndex(index);
                                if (cubeList[index].imageMap) {
                                    cubeList[index].imageMap.push({
                                        imageUrl: pp.imgUrl,
                                        '3Dcenter': data['3Dcenter'],
                                        '3Dcenter2': data['3Dcenter2'],
                                        '3Dscale': data['3Dscale'],
                                        '3Dsize': data['3Dsize'],
                                        box_type:pp.box_type
                                    });
                                } else {
                                    cubeList[index].imageMap = [{
                                        imageUrl: pp.imgUrl,
                                        '3Dcenter': data['3Dcenter'],
                                        '3Dcenter2': data['3Dcenter2'],
                                        '3Dscale': data['3Dscale'],
                                        '3Dsize': data['3Dsize'],
                                        box_type:pp.box_type
                                    }];
                                }
                                let cubePoints = pp.getRectDataByIndex(index);
                                let bbox = pp.getRectDomDataByIndex(index);
                                cubeList[index].cubeMap.push({
                                    imageUrl: pp.imgUrl,
                                    cubePoints: cubePoints && cubePoints.boxRectData,
                                    bbox
                                });
                            }
                        });
                    }
                    //console.log(cubeList);
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
                switch (e.type) {
                    case 'setMessage':
                    case 'updateMesh':
                    case 'createBoxComplete': {
                        this.resultList[e.message.index] = cloneDeep(e.message);
                        break;
                    }
                    case 'deleteAllMesh': {
                        this.resultList = {};
                        break;
                    }
                    case 'deleteMesh': {
                        delete this.resultList[e.message.index];
                        break;
                    }
                    case 'loadDataComplete':
                    case 'load': {
                        this.resultList = {};
                        e.message.forEach(item => {
                            this.resultList[item.index] = cloneDeep(item);
                        });
                        break;
                    }
                }
                let resultList = Object.values(this.resultList);
                let currentItem = this.pointCloud.getRectByIndex(this.currentShapeIndex);
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
                    //console.log(this.taskInfo);
                    let attr = {
                        category: [label.category || ''],
                        color: label.color,
                        label: [label.label || ''],
                        code: [label.shortValue || ''],
                        maxDepth: label.maxDepth || undefined,
                        minDepth: label.minDepth || undefined,
                        maxWidth: label.maxWidth || undefined,
                        minWidth: label.minWidth || undefined,
                        maxHeight: label.maxHeight || undefined,
                        minHeight: label.minHeight || undefined,
                    };
                    this.pointCloud.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            appendLabel (obj) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloud.getRectByIndex(this.currentShapeIndex);
                    let attr = (target && target.attr) || {
                        category: [],
                        label: [],
                        code: [],
                        color: obj.color,
                        maxDepth: undefined,
                        minDepth: undefined,
                        maxWidth: undefined,
                        minWidth: undefined,
                        maxHeight: undefined,
                        minHeight: undefined,
                    };
                    attr = cloneDeep(attr);
                    if (obj.localTagIsUnique) { // localTagIsUnique值=1  代表可以选多个标签
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
                    } else { // 标签所在组至多一个标签 localTagIsUnique值=0
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
                    this.pointCloud.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            deleteLabel (index) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloud.getRectByIndex(this.currentShapeIndex);
                    if (~index && target.attr) {
                        let attr = cloneDeep(target.attr);
                        attr.label.splice(index, 1);
                        attr.code.splice(index, 1);
                        attr.category.splice(index, 1);
                        this.pointCloud.setMessageByIndex({attr}, this.currentShapeIndex);
                    }
                }
            },

            //有用重置修改的读取数据
            loadData(d){
                if(!this.pointCloud) return;
                this.pointCloud.loadPointsData(d);

                this.imageControls.map((pp)=>{
                    pp.loadData(d);
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
            if (this.SelectAndCreate){
                this.SelectAndCreate.destroy();
                this.SelectAndCreate = null;
            }
            if (this.pointCloud) {
                erd.uninstall(this.$el.querySelector(".point-cloud-content"));
                this.pointCloud._listeners = {};
                this.pointCloud.dispose();
                this.pointCloud = null;
            }
            this.unbindDomEvent();
        }
    };
</script>

<style lang="scss">

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
        justify-content: flex-start;
        .right-btns {
            margin-left: 48px;
        }
    }

    .ivu-btn>.ivu-icon+span, .ivu-btn>span+.ivu-icon{
        margin: 0;
    }
</style>

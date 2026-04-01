<template>
    <div class="point-cloud-wrapper">
        <div class="point-cloud-tools" >
            <div class="left-btns" v-if="editable">
                <Tooltip placement="top">
                    <Button type="primary" size="small" :disabled="playing" @click="startCreate" icon="md-add"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_create')}}(N)</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="primary" size="small" :disabled="playing" @click="rotateItem" icon="md-repeat"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_totate')}}(R)</p>
                    </div>
                </Tooltip>
<!--                <Tooltip placement="top">
                    <Button type="primary" size="small" :disabled="playing" @click="rotateItem" icon="md-repeat"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_totate')}}(R)</p>
                    </div>
                </Tooltip>-->
                <Tooltip placement="top">
                    <Button type="primary" size="small" :disabled="playing" @click="translateItem" icon="md-move"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_move')}}(T)</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="primary" size="small" :disabled="playing" @click="createCutObj" icon="ios-crop-outline"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_ai_create')}}</p>
                    </div>
                </Tooltip>
            </div>
            <div class="play-controls" >
                <ButtonGroup>
                    <Button type="primary" size="small" @click="togglePlay" :disabled="!canplay||pointsLoading">
                        <Icon :type="playing ? 'md-pause' : 'md-play'" />
                    </Button>
                    <Button type="primary" size="small" @click="prevPcd" :disabled="playing||pointsLoading">
                        <Icon type="ios-arrow-back" />
                    </Button>
                    <Button type="primary" size="small" @click="nextPcd" :disabled="playing||pointsLoading">
                        <Icon type="ios-arrow-forward" />
                    </Button>
                </ButtonGroup>
                <Tooltip placement="top">
                    <Button v-if="editable" type="primary" size="small" @click="nextCopy" :disabled="playing||(currentItemIndex==totalCounter)||pointsLoading">
                        <Icon type="ios-paper-outline"/><Icon type="md-arrow-forward"/>
                    </Button>
                    <div slot="content">
                        <p>{{$t("copy_result_to_next_frame")}}</p>
                    </div>
                </Tooltip>
                <InputNumber v-model="currentItemIndex"
                    :min="1" :max="totalCounter"
                    @on-change=changeFrame
                    size='small' :disabled="playing||pointsLoading"></InputNumber>
                <Tag color="default" > {{(currentItemIndex || '') + ' / ' + totalCounter}}</Tag>
                <Tooltip placement="top">
                    <Button type="primary" size="small" @click="toggleTrackLine" ><Icon type="ios-trending-up" /></Button>
                    <div slot="content">
                        <p>{{$t('cloud_line')}}</p>
                    </div>
                </Tooltip>
                <!--<Checkbox v-if="editable" v-model="moveMode" :disabled="playing">{{$t("copy_result_to_next_frame")}}</Checkbox>-->

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
            <div class="right-btns" v-if="editable">
                <!--<Button type="error" size="small" @click="deleteItem" :disabled="playing">{{$t('cloud_delete')}}(Del)</Button>-->
                <Tooltip placement="top">
                    <Button type="error" size="small" @click="needDeleteItemInLine=true" :disabled="playing" icon="md-backspace"></Button>
                    <div slot="content">
                        <p>{{$t('delete_mesh_all_frame')}}</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button type="error" size="small" @click="needDeleteCurrentFrameItems=true" :disabled="playing" icon="md-trash"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_delete_current_frame')}}</p>
                    </div>
                </Tooltip>
                <Tooltip placement="top">
                    <Button size="small" @click="needDeleteAllItems=true" :disabled="playing" icon="md-close-circle"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_delete_all_frame')}}</p>
                    </div>
                </Tooltip>

                <Checkbox @on-change="toggleShowOne" v-model="showOne" :class="textBg">{{$t('show_one')}}</Checkbox>

                <Tooltip placement="top">
                    <Button size="small" icon="md-expand" @click="fullScreen"></Button>
                    <div slot="content">
                        <p>{{$t('cloud_full')}}</p>
                    </div>
                </Tooltip>

                <Modal  width="360"
                        v-model="needDeleteItemInLine"
                        :title="$t('delete_mesh_all_frame')">
                    <div slot="footer">
                        <i-button size="large" @click="needDeleteItemInLine=false">{{$t('cloud_cancel')}}</i-button>
                        <i-button type="error" size="large" @click="deleteItemInLine">{{$t('cloud_ok')}}</i-button>
                    </div>
                    <p>{{$t('delete_mesh_all_frame_text')}}</p>
                </Modal>

                <Modal  width="360"
                        v-model="needDeleteCurrentFrameItems"
                        :title="$t('cloud_delete_current_frame')">
                    <div slot="footer">
                        <i-button size="large" @click="needDeleteCurrentFrameItems=false">{{$t('cloud_cancel')}}</i-button>
                        <i-button type="error" size="large" @click="deleteCurrentFrameItems">{{$t('cloud_ok')}}</i-button>
                    </div>
                    <p>{{$t('cloud_delete_current_frame_text')}}</p>
                </Modal>

                <Modal  width="360"
                        v-model="needDeleteAllItems"
                        :title="$t('cloud_delete_all_frame')">
                    <div slot="footer">
                        <i-button size="large" @click="needDeleteAllItems=false">{{$t('cloud_cancel')}}</i-button>
                        <i-button type="error" size="large" @click="deleteAllItems">{{$t('cloud_ok')}}</i-button>
                    </div>
                    <p>{{$t('cloud_delete_all_frame_text')}}</p>
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
                    <div class="point-cloud--2d-tools" v-if="!boxType">
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

                    </div>
                    <div class="point-cloud-2d-move" v-if="!boxType">
                        <Button type="primary"  icon="md-arrow-up" size="small" class="front" @click="moveFront(index)"></Button>
                        <Button type="primary"  icon="md-arrow-down" size="small" class="back" @click="moveBack(index)"></Button>
                        <Button type="primary"  icon="md-arrow-back" size="small" class="left" @click="moveLeft(index)"></Button>
                        <Button type="primary"  icon="md-arrow-forward" size="small" class="right" @click="moveRight(index)"></Button>
                        <Button type="primary" shape="circle"  icon="md-arrow-up" size="small" class="up" @click="moveUp(index)"></Button>
                        <Button type="primary" shape="circle"  icon="md-arrow-down" size="small" class="down" @click="moveDown(index)"></Button>
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
                <Button type="primary" shape="circle" icon="md-arrow-up" size="small" class="up" @click="rectMoveUp()"></Button>
                <Button type="primary" shape="circle" icon="md-arrow-down" size="small" class="down" @click="rectMoveDown()"></Button>
            </div>

            <div class="point-cloud-total" v-show="dataTotal!=0&&dataTotal<100" :style="{width:dataTotal+'%'}" ></div>

            <div class="point-cloud-loading" v-show="pointsLoading">
                <div class="point-cloud-loading-text">Loading……</div>
            </div>

        </div>
    </div>
</template>

<script>
    import PointCloud from "./pointcloud.js";
    import mixin from './mixin';
    import cloneDeep from 'lodash.clonedeep';
    import find from 'lodash.find';
    import EventBus from '../../common/event-bus';

    import elementResizeDetectorMaker from "element-resize-detector";

    let erd = elementResizeDetectorMaker();

    export default {
        name: "pointcloud-tracking",
        mixins: [mixin],
        PointCloud: null,
        SelectAndCreate: null,
        resultList: {},
        computed: {
            /*getMoveMode () {
                if (this.PointCloud) {
                    return this.PointCloud.moveMode;
                }
            }*/
        },
        watch: {
/*            '$store.state.app.shrink' () {
                // 导航栏变动更新工具大小
                if (this.pointCloud) {
                    setTimeout(() => {
                        this.pointCloud.lpc.onWindowResize();
                    }, 300);
                }
            }*/
        },
        data () {
            return {
                isReady: false,
                currentShapeIndex: void 0,
                editable: false,
                editIndex: -1,
                defaultLabel: null,
                totalCounter: 1,
                currentItemIndex: 1,
                playing: false,
                trackLineShowing: false,

                speed: 0.1,
                showOne: false,

                canplay: false,

                needDeleteItemInLine: false,
                needDeleteCurrentFrameItems: false,
                needDeleteAllItems: false,

                image2dList: [],
                boxType: 0,
                has2dImage: false,

                cameraLookIndex:-1,

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

                textBg:"",

                dataTotal:0,

                pointsLoading:true
            };
        },
        mounted () {
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

            this.textBg = this.fullTextBg();

            erd.listenTo(this.$el.querySelector(".point-cloud-content"),(element) => {
                if (this.pointCloud) {
                    this.resize();
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
                    let targetTol = this.$el.querySelector('.point-cloud-total');

                    let bbox = target.getBoundingClientRect();
                    let height = window.innerHeight - bbox.top;
                    if (height < 520) {
                        height = 520;
                    }
                    targetTol.style.top = (height-3) + 'px';
                    target.style.height = height + 'px';
                    if (target2d) {
                        target2d.style.height = height + 'px';
                    }
                    if (this.pointCloud) {
                        this.pointCloud.lpc.onWindowResize();

                        if (this.pointCloud.pps) {
                            this.pointCloud.pps.forEach((pp, index) => {
                                this.editIndex === index && this.updateControlSize(index);
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

                this.canplay=false;

                this.has2dImage = !!(options.imageUrls && options.imageUrls.length);
                if(this.has2dImage) {
                    this.image2dList = options.imageUrls[0].map((item) => {
                        return {
                            ...item,
                            adjustIsOpen: false
                        };
                    });
                }
                let result = options.result;
                let resultFormat = new Array(options.urls.length);
                // 旧数据转换
                if (result[0] && result[0].frames) {
                    result.forEach((item) => {
                        item.frames.forEach(cube => {
                            if (cube.data) {
                                if (resultFormat[cube.frameNumber]) {
                                    resultFormat[cube.frameNumber].push(cube.data);
                                } else {
                                    resultFormat[cube.frameNumber] = [cube.data];
                                }
                            }
                        });
                    });
                } else {
                    result.forEach((cube) => {
                        let frames = resultFormat[cube.frame];
                        if (frames) {
                            frames.push(cube);
                        } else {
                            frames = [cube];
                        }
                        resultFormat[cube.frame] = frames;
                    })
                }
                let urls = options.urls;
                this.totalCounter = urls.length;
                this.$nextTick(() => {
                    if(this.SelectAndCreate){
                        this.SelectAndCreate.destroy();
                    }
                    if (this.pointCloud) {
                        this.pointCloud.destroy();
                    }

                    this.editable = options.allowEditing;

                    let ele = this.$el.querySelector('.point-cloud-3d');

                    let image2dContainer = this.$el.querySelector('.point-cloud-2d');
                    let itemEleList = image2dContainer.querySelectorAll('.p-2d-item-content');
                    if(options.imageConfigs){
                        options.imageConfigs.map((ic,i)=>{
                            ic.element=itemEleList[i];
                            ic.enabled=(this.editIndex === i);// 处于放大状态的 默认开启放缩功能
                            ic.imgWidth=ic.width || ic.camera_internal.cx * 2;
                            ic.imgHeight=ic.height || ic.camera_internal.cy * 2;
                            ic.focusLength=ic.camera_internal.fx;
                            ic.mat4Array = ic.camera_external && ic.camera_external.map(ele => parseFloat(ele));

                            let boxType = ['box', 'plane'].indexOf(ic.box_type); // box  8个点 plane 4个点
                            this.boxType = boxType !== -1 ? boxType : 0;

                            ic.isRectShow=(this.boxType === 0);
                            //ic.modes=this.boxType;
                        });
                    }



                    this.pointCloud = new PointCloud.LookPointCloudList(urls, resultFormat, {
                            element: ele,
                            // pcdUrl: options.src,
                            dataUrl: result,
                            isTCEditorOpen: options.allowEditing,
                            mode: PointCloud.MeshModes.car,
                            alphaInherit: false,
                            labelRangeRaidus: options.labelRangeRaidus && parseFloat(options.labelRangeRaidus),
                            groundOffset: options.groundOffset && parseFloat(options.groundOffset),

                            unSyncMeshSize: options.unSyncMeshSize
                        },
                        options.imageUrls,
                        options.imageConfigs
                    );
                    this.groundOffset = this.pointCloud.lpc.groundOffset;
                    this.SelectAndCreate=new PointCloud.SelectAndCreate(this.pointCloud.lpc);
                    //this.pointCloud.moveMode = 1;
                    this.currentItemIndex = 1;
                    // let userId = this.gmixin_userInfo.id;
                    // this.pointCloud.lpc.setBasicMessage({
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

                this.pointCloud.lpc.changeOcclusionMode(+this.occlusionMode);

                this.cameraLookIndex = -1;
            },
            //修改遮罩大小
            changeOcclusionSize(){
                if(!this.pointCloud||!(+this.occlusionSize)) return;

                this.pointCloud.lpc.changeOcclusionSize(+this.occlusionSize);

                this.cameraLookIndex = -1;
            },
            //修改点大小
            changePointSize(){
                if(!this.pointCloud) return;

                this.pointCloud.lpc.changePointSize(this.pointSize);
            },
            //修改背景颜色
            changeBackground(){
                if(!this.pointCloud) return;
                if(this.background==="")
                    this.background = "#000000";

                this.pointCloud.lpc.changeBackground(this.background);

                this.cameraLookIndex = -1;
            },
            //修改地面偏移
            setGroundOffset(){
                if(!this.pointCloud) return;

                this.pointCloud.lpc.setGroundOffset(+this.groundOffset);

                this.cameraLookIndex = -1;
            },


            toggleShowOne (){
                if(!this.pointCloud) return;

                this.doShowOne();
            },
            doShowOne (){
                if(this.showOne) {
                    this.pointCloud.lpc.showOne();
                } else {
                    this.pointCloud.lpc.unShowOne();
                }
            },

            viewByCamera (index) {
                let pp = this.pointCloud.pps[index];
                this.pointCloud.lpc.setPointsColor(pp.camera);
                this.cameraLookIndex=index;
            },
            viewByCameraAtNext (){
                if(this.pointCloud){
                    if(this.pointCloud.pps){
                        let pps=this.pointCloud.pps;
                        if(pps[this.cameraLookIndex]){
                            this.viewByCamera(this.cameraLookIndex);
                        }
                    }
                }
            },
            createRectDom (index) {
                let pp = this.pointCloud.pps[index];
                pp.createRectDom(pp.nowRect);
            },
            createAllRectDom (index) {
                let pp = this.pointCloud.pps[index];
                pp.createAllRectDom();
            },
            deleteRectDom (index) {
                let pp = this.pointCloud.pps[index];
                pp.deleteRectDomByNow();
            },
            deleteAllRectDom (index) {
                let pp = this.pointCloud.pps[index];
                pp.deleteAllRectDom();
            },
            // scaleCube (index, delta) {
            //     let pp = this.imageControls[index];
            //     pp.addRectScaleByNow(delta);
            // },
            resetCube (index) {
                let pp = this.pointCloud.pps[index];
                pp.resetRectByNow();
            },
            editCube (index,callback) {
                if (this.editIndex === index) {
                    this.editIndex = -1;
                } else {
                    this.editIndex = index;
                }
                this.$nextTick(() => {
                    let pp = this.pointCloud.pps[index];
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

            rectMoveFront(){
                if(this.pointCloud){
                    this.pointCloud.lpc.moveFrontByNow(this.speed);
                }
            },
            rectMoveBack(){
                if(this.pointCloud){
                    this.pointCloud.lpc.moveFrontByNow(-this.speed);
                }
            },
            rectMoveLeft(){
                if(this.pointCloud){
                    this.pointCloud.lpc.moveLeftByNow(this.speed);
                }
            },
            rectMoveRight(){
                if(this.pointCloud){
                    this.pointCloud.lpc.moveLeftByNow(-this.speed);
                }
            },
            rectMoveUp(){
                if (this.pointCloud) {
                    this.pointCloud.lpc.moveUpByNow(this.speed);
                }
            },
            rectMoveDown(){
                if (this.pointCloud) {
                    this.pointCloud.lpc.moveUpByNow(-this.speed);
                }
            },

            moveFront (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveFrontByNow(this.speed);
                }
            },
            moveBack (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveFrontByNow(-this.speed);
                }
            },
            moveLeft (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveLeftByNow(this.speed);
                }
            },
            moveRight (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveLeftByNow(-this.speed);
                }
            },
            moveUp (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveUpByNow(this.speed);
                }
            },
            moveDown (index) {
                if (this.pointCloud) {
                    this.pointCloud.pps[index].moveUpByNow(-this.speed);
                }
            },

            toggleMapSizeAdjust (index) {
                let pp = this.pointCloud.pps[index];
                if (!pp.enabled) return;

                this.image2dList.splice(index, 1, {
                    ...this.image2dList[index],
                    adjustIsOpen: !this.image2dList[index].adjustIsOpen
                });
                pp.toggleTC();
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
                if (this.playing) return;
                switch (keyCode) {
                    case 78: { // N 新增框
                        this.pointCloud.lpc.createBox();
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
                        this.pointCloud.lpc.deleteMeshByNow();
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
                this.pointCloud.lpc.addEventListener('selectMesh', (e) => {
                    this.currentShapeIndex = (e.message && e.message.index) || void 0;
                    this.resultChange(e);
                    this.updateItemMessage(e);
                    this.updateCubeSizeInfo(e);
                });

                this.pointCloud.lpc.addEventListener('deleteMesh', (e) => {
                    this.resultChange(e);
                    this.clearItemMessage();
                });

                this.pointCloud.lpc.addEventListener('updateMesh', (e) => {
                    this.resultChange(e);
                    this.updateItemMessage(e);
                    this.updateCubeSizeInfo(e);

                    // if(userId!=e.message.mBy){
                    //     e.message.cBy=e.message.mBy;
                    //     e.message.cTime=e.message.mTime;
                    //     e.message.cStep=e.message.mStep;
                    // }
                    // e.message.mBy=userId;
                    // e.message.mTime=(+Date.now().toString());
                    // e.message.mStep=this.taskInfo?this.taskInfo.step_id:null;
                });

                this.pointCloud.lpc.addEventListener('createBoxComplete', (e) => {
                    this.currentShapeIndex = e.message.index;
                    // 更新创建人信息
                    e.message.cBy = this.gmixin_userInfo.id;
                    e.message.cTime = Date.now();
                    e.message.cStep = this.taskInfo?this.taskInfo.step_id: '';
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
                    this.pointCloud.lpc.setMessageByIndex({attr}, e.message.index);
                    this.resultChange(e);
                });

                this.pointCloud.lpc.addEventListener('deleteAllMesh', (e) => {
                    this.resultChange(e);
                    this.clearItemMessage();
                });

                this.pointCloud.lpc.addEventListener("setMessage", (e) => {
                    this.resultChange(e);
                    this.updateItemMessage(e);
                });

                this.pointCloud.lpc.addEventListener('loadStart',(e)=>{
                    this.pointsLoading=true;
                });

                this.pointCloud.lpc.addEventListener('loadComplete', (e) => {
                    this.isReady = true;
                    this.resultChange({
                        type: 'load',
                        message: e.message || [],
                    });
                    this.$emit('ready');

                    this.viewByCameraAtNext();

                    this.occlusionSize=this.pointCloud.lpc.occlusionSize;
                });
                this.pointCloud.lpc.addEventListener("loadDataComplete", (e) => {
                    for(let i=0;i<e.message.length;i++){
                        let so = this.checkSize(e.message[i]);

                        if(so.check){
                            this.pointCloud.lpc.clearErrorByIndex(e.message[i].index);
                        }else{
                            this.pointCloud.lpc.setErrorByIndex(e.message[i].index);
                        }
                    }

                    this.pointsLoading=false;

                });
                this.pointCloud.lpc.addEventListener('loadTotal', (e) => {
                    this.$emit('progress', e);
                });

                this.pointCloud.lpc.addEventListener('loadError', (e) => {
                    this.$emit('error', e);
                    this.isReady = false;
                });
                this.pointCloud.addEventListener('indexChange', (e) => {
                    this.currentItemIndex = e.message + 1;
                    this.viewByCameraAtNext();
                    this.doShowOne();
                });

                this.pointCloud.addEventListener("preLoadComplete", (e) => {
                    this.canplay = true;

                    this.dataTotal = 100;
                });

                this.pointCloud.addEventListener("preLoadTotal",(e)=>{
                    this.dataTotal=e.message;
                })
            },

            changeEditCube(index){
                this.editCube(this.editIndex, ()=> {
                    this.editCube(index-1);
                });
            },

            changeFrame (frameIndex) {
                if (this.pointCloud && typeof frameIndex === 'number') {
                    this.pointCloud.moveTo(frameIndex - 1);
                }
            },
            saveFrame () {
                if (this.pointCloud) {
                    this.pointCloud.saveNow();
                }
            },
            toggleTrackLine () {
                if (!this.pointCloud.needShowLine) {
                    this.pointCloud.showLine();
                } else {
                    this.pointCloud.hideLine();
                }
            },
            togglePlay () {
                if (this.playing) {
                    this.pointCloud.pause();
                } else {
                    this.pointCloud.play();
                }
                this.playing = !this.playing;
            },
            prevPcd () {
                if (this.pointCloud) {
                    this.pointCloud.prev();
                }
            },
            nextPcd () {
                if (this.pointCloud) {
                    this.pointCloud.next();
                }
            },
            nextCopy () {
                if (this.pointCloud) {
                    this.pointCloud.nextCopy();
                }
            },
            updateCubeSizeInfo (e) {
                if (this.pointCloud) {

                    let ro = this.checkSize(e.message);

                    let sizeInfo = this.pointCloud.lpc.threeSize;
                    let domObj = {
                        t: {
                            ele: this.pointCloud.lpc.$doPlaneTop,
                            format: this.$t('cloud_lw'),
                            st:[[ro.wt1,ro.wt2],[ro.ht1,ro.ht2]]
                        },
                        l: {
                            ele: this.pointCloud.lpc.$doPlaneLeft,
                            format: this.$t('cloud_lh'),
                            st:[[ro.wt1,ro.wt2],[ro.dt1,ro.dt2]]
                        },
                        f:
                        {
                            ele: this.pointCloud.lpc.$doPlaneFront,
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
                    this.pointCloud.lpc.clearErrorByIndex(m.index);
                }else{
                    this.pointCloud.lpc.setErrorByIndex(m.index);
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
                    this.pointCloud.lpc.changeTransformControls('rotate');
                }
            },
            translateItem () {
                if (this.pointCloud) {
                    this.pointCloud.lpc.changeTransformControls('translate');
                }
            },
            selectItem (id) {
                if (this.pointCloud) {
                    this.pointCloud.lpc.findMeshByIndex(id);
                }
            },
            deleteItem () {
                if (this.currentShapeIndex) {
                    this.removeItemById(this.currentShapeIndex);
                }
            },
            deleteItemInLine () {
                this.needDeleteItemInLine = false;
                if (this.pointCloud) {
                    this.pointCloud.deleteMeshInLineByNow();
                }
            },
            removeItemById (index) {
                if (this.pointCloud) {
                    this.pointCloud.lpc.deleteMeshByIndex(index);
                }
            },
            deleteCurrentFrameItems () {
                this.needDeleteCurrentFrameItems = false;
                if (this.pointCloud) {
                    this.pointCloud.lpc.deleteAllMesh();
                }
            },
            deleteAllItems () {
                this.needDeleteAllItems = false;
                if (this.pointCloud) {
                    this.pointCloud.clearData();
                }
            },
            startCreate () {
                if (this.isReady && this.pointCloud) {
                    this.pointCloud.lpc.createBox();
                }
            },
            getResult (save = false) {
                if (this.pointCloud) {
                    let cubelist = this.pointCloud.getData();
                    let result = [];
                    cubelist.forEach((cubes, frame) => {
                        cubes.forEach(cube => {
                            cube.type = "pcl_tracking",
                            cube.id = cube.index;
                            cube.frame = frame;
                            result.push(cube);
                        })
                    })
                    // let result = [];
                    // let tracklines = this.pointCloud.getLineData();
                    // tracklines.forEach(trackLine => {
                    //     result.push({
                    //         type: "pcl_tracking",
                    //         id: trackLine.index,
                    //         frames: [],
                    //         trackLine: trackLine.data
                    //     });
                    // });
                    // result.forEach(track => {
                    //     cubelist.forEach((cubeFrame, index) => {
                    //         let cube = cubeFrame.find(cube => cube.index === track.id);
                    //         let frame = {
                    //             data: cube || null,
                    //             frameNumber: index
                    //         };
                    //         track.frames.push(frame);
                    //     });
                    // });
                    return result;
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
                    case 'load': {
                        this.resultList = {};
                        e.message.forEach(item => {
                            this.resultList[item.index] = cloneDeep(item);
                        });
                        break;
                    }
                }
                let resultList = Object.values(this.resultList);
                let currentItem = this.pointCloud.lpc.getRectByIndex(this.currentShapeIndex);
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
                        maxDepth: label.maxDepth || undefined,
                        minDepth: label.minDepth || undefined,
                        maxWidth: label.maxWidth || undefined,
                        minWidth: label.minWidth || undefined,
                        maxHeight: label.maxHeight || undefined,
                        minHeight: label.minHeight || undefined,
                    };
                    this.pointCloud.lpc.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            appendLabel (obj) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloud.lpc.getRectByIndex(this.currentShapeIndex);
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
                    this.pointCloud.lpc.setMessageByIndex({attr}, this.currentShapeIndex);
                }
            },
            deleteLabel (index) {
                if (this.currentShapeIndex) {
                    let target = this.pointCloud.lpc.getRectByIndex(this.currentShapeIndex);
                    if (~index && target.attr) {
                        let attr = cloneDeep(target.attr);
                        attr.label.splice(index, 1);
                        attr.code.splice(index, 1);
                        attr.category.splice(index, 1);
                        this.pointCloud.lpc.setMessageByIndex({attr}, this.currentShapeIndex);
                    }
                }
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
                this.pointCloud.destroy();
                this.pointCloud = null;
            }
            this.unbindDomEvent();

        }
    };
</script>

<style lang="scss">

    .point-cloud-wrapper {
        position: relative;

        .text-bg2{
/*            background: rgba(0,0,0,0.7);
            color:#fff;*/
        }

        .text-bg{
            color:#000;
        }
    }

    .point-cloud-total{
        position:absolute;
        height:3px;
        z-index:10;
        background-color:#57a3f3;
        left:0;
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
                    background-color: #6a6c6f;

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
    }

    .play-controls{
        margin-left: 15px;
        margin-top: -1px;
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

    .point-cloud-loading{
        position:absolute;
        width: 100%;
        height:100%;
        left:0;
        top:0;

        .point-cloud-loading-text{
            height: 50px;
            width: 100%;
            text-align: center;
            color:#eee;
            font-size: 20px;
            line-height: 50px;
            left:0;
            top:50%;
            margin-top:-25px;
            position: absolute;
        }

    }

</style>

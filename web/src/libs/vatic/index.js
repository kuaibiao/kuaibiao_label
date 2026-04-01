import './polyfill.js';
import PouchDB from './pouchdb.min.js';
import uuid from 'uuid/v4';
import JSZipUtils from './jszip-utils.min';
import cloneDeep from 'lodash.clonedeep';
import {i18n} from "../../locale";
import {
    FramesManager,
    blobToImage,
    dataURLtoBlob,
    extractFramesFromVideo,
    extractFramesFromZip,
    OpticalFlow,
    BoundingBox,
    AnnotatedFrame,
    AnnotatedObject,
    AnnotatedObjectsTracker,
} from './vatic';
import Resizable from 'resizable';

import {
    Common,
    MouseEvent,
    ImgInfo,
    CanvasInfo,
    ZoomIndex,
    Inputs,
} from './zoom/index';

export function initVideoAnnotation (container, videoUrl, result, EventBus, serverTime, userId) {
    var clientTime = Math.floor(new Date().valueOf() / 1000);
    function getTime () {
        let now = Math.floor(new Date().valueOf() / 1000);
        return +serverTime + (now - clientTime);
    }
    $(container).css('padding', 5).html(
        `<div id="doodle"> 
            <div id="guide-x" class="guide-box g-x" data-tip="x轴"></div> <!--辅助线-->
            <div id="guide-y" class="guide-box g-y" data-tip="y轴"></div>
            <canvas id="canvas"></canvas>
            <canvas id="canvas2"></canvas>
        </div>
        <div class="video-info">
         <div class="video-info-size" >
             <span id="videoDimensions"></span>
             <span id="extractionProgress"></span>
         </div>
         <div class="video-info-progress"> 
          ${i18n.t('tool_video_play_progress')}：<span id="playerStatus" class="play-status"></span>
          ${i18n.t('tool_video_play_speed')}:
            <input type="number" id="speed" value="0.2" min="0.1" max="4" step="0.1" style="width:60px" /></div>
         </div>
        <div class="video-control">
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_pre_frame')}" disabled id="preframe" />
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_next_frame')}" disabled id="nextframe" />
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_play_pause')}" disabled id="playpause" />
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_add_item')}" disabled id="addAnn" />
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_cancel_item')}"  disabled id="delAnn" />
            <input type="button" class="ivu-btn ivu-btn-small control" value="${i18n.t('tool_video_help_line')}"  disabled id="toggleGuideLine" />
        </div>
        
        <div id="slider"></div>
        <div id="objects"></div>`
    );
    let preFrameEle = $('#preframe');
    let nextFrameEle = $('#nextframe');
    let playPauseEle = $('#playpause');
    let addAnnELe = $('#addAnn');
    let delAnnEle = $('#delAnn');
    let toggleGuideLineEle = $('#toggleGuideLine');
    preFrameEle.click(function () {
        framesPre();
    });
    nextFrameEle.click(function () {
        framesNext();
    });
    playPauseEle.click(function () {
        playPause();
    });
    addAnnELe.click(function () {
        annBtn();
    });
    delAnnEle.click(function () {
        delAnn();
    });
    toggleGuideLineEle.click(function () {
        toggleGuideLine();
    });
    // 配置信息
    let config = {
        // Should be higher than real FPS to not skip real frames 应高于实际FPS以不跳过实帧
        // Hardcoded due to JS limitations 由于JS限制而硬编码
        fps: 18,
        // Low rate decreases the chance of losing frames with poor browser performances
        // 低速率降低了浏览器性能不佳的丢失机率
        playbackRate: 0.4,
        // Format of the extracted frames 提取帧的格式
        imageMimeType: 'image/jpeg',
        imageExtension: '.jpg',
        // Name of the extracted frames zip archive 提取的框架名称zip存档
        framesZipFilename: 'extracted-frames.zip',
        viewWidth: $(container).width() - 32,
    };
    config.viewWidth = config.viewWidth > 800 ? 800 : config.viewWidth;
    let doodle = document.querySelector('#doodle'); // 涂鸦
    let canvas = document.querySelector('#canvas');
    let canvas2 = document.querySelector('#canvas2');
    let ctx = canvas.getContext('2d');
    // let videoFile = document.querySelector('#videoFile1'); // 1.视频文件
    // let zipFile = document.querySelector('#zipFile1'); // 2.Zip文件
    let videoDimensionsElement = document.querySelector('#videoDimensions'); // 视频尺寸
    let extractionProgressElement = document.querySelector('#extractionProgress');
    let downloadFramesButton = document.querySelector('#downloadFrames');
    let playButton = document.querySelector('#play');
    // let pauseButton = document.querySelector('#pause');
    let speedInput = document.querySelector('#speed');
    let sliderElement = document.querySelector('#slider');
    let playerStatus = document.querySelector('#playerStatus');
    let framesManager = new FramesManager(config);
    let annotatedObjectsTracker = new AnnotatedObjectsTracker(framesManager);
    window.annotatedObjectsTracker = annotatedObjectsTracker;
    let slider = {
        init: function (min, max, onChange) {
            $(sliderElement).slider('option', 'min', min);
            $(sliderElement).slider('option', 'max', max);
            $(sliderElement).on('slidestop', (e, ui) => {
                onChange(ui.value);
            });
            $(sliderElement).slider('enable');
        },
        setPosition: function (frameNumber) {
            $(sliderElement).slider('option', 'value', frameNumber);
        },
        reset: function () {
            $(sliderElement).slider({disabled: true});
        }
    };
    slider.reset();
    let player = {
        currentFrame: 0,
        isPlaying: false,
        isReady: false,
        timeout: null,
        initialize: function () { // 初始化
            this.currentFrame = 0;
            this.isPlaying = false;
            this.isReady = false;
            // playButton.disabled = true;
            // playButton.style.display = 'inline-block';
            // pauseButton.disabled = true;
            // pauseButton.style.display = 'none';
        },
        ready: function () {
            this.isReady = true;
            // playButton.disabled = false;
        },
        seek: function (frameNumber) {
            if (!this.isReady) {
                return;
            }
            this.pause();
            if (frameNumber >= 0 && frameNumber < framesManager.frames.totalFrames()) {
                this.drawFrame(frameNumber);
                this.currentFrame = frameNumber;
            }
        },
        play: function () {
            if (!this.isReady) {
                return;
            }
            this.isPlaying = true;
            // playButton.disabled = true;
            // playButton.style.display = 'none';
            // pauseButton.disabled = false;
            // pauseButton.style.display = 'inline-block';
            this.nextFrame();
        },
        pause: function () {
            if (!this.isReady) {
                return;
            }
            this.isPlaying = false;
            if (this.timeout !== null) {
                clearTimeout(this.timeout);
                this.timeout = null;
            }
            // pauseButton.disabled = true;
            // pauseButton.style.display = 'none';
            // playButton.disabled = false;
            // playButton.style.display = 'inline-block';
        },
        toogle: function () {
            if (!this.isPlaying) {
                this.play();
            } else {
                this.pause();
            }
        },
        nextFrame: function () {
            if (!this.isPlaying) {
                return;
            }
            if (this.currentFrame >= framesManager.frames.totalFrames()) {
                this.done();
                return;
            }
            this.drawFrame(this.currentFrame).then(() => {
                this.currentFrame++;
                this.timeout = setTimeout(() => this.nextFrame(), 1000 / (config.fps * parseFloat(speedInput.value)));
            });
        },
        drawFrame: function (frameNumber) { // 播放每一帧
            return new Promise((resolve, _) => {
                annotatedObjectsTracker.getFrameWithObjects(frameNumber).then((frameWithObjects) => {
                    // ctx.drawImage(frameWithObjects.img, 0, 0, frameWithObjects.img.width, frameWithObjects.img.height, 0, 0, config.viewWidth, config.viewHeight);
                    // ctx.drawImage(frameWithObjects.img, 0, 0);
                    commonObj2.img = frameWithObjects.img;
                    imgInfoObj2.init(commonObj2.img);
                    inputsObj2.init(imgInfoObj2);
                    imgInfoObj2.imgDraw();
                    for (let i = 0; i < frameWithObjects.objects.length; i++) {
                        let object = frameWithObjects.objects[i];
                        let annotatedObject = object.annotatedObject;
                        let annotatedFrame = object.annotatedFrame;
                        // 回显标签
                        $(annotatedObject.controls).find('.txt-name').text(annotatedFrame.attr && annotatedFrame.attr.label.join(','));
                        if (annotatedFrame.isVisible()) {
                            annotatedObject.dom.style.display = 'inline-block';
                            let pos = {};
                            if (imgInfoObj2.scale != 0) { // 初始化标注时,检测当前帧是否放大缩小
                                let width = annotatedFrame.bbox.width * (imgInfoObj2.draw.width / imgInfoObj2.draw.swidth);
                                let height = annotatedFrame.bbox.height * (imgInfoObj2.draw.height / imgInfoObj2.draw.sheight);
                                let left = imgInfoObj2.draw.x + annotatedFrame.bbox.x * (imgInfoObj2.draw.width / imgInfoObj2.draw.swidth);
                                let top = imgInfoObj2.draw.y + annotatedFrame.bbox.y * (imgInfoObj2.draw.width / imgInfoObj2.draw.swidth);
                                pos = {x: left, y: top, width: width, height: height};
                            } else {
                                pos = {x: annotatedFrame.bbox.x, y: annotatedFrame.bbox.y, width: annotatedFrame.bbox.width, height: annotatedFrame.bbox.height};
                            }
                            annotatedObject.dom.style.width = pos.width + 'px';
                            annotatedObject.dom.style.height = pos.height + 'px';
                            annotatedObject.dom.style.left = pos.x + 'px';
                            annotatedObject.dom.style.top = pos.y + 'px';
                            annotatedObject.visible.prop('checked', true);
                        } else {
                            annotatedObject.dom.style.display = 'none';
                            annotatedObject.visible.prop('checked', false);
                        }
                    }
                    let shouldHideOthers = frameWithObjects.objects.some(o => o.annotatedObject.hideOthers);
                    if (shouldHideOthers) {
                        for (let i = 0; i < frameWithObjects.objects.length; i++) {
                            let object = frameWithObjects.objects[i];
                            let annotatedObject = object.annotatedObject;
                            if (!annotatedObject.hideOthers) {
                                annotatedObject.dom.style.display = 'none';
                            }
                        }
                    }
                    globalAnnObjs.renderLabelList();
                    slider.setPosition(this.currentFrame);
                    this.processStatus(); // 显示当前播放到多少帧了
                    resolve();
                });
            });
        },
        done: function () {
            this.currentFrame = 0;
            this.isPlaying = false;
            // slider.setPosition(this.currentFrame);
            // playButton.disabled = false;
            // playButton.style.display = 'inline-block';
            // pauseButton.disabled = true;
            // pauseButton.style.display = 'none';
        },
        processStatus: function () { // 当前播放到多少帧了
            playerStatus.innerHTML = (this.currentFrame + 1) + "/" + framesManager.frames.totalFrames() + i18n.t('tool_video_frame');
        }
    };
    let commonObj2 = new Common();
    let inputsObj2 = new Inputs();
    let canvasInfoObj2 = new CanvasInfo(commonObj2);
    let imgInfoObj2 = new ImgInfo(commonObj2, canvasInfoObj2, inputsObj2, player);
    // inputsObj2.init(imgInfoObj2);
    let mouseEventObj2 = new MouseEvent(commonObj2, imgInfoObj2, inputsObj2);
    let zoomIndexObj2 = new ZoomIndex(commonObj2, canvasInfoObj2, inputsObj2, imgInfoObj2, mouseEventObj2);
    function clearAllAnnotatedObjects () { // 2.清除'所有'标注对象
        for (let i = 0; i < annotatedObjectsTracker.annotatedObjects.length; i++) {
            clearAnnotatedObject(i);
        }
    }
    function clearAnnotatedObject (i) { // 3.清除标注对象
        let annotatedObject = annotatedObjectsTracker.annotatedObjects[i];
        annotatedObject.controls.remove();
        annotatedObject.controls.unbind('click');
        $(annotatedObject.dom).unbind('click');
        annotatedObject.dom.resizable.destroy();
        $(annotatedObject.dom).remove();
        globalAnnObjs.index = -1;
        annotatedObjectsTracker.annotatedObjects.splice(i, 1);
    }
    // playButton.addEventListener('click', playClicked, false);
    // pauseButton.addEventListener('click', pauseClicked, false);
    // function playClicked () {
    //     player.play();
    // }
    //
    // function pauseClicked () {
    //     player.pause();
    // }
    // blob转成base64
    function blobToDataURL (blob, callback) {
        var a = new FileReader();
        a.onload = function (e) {
            callback(e.target.result);
        };
        a.readAsDataURL(blob);
    }

    function initializeCanvasDimensions (img) {
        var outerBox = {width: 0, height: 0};
        outerBox.width = parseInt($(doodle).width()); // canvas外部div的宽
        if (outerBox.width < img.width) {
            outerBox.height = Math.round((outerBox.width / img.width) * img.height);
        } else {
            outerBox.height = img.height;
        }
        doodle.style.width = outerBox.width + 'px';
        doodle.style.height = outerBox.height + 'px';
        canvas.width = outerBox.width;
        canvas.height = outerBox.height;
        sliderElement.style.width = outerBox.width + 'px';
        updateCanvasByImg(img);
        zoomIndexObj2.ready(img); // 初始化缩放拖拽辅助线插件
    }
    function updateCanvasByImg (img) {
        if (img) {
            if (annotatedObjectsTracker.ctx && annotatedObjectsTracker.ctx.canvas) {
                annotatedObjectsTracker.ctx.canvas.width = img.width;
                annotatedObjectsTracker.ctx.canvas.height = img.height;
            }
        }
    }
    // 加载Zip文件
    function extractionFilefromZip (src) {
        clearAllAnnotatedObjects(); slider.reset(); player.initialize();
        let promise;
        promise = new Promise((resolve, _) => {
            JSZipUtils.getBinaryContent(src, function (err, data) {
                if (err) { throw err; }
                resolve(extractFramesFromZip(config, data));
            });
        });
        promise.then((frames) => { // 视频提取完成
            URL.revokeObjectURL(src);
            setTimeout(function () { extractionProgressElement.innerHTML = i18n.t('tool_video_capture_finish') + frames.totalFrames() + i18n.t('tool_video_frame'); }, 500);
            if (frames.totalFrames() > 0) {
                frames.getFrame(0).then((blob) => {
                    blobToImage(blob).then((img) => {
                        initializeCanvasDimensions(img);
                        ctx.drawImage(img, 0, 0);
                        // ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, config.viewWidth, config.viewHeight);
                        videoDimensionsElement.innerHTML = i18n.t('tool_video_video_size') + img.width + 'x' + img.height;
                        framesManager.set(frames);
                        commonObj2.toggleGuideLine();
                        slider.init(0, framesManager.frames.totalFrames() - 1, (frameNumber) => player.seek(frameNumber));
                        player.ready();
                        showResult(result);
                        [preFrameEle,
                            nextFrameEle,
                            playPauseEle,
                            addAnnELe,
                            delAnnEle,
                            toggleGuideLineEle].map(ele => {
                            ele.prop('disabled', false);
                        });
                    });
                });
            }
        });
    }
    function extractionFileUploaded (src) {
        clearAllAnnotatedObjects();
        slider.reset();
        player.initialize();

        let promise;

        let dimensionsInitialized = false;

        promise = extractFramesFromVideo(
            config,
            src,
            (percentage, framesSoFar, lastFrameBlob) => {
                blobToImage(lastFrameBlob).then((img) => {
                    if (!dimensionsInitialized) {
                        dimensionsInitialized = true;
                        initializeCanvasDimensions(img);
                    }
                    // ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, config.viewWidth, config.viewHeight);
                    // ctx.drawImage(img, 0, 0);
                    videoDimensionsElement.innerHTML = '视频尺寸: ' + img.width + 'x' + img.height;
                    extractionProgressElement.innerHTML = '已完成 ' + (percentage * 100).toFixed(2) + ' %  ' + framesSoFar + '帧';
                });
            });

        promise.then((frames) => {
            extractionProgressElement.innerHTML = '解析完成，当前视频共 ' + frames.totalFrames() + '帧';
            if (frames.totalFrames() > 0) {
                frames.getFrame(0).then((blob) => {
                    blobToImage(blob).then((img) => {
                        initializeCanvasDimensions(img);
                        // ctx.drawImage(img, 0, 0);
                        // ctx.drawImage(img, 0, 0, img.width, img.height, 0, 0, config.viewWidth, config.viewHeight);
                        videoDimensionsElement.innerHTML = '视频尺寸: ' + img.width + 'x' + img.height;

                        framesManager.set(frames);
                        slider.init(
                            0,
                            framesManager.frames.totalFrames() - 1,
                            (frameNumber) => player.seek(frameNumber)
                        );
                        player.ready();
                        showResult(result);
                        [preFrameEle,
                            nextFrameEle,
                            playPauseEle,
                            addAnnELe,
                            delAnnEle].map(ele => {
                            ele.prop('disabled', false);
                        });
                    });
                });
            }
        });
    }
    function interactify (dom, onChange) {
        let bbox = $(dom);
        bbox.addClass('bbox');
        let createHandleDiv = (className) => {
            let handle = document.createElement('div');
            handle.className = className;
            bbox.append(handle);
            return handle;
        };
        var resizable = new Resizable(dom, {
            within: 'parent',
            handles: 's, se, e, ne, n, nw, w, sw',
            threshold: 1,
            draggable: {
                within: 'parent',
                handle: createHandleDiv('handle center-drag')
            },
            css3: false,
        });
        resizable.draggable.on('dragend', function () {
            let position = bbox.position();
            onChange(position.left, position.top, bbox.outerWidth(), bbox.outerHeight());
        });
        dom.resizable = resizable;
        resizable.on('resize', function () {
            let position = bbox.position();
            onChange(position.left, position.top, bbox.outerWidth(), bbox.outerHeight());
        });
    }
    let mouse = {x: 0, y: 0, startX: 0, startY: 0};
    let tmpAnnotatedObject = null;
    doodle.onmousemove = function (e) {
        let ev = e || window.event;
        if (ev.pageX) {
            mouse.x = ev.pageX;
            mouse.y = ev.pageY;
        } else if (ev.clientX) {
            mouse.x = ev.clientX;
            mouse.y = ev.clientY;
        }
        let offset = $(doodle).offset();
        mouse.x -= offset.left;
        mouse.y -= offset.top;
        // 辅助线
        if (mouseEventObj2.guideIsShow === true) {
            if (doodle.style.cursor == 'crosshair') {
                $('#guide-x').css({"top": (mouse.y + 1) + 'px'});
                $('#guide-y').css({"left": (mouse.x + 1) + 'px'});
            } else {
                $('#guide-x').css({"top": mouse.y + 'px'});
                $('#guide-y').css({"left": mouse.x + 'px'});
            }
        }
        if (tmpAnnotatedObject !== null) {
            tmpAnnotatedObject.width = Math.abs(mouse.x - mouse.startX);
            tmpAnnotatedObject.height = Math.abs(mouse.y - mouse.startY);
            tmpAnnotatedObject.x = (mouse.x - mouse.startX < 0) ? mouse.x : mouse.startX;
            tmpAnnotatedObject.y = (mouse.y - mouse.startY < 0) ? mouse.y : mouse.startY;
            tmpAnnotatedObject.dom.style.width = tmpAnnotatedObject.width + 'px';
            tmpAnnotatedObject.dom.style.height = tmpAnnotatedObject.height + 'px';
            tmpAnnotatedObject.dom.style.left = tmpAnnotatedObject.x + 'px';
            tmpAnnotatedObject.dom.style.top = tmpAnnotatedObject.y + 'px';
        }
    };
    doodle.onclick = function (e) {
        if (doodle.style.cursor !== 'crosshair') {
            return;
        }
        if (tmpAnnotatedObject !== null) {
            let annotatedObject = new AnnotatedObject(framesManager);
            annotatedObject.cBy = userId;
            annotatedObject.cTime = getTime();
            console.log(globalAnnObjs.defaultLabel, player.currentFrame);
            let defaultLabel = globalAnnObjs.defaultLabel;
            annotatedObject.label = Object.assign({}, globalAnnObjs.defaultLabel); // todo Label
            // annotatedObject.name = annotatedObject.label.label;
            annotatedObject.dom = tmpAnnotatedObject.dom;
            // 鼠标点击选中,再次点击取消
            $(annotatedObject.dom).click(function () {
                // 查出当前dom索引值
                for (let i = 0; annotatedObjectsTracker.annotatedObjects.length; i++) {
                    if (annotatedObject === annotatedObjectsTracker.annotatedObjects[i]) {
                        globalAnnObjs.selectAnnObjs(i);
                        break;
                    }
                }
                globalAnnObjs.renderLabelList();
            });

            let bbox = new BoundingBox(tmpAnnotatedObject.x, tmpAnnotatedObject.y, tmpAnnotatedObject.width, tmpAnnotatedObject.height);
            if (imgInfoObj2.scale !== 0) { // 初始化标注时,检测当前帧是否放大缩小
                let boxTmp = imgInfoObj2.getBoxCoordinate(bbox);
                bbox.x = boxTmp.x;
                bbox.y = boxTmp.y;
                bbox.width = boxTmp.width;
                bbox.height = boxTmp.height;
            }
            annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, cloneDeep(annotatedObject.label)));
            // console.log(framesManager);
            annotatedObjectsTracker.annotatedObjects.push(annotatedObject);
            tmpAnnotatedObject = null;
            interactify(
                annotatedObject.dom,
                (x, y, width, height) => {
                    let boxTmp = imgInfoObj2.processBoxCoordinate(x, y, width, height);
                    let bbox = new BoundingBox(boxTmp.x, boxTmp.y, boxTmp.width, boxTmp.height);
                    let currentFrame = annotatedObject.get(player.currentFrame);
                    let currentLabel;
                    if (currentFrame) {
                        currentLabel = cloneDeep(currentFrame.attr);
                    } else {
                        currentLabel = annotatedObject.label;
                    }
                    annotatedObject.mBy = userId;
                    annotatedObject.mTime = getTime();
                    annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, currentLabel));
                    // console.log(framesManager);
                }
            );
            addAnnotatedObjectControls(annotatedObject);
            doodle.style.cursor = 'default';
        } else {
            let ev = e || window.event;
            if (ev.pageX) {
                mouse.x = ev.pageX;
                mouse.y = ev.pageY;
            } else if (ev.clientX) {
                mouse.x = ev.clientX;
                mouse.y = ev.clientY;
            }
            let offset = $(doodle).offset();
            mouse.x -= offset.left;
            mouse.y -= offset.top;
            mouse.startX = mouse.x;
            mouse.startY = mouse.y;
            let dom = newBboxElement();
            dom.style.left = mouse.x + 'px';
            dom.style.top = mouse.y + 'px';
            tmpAnnotatedObject = {dom: dom};
        }
    };
    function newBboxElement () { // 新的标注框
        let dom = document.createElement('div');
        dom.className = 'bbox';
        doodle.appendChild(dom);
        return dom;
    }
    function addAnnotatedObjectControls (annotatedObject) { // 添加'标注'控制框
        let nameVal = (annotatedObject.label && annotatedObject.label.label) || globalAnnObjs.defaultLabel.label || ['']; // todo Label
        let name = $('<span class="txt-name">' + nameVal.join('-') + "</span>");
        let idVal = uuid();
        if (!annotatedObject.id) {
            annotatedObject.id = idVal;
        }
        let visibleLabel = $('<label>');
        visibleLabel.click(function (e) {
            e.stopPropagation();
        });
        let visible = $('<input type="checkbox" checked="checked" />');
        annotatedObject.visible = visible;
        visible.change(function (e) {
            e.stopPropagation();
            let bbox;
            annotatedObject.mBy = userId;
            annotatedObject.mTime = getTime();
            if (this.checked) {
                annotatedObject.dom.style.display = 'inline-block';
                let jquery = $(annotatedObject.dom);
                let position = jquery.position();
                bbox = new BoundingBox(Math.round(position.left), Math.round(position.top), Math.round(jquery.width()), Math.round(jquery.height()));
            } else {
                annotatedObject.dom.style.display = 'none';
                bbox = null;
            }
            let annotatedFrame = annotatedObject.get(player.currentFrame);
            let currentLabel;
            if (annotatedFrame) {
                currentLabel = cloneDeep(annotatedFrame.attr);
            } else {
                currentLabel = cloneDeep(annotatedObject.label);
            }
            annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, cloneDeep(currentLabel)));
            // console.log(framesManager);
        });
        visibleLabel.append(i18n.t('tool_video_item_visible'));
        visibleLabel.append(visible);
        /* let hideLabel = $('<label>'); let hide = $('<input type="checkbox" />'); hide.change(function() { annotatedObject.hideOthers = this.checked; }); hideLabel.append(hide); hideLabel.append('Hide others?'); */
        let del = $(`<input type="button" class="ivu-btn ivu-btn-small" value="${i18n.t('tool_video_item_del')}" />`);
        del.click(function () { // 按钮：删除'标注'
            for (let i = 0; annotatedObjectsTracker.annotatedObjects.length; i++) {
                if (annotatedObject === annotatedObjectsTracker.annotatedObjects[i]) {
                    globalAnnObjs.removeAllAnnObjsClass();
                    clearAnnotatedObject(i);
                    break;
                }
            }
        });
        let div = $('<div></div>');
        div.addClass('bz-box');
        div.append(name);
        div.append($('<br />'));
        div.append(del);
        div.append(visibleLabel);
        div.append($('<br />'));
        annotatedObject.controls = div;
        $('#objects').append(div);
        // 鼠标点击选中,再次点击取消
        div.click(function () {
            for (let i = 0; annotatedObjectsTracker.annotatedObjects.length; i++) {
                if (annotatedObject === annotatedObjectsTracker.annotatedObjects[i]) {
                    globalAnnObjs.selectAnnObjs(i);
                    break;
                }
            }
            globalAnnObjs.renderLabelList();
        });
        for (let i = 0; annotatedObjectsTracker.annotatedObjects.length; i++) {
            if (annotatedObject === annotatedObjectsTracker.annotatedObjects[i]) {
                globalAnnObjs.removeAllAnnObjsClass();
                globalAnnObjs.selectAnnObjs(i);
                break;
            }
        }
        globalAnnObjs.renderLabelList();
    }
    function framesPre () { // 前一帧
        player.seek(player.currentFrame - 1);
    }

    function framesNext () { // 后一帧
        player.seek(player.currentFrame + 1);
    }

    function annBtn () { // 新加标注,快捷键n
        doodle.style.cursor = 'crosshair';
    }

    function setAnn (obj) { // 设置标注名称
        var i = globalAnnObjs.index;
        if (i >= 0) {
            globalAnnObjs.setAnnObjsInfo({
                label: [obj.label],
                code: [obj.shortValue],
                category: [obj.category],
                color: obj.color
            });
        }
    }
    function toggleGuideLine () {
        commonObj2.toggleGuideLine();
    }
    function delAnn () { // 取消当前标注
        if (tmpAnnotatedObject !== null) {
            doodle.removeChild(tmpAnnotatedObject.dom);
            tmpAnnotatedObject = null;
        }
        doodle.style.cursor = 'default';
    }
    function setDefaultLabel (labelObj) {
        globalAnnObjs.defaultLabel = {
            label: [labelObj.label],
            code: [labelObj.shortValue],
            category: [labelObj.category],
            color: labelObj.color
        };
    }
    function appendLabel (labelObj) {
        globalAnnObjs.appendAnnObjsInfo(labelObj);
    }
    function deleteLabel (index) {
        globalAnnObjs.delAnnObjsInfo(index);
    }
    // 播放或暂停
    function playPause () {
        player.toogle();
    }
    window.player = player;
    // 对象：处理当前选中的'标注'框
    var globalAnnObjs = {
        defaultLabel: {},
        index: -1, // 当前选中的索引,
        renderLabelList: function () {
            let result = [];
            if (this.index >= 0) {
                let annotatedObject = annotatedObjectsTracker.annotatedObjects[this.index];
                let annotatedFrame = annotatedObject.get(player.currentFrame);
                if (annotatedFrame) {
                    let attr = annotatedFrame.attr;
                    attr && attr.label && attr.label.forEach((item, i) => {
                        result.push({
                            categoryText: attr.category[i],
                            shortValue: attr.code[i],
                            text: item
                        });
                    });
                }
            }
            EventBus.$emit('renderLabelList', result);
        },
        setAnnObjsInfo: function (obj) { // 设置标注信息'替换方式' // todo Label
            this.defaultLabel = cloneDeep(obj);
            if (this.index >= 0) {
                if (obj.label) {
                    let annotatedObject = annotatedObjectsTracker.annotatedObjects[this.index];
                    annotatedObject.mBy = userId;
                    annotatedObject.mTime = getTime();
                    annotatedObject.label = cloneDeep(obj);
                    let annotatedFrame = annotatedObject.get(player.currentFrame);
                    if (annotatedFrame) {
                        annotatedFrame.attr = cloneDeep(obj);
                    } else {
                        let jquery = $(annotatedObject.dom);
                        let position = jquery.position();
                        let bbox = new BoundingBox(Math.round(position.left), Math.round(position.top), Math.round(jquery.width()), Math.round(jquery.height()));
                        if (!annotatedObject.visible.prop('checked')) {
                            bbox = null;
                        }
                        // 取当前帧号 重置选择的对象在当前帧所标注的信息
                        annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, cloneDeep(obj)));
                    }
                    annotatedObject.controls.find('.txt-name').text(obj.label.join('-'));
                }
                this.renderLabelList();
            }
        },
        appendAnnObjsInfo: function (obj) { // 设置标注信息'追加方式' // todo Label
            if (this.index >= 0) {
                let annotatedObject = annotatedObjectsTracker.annotatedObjects[this.index];
                annotatedObject.mBy = userId;
                annotatedObject.mTime = getTime();
                let annotatedFrame = annotatedObject.get(player.currentFrame);
                let label = cloneDeep((annotatedFrame && annotatedFrame.attr) || annotatedObject.label || {
                    label: [],
                    code: [],
                    category: [],
                    color: '',
                });
                if (obj.localTagIsUnique) {
                    let index = label.category.indexOf(obj.category || obj.label);
                    if (~index) {
                        // 选择的标签已存在 不做处理
                        let labelIndex = -1;
                        for (let i = 0; i < label.label.length; i++) {
                            if (label.category[i] === obj.category && label.label[i] === obj.label) {
                                labelIndex = i;
                                break;
                            }
                        }
                        if (~labelIndex) {
                            label.label.splice(labelIndex, 1, obj.label);
                            label.code.splice(labelIndex, 1, obj.shortValue || '');
                            label.category.splice(labelIndex, 1, obj.category || obj.label);
                        } else {
                            label.label.push(obj.label || '');
                            label.code.push(obj.shortValue || '');
                            label.category.push(obj.category || obj.label);
                        }
                    } else {
                        label.label.push(obj.label || '');
                        label.code.push(obj.shortValue || '');
                        label.category.push(obj.category || obj.label);
                    }
                } else {
                    let index = label.category.indexOf(obj.category || obj.label);
                    if (~index) {
                        label.label.splice(index, 1, obj.label);
                        label.code.splice(index, 1, obj.shortValue || '');
                        label.category.splice(index, 1, obj.category || obj.label);
                    } else {
                        label.label.push(obj.label || '');
                        label.code.push(obj.shortValue || '');
                        label.category.push(obj.category || obj.label);
                    }
                }
                annotatedObject.label = cloneDeep(label);
                if (annotatedFrame) {
                    annotatedFrame.attr = cloneDeep(label);
                    annotatedObject.controls.find('.txt-name').text(label.label.join(','));
                } else {
                    let jquery = $(annotatedObject.dom);
                    let position = jquery.position();
                    let bbox = new BoundingBox(Math.round(position.left), Math.round(position.top), Math.round(jquery.width()), Math.round(jquery.height()));
                    if (!annotatedObject.visible.prop('checked')) {
                        bbox = null;
                    }
                    // 取当前帧号 重置选择的对象在当前帧所标注的信息
                    annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, cloneDeep(label)));
                    annotatedObject.controls.find('.txt-name').text(label.label.join(','));
                }
                this.renderLabelList();
            }
        },
        delAnnObjsInfo: function (index) { // todo 删除标签
            if (this.index >= 0) {
                let annotatedObject = annotatedObjectsTracker.annotatedObjects[this.index];
                let annotatedFrame = annotatedObject.get(player.currentFrame);
                annotatedObject.mBy = userId;
                annotatedObject.mTime = getTime();
                if (annotatedFrame) {
                    let label = annotatedFrame.attr;
                    label.label.splice(index, 1);
                    label.code.splice(index, 1);
                    label.category.splice(index, 1);
                    annotatedFrame.attr = cloneDeep(label);
                    annotatedObjectsTracker.annotatedObjects[this.index].controls.find('.txt-name').text(label.label.join(','));
                    this.renderLabelList();
                }
            }
        },
        removeAllAnnObjsClass: function () { // 移除所有当前class样式
            for (var i = 0; i < annotatedObjectsTracker.annotatedObjects.length; i++) {
                // 1.标注
                $(annotatedObjectsTracker.annotatedObjects[i].dom).removeClass('bbox-cur');
                // 2.标注控制器
                annotatedObjectsTracker.annotatedObjects[i].controls.removeClass('bz-box-cur');
            }
            // this.renderLabelList();
        },
        selectAnnObjs: function (i) { // 设置当前选中索引i的对象
            if (i >= 0) {
                this.index = i;
            } else {
                this.index = -1;
            }
            // 1.标注
            if ($(annotatedObjectsTracker.annotatedObjects[i].dom).hasClass('bbox-cur')) {
                this.index = -1;
                $(annotatedObjectsTracker.annotatedObjects[i].dom).removeClass('bbox-cur');
            } else {
                this.removeAllAnnObjsClass();
                $(annotatedObjectsTracker.annotatedObjects[i].dom).addClass('bbox-cur');
            }
            // 2.标注控制器
            if (annotatedObjectsTracker.annotatedObjects[i].controls.hasClass('bz-box-cur')) {
                annotatedObjectsTracker.annotatedObjects[i].controls.removeClass('bz-box-cur');
            } else {
                annotatedObjectsTracker.annotatedObjects[i].controls.addClass('bz-box-cur');
            }
        }
    };
    window.onkeydown = function (e) {
        let preventDefault = true;
        if (!player.isReady) {
            return;
        }
        if (e.keyCode === 32) { // space
            player.toogle();
        } else if (e.keyCode === 78) { // n
            doodle.style.cursor = 'crosshair';
        } else if (e.keyCode === 27) { // escape
            if (tmpAnnotatedObject !== null) {
                doodle.removeChild(tmpAnnotatedObject.dom);
                tmpAnnotatedObject = null;
            }

            doodle.style.cursor = 'default';
        } else if (e.keyCode === 37) { // left
            player.seek(player.currentFrame - 1);
        } else if (e.keyCode === 39) { // right
            player.seek(player.currentFrame + 1);
        } else {
            preventDefault = false;
        }
        if (preventDefault) {
            e.preventDefault();
        }
    };
    function showResult (result) {
        for (let i = 0; i < result.length; i++) {
            let object = result[i];
            // let name = (object.notes && object.notes.label) || '';
            let id = object.id;
            let annotatedObject = new AnnotatedObject(framesManager);
            // annotatedObject.name = name;
            // annotatedObject.label = Object.assign({}, object.notes);
            annotatedObject.id = id;
            annotatedObject.cBy = object.cBy || '';
            annotatedObject.cTime = object.cTime || '';
            annotatedObject.mBy = object.mBy || '';
            annotatedObject.mTime = object.mTime || '';
            annotatedObject.dom = newBboxElement();
            annotatedObjectsTracker.annotatedObjects.push(annotatedObject);
            $(annotatedObject.dom).click(function () {
                // 查出当前dom索引值
                for (let i = 0; annotatedObjectsTracker.annotatedObjects.length; i++) {
                    if (annotatedObject === annotatedObjectsTracker.annotatedObjects[i]) {
                        globalAnnObjs.selectAnnObjs(i);
                        break;
                    }
                }
                globalAnnObjs.renderLabelList();
            });
            interactify(
                annotatedObject.dom,
                (x, y, width, height) => {
                    let bbox = new BoundingBox(x, y, width, height);
                    let currentFrame = annotatedObject.get(player.currentFrame);
                    let currentLabel;
                    if (currentFrame) {
                        currentLabel = cloneDeep(currentFrame.attr);
                    } else {
                        currentLabel = annotatedObject.label;
                    }
                    annotatedObject.mBy = userId;
                    annotatedObject.mTime = getTime();
                    annotatedObject.add(new AnnotatedFrame(player.currentFrame, bbox, true, framesManager, currentLabel));
                    // console.log(framesManager);
                }
            );
            let lastFrame = -1;
            let polygons = object.frames;
            for (let j = 0; j < polygons.length; j++) {
                let polygon = polygons[j];
                let frameNumber = polygon.frameNumber;
                let isGroundThrough = polygon.isGroundTruth === 1;
                if (lastFrame + 1 !== frameNumber) {
                    let annotatedFrame = new AnnotatedFrame(lastFrame + 1, null, true, framesManager);
                    // console.log(framesManager);
                    annotatedObject.add(annotatedFrame);
                }
                if (polygon.bbox) {
                    let x = polygon.bbox.x;
                    let y = polygon.bbox.y;
                    let w = polygon.bbox.width;
                    let h = polygon.bbox.height;
                    let bbox = new BoundingBox(x, y, w, h);
                    annotatedObject.add(new AnnotatedFrame(frameNumber, bbox, isGroundThrough, framesManager, cloneDeep(polygon.attr)));
                } else {
                    annotatedObject.add(new AnnotatedFrame(frameNumber, null, isGroundThrough, framesManager, cloneDeep(polygon.attr)));
                }
                // console.log(framesManager);
                lastFrame = frameNumber;
            }
            if (lastFrame + 1 < framesManager.frames.totalFrames()) {
                let annotatedFrame = new AnnotatedFrame(lastFrame + 1, null, true, framesManager);
                annotatedObject.add(annotatedFrame);
            }
            addAnnotatedObjectControls(annotatedObject);
        }
        player.drawFrame(player.currentFrame);
    }

    function getResult () {
        let result = [];
        let totalFrames = framesManager.frames.totalFrames();
        for (let i = 0; i < annotatedObjectsTracker.annotatedObjects.length; i++) {
            let annotatedObject = annotatedObjectsTracker.annotatedObjects[i];
            // if (annotatedObject.frames.length < totalFrames) {
            //     return 'notComplete';
            // }
            let item = {
                type: "framebox",
                id: annotatedObject.id,
                createdFrame: 0,
                startFrame: 0,
                endFrame: totalFrames - 1,
                frames: [],
                cBy: annotatedObject.cBy,
                cTime: annotatedObject.cTime,
                mBy: annotatedObject.mBy || '',
                mTime: annotatedObject.mTime || '',
            };
            for (let frameNumber = 0; frameNumber < totalFrames; frameNumber++) {
                let annotatedFrame = annotatedObject.get(frameNumber);
                if (annotatedFrame === null) {
                    continue;
                }
                let bbox = annotatedFrame.bbox;
                // if (bbox !== null) {
                let isGroundTruth = annotatedFrame.isGroundTruth ? 1 : 0;
                let box = {
                    frameNumber: frameNumber,
                    fileName: annotatedFrame.fileName,
                    bbox: bbox,
                    attr: annotatedFrame.attr,
                    isGroundTruth: isGroundTruth,
                };
                item.frames.push(box);
                // }
            }
            result.push(item);
        }
        return result;
    }
    function destroy () {
        if (commonObj2) {
            commonObj2.destroy();
            inputsObj2.destroy();
            canvasInfoObj2.destroy();
            imgInfoObj2.destroy();
            mouseEventObj2.destroy();
            zoomIndexObj2.destroy();
        }
    }
    extractionFilefromZip(videoUrl);
    initVideoAnnotation.setAnn = setAnn;
    initVideoAnnotation.setDefaultLabel = setDefaultLabel;
    initVideoAnnotation.appendLabel = appendLabel;
    initVideoAnnotation.deleteLabel = deleteLabel;
    initVideoAnnotation.getResult = getResult;
    initVideoAnnotation.globalAnnObjs = globalAnnObjs;
    initVideoAnnotation.destroy = destroy;
    window.initVideoAnnotation = initVideoAnnotation;
}

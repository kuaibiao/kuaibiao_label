<template>
    <div class="audio-segment-wrapper" ref="wrapper">
        <div class="audio-segment-wave-container" ref="container">

        </div>
        <div class="audio-segment-tool">
            <div class="tools-play-control">
                <div>
                    <Button type="primary"
                            size="small"
                            :disabled="!isReady"
                            @click="togglePlay">
                        <Icon :type="isPlaying ? 'ios-pause':  'ios-play'" size="18"></Icon>
                    </Button>
                    <Button type="primary" size="small" :disabled="!isReady" @click="zoomIn">{{$t('audio_zoomIn')}}</Button>
                    <Button type="primary" size="small" :disabled="!isReady" @click="zoomOut">{{$t('audio_zoomOut')}}</Button>
                    <Button type="text"> {{formatTime(this.currentTime) + ' / ' + formatTime(this.totalTime)}}</Button>
                </div>
                <div>
                    <Checkbox v-model="autoPlay" @on-change="autoPlayChange" v-if="isSegmentation">
                        <span>{{$t('audio_autoPlay_on_selected')}}</span>
                    </Checkbox>
                    <Checkbox v-model="isLoop" @on-change="isLoopChange">
                        <span>{{$t('audio_play_in_loop')}}</span>
                    </Checkbox>
                </div>
            </div>
            <div class="tools-list-header" v-if="isSegmentation">
                <h3>{{ $t('audio_result_counter') + ': ' + segments.length }}</h3>
                <Button type="error" size="small" @click="clearResult" v-if="allowEditing">{{$t('audio_clear_result')}}</Button>
            </div>
            <div class="audio-segment-list" v-if="isSegmentation">
                <Table :height="listHeight"
                       border
                       size="small"
                       :columns="tableColumn"
                       :data="segments"
                       :rowKey="false"
                       :row-class-name="selectClassName"
                       @on-row-dblclick="selectSegment"
                       ref="segmentTable"
                ></Table>
            </div>
        </div>
    </div>
</template>

<script>
    import AudioSegment, {Util} from './AudioSegmentation.min';
    import throttle from 'lodash.throttle';
    import cloneDeep from 'lodash.clonedeep';
    import SegmentAttr from './segment-attr';

    let AudioContext = window.AudioContext || window.webkitAudioContext;
    let audioContext = new AudioContext();
    export default {
        name: "audio-segment",
        audioSegment: null,
        components: {
            SegmentAttr,
        },
        data () {
            return {
                listHeight: 240,
                segments: [],
                currentSegment: null,
                isLoop: false, // 是否循环播放
                autoPlay: false,
                isReady: false,
                isPlaying: false,
                totalTime: 0,
                currentTime: 0,
                defaultAttrInfo: [],
                allowEditing: false,
                isSegmentation: false,
                tableColumn: [{
                    title: '',
                    key: 'id',
                    width: 36,
                    render: (h, params) => {
                        return h('Icon', {
                            props: {
                                type: 'ios-play',
                                size: 18
                            },
                            on: {
                                click: () => {
                                    this.audioSegment.setActiveSegmentById(params.row.id, true);
                                }
                            }
                        });
                    }
                }, {
                    title: this.$t('audio_segment_time'),
                    key: 'startTime',
                    width: 180,
                    render: (h, params) => {
                        return h('div', Util.formatTime(params.row.start, false) + '--' + Util.formatTime(params.row.end, false));
                    }
                }, {
                    title: this.$t('audio_segment_valid'),
                    key: 'isValid',
                    width: 80,
                    render: (h, params) => {
                        return h('Checkbox', {
                            props: {
                                value: params.row.isVaild,
                                disabled: !this.allowEditing,
                            },
                            on: {
                                'on-change': (e) => {
                                    this.audioSegment.setSegmentValid(params.row.id, e);
                                }
                            }
                        });
                    }
                }, {
                    title: this.$t('audio_segment_text'),
                    key: 'text',
                    minWidth: 300,
                    render: (h, params) => {
                        if (this.allowEditing && this.currentSegment && this.currentSegment.id === params.row.id) {
                            return h('Input', {
                                props: {
                                    type: 'text',
                                    value: params.row.note.text,
                                    autofocus: true,
                                },
                                class: 'segment-text',
                                on: {
                                    'on-blur': (e) => {
                                        let text = e.target.value;
                                        if (params.row.note.text !== text) {
                                            this.audioSegment.setSegmentText(params.row.id, text);
                                            this.segmentUpdate();
                                        }
                                    }
                                }
                            });
                        } else {
                            return h('span', params.row.note.text);
                        }
                    }
                },
                {
                    title: this.$t('audio_segment_attr'),
                    key: 'id',
                    width: 300,
                    render: (h, params) => {
                        return h(SegmentAttr, {
                            props: {
                                data: params.row.note.attr
                            }
                        });
                    }
                }]
            };
        },
        mounted () {
        },
        methods: {
            init (option) {
                if (this.audioSegment) {
                    this.audioSegment.destroy();
                }
                this.audioSegment = new AudioSegment(this.buildOption(option));
                this.audioSegment.setLoopPlay(Boolean(this.isLoop));
                this.bindEvent();
            },
            buildOption (option) {
                this.allowEditing = typeof option.allowEditing === 'undefined' ? true : option.allowEditing;
                this.isSegmentation = typeof option.isSegmentation === 'undefined' ? true : option.isSegmentation;
                let config = {
                    container: this.$refs.container,
                    src: option.src,
                    userId: option.userId,
                    serverTime: +option.serverTime,
                    segments: option.segments || [],
                    height: 128,
                    allowEditing: this.allowEditing && this.isSegmentation,
                };
                // todo waveform 有问题
                if (!1 && option.waveform) {
                    let waveform = option.waveform;
                    let waveFile = new Blob([waveform], {type: 'application/json'});
                    config.dataUri = {
                        json: URL.createObjectURL(waveFile)
                    };
                    // todo 音频波形文件 处理
                } else {
                    config.audioContext = audioContext;
                }
                return config;
            },
            bindEvent () {
                this.audioSegment.on('ready', () => {
                    this.isReady = true;
                    this.totalTime = this.audioSegment.player.getDuration();
                    if (this.isSegmentation) {
                        let top = this.$refs.segmentTable.$el.getBoundingClientRect().top;
                        this.listHeight = window.innerHeight - top - 22;
                    }
                    this.$emit('ready');
                });
                this.audioSegment.on('error', () => {
                    this.isReady = false;
                    this.totalTime = 0;
                    this.$emit('error');
                });
                this.audioSegment.on('progress', (event) => {
                    if (event.lengthComputable && event.total) {
                        let loaded = (event.loaded / event.total).toFixed(2);
                        this.$emit('loadProgress', +loaded * 100);
                    }
                });
                this.audioSegment.on('segments.add', this.segmentAdd);
                this.audioSegment.on('segments.remove', this.segmentUpdate);
                this.audioSegment.on('segments.remove_all', this.segmentUpdate);
                this.audioSegment.on('segments.dragend', throttle(this.segmentUpdate, 300));
                this.audioSegment.on('player.time_update', (currentTime) => {
                    this.currentTime = currentTime;
                });
                this.audioSegment.on('player.play', () => {
                    this.isPlaying = true;
                });
                this.audioSegment.on('player.pause', () => {
                    this.isPlaying = false;
                });
                this.audioSegment.on('player.ended', () => {
                    this.isPlaying = false;
                });
                this.audioSegment.on('segments.select', (seg, play) => {
                    this.currentSegment = seg;
                    if (play || this.autoPlay) {
                        this.playSegment(seg.id);
                    }
                    if (this.allowEditing && this.isSegmentation) {
                        this.$nextTick(() => {
                            let parent = this.$refs.segmentTable.$el;
                            let input = parent.querySelector('.segment-text > input');
                            if (input) {
                                input.focus();
                            }
                        });
                        this.$emit('showSegmentAttr', seg.note.attr);
                    }
                });
            },
            setDefaultAttr (attr) {
                this.defaultAttrInfo = attr;
            },
            saveSegmentAttr (attr) {
                if (!this.isReady) {
                    return;
                }
                if (this.allowEditing && this.currentSegment) {
                    this.audioSegment.setSegmentAttr(this.currentSegment.id, attr);
                    this.segmentUpdate();
                }
            },
            selectClassName (row) {
                if (this.currentSegment && this.currentSegment.id === row.id) {
                    return 'segment-row  active-row';
                }
                return 'segment-row ';
            },
            togglePlay () {
                if (this.audioSegment.player.isPlaying) {
                    this.pause();
                } else {
                    this.play();
                }
            },
            selectSegment (row) {
                if (this.currentSegment && this.currentSegment.id === row.id) {
                    this.autoPlay && this.playSegment(row.id);
                    return;
                }
                this.audioSegment.setActiveSegmentById(row.id, false);
            },
            segmentAdd (segments) {
                segments.forEach((segment) => {
                    if (segment.note.attr.length === 0) {
                        this.audioSegment.setSegmentAttr(segment.id, this.defaultAttrInfo);
                    }
                });
                this.segmentUpdate();
            },
            segmentUpdate () {
                this.segments = cloneDeep(this.audioSegment.getSegments());
            },
            getSegments () {
                return this.audioSegment ? this.audioSegment.getSegments() : [];
            },
            autoPlayChange (value) {
                this.autoPlay = value;
            },
            isLoopChange (value) {
                this.audioSegment && this.audioSegment.setLoopPlay(value);
            },
            zoomIn () {
                this.audioSegment && this.audioSegment.zoomIn();
            },
            zoomOut () {
                this.audioSegment && this.audioSegment.zoomOut();
            },
            play () {
                this.audioSegment && this.audioSegment.play();
            },
            pause () {
                this.audioSegment && this.audioSegment.pause();
            },
            playSegment (id) {
                this.audioSegment && this.audioSegment.playSegmentById(id);
            },

            clearResult () {
                this.audioSegment && this.audioSegment.removeAllSegments();
            },
            formatTime (time) {
                return Util.formatTime(time);
            }
        },
        destroyed () {
            this.audioSegment && this.audioSegment.destroy();
        }
    };
</script>
<style lang="scss">
    .audio-segment-wrapper {
        .audio-segment-wave-container {
            margin: 5px;
        }

        .zoom-container, .overview-container {
            box-shadow: inset 0 0 15px 0 rgba(0, 0, 0, 0.6);
        }

        .overview-container {
            margin: 10px 0;
        }

        .tools-play-control, .tools-list-header {
            display: flex;
            justify-content: space-between;
            padding: 0 15px;
            margin: 5px 0;
        }

        .list-row {
            display: flex;
            justify-content: space-between;
        }

        .audio-segment-list {
            .ivu-table-cell {
                padding-left: 8px;
                padding-right: 8px;
            }

            .active-row {
                background-color: #2db7f5;

                td {
                    background-color: #2db7f5;
                    border-color: #2db7f5;
                    color: #fff;
                }
            }
        }

    }

</style>


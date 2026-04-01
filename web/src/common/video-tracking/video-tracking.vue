<template>
    <div class="video-tracking-component">
        <div class="video-tracking-placeholder"></div>
        <div class="player">
            <ButtonGroup>
                <Button @click="previousItem" size="small" type="primary"
                        :disabled="playing || currentIndex === 1">{{$t('tool_video_pre_frame')}}</Button>
                <Button @click="nextItem" size="small"
                        type="primary"
                        :disabled="playing || currentIndex === total">{{$t('tool_video_next_frame')}}</Button>
                <Button @click="playPause"
                        size="small"
                        type="primary">{{playing ? $t('tool_pause'): $t('tool_play')}}</Button>
            </ButtonGroup>
            <InputNumber size="small" v-model="jumpIndex"
                         @on-change="jumpTo"
                         :disabled="playing"
                         :min="1" :max="total"></InputNumber>
            <Tag  color="primary" style="width: 72px; text-align: center;">{{currentIndex + '/' + total}}</Tag>
            <Button @click="clearAll" size="small" type="error" v-if="!viewMode">{{$t('tool_delete_all')}}</Button>
            <Tooltip trigger="focus">
              <!-- 每秒播放帧数 (1-20的数字) -->
                <div slot="content">{{$t('tool_frame_20')}}</div>{{$t('tool_play_speed')}}:
                <InputNumber v-model="speed"
                             :min="1"
                             :max="20"
                             :editable="false"
                             size="small"
                             @on-change="speedChange">
                </InputNumber>
            </Tooltip>
        </div>
    </div>
</template>

<script>
    import '../../libs/image-label/image-label.css';
    import '../../libs/image-label/image-label.min';
    import '../../libs/image-label/video-label.min';

    export default {
        name: "video-tracking",
        videoLabel: null,
        data () {
            return {
                isReady: true,
                playing: false,
                currentIndex: 1,
                jumpIndex: 1,
                total: 1,
                speed: 5,
                viewMode: false,
            };
        },
        mounted () {
            this.keyDownHandle = this.keyDownHandle.bind(this);
        },
        methods: {
            init (options) {
                if (this.videoLabel) {
                    this.videoLabel.destroy();
                }
                this.viewMode = options.viewMode;
                this.videoLabel = new window.VideoLabel({
                    viewMode: options.viewMode,
                    container: this.$el.querySelector('.video-tracking-placeholder'),
                    EventBus: options.EventBus,
                    user_id: options.user_id,
                    server_time: Math.floor(new Date().valueOf() / 1000),
                    draw_type: options.draw_type,
                    // items:ImageList,
                    sourceType: options.sourceType,
                    result: options.result || [],
                    src: options.src
                }, window.ImageLabel);
                this.videoLabel.fps = this.speed;

                this.videoLabel.on('ready', () => {
                    this.total = this.videoLabel.items.length;
                    this.$emit('ready');
                    this.bindDomEvent();
                });
                this.videoLabel.on('play', () => {
                    this.playing = true;
                });
                this.videoLabel.on('pause', () => {
                    this.playing = false;
                });
                this.videoLabel.on('progress', (e) => {
                    this.$emit('progress', e);
                });

                this.videoLabel.on('frameChange', (index) => {
                    this.currentIndex = index + 1;
                });
            },
            speedChange (speed) {
                this.videoLabel.fps = speed;
            },
            bindDomEvent () {
                window.addEventListener('keydown', this.keyDownHandle, false);
            },
            unBindDomEvent () {
                window.removeEventListener('keydown', this.keyDownHandle, false);
            },
            keyDownHandle (e) {
                let keyCode = e.keyCode || e.which;
                switch (keyCode) {
                    case 39: { // right
                        this.nextItem();
                        break;
                    }
                    case 37 : { // left
                        this.previousItem();
                        break;
                    }
                    case 32: {
                        e.preventDefault();
                        this.playPause();
                        break;
                    }
                    // eslint-disable-next-line no-empty
                    default: {

                    }
                }
            },
            clearAll () {
                this.videoLabel.clearAll();
            },
            getResult () {
                return this.videoLabel.toJson();
            },
            nextItem () {
                this.videoLabel.nextItem();
            },
            previousItem () {
                this.videoLabel.previousItem();
            },
            gotoItem (index) {
                this.videoLabel.gotoItem(index);
            },
            jumpTo (index) {
                if (index !== null) {
                    this.videoLabel.gotoItem(index - 1);
                }
            },
            playPause () {
                if (this.playing) {
                    this.pause();
                } else {
                    this.play();
                }
            },
            play () {
                if (this.currentIndex === this.total) {
                    this.gotoItem(0);
                }
                this.videoLabel.play();
            },
            pause () {
                this.videoLabel.pause();
            },
            destroy () {
                this.unBindDomEvent();
                if (this.videoLabel) {
                    this.videoLabel.destroy();
                }
            }
        },
        destroyed () {
            this.destroy();
        }
    };
</script>

<style scoped>
    .player {
        text-align: center;
    }
</style>

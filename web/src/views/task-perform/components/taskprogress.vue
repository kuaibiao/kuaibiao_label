<template>
    <div class="task-progess-con">
        <Icon type="ios-timer-outline" color="#19a859" size="16"></Icon>
        <span v-if="total > 0">{{$t('tool_done')}} {{current}} / {{total}}, {{$t('tool_surplus')}} {{ total - current}} </span>
        <span class="time-reminder" :class="isDanger ? 'danger' : ''">{{timeReminder}}</span> {{$t('tool_overdue')}}
    </div>
</template>
<script>
import EventBus from '@/common/event-bus';
export default {
    name: 'task-progess',
    props: {
        total: {
            type: Number,
            default: 0,
        },
        current: {
            type: Number,
            default: 0
        },
        timeout: {
            type: Number,
            default: 0
        },
        noticeAble: {
            type: Boolean,
            default: true,
        }
    },
    data () {
        return {
            timeReminder: '00:00',
            timeId: 0,
            clientTime: Math.floor(new Date().valueOf() / 1000),
            isDanger: false,
            dangerTime: 120,
        };
    },
    watch: {
        current () {
            this.clientTime = Math.floor(new Date().valueOf() / 1000);
            clearTimeout(this.timeId);
            this.start();
        }
    },
    mounted () {
        EventBus.$on('start-counter-time', this.restart);
        this.clientTime = Math.floor(new Date().valueOf() / 1000);
        this.start();
    },
    methods: {
        restart () {
            this.clientTime = Math.floor(new Date().valueOf() / 1000);
            clearTimeout(this.timeId);
            this.start();
        },
        start () {
            this.timeId = setTimeout(() => {
                this.countDown();
            }, 1000);
        },
        countDown () {
            if (this.timeout === 0) {
                return;
            }
            let now = Math.floor(new Date().valueOf() / 1000);
            let remainingTime = this.timeout - (now - this.clientTime);
            remainingTime = remainingTime > 0 ? remainingTime : 0;
            let minutes = Math.floor(remainingTime / 60);
            let seconds = Math.floor(remainingTime % 60);
            minutes = minutes > 9 ? '' + minutes : '0' + minutes;
            seconds = seconds > 9 ? '' + seconds : '0' + seconds;
            this.timeReminder = minutes + ':' + seconds;
            this.isDanger = remainingTime <= this.dangerTime;
            if (remainingTime === this.dangerTime && this.noticeAble) {
                this.$Notice.warning({
                    title: this.$t('tool_task_timeout_alert'),
                    render: h => {
                        return h('span',
                            {
                                style: {
                                    color: 'red',
                                    fontSize: '13px',
                                    lineHeight: '1.4'
                                },
                                domProps: {
                                    // innerHTML: `任务将在 ${this.timeReminder} 后超时, 届时将回退已领取的作业`,
                                    innerHTML: this.$t('tool_timeout_rollback', {num: this.timeReminder}),
                                }
                            }
                        );
                    },
                    duration: 0,
                    name: 'taskWillTimeout'
                });
            }
            if (remainingTime === 0 && this.timeout !== 0) {
                this.noticeAble && EventBus.$emit('task-timeout');
                this.$Notice.close('taskWillTimeout');
                this.noticeAble && this.$Notice.warning({
                    title: this.$t('tool_task_timeout'),
                    render: h => {
                        return h('span',
                            {
                                style: {
                                    color: 'red',
                                    fontSize: '13px',
                                    lineHeight: '1.4'
                                },
                                domProps: {
                                    // innerHTML: `任务已超时,请刷新页面重新领取`,
                                    innerHTML: this.$t('tool_timeout_refresh'),
                                }

                            }
                        );
                    },
                    duration: 0,
                    name: 'taskTimeout'
                });
                return;
            }
            this.start();
        }
    },
    beforeDestroy () {
        EventBus.$off('start-counter-time', this.restart);
        this.$Notice.close('taskWillTimeout');
        this.$Notice.close('taskTimeout');
        if (this.timeId) {
            clearTimeout(this.timeId);
        }
    }
};
</script>
<style lang="scss">
.task-progess-con {
    height: 36px;
    min-width: 120px;
    line-height: 36px;
    margin-right: 20px;
    vertical-align: middle;
    .time-reminder {
        display: inline-block;
        margin-left: 10px;
        text-align: right;
        &.danger {
            color: red;
            font-size: 1.2em;
        }
    }
}
</style>



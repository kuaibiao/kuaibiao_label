<template>
    <div class="task-view">
        <component :is="TaskView[stepInfo.type]"
            :templateInfo = "templateInfo"
            :taskList = "taskList"
            :userList = "userList"
            :categoryInfo = "categoryInfo"
            :serverTime = "serverTime"
            :timeout = "timeout"
            :taskStat = "taskStat"
            :taskInfo = "taskInfo"
            :qualityData="qualityData"
            :stepInfo = "stepInfo"
            @componentsLoaded = "componentsLoaded"
        ></component>
        <Spin fix v-if="loading"></Spin>
    </div>
</template>

<script>
import '@/libs/tooltips/bootstrap.min.css'; // 只包含 tooltips 和 poppover
import '@/libs/tooltips/bootstrap.min.js'; // 只包含 tooltips 和 poppover
import api from '@/api';
import util from "@/libs/util";
import TaskProduce from './produce/index';
import TaskAudit from './audit/index';
import TaskQuality from './quality/index';
import EventBus from '@/common/event-bus';
const taskListOrder = {
    random: 1,
    sorted: 0,
}
export default {
    name: 'task-view',
    data () {
        return {
            TaskView: {
                0: TaskProduce,
                1: TaskAudit,
                3: TaskQuality,
            },
            data_sort: taskListOrder.sorted,
            stepInfo: {},
            templateInfo: [],
            taskList: [],
            userList: [],
            categoryInfo: {},
            serverTime: 0,
            timeout: 0,
            currentUserId: '',
            needConfirmLeave: false,
            taskStat: {},
            loading: true,
            taskInfo: {},
            qualityData: {},
        };
    },
    beforeRouteLeave (to, from, next) { // 离开前提示
        if (!this.$store.state.user.userInfo.id) {
            this.needConfirmLeave = false;
        }
        if (this.needConfirmLeave) {
            const answer = window.confirm(this.$t('tool_leave_content_lost'));
            if (answer) {
                next();
            } else {
                util.openNewPage(this, this.$route.name, this.$route.params, this.$route.query);
                next(false);
            }
        } else {
            next();
        }
    },
    mounted () {
        this.projectId = this.$route.query.project_id;
        this.taskId = this.$route.query.task_id;        
        this.$store.state.app.userInfoRequest.then(() => {
            this.fetchTask();
        });
        EventBus.$on('perform-fetchTask', this.fetchTask);
        // 监听此事件的地方 还包括 每个分类的审核
        EventBus.$on('clear-fetchTask', this.clearTask);
        EventBus.$on('needConfirmLeave', this.setneedConfirmLeave);
        this.bindKeyDownEvent();
    },
    methods: {
        handleKeyDown (e) {
            let keyCode = e.keyCode || e.which;
            let target = e.target;
            if (keyCode === 32) { // 页面内的焦点元素,在空格按下时 会触发焦点元素的click事件
                // let tags = ['input', 'textarea']; // 屏蔽掉部分表单元素，不完善。
                if (target.tagName.toLowerCase() === 'input' ||
                    target.tagName.toLowerCase() === 'textarea'
                ) {
                    return;
                }
                e.preventDefault();
            }
        },
        bindKeyDownEvent () {
            $(document).on('keydown', this.handleKeyDown);
        },
        unbindKeyDownEvent () {
            $(document).off('keydown', this.handleKeyDown);
        },
        componentsLoaded () {
            this.loading = false;
        },
        setneedConfirmLeave (e) {
            this.needConfirmLeave = e;
        },
        // 领取任务 
        fetchTask () {
            let reqData = {
                access_token: this.$store.state.user.userInfo.accessToken,
                project_id: this.projectId,
                task_id: this.taskId,
                user_id: this.currentUserId,
                data_sort: this.data_sort,
                op: 'fetch'
            };
            if (!reqData.project_id || !reqData.access_token || !reqData.task_id) {
                this.$store.commit('removeTag', 'perform-task');
                let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                if (preRouter) {
                    this.$router.push({
                        path: preRouter.path,
                        params: preRouter.params,
                        query: preRouter.query,
                    });
                }
                return;
            }
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: reqData,
                success: (res) => {
                    if (res.error) {
                        this.needConfirmLeave = false;
                        this.$Message.destroy();
                        this.$Message.info({
                            content: res.message,
                            duration: 3
                        });
                        this.$store.commit('removeTag', 'perform-task');
                        let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                        if (preRouter) {
                            this.$router.push({
                                path: preRouter.path,
                                params: preRouter.params,
                                query: preRouter.query,
                            });
                        }
                    } else {
                        this.stepInfo = res.data.step;
                        if (~['0', '2'].indexOf(this.stepInfo.type) && res.data.list.length === 0) { // 执行步骤判断是否有剩余作业
                            this.needConfirmLeave = false;
                            this.$Message.destroy();
                            this.$Message.info({
                                content: this.$t('tool_no_job'),
                                duration: 2
                            });
                            this.$store.commit('removeTag', 'perform-task');
                            let preRouter = !this.$store.state.app.prevPageUrl.name ? {path: '/my-task/list'} : this.$store.state.app.prevPageUrl;
                            if (preRouter) {
                                this.$router.push({
                                    path: preRouter.path,
                                    params: preRouter.params,
                                    query: preRouter.query,
                                });
                            }
                            return;
                        }
                        this.categoryInfo = res.data.category;
                        this.taskList = res.data.list;                        
                        this.userList = res.data.parentWorkUsers;
                        this.taskInfo = res.data.task;
                        // 避免重复赋值 导致组件渲染异常
                        if (this.templateInfo.length === 0) {
                            this.templateInfo = (res.data.template && res.data.template.config) || [];
                        }
                        this.serverTime = res.data.time;
                        this.timeout = res.data.timeout;
                        EventBus.$emit('start-counter-time');
                        this.taskStat = res.data.stat? {
                            label_count: res.data.stat.label_count,
                            point_count: res.data.stat.point_count,
                            work_count: res.data.stat.work_count
                        } : {
                            label_count: 0,
                            point_count: 0,
                            work_count: 0
                        };
                        this.qualityData = {
                            pass_rate: res.data.pass_rate,
                            audit_rate: res.data.audit_rate,
                        };
                    }
                },
                error: (res) => {
                    this.needConfirmLeave = false;
                    // 错误处理
                }
            });
        },
        clearTask (e) {
            let oldUserId;
            if (e.type === 'workerChange') {
                this.currentUserId = e.data.cur;
                oldUserId = e.data.pre;
            } else {
                this.data_sort = e.data.cur;
            }
            this.loading = true;
            $.ajax({
                url: api.task.execute,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.projectId,
                    task_id: this.taskId,
                    user_id: oldUserId,
                    op: 'clear'
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.info({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.fetchTask();
                    }
                },
                error: (res) => {
                    this.loading = false;
                    this.needConfirmLeave = false;
                    // 错误处理
                }
            });
        }
    },
    destroyed () {
        EventBus.$off('perform-fetchTask', this.fetchTask);
        EventBus.$off('needConfirmLeave', this.setneedConfirmLeave);
        EventBus.$off('clear-fetchTask', this.clearTask);
        this.unbindKeyDownEvent();
    }
};
</script>
<style lang="scss">
.task-view {
    min-height: 100%;
    background: #fff;
    padding: 8px;
}
.task-btn-group {
    button {
        margin-left: 5px;
    }
}
.task-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin:0 0 5px;
    .task-btn-group, 
    .flex-space-between {
        display: flex;
        justify-content: flex-end;
        align-items: center;
    }
}
</style>
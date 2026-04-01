<template>
    <div class="subcontent" id="project_index">
        <div class="main-con">
            <Card>
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_project_count')}}</div>
                <Row :style="{paddingBottom: Object.keys(processTotle).length > 0 ? '10px' : '5px'}" type="flex" justify="space-around">
                    <i-col span="6">
                        <span class="header-item-title">
                            <span><span class="con-icon"></span>{{$t('project_total_project_operation')}}</span>
                            <strong>{{ work_amount ? getTransform(work_amount) : 0 }}</strong>
                        </span>
                    </i-col>
                    <i-col span="8">
                         <span class="header-item-title">
                            <span><span class="con-icon"></span>{{$t('project_participants')}}</span>
                            <strong>{{assigned_user ? getTransform(assigned_user) : 0}}</strong>
                        </span>
                    </i-col>
                    <i-col span="8">
                         <span class="header-item-title">
                            <span><span class="con-icon"></span>{{$t('project_cumulative_working_hours')}}</span>
                            <strong>{{work_time ? (work_time*1/3600).toFixed(2) : 0}} h</strong>
                        </span>
                    </i-col>
                </Row>
            </Card>
            <!-- <Row v-if="Object.keys(processTotle).length" style="border-top: 1px solid #dddddd;padding: 10px 0 0">
                <Row :gutter="10">
                    <i-col v-for="(task, key, index) in processTotle" :key="index" :style="{width: Math.floor(100/Object.keys(processTotle).length)+'%',display:'inline-block'}">
                        <Tooltip style="position:relative">
                            <div class="tip">
                                <p 
                                :style="{background: getStep(task.step_id),
                                width: (((task.totle ? task.totle*1 : 0) / (work_amount ? work_amount : 1))*100).toFixed(2)+'%',
                                height: '35px',
                                zIndex:'2',
                                borderLeft: (task.totle ? task.totle*1 : 0) == 0? '1px solid #3da2ea': 'none'}">
                                </p>
                                <span class="percentage">
                                    {{ (((task.totle ? task.totle*1 : 0) / (work_amount ? work_amount : 1))*100).toFixed(2) }} %
                                    <span>({{task.totle ? task.totle*1 : 0}} / {{(work_amount ? work_amount : 1)}})</span>
                                </span>
                                <span class="task_name">{{getStepType(task.step_id)}}</span>
                            </div>
                            <div slot="content">
                                <p>{{$t('project_process_tip', {name: task.step_id})}}</p>
                            </div>
                        </Tooltip>
                    </i-col>
                </Row>
            </Row> -->
        </div>
        <div class="main-con">
            <Card >
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_work_schedule')}}</div>
                <div v-for="(batch, index) in batches" :key="index" style="padding: 0 20px">
                    <Row>
                        <i-col v-for="(task, key) in getTasks(batch.id)" :key="key" class="progress">
                            <Tooltip style="position:relative">
                                <div class="tip">
                                    <span class="percentage">{{getStepType(task.step_id)}}</span>
                                    <span class="task_name">
                                        {{ (((task.stat ? task.stat.work_count*1 : 0) / (batch.amount*1? batch.amount*1:1))*100).toFixed(2) }} %
                                        <span>({{(task.stat ? task.stat.work_count*1 : 0)}} / {{(batch.amount*1? batch.amount*1:1)}})</span>
                                    </span>
                                    <Progress   :success-percent="successPrecent(task.stat,batch)" 
                                                :percent="progressPrecent(task.stat,batch)"                           
                                                hide-info
                                                :stroke-color="percentColor"/>
                                </div> 
                                <div slot="content">
                                    <p>{{getStepType(task.step_id)}}：{{ (((task.stat ? task.stat.work_count*1 : 0) / (batch.amount*1? batch.amount*1:1))*100).toFixed(2) }} %</p>
                                    <p>{{$t('project_difficulty')}}：{{task.stat ? task.stat.difficult_count * 1 - task.stat.difficult_revise_count * 1 : 0}} 个</p>
                                    <p>{{$t('project_refused')}}：{{task.stat ? task.stat.refused_count * 1 - task.stat.refused_revise_count * 1 : 0}} 个</p>
                                    <!-- <p>{{$t('project_process_id')}}： {{ task.step_id }}</p>
                                    <p>{{$t('project_task_id')}}： {{ task.id }}</p>
                                    <p>{{$t('project_task_names')}}： {{ task.name }}</p>
                                    <p>{{$t('project_number_executions')}}： {{ task.stat ? task.stat.work_count : 0 }}</p>
                                    <p>{{$t('project_number_jobs')}}： {{ batch.amount*1? batch.amount*1:1 }}</p>
                                    <p v-if="task.crowdsourcing || task.team || task.aimodel">{{$t('project_execute')}}： {{ getPlatform(task.platform_type, task) }}</p> -->
                                </div>
                            </Tooltip>
                        </i-col>
                    </Row>
                </div>
            </Card>
        </div>
        <div class="main-con">
            <Card>
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_data_package')}}</div>
                <div class="form-item">
                    <Table 
                        size="large"
                        highlight-row 
                        :columns="tableOption" 
                        :data="uploadfiles"
                        stripe
                        show-header
                    ></Table>
                </div>
            </Card>
        </div>
        <!-- <div class="main-con">
            <Card v-if="attachments.length > 0">
                <div slot="title" class="item_title" v-if="attachments.length > 0"><span class="blue-icon"></span>{{$t('project_demand_attachment')}}</div>
                <div class="attachment" v-if="attachments.length > 0">
                    <span 
                    v-for="(file, index) in attachments" 
                    @click="downloadFile(file.key)"
                    :key="index"
                    class="file-item">
                        <Icon type="ios-paper-outline" style="font-size:55px"/>
                        <span style="word-break: break-all">{{file.name}}</span>
                    </span>
                </div>
            </Card>
        </div> -->
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
export default {
    props: {
        project: {
            type: Object
        },
        stepTypes: {
            type: Object,
            default: {}
        },
        uploadfiles: {
            type: Array,
            default: []
        },
        attachments: {
            type: Array,
            default: []
        },
    },
    data () {
        return {
            template: [],
            tableOption: [
                {
                    title: this.$t('project_file_name'),
                    key: 'name',
                    align: 'center'
                },
                {
                    title: this.$t('project_file_size'),
                    key: 'size',
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            para.row.size_format
                        );
                    }
                },
                {
                    title: this.$t('project_upload_time'),
                    key: 'ctime',
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.ctime * 1000),
                                'yyyy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                }
            ],
        };
    },
    computed: {
        work_time () {
            return (this.project && this.project.stat) ? this.project.stat.work_time_count * 1 : 0;
        },
        work_amount () {
            return this.project ? this.project.amount * 1 : 0;
        },
        assigned_user () {
            return (this.project && this.project.stat) ? this.project.stat.people_count * 1 : 0;
        },
        task_amount () {
            return (this.project && this.project.tasks) ? this.project.tasks.length : 0;
        },
        step_amount () {
            return (this.project && this.project.steps) ? this.project.steps.length : 0;
        },
        batch_amount () {
            return (this.project && this.project.batches) ? this.project.batches.length : 0;
        },
        batches () {
            return this.project ? this.project.batches : [];
        },
        tasks () {
            return this.project ? this.project.tasks : [];
        },
        processTotle () {
            let arr = {};
            if (this.tasks) {
                $.each(this.tasks, (k, v) => {
                    if (arr[v.step.id]) {
                        arr[v.step.id].totle += (v.stat ? v.stat.work_count * 1 : 0);
                    } else {
                        arr[v.step.id] = {
                            step_id: v.step.id,
                            step_name: v.step.name,
                            totle: v.stat ? v.stat.work_count * 1 : 0,
                            name: v.step ? v.step.name : ''
                        };
                    }
                });
                return arr;
            } else {
                return arr;
            }
        },
        progressPrecent () {
            return function (taskStat,batch) {
                if (taskStat && batch) {
                    let difficult = taskStat.difficult_count * 1 - taskStat.difficult_revise_count * 1;
                    let refused = taskStat.refused_count * 1 - taskStat.refused_revise_count * 1;
                    let work = taskStat.work_count * 1;
                    let count = difficult + refused + work;
                    let amount = batch.amount * 1;
                    if (count == 0) {
                        return 0;
                    } else {
                        let precent = ((difficult + refused + work) / amount * 100).toFixed(2) * 1;
                        return precent;
                    }
                } else {
                    return 0;
                }
            }
        },
        successPrecent () {
            return function (taskStat,batch) {
                if (taskStat && batch) {
                    let work = taskStat.work_count * 1;
                    let amount = batch.amount * 1;
                    if (work == 0) {
                        return 0;
                    } else {
                        let precent = (work / amount *100).toFixed(2) * 1;
                        return precent; 
                    }
                } else {
                    return 0;
                }
            }
        },
        percentColor () {
            if (this.progressPrecent == 0) {
                return "#F3F3F3";
            } else {
                return "#FFCC00";
            }
        },
        getTransform () {
            return function (num) {
                if (!num) {
                    return "0";
                }
                return num.toString().replace(/\B(?=(\d{3})+$)/g,",");
            }
        }
    },
    methods: {
        checkFtp (id) {
            $.ajax({
                url: api.user.openFtp,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: id
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 2
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        getPlatform (type, task) {
            if (type == '0') {
                return task.team.name + this.$t('project_teams');
            } else if (type == '1') {
                return task.crowdsourcing.name + this.$t('project_crowdsourcings');
            } else {
                return task.aimodel.name + this.$t('project_ais');
            }
        },
        getStep (id) {
            let color;
            $.each(this.project.steps, (k, v) => {
                if (v.id == id) {
                    color = v.type;
                }
            });
            return color == '0' ? '#80db61' : '#3da2ea';
        },
        getStepType (id) {
            let type;
            $.each(this.project.steps, (k, v) => {
                if (v.id == id) {
                    type = v.type;
                }
            });
            return this.stepTypes[type];
        },
        downloadFile (path) {
            window.open(api.download.file + '?file=' + path);
        },
        getTasks (id) {
            return this.tasks.filter((k, v) => {
                return id == k.batch_id;
            });
        },
        getColor (type) {
            // let type;
            // $.each(this.project.steps, (k, v) => {
            //     if (v.id == id) {
            //         type = v.type;
            //     }
            // });
            if (type == '0') {
                return 'success';
            } else if (type == '1') {
                return 'wrong';
            } else if (type == '3') {
                return 'normal';
            }
        }
    },
};
</script>
<style scoped>
    .subcontent{
        background: #efefef;
    }
    .main-con{
        background:#efefef;
        padding: 20px 25px 10px;
    }
    .con-left{
        border-right: 1px solid #ccc;
        display: flex;
        justify-content: center;
    }
    .con-right{
        display: flex;
        justify-content:space-around;
        height:60px;
    }
    .con-icon{
        display: inline-block;
        width: 10px;
        height: 10px;
        text-align: center;
        line-height: 60px;
        background: #2d8cf0;
        border-radius: 50%;
        font-size: 40px;
        margin-right: 20px
    }
    .header-item-title {
        display: flex;
        flex-direction: column;
        /* justify-content: center; */
        align-items: center;
    }
    .header-item-title strong{
        font-size: 28px;
        font-weight: 500
    }
    .blue-icon{
        display: inline-block;
        width: 3px;
        height: 18px;
        background: #2d8cf0;
        position: relative;
        top: 3px;
        margin-right:15px;
    }
    .file-item{
        display: flex;
        flex-direction: column;
        justify-content: center;
        text-align: center;
        margin-right: 20px;
        width: 200px;
        cursor: pointer
    }
    .attachment{
        display: flex;
        justify-content: start;
        padding: 0 20px 20px; 
    }
    .tip{
        margin:20px;
        display: flex;
    }
    .tip .percentage{
        display: block;
        width: 140px;
    }
    .form-item {
        padding: 10px 20px;
    }
    .task_name {
       display: block;
       width: 25%;
    }
    .item_icon{
        width:5px;
        height:5px;
    }
    .progress{
        width: 80%;
        font-size: 16px;
    }
</style>
<style>
    #project_index .item_title {
        margin-left: 8px;
        font-size: 15px;
        font-weight: 700;
    }
    #project_index .ivu-tooltip, .ivu-tooltip-rel {
        display: block;
    }
</style>
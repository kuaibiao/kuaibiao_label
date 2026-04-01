<template>
    <div id="task_detail">
        <div class="layout-content">
            <Row>
                <i-col span="2">
                    <img :src="formatUrl(avatorPath)" style="width: 70%;" v-if="avatorPath"/>
                </i-col>
                <i-col span="22" :push="!avatorPath ? 2 : 0">
                    <Row>
                        <i-col span="24" class="task-detail-header">
                            <strong style="font-size:16px; padding: 0 10px 0 0; flex-basis: 60%">{{task_name}}</strong>
                            <div class="">
                                <Button type="primary" @click="statDownloadExcel" :loading=downloadLoadingExcel>{{$t('operator_export_performance')}} (EXCEL)</Button>
                                <Button v-if="step_type" type="primary" @click="toTaskPerform">{{getstep(step_type)}}</Button>
                            </div>
                        </i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                        <i-col span="6">{{$t('operator_project_id')}} : {{project_id}}</i-col>
                        <i-col span="5">{{$t('operator_batch_id')}} : {{batch_id}}</i-col>
                        <i-col span="5">{{$t('operator_step_id')}} : {{step_id}}</i-col>
                        <i-col span="5">{{$t('operator_task_id')}} : {{$route.params.id}}</i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                        <i-col span="6">{{$t('operator_starting_time')}} : {{start_time}}</i-col>
                        <i-col span="6">{{$t('operator_end_time')}} : {{end_time}}</i-col>
                    </Row>
                </i-col>
            </Row>
            <ul class="tabpane">
                <!--我的绩效-->
                <li :class="currentIndex == 'stat-list' ? 'active' : ''" @click="tabClick('stat-list')">{{$t('operator_my_performance')}}</li>
                <!-- <li :class="currentIndex == 'stat-map' ? 'active' : ''" @click="tabClick('stat-map')">{{$t('operator_my_performance_chart')}}</li> -->
                <!--我的作业-->
                <li :class="currentIndex == 'work-list' ? 'active' : ''" @click="tabClick('work-list')">{{$t('operator_my_job')}}</li>
            </ul>
        </div>
        <div>
            <component
                :is="currentView"
                :projectId='project_id'
                :stepId="step_id"
                :image_label="is_image_label"
                :step_type="step_type"
                :task_view="view"
                :templateInfo="template"
            ></component>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import Vue from 'vue';
import util from '@/libs/util';
import workstat from './workstat';
// import statistics from './statistics';
import workList from './work-list';
import {categoryDefaultIcon} from '@/common/categoryDefaultIcon';

export default {
    name: "my-task-detail",
    components: {
        workstat,
        // statistics,
        workList
    },
    data () {
        return {
            currentIndex: this.$route.params.tab,
            isPrimary: true,
            dateValue: '',
            btnColor: true,
            is_image_label: '0',
            step_type: '',
            view: 'text_analysis',
            template: [],
            ViewMap: {
                'stat-list': workstat,
                // 'stat-map': statistics,
                'work-list': workList,
            },
            avatorPath: categoryDefaultIcon.thumbnail,
            task_name: '',
            project_id: '',
            batch_id: '',
            step_id: '',
            created_at: '',
            start_time: '',
            end_time: '',
            downloadLoadingCsv: false,
            downloadLoadingExcel: false,
            sumData: {}
        };
    },
    watch: {
        '$route.params' () {
            this.currentIndex = this.$route.params.tab || 'stat-list';
        }
    },
    computed: {
        currentView () {
            return this.ViewMap[this.currentIndex];
        }
    },
    mounted () {
        this.getTaskDetail();
        this.$store.state.app.userInfoRequest.then(res => {
            this.user_id = res.data.user.id;
        });
    },
    methods: {
        formatUrl (url) {
            return api.staticBase + util.replaceUrl(url);
        },
        tabClick (index) {
            this.currentIndex = index;
            // this.currentView = this.ViewMap[index];
            if (index !== 'work-list') {
                this.$router.push({
                    name: 'my-task-detail',
                    params: {
                        id: this.$route.params.id,
                        tab: index,
                        index: 'index'
                    }
                });
            } else {
                this.$router.push({
                    name: 'my-task-detail',
                    params: {
                        id: this.$route.params.id,
                        tab: index,
                        index: 'all'
                    }
                });
            }
        },
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: "post",
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id
                },
                success: res => {
                    let data = res.data;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.is_image_label = res.data.info.project.category.file_type;
                        this.view = res.data.info.project.category.view;
                        this.step_type = res.data.info.step.type;
                        this.project_id = res.data.info.project.id;
                        this.batch_id = res.data.info.batch_id;
                        this.step_id = res.data.info.step_id;
                        this.template = res.data.template.config || [];
                        this.task_name = res.data.info.name;
                        this.avatorPath = res.data.info.project.category.thumbnail || categoryDefaultIcon.thumbnail;
                        this.created_at = util.timeFormatter(
                            new Date(+res.data.info.updated_at * 1000),
                            'yyyy-MM-dd hh:mm:ss'
                        );
                        this.start_time = util.timeFormatter(
                            new Date(+res.data.info.start_time * 1000),
                            'yyyy-MM-dd hh:mm:ss'
                        );
                        this.end_time = util.timeFormatter(
                            new Date(+res.data.info.end_time * 1000),
                            'yyyy-MM-dd hh:mm:ss'
                        );
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        toTaskPerform () {
            this.$router.push({
                name: "perform-task",
                query: {
                    project_id: this.project_id,
                    task_id: this.$route.params.id
                }
            });
        },
        statDownload () {
            this.$store.state.app.userInfoRequest.then(res => {
                this.user_id = res.data.user.id;
                this.requestStat(this.user_id);
            });
        },
        statDownloadCsv () {
            this.$store.state.app.userInfoRequest.then(res => {
                this.user_id = res.data.user.id;
                this.requestStat(this.user_id, 0);
            });
        },
        statDownloadExcel () {
            this.$store.state.app.userInfoRequest.then(res => {
                this.user_id = res.data.user.id;
                this.requestStat(this.user_id, 1);
            });
        },
        requestStat (id, is_excel) {
            if (is_excel === 0) {
                this.downloadLoadingCsv = true;
            } else if (is_excel === 1) {
                this.downloadLoadingExcel = true;
            }
            $.ajax({
                url: api.stat.statExport,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.project_id,
                    task_id: this.$route.params.id,
                    step_id: this.step_id,
                    user_id: id,
                    is_excel: is_excel
                },
                success: res => {
                    if (is_excel === 0) {
                        this.downloadLoadingCsv = false;
                    } else if (is_excel === 1) {
                        this.downloadLoadingExcel = false;
                    }
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        window.open(api.download.file + '?file=' + res.data.download);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        if (is_excel === 0) {
                            this.downloadLoadingCsv = false;
                        } else if (is_excel === 1) {
                            this.downloadLoadingExcel = false;
                        }
                    });
                }
            });
        },
        getstep (step_type) {
            if (step_type === '0') {
                return this.$t('operator_execute');
            } else if (step_type === '1') {
                return this.$t('operator_audit');
            } else {
                return this.$t('operator_qc');
            }
        }
    },
};
</script>

<style scoped>
    .layout-content {
      position: relative;
      background: #ffffff;
        min-height: 180px;
      padding: 20px 20px 40px 20px;
    }
    .task-detail-header {
        display: flex;
        justify-content: space-between;
    }
    .task-detail-header button {
        margin: 2px;
    }
    .tabpane {
      /* width: 480px; */
      height: 37px;
      list-style: none;
      position: absolute;
      bottom: 0;
      left: 40px;
    }
    .tabpane li {
      font-size: 14px;
      float: left;
      /* width: 90px; */
      height: 37px;
      padding: 8px 16px;
      margin-right: 16px;
      line-height: 21px;
      color: #999999;
      text-align: center;
      cursor: pointer;
    }
    .tabpane li:hover {
      color: #333333;
    }
    .tabpane li.active {
      color: #2d8cf0;
      border-bottom: 2px solid #2d8cf0;
    }
    #task_detail .ivu-tabs-bar {
      border-bottom: none;
      margin-bottom: 0;
      margin-left: 40px;
    }

    .sumData {
        font-size: 14px;
    }
    .sumData span{
        display: inline-block;
        padding: 10px 0;
        margin-right: 40px;
    }
    .flex-detail{
        margin-top: 15px;
    }
    .flex-detail span{
        margin-right: 100px;
    }
</style>
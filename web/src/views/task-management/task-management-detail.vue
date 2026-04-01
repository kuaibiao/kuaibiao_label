<template>
    <div id="task_detail">
        <div class="layout-content">
            <Row>
                <i-col span="2">
                    <img :src="staticBase + avatorPath" style="width: 70%;"/>
                </i-col>
                <i-col span="22" :push="!avatorPath ? 2 : 0">
                    <Row>
                        <i-col span="24" class="task-detail-header">
                            <strong style="font-size:16px; padding: 0 10px 0 0; flex-basis: 60%">{{task_name}}</strong>
                            <div class="task-detail-btn">
                                <Button type="primary" @click="workExport">{{$t('admin_export_record')}}</Button>
                                <Button type="primary"  @click="statDownloadExcel" :loading=downloadLoadingExcel>{{$t('admin_export_performance')}} (EXCEL)</Button>
                                <Button type="primary"  @click="toSetUser">{{$t('admin_settings')}}</Button>
                            </div>
                        </i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                        <i-col span="6">{{$t('admin_item_id')}} : {{project_id}}</i-col>
                        <i-col span="5">{{$t('admin_batch_id')}} : {{batch_id}}</i-col>
                        <i-col span="5">{{$t('admin_process_id')}} : {{step_id}}</i-col>
                        <i-col span="5">{{$t('admin_task_id')}} : {{$route.params.id}}</i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                        <i-col span="6">{{$t('admin_start_time')}}: {{start_time}}</i-col>
                        <i-col span="6">{{$t('admin_end_time')}}: {{end_time}}</i-col>
                    </Row>
                </i-col>
            </Row>
            <ul class="tabpane">
                <li :class="currentIndex == 'stat-list' ? 'active' : ''" @click="tabClick('stat-list')">{{$t('admin_list_performance')}}</li>
                <!-- <li :class="currentIndex == 'stat-map' ? 'active' : ''" @click="tabClick('stat-map')">{{$t('admin_chart_performance')}}</li> -->
                <li :class="currentIndex == 'work-list' ? 'active' : ''" @click="tabClick('work-list')">{{$t('admin_list_job')}}</li>
            </ul>
        </div>
        <div>
            <component
                :is="currentView"
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
import workuser from './work-user';
// import statistics from './task-statistics';
import worklist from './work-list';
import {categoryDefaultIcon} from '@/common/categoryDefaultIcon';

export default {
    data () {
        return {
            staticBase: api.staticBase,
            currentIndex: this.$route.params.tab || 'stat-list',
            isPrimary: true,
            dateValue: '',
            btnColor: true,
            downloadLoadingCsv: false,
            downloadLoadingExcel: false,
            is_image_label: '0',
            step_type: '',
            step_id: '',
            project_id: '',
            batch_id: '',
            view: 'text_analysis',
            template: [],
            ViewMap: {
                'stat-list': workuser,
                // 'stat-map': statistics,
                'work-list': worklist,
            },
            // currentView: workuser,
            avatorPath: categoryDefaultIcon.thumbnail,
            task_name: '',
            created_at: '',
            start_time: '',
            end_time: '',
        };
    },
    watch: {
        '$route.params' () {
            this.currentIndex = this.$route.params.tab || 'stat-list';
        },
    },
    computed: {
        currentView () {
            return this.ViewMap[this.currentIndex];
        }
    },
    methods: {
        workExport () {
            $.ajax({
                url: api.stat.operationExport,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    step_id: this.step_id,
                    task_id: this.$route.params.id,
                    project_id: this.project_id,
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        window.open(api.download.file + '?file=' + res.data.download);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        tabClick (index) {
            this.currentIndex = index;
            // this.currentView = this.ViewMap[index];
            if (index !== 'work-list') {
                this.$router.push({
                    name: 'task-management-detail',
                    params: {
                        id: this.$route.params.id,
                        tab: index,
                        index: 'index'
                    }
                });
            } else {
                this.$router.push({
                    name: 'task-management-detail',
                    params: {
                        id: this.$route.params.id,
                        tab: index,
                        index: 'all'
                    }
                });
            }
        },
        toSetUser () {
            this.$router.push({
                name: 'set-task-user',
                params: {
                    task_id: this.$route.params.id
                }
            });
        },
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.$route.params.id
                },
                success: res => {
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.task_name = res.data.info.name;
                        this.is_image_label = res.data.info.project.category.file_type;
                        this.view = res.data.info.project.category.view;
                        this.step_type = res.data.info.step.type;
                        this.step_id = res.data.info.step.id;
                        this.project_id = res.data.info.project.id;
                        this.batch_id = res.data.info.batch_id;
                        this.template = res.data.template.config || [];
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
        statDownloadCsv () {
            this.requestStat(0);
        },
        statDownloadExcel () {
            this.requestStat(1);
        },
        requestStat (is_excel) {
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
    },
    mounted () {
        this.getTaskDetail();
    },
};
</script>

<style scoped>
.layout-content{
    position: relative;
    background: #ffffff;
    min-height: 180px;
    padding: 20px 20px 40px;
}
.task-detail-header {
    display: flex;
    justify-content: space-between;
}
.task-detail-header button {
    margin: 2px;
}
.tabpane{
    /* width: 360px; */
    height: 37px;
    list-style: none;
    position: absolute;
    bottom: 0;
    left: 40px;
}
.tabpane li{
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
.tabpane li:hover{
    color: #333333
}
.tabpane li.active{
    color: #2d8cf0;
    border-bottom: 2px solid #2d8cf0
}
.flex-detail{
    margin-top: 15px;
}
.flex-detail span{
    margin-right: 100px;
}
</style>
<style>
#task_detail .ivu-tabs-bar {
    border-bottom: none;
    margin-bottom: 0;
    margin-left: 40px;
}
</style>

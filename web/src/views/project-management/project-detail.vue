<template>
    <div>
        <div class="layout-content">
            <Row>
                <i-col span="2">
                    <img v-if="avatorPath" :src="staticBase + avatorPath" style="width: 70%;"/>
                </i-col>
                <i-col span="22" :push="avatorPath ? 0 : 2">
                    <Row>
                        <i-col span="24" class="project-detail-info">
                            <div class="info-item">
                                <strong style="font-size:16px; max-width: 600px; display: inline-block;">{{project.name}}</strong>
                                <Tag v-if="showStr" :color="statusColor" style="margin-left: 10px; vertical-align: top;">{{showStr}}</Tag>
                            </div>
                            <div class="info-item">
                                <!-- <Button type="default" @click="pause" v-if="project.status == '2'">{{$t('project_pause')}}</Button> -->
                                <!-- <Button type="success" @click="recover" v-show="project.status == '3'">{{$t('project_recover')}}</Button> -->
                                <Button type="primary" @click="workExport">{{$t('project_export_work_record')}}</Button>
                                
                                <Button @click="requestStat" v-show="stepData.length" type="primary" class="exportDataExcel">{{$t('project_export_data')}}(Excel)</Button>
                            </div>
                        </i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                         <i-col span="6">ID :   {{$route.params.id}}</i-col>
                         <i-col span="6">{{$t('project_projecy_type')}} :   {{project_type}}</i-col>
                         <i-col span="6">{{$t('project_founder')}} :   {{user_name}}</i-col>
                    </Row>
                    <Row :gutter="20" style="font-size: 14px;margin: 10px 0;">
                        <i-col span="6">{{$t('project_starting_time')}} :   {{start_time}}</i-col>
                        <i-col span="6">{{$t('project_finish_time')}} :   {{end_time}}</i-col>
                    </Row>
                </i-col>
            </Row>
            <ul class="tabpane">
                <li :class="currentIndex == 'overview' ? 'active' : ''" @click="tabClick('overview')">{{$t('project_project_details')}}</li>
                <li :class="currentIndex == 'dataList' ? 'active' : ''" @click="tabClick('dataList')">{{$t('project_data_list')}}</li>
                <li :class="currentIndex == 'stat' ? 'active' : ''" @click="tabClick('stat')">{{$t('project_project_performance')}}</li>
                <li :class="currentIndex == 'qc' ? 'active' : ''" @click="tabClick('qc')">{{$t('project_quality_control')}}</li>
                <li :class="currentIndex == 'download' ? 'active' : ''" @click="tabClick('download')">{{$t('project_download_result')}}</li>
                <li :class="currentIndex == 'record' ? 'active' : ''" @click="tabClick('record')">{{$t('project_operation_records')}}</li>
            </ul>
        </div>
        <div>
            <component
                :is="currentView"
                :project="project"
                :attachments="attachments"
                :uploadfiles="uploadfiles"
                :stepData="stepData"
                :stepTypes="stepTypes"
                :tasks="tasks"
                ></component>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import Vue from 'vue';
import util from '@/libs/util';
import overview from './project-detail-components/overview';
import dataList from './project-detail-components/data-list';
import qc from './project-detail-components/project-qc';
import stat from './project-detail-components/stat';
import download from './project-detail-components/download';
import record from './project-detail-components/operation';
import {categoryDefaultIcon} from '@/common/categoryDefaultIcon';
export default {
    name: 'project-detail',
    data () {
        return {
            addModal: false,
            currentIndex: this.$route.params.tab,
            avatorPath: '',
            staticBase: api.staticBase,
            ViewMap: {
                'overview': overview,
                'dataList': dataList,
                'qc': qc,
                'stat': stat,
                'download': download,
                'record': record,
            },
            tableData: [],
            created_at: '',
            start_time: '',
            end_time: '',
            user_name: '',
            project_type: '',
            project: {},
            uploadfiles: [],
            attachments: [],
            stepData: [],
            stepTypes: {},
            statuses: {},
            tasks: [],
            showStr: '',
            statusColor: ''
        };
    },
    watch: {
        '$route.params' () {
            this.currentIndex = this.$route.params.tab || 'overview';
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
                    project_id: this.$route.params.id
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        util.downloadFile(this, api.download.file + '?file=' + res.data.download)
                        // window.open(api.download.file + '?file=' + res.data.download);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        requestStat () {
            $.ajax({
                url: api.stat.statExport,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id
                },
                success: (res) => {
                    let data = res.data;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        util.downloadFile(this, api.download.file + '?file=' + res.data.download)
                        // window.open(api.download.file + '?file=' + res.data.download);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        tabClick (index) {
            this.currentIndex = index;
            this.$router.push({
                name: 'project-detail',
                params: {
                    id: this.$route.params.id,
                    tab: index,
                }
            });
        },
        toConfiguration () {
            let step = this.get_configstep();
            this.$router.push({
                name: 'configuration-project-step',
                params: {
                    id: this.$route.params.id,
                    step: step.toString()
                }
            });
        },
        get_configstep () {
            if (!this.project.unpack) {
                return '1';
            } else {
                return '2';
            }
        },
        pause () {
            $.ajax({
                url: api.project.pause,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 3
                        });
                        this.getProjectDetail();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.stopModel = false;
                    });
                }
            });
        },
        recover () {
            $.ajax({
                url: api.project.recover,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.success({
                            content: this.$t('project_operation_success'),
                            duration: 3
                        });
                        this.getProjectDetail();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.stopModel = false;
                    });
                }
            });
        },
        getProjectDetail () {
            $.ajax({
                url: api.project.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id
                },
                success: res => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.project = res.data.project;
                        this.avatorPath = res.data.project.category.thumbnail || '';
                        this.attachments = res.data.attachments;
                        this.uploadfiles = res.data.uploadfiles;
                        this.project_type = res.data.project.category.name;
                        this.created_at = util.timeFormatter(
                            new Date(+res.data.project.created_at * 1000),
                            'yyyy-MM-dd'
                        );
                        this.start_time = util.timeFormatter(
                            new Date(+res.data.project.start_time * 1000),
                            'yyyy-MM-dd'
                        );
                        this.end_time = util.timeFormatter(
                            new Date(+res.data.project.end_time * 1000),
                            'yyyy-MM-dd'
                        );
                        this.user_name = res.data.project.user.email;
                        this.stepData = res.data.project.steps;
                        // this.stepTypes = res.data.step_info.step_types;
                        this.stepTypes = res.data.stepTypes;
                        this.tasks = res.data.project.tasks;
                        this.statuses = res.data.statuses;
                        this.showStatuse(res.data.project.status);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        showStatuse (status) {
            this.showStr = this.statuses[status];
            if (status == '0') {         // 发布中
                this.statusColor = '#5cadff';
            } else if (status == '1') {  // 配置中
                this.statusColor = '#2d8cf0';
            } else if (status == '2') {  // 作业准备中
                this.statusColor = '#999999';
            }else if (status == '3') {  // 作业中
                this.statusColor = '#19be6b';
            } else if (status == '4') {  // 已暂停
                this.statusColor = '#ed3f14';
            } else if (status == '6') {  // 完成
                this.statusColor = '#ff9900';
            } else if (status == '7') {
                this.statusColor = 'red';
            }
        }
    },
    mounted () {
        this.getProjectDetail();
    },
    components: {
        overview,
        dataList,
        qc,
        stat,
        download,
        record
    }
};
</script>

<style scoped >
    .project-detail-info {
        display: flex;
        justify-content: space-between;
    }
    .project-detail-info .info-item button {
        margin-bottom: 5px;
    }
    .project-detail-info .info-item button + button {
        margin-left: 5px;
    }
.layout-content{
    position: relative;
    background: #ffffff;
    min-height: 180px;
    padding: 20px 20px 40px 20px;
}
.tabpane{
    /* width: 480px; */
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
</style>

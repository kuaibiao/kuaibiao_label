<style lang="less">
    @import "../../styles/common.less";
    @import "./components/styles/infor-card.less";
</style>
<template>
    <div class="home-main">
        <Row :gutter="15">
            <i-col :md="24" :lg="8">
                <Row class-name="home-page-row1" :gutter="10">
                    <i-col :sm="24" :md="24" :lg="24" :style="{marginBottom: '10px'}">
                        <Card>
                            <Row type="flex" :style="userInfor">
                                <i-col span="8" >
                                    <Row class-name="made-child-con-middle" type="flex" align="middle">
                                        <div class="head-pic" v-if="avatar">
                                            <img class="avator-img" :src="getUserLogo(avatar)"/>
                                        </div>
                                        <div class="head-pic" v-if="!avatar">
                                            <img class="avator-img" src="../../images/default.jpg"/>
                                        </div>
                                    </Row>
                                </i-col>
                                <i-col span="16" style="padding-left:6px;">
                                    <Row class-name="made-child-con-middle" type="flex" align="middle">
                                        <div>
                                            <b class="card-user-infor-name">{{nickname}}</b>
                                            <p style="margin-bottom:5px">{{email}}</p>
                                            <p>
                                                <Button v-for="(item,index) in userRoles" :key="index" size="small" :style="{marginRight:'10px',backgroundColor:'#19be6b',color: '#ffffff'}">{{item}}</Button>
                                            </p>
                                        </div>
                                    </Row>
                                </i-col>
                            </Row>
                            <div class="line-gray"></div>
                            <div style="padding:23px;">
                                <Row class="margin-top-8">
                                    <span>{{$t('home_last_login_time')}}:</span>
                                    <span style="margin-left: 20px">{{loginTime}}</span>
                                </Row>
                                <Row class="margin-top-8">
                                    <span>{{$t('home_last_login_ip')}}:</span>
                                    <span style="margin-left: 20px">{{loginIp}}</span>
                                </Row>
                            </div>
                        </Card>
                    </i-col>
                </Row>
            </i-col>
            <i-col :md="24" :lg="16">
                <!-- 管理员 -->
                <Row :gutter="15" v-if="access == 0">
                    <i-col :md="24" :lg="24">
                        <Row :gutter="20">
                            <i-col :xs="24" :sm="24" :md="12" :lg="12" :style="{marginBottom: '10px'}">
                                <Card :padding="0" class="home-card">
                                    <div :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                        <div class="card-title-name">
                                            <p>
                                                <span>{{$t('home_executable_task')}}</span>
                                                <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                    <Icon type="ios-help-circle-outline" class="card-help"/>
                                                    <div slot="content">
                                                        <p v-for="(item,index) in taskDesc" :key="index">{{item}}</p>
                                                    </div>
                                                </Poptip>
                                            </p>
                                        </div>
                                        <div class="card-detail-top">
                                            <img src="../../images/default-image/task.png" alt="">
                                            <div class="card-detail-right">
                                                <div class="card-detail-digit">
                                                    <p style="color: #5489F9;">{{transformValue(userTaskCount)}}</p>
                                                    <p>{{$t('home_person')}}</p>
                                                </div>
                                                <div class="divider"></div>
                                                <div class="card-detail-digit">
                                                    <p style="color: #F7BF20;">{{transformValue(taskCount)}}</p>
                                                    <p>{{$t('home_all')}}</p>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </Card>
                            </i-col>
                            <i-col :xs="24" :sm="24" :md="12" :lg="12" :style="{marginBottom: '10px'}">
                                    <Card :padding="0" class="home-card">
                                        <div :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                            <div class="card-title-name">
                                                <p>
                                                    <span>{{$t('home_executable_work')}}</span>
                                                    <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                        <Icon type="ios-help-circle-outline" class="card-help"/>
                                                        <div slot="content">
                                                            <p v-for="(item,index) in workDesc" :key="index">{{item}}</p>
                                                        </div>
                                                    </Poptip>
                                                </p>
                                            </div>
                                            <div class="card-detail-top">
                                                <img src="../../images/default-image/work.png" style="margin-top:3px;" alt="">
                                                <div class="card-detail-right">
                                                    <div class="card-detail-digit">
                                                        <p style="color: #5489F9;">{{transformValue(userDataCount)}}</p>
                                                        <p>{{$t('home_person')}}</p>
                                                    </div>
                                                    <div class="divider"></div>
                                                    <div class="card-detail-digit">
                                                        <p style="color: #F7BF20;">{{transformValue(dataCount)}}</p>
                                                        <p>{{$t('home_all')}}</p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </Card>
                            </i-col>
                        </Row>
                    </i-col>
                    <i-col :md="24" :lg="24">
                        <Row :gutter="20">
                            <i-col :xs="24" :sm="24" :md="12" :lg="12" :style="{marginBottom: '10px'}">
                                <Card :padding="0" class="home-card">
                                    <div :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                        <div class="card-title-name">
                                            <p>
                                                <span>{{$t('home_active_user')}}</span>
                                                <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                    <Icon type="ios-help-circle-outline" class="card-help"/>
                                                    <div slot="content">
                                                        <p v-for="(item,index) in userDesc" :key="index">{{item}}</p>
                                                     </div>
                                                </Poptip>
                                            </p>
                                        </div>
                                        <div class="card-detail-bottom">
                                            <img src="../../images/default-image/user.png" style="margin-top:1px;" alt="">
                                            <p>{{transformValue(userActiveCount)}}</p>
                                        </div>
                                        <div class="card-detail-all">
                                            <span>{{$t('home_all_user')}}：</span>
                                            <span>{{transformValue(userCount)}}/{{userCountLimit}}</span>
                                        </div>
                                    </div>
                                </Card>
                            </i-col>
                            <i-col :xs="24" :sm="24" :md="12" :lg="12" :style="{marginBottom: '10px'}">
                                <Card :padding="0" class="home-card">
                                    <div :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                        <div class="card-title-name">
                                            <p>
                                                <span>{{$t('home_active_project')}}</span>
                                                <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                    <Icon type="ios-help-circle-outline" class="card-help"/>
                                                    <div slot="content">
                                                        <p v-for="(item,index) in projDesc" :key="index">{{item}}</p>
                                                    </div>
                                                </Poptip>
                                            </p>
                                        </div>
                                        <div class="card-detail-bottom">
                                            <img src="../../images/default-image/project.png" style="margin-top:5px;" alt="">
                                            <p>{{transformValue(projectRunningCount)}}</p>
                                        </div>
                                        <div class="card-detail-all">
                                            <span>{{$t('home_all_projects')}}：</span>
                                            <span>{{transformValue(projectCount)}}</span>
                                        </div>
                                    </div>
                                </Card>
                            </i-col>
                        </Row>
                    </i-col>
                </Row>
                <!-- 作业员 -->
                <Row :gutter="15" v-if="access == 1">
                    <i-col :md="24" :lg="24">
                        <Row :gutter="20">
                            <i-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" :style="{marginBottom: '10px'}">
                                <Card :padding="0" class="home-card">
                                    <Row :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                        <div class="card-title-name">
                                            <p>
                                                <span>{{$t('home_today_work')}}</span>
                                                <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                    <Icon type="ios-help-circle-outline" class="card-help"/>
                                                    <div slot="content">
                                                        <p v-for="(item,index) in dailyWork2" :key="index">{{item}}</p>
                                                    </div>
                                                </Poptip>
                                            </p>
                                        </div>
                                        <div class="card-detail-work-bottom">
                                            <img src="../../images/default-image/dailyWork.png">
                                            <p>0</p>
                                        </div>
                                    </Row>
                                </Card>
                            </i-col>
                            <i-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" :style="{marginBottom: '10px'}">
                                <Card :padding="0" class="home-card">
                                    <Row :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                        <div class="card-title-name">
                                            <p>
                                                <span>{{$t('home_executable_task')}}</span>
                                                <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                    <Icon type="ios-help-circle-outline" class="card-help"/>
                                                    <div slot="content">
                                                        <p v-for="(item,index) in taskDesc2" :key="index">{{item}}</p>
                                                    </div>
                                                </Poptip>
                                            </p>
                                        </div>
                                        <div class="card-detail-work-bottom">
                                            <img src="../../images/default-image/task.png" alt="">
                                            <p>{{transformValue(userTaskCount)}}</p>
                                        </div>
                                    </Row>
                                </Card>
                            </i-col>
                            <i-col :xs="24" :sm="24" :md="24" :lg="8" :xl="8" :style="{marginBottom: '10px'}">
                                    <Card :padding="0" class="home-card">
                                        <Row :style="{height: access == '0' ? '172px' : '250px'}" class="infor-card-con">
                                            <div class="card-title-name">
                                                <p>
                                                    <span>{{$t('home_executable_work')}}</span>
                                                    <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" class="card-help-icon">
                                                        <Icon type="ios-help-circle-outline" class="card-help"/>
                                                        <div slot="content">
                                                            <p v-for="(item,index) in workDesc2" :key="index">{{item}}</p>
                                                        </div>
                                                    </Poptip>
                                                </p>
                                            </div>
                                            <div class="card-detail-work-bottom">
                                                <img src="../../images/default-image/work.png" alt="">
                                                <p>{{transformValue(userDataCount)}}</p>
                                            </div>
                                        </Row>
                                    </Card>
                            </i-col>
                        </Row>
                    </i-col>
                </Row>
            </i-col>
        </Row>
        <Row :gutter="15" v-if="access == 0">
            <i-col :xs="24" :sm="24" :md="24" :xl="12" :style="{marginBottom: '15px'}">
                <Card :padding="0">
                    <p slot="title" style="height: 28px" class="card-title">
                        <Icon type="ios-pie-outline" />
                        <span style="font-size: 18px;line-height: 28px;color:#82878D;">{{$t('home_data_ratio')}}</span>
                    </p>
                    <RadioGroup v-model="selType" slot="extra" type="button">
                        <Radio :style="dataObject" label="data">{{$t('home_data_volume')}}</Radio>
                        <Radio :style="sizeObject" label="size">{{$t('home_space_occupation')}}</Radio>
                    </RadioGroup>
                    <Row class="bar-con">
                        <i-col span="24">
                            <dataBar :dataStat="selType=='data'?dataStat:sizeStat" :type="selType"></dataBar>
                        </i-col>
                    </Row>
                </Card>
            </i-col>
            <i-col :xs="24" :sm="24" :md="24" :xl="12" :style="{marginBottom: '15px'}">
                <Card :padding="0">
                    <p slot="title" style="height: 28px" class="card-title">
                        <Icon type="ios-pie-outline" />
                        <span style="font-size: 18px;line-height: 28px;color:#82878D;">{{$t('home_create_project_count')}}</span>
                    </p>
                    <Row class="map-con">
                        <i-col span="24">
                            <categoryBar :categoryGroup="categoryGroup"></categoryBar>
                        </i-col>
                    </Row>
                </Card>
            </i-col>
        </Row>
        <Row>
            <Card>
                <p slot="title" style="height: 28px" class="card-title">
                    <Icon type="md-pulse" />
                    <span style="font-size: 18px;line-height: 28px;color:#82878D;">{{$t('home_work_count')}}</span>
                </p>
                <div class="line-chart-con">
                    <data-map :dataGroup="dataGroup"></data-map>
                </div>
            </Card>
        </Row>
        <Spin size="large" fix v-if="spinShow"></Spin>
    </div>
</template>

<script>
import dataMap from './components/dataMap.vue';
import countUp from './components/countUp.vue';
import inforCard from './components/inforCard.vue';
import dataBar from './components/dataBar.vue';
import categoryBar from './components/categoryBar.vue';
import api from '@/api';
import util from '@/libs/util';
export default {
    name: 'home',
    components: {
        dataMap,
        countUp,
        inforCard,
        dataBar,
        categoryBar
    },
    data () {
        return {
            spinShow: true,
            staticBase: api.staticBase,
            avatar: '',
            nickname: '',
            userRoles: [],
            loginIp: '',
            email: '',
            loginTime: '',
            loginaddress: '',
            siteInfo: {},
            taskCount: 0,
            userTaskCount:0,
            userActiveCount: 0,
            dataCount: 0,
            userDataCount: 0,
            myWorkCount: 0,
            userCount: 0,
            userCountLimit: 0,
            projectCount: 0,
            projectRunningCount: 0,
            selType: 'data',
            dataStat: [],
            sizeStat: [],
            categoryGroup: [],
            dataGroup: {},
            dataStyle: {
                borderRadius:"14px 0px 0px 14px",
                border: "none", 
                background: "#E9F0F3",
                color:"#5A667F",
                width:"77px",
                height:"28px",
                textAlign:"center",
                padding: "0"
            },
            dataActiveStyle: {
                borderRadius:"14px 0px 0px 14px",
                border: "none", 
                background: "#54B0FE",
                color:"#FFFFFF",
                width:"77px",
                height:"28px",
                textAlign:"center",
                padding: "0"
            },
            sizeStyle: {
                borderRadius:"0px 14px 14px 0px",
                border: "none", 
                background: "#E9F0F3",
                color:"#5A667F",
                width:"77px",
                height:"28px",
                textAlign:"center",
                padding: "0"
            },
            sizeActiveStyle: {
                borderRadius:"0px 14px 14px 0px",
                border: "none", 
                background: "#54B0FE",
                color:"#FFFFFF",
                width:"77px",
                height:"28px",
                textAlign:"center",
                padding: "0"
            },
            userDesc: [ // 管理员 活跃用户
                this.$t('home_active_user') + '：' + this.$t('home_active_user_desc'),
                this.$t('home_all_user') + '：' + this.$t('home_all_user_desc')
            ],
            workDesc: [ // 管理员 可执行作业数
                this.$t('home_all') + '：' + this.$t('home_executable_work_desc'),
                this.$t('home_person') + '：' + this.$t('home_executable_work_desc_person')
            ],
            taskDesc: [ // 管理员 可执行任务
                this.$t('home_all') + '：' + this.$t('home_executable_task_desc'),
                this.$t('home_person') + '：' + this.$t('home_executable_task_desc_person')
            ],
            projDesc: [ // 管理员 运行项目
                this.$t('home_active_project') + '：' + this.$t('home_active_project_desc'),
                this.$t('home_all_projects') + '：' + this.$t('home_all_projects_desc')
            ],
            workDesc2: [ // 作业员 可执行作业数
                this.$t('home_executable_work_desc_person')
            ],
            taskDesc2: [ // 作业员 可执行任务
                this.$t('home_executable_task_desc_person')
            ],
            dailyWork2: [ // 作业员 今日作业量
                this.$t('home_today_work_desc')
            ]
        };
    },
    computed: {
        access () {  // 0 管理员  1 作业员
            let roles = this.$store.state.user.userInfo.roles;
            let limit = false;
            $.each(roles, (k, v) => {
                if (v.item_name == 'manager') {
                    limit = true;
                }
            })
            return limit ? 0 : 1;
        },
        userInfor () {
            if (this.access == 1) {
                return {
                    height: "110px"
                }
            } else {
                return {
                    height: "215px"
                }
            }
        },
        styleHeight () {
            if (this.access == 1) {
                return {
                    height: "210px"
                }
            } else {
                return {
                    height: "172px"
                }
            }
        },
        styleTop () {
            if (this.access == 1) {
                return {
                    top: "57px"
                }
            } else {
                return {
                    top: "20px"
                }
            }
        },
        dataObject () {
            if (this.selType == "data") {
                return this.dataActiveStyle;
            } else {
                return this.dataStyle
            }
        },
        sizeObject () {
            if (this.selType == "size") {
                return this.sizeActiveStyle;
            } else {
                return this.sizeStyle;
            }
        }
    },
    watch: {
        '$store.state.app.lang' () {
            this.userDesc = [ // 管理员 活跃用户
                this.$t('home_active_user') + '：' + this.$t('home_active_user_desc'),
                this.$t('home_all_user') + '：' + this.$t('home_all_user_desc')
            ];
            this.workDesc = [ // 管理员 可执行作业数
                this.$t('home_all') + '：' + this.$t('home_executable_work_desc'),
                this.$t('home_person') + '：' + this.$t('home_executable_work_desc_person')
            ];
            this.taskDesc = [ // 管理员 可执行任务
                this.$t('home_all') + '：' + this.$t('home_executable_task_desc'),
                this.$t('home_person') + '：' + this.$t('home_executable_task_desc_person')
            ];
            this.projDesc = [ // 管理员 运行项目
                this.$t('home_active_project') + '：' + this.$t('home_active_project_desc'),
                this.$t('home_all_projects') + '：' + this.$t('home_all_projects_desc')
            ];
            this.workDesc2 = [ // 作业员 可执行作业数
                this.$t('home_executable_work_desc_person')
            ];
            this.taskDesc2 = [ // 作业员 可执行任务
                this.$t('home_executable_task_desc_person')
            ];
            this.dailyWork2 = [ // 作业员 今日作业量
                this.$t('home_today_work_desc')
            ]
        }
    },
    mounted () {
        this.getUserIndex();
    },
    methods: {
        transformValue (val) {
            let endVal = 0;
            let unit = '';
            if (val < 1000) {
                endVal = val;
            } else if (val >= 1000 && val < 1000000) {
                unit = 'K';
                endVal = parseInt(val / 1000) + unit;
            } else if (val >= 1000000 && val < 10000000000) {
                unit = 'M';
                endVal = parseInt(val / 1000000) + unit;
            } else {
                unit = 'B';
                endVal = parseInt(val / 1000000000) + unit;
            }
            return endVal;
        },
        limitRoles () { // 0 管理员  1 作业员  2 管理员 作业员
            let roles = this.$store.state.user.userInfo.roles;
            let arr = [];
            $.each(roles, (k, v) => {
                arr.push(v.item_name);
            })
            if (arr.length == 1) {
                if (arr.includes('manager')) {
                    return '0';
                } else {
                    return '1';
                }
            } else if (arr.length == 2) {
                return '2';
            }
        },
        getUserLogo (url) {
            if (url.indexOf('http') > -1) {
                return url;
            } else {
                return api.staticBase + url;
            }
        },
        getUserIndex () {
            this.spinShow = true;
            $.ajax({
                url: api.user.index,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                },
                success: (res) => {
                    this.spinShow = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.userRoles = [];
                        $.each(res.data.user.roles, (k, v) => {
                            this.userRoles.push(res.data.roles[v.item_name]);
                        });
                        this.avatar = res.data.user.avatar;
                        this.email = res.data.user.email;
                        this.nickname = res.data.user.nickname;
                        this.loginTime = util.timeFormatter(new Date(res.data.user.userStat.login_last_time * 1000), 'yyyy-MM-dd');
                        this.loginIp = res.data.user.userStat.login_last_ip;
                        let resData = res.data;
                        this.myWorkCount = resData.myStatToday ? resData.myStatToday.work_count * 1 : 0;
                        this.userActiveCount = resData.userActiveCount * 1;
                        this.taskCount = resData.taskCount * 1;
                        this.userTaskCount = resData.userTaskCount * 1;
                        this.userCountLimit = resData.userCountLimit * 1;
                        this.dataCount = resData.dataCount * 1;
                        this.userDataCount = resData.userDataCount * 1;
                        this.userCount = resData.userCount * 1;
                        this.projectCount = resData.projectCount * 1;
                        this.projectRunningCount = resData.projectRunningCount *1;
                        this.categoryGroup = this.getCategoryGroup(resData.projectStatByCategory);
                        this.dataStat = this.getDataStat(resData.dataStat);
                        this.sizeStat = this.getDataStat(resData.dataSize);
                        let dates = [];
                        let pubData = [];
                        let finData = [];
                        let userFinData = [];
                        // let arr = (this.access == 0) ? resData.teamStatByDay : resData.myStatByDay;
                        // $.each(arr, (k, v) => {
                        //     dates.push(v.date);
                        //     pubData.push(v.amount);
                        // });
                        if (this.limitRoles() == 0) {  // 管理员
                            $.each(resData.dataPublishMonths, (k, v) => {
                                let str1 = v.table_suffix.substr(0,2);
                                let str2 = v.table_suffix.substr(2);
                                let table_suffix = str2 + '-' + str1;
                                dates.push(table_suffix);
                                pubData.push(v.amount);
                                var i;
                                var lock = false;
                                for (i = 0; i < resData.dataFinishMonths.length; i++) {
                                    if (v.table_suffix == resData.dataFinishMonths[i].table_suffix) {
                                        finData.push(resData.dataFinishMonths[i].amount);
                                        lock = true;
                                    }
                                }
                                if (!lock) {
                                    finData.push(0);
                                }
                            });
                            this.dataGroup = {
                                num: 0,
                                dates,
                                pubData,
                                finData
                            };
                        } else if (this.limitRoles() == 1) { // 作业员

                        } else if (this.limitRoles() == 2) { // 管理员 作业员
                            $.each(resData.dataPublishMonths, (k, v) => {
                                let str1 = v.table_suffix.substr(0,2);
                                let str2 = v.table_suffix.substr(2);
                                let table_suffix = str2 + '-' + str1;
                                dates.push(table_suffix);
                                pubData.push(v.amount);
                                var i;
                                var lock = false;
                                var flog = false;
                                for (i = 0; i < resData.dataFinishMonths.length; i++) {
                                    if (v.table_suffix == resData.dataFinishMonths[i].table_suffix) {
                                        finData.push(resData.dataFinishMonths[i].amount);
                                        lock = true;
                                    }
                                }
                                for (i = 0; i < resData.userDataFinishMonths.length; i++) {
                                    if (v.table_suffix == resData.userDataFinishMonths[i].table_suffix) {
                                        userFinData.push(resData.userDataFinishMonths[i].amount);
                                        flog = true;
                                    }
                                }
                                if (!lock) {
                                    finData.push(0);
                                }
                                if (!flog) {
                                    userFinData.push(0);
                                }
                            });
                            this.dataGroup = {
                                num: 2,
                                dates,
                                pubData,
                                finData,
                                userFinData
                            }
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.spinShow = false;
                    });
                }
            });
        },
        getDataStat (data) {
            let arr = [];
            $.each(data, (k, v) => {
                arr.push({
                    value: v.count,
                    name: v.name
                });
            });
            return arr;
        },
        getCategoryGroup (data) {
            let categories = [];
            $.each(data, (k, v) => {
                if (v.category) {
                    categories.push({
                        value: v.count,
                        name: v.category.name
                    });
                }
            });
            // 从大到小排序
            for (let i = 0; i < categories.length; i++) {
                for (let j = i+1; j < categories.length; j++) {
                    if (categories[i].value*1 < categories[j].value*1 ) {
                        let tmp = categories[j];
                        categories[j] = categories[i];
                        categories[i] = tmp;
                    }
                }
            }
            if (categories.length > 18) {
                let arr = categories.splice(17,categories.length-17);
                let value = 0;
                $.each(arr, (k,v) => {
                    value += v.value * 1;
                })
                categories.push({
                    value: value,
                    name: this.$t('operator_other')
                })
            }
            return categories;
        }
    }
};
</script>

<style lang="less">
// .user-infor{
//     height: 215px;
// }
.avator-img{
    // display: block;
    // width: 70%;
    // max-width: 100px;
    // height: auto;
    // border-radius: 50%
    width:100%;
    height: 100%;
}
.head-pic {
    width:100px;
    height: 100px;
    border-radius: 50%;
    overflow: hidden;
    margin: 0 auto;
}
.card-user-infor-name{
    display: inline-block;
    max-width: 187px;
    font-size: 2em;
    color: #2d8cf0;
    overflow: hidden;
    white-space: nowrap;
    text-overflow: ellipsis;
}
.card-title{
    color: #abafbd;
}
.made-child-con-middle{
    height: 100%;
}
.to-do-list-con{
    height: 190px;
    overflow: auto;
}
.to-do-item{
    padding: 2px;
}

.infor-card-con{
    height: 100px;
}
.infor-card-icon-con{
    height: 100%;
    color: white;
    border-radius: 3px 0 0 3px;
}
.map-con{
    height: 300px;
}
.bar-con{
    height: 300px;
}
.map-incon{
    height: 100%;
}
.data-source-row{
    height: 200px;
}
.line-chart-con{
    height: 342px;
}

</style>

<style>
@font-face {font-family: "iconfont";
  src: url('../../styles/fonts/iconfont.eot?t=1545805209021'); /* IE9*/
  src: url('../../styles/fonts/iconfont.eot?t=1545805209021#iefix') format('embedded-opentype'), /* IE6-IE8 */
  url('data:application/x-font-woff;charset=utf-8;base64,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') format('woff'),
  url('../../styles/fonts/iconfont.ttf?t=1545805209021') format('truetype'), /* chrome, firefox, opera, Safari, Android, iOS 4.2+*/
  url('../../styles/fonts/iconfont.svg?t=1545805209021#iconfont') format('svg'); /* iOS 4.1- */
}

.iconfont {
    display: inline-block;
    font-family: 'iconfont' !important;
    speak: none;
    font-style: normal;
    font-weight: normal;
    font-variant: normal;
    text-transform: none;
    text-rendering: auto;
    line-height: 1;
    -webkit-font-smoothing: antialiased;
    -moz-osx-font-smoothing: grayscale;
    vertical-align: middle;
}

.icon-people:before { content: "\e671"; }

.icon-tenant:before { content: "\e672"; }

.icon-card:before { content: "\e673"; }

.icon-peoples:before { content: "\e674"; }

.icon-bingtu-blue:before { content: "\e675"; }

.icon-bingtu-green:before { content: "\e676"; }

.home-main .iconfont {
    font-size: 50px !important;
}
</style>
<style scoped>
.empty {
    text-align: center;
}
.empty img{
    vertical-align: center
}
</style>
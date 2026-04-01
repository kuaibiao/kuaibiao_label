<template>
    <Row class="project-setting-con">
        <div style="background:#fff;">
            <h1 class="page-title">{{$t('project_project_settings')}}</h1>
            <Row class="nextBtn">
                <Button type="primary" v-if="(project.status == '0') || (project.status == '1')" @click="startProject" :loading="startProjectLoading" style="float:right;margin-right:20px">
                    {{$t('project_start_project')}}
                </Button>
                <Button type="primary" v-else @click="startProject" :loading="startProjectLoading" style="float:right;margin-right:20px">
                    {{$t('project_confirm_change')}}
                </Button>
                <Button @click="projectSetting" style="float:right;margin-right:20px">
                    {{$t('project_prev_project_settings')}}
                </Button>
                <Button v-if="(project.status == '0') || (project.status == '1')" @click="saveSetting" :loading="saveTaskLoading" style="float:right;margin-right:20px">
                    {{$t('project_save_project')}}
                </Button>
            </Row>
        </div>
        <div >
            <div class="layout-content-header" style="padding-top:20px">
                <div v-for="(item, index) in formData" :key="index" class="project-info">
                    <div style="margin-left:30px;">
                        <h3>{{step_types[item.step_type]}}</h3>
                        <Row>
                            <i-col span="24">
                                <span>{{$t('project_task_name')}}：{{item.name}}</span>
                            </i-col>
                        </Row>
                        <Row>
                            <i-col span="8">
                                <span>{{$t('project_tickets_received_each_time')}}：</span>
                                <InputNumber v-model="item.receive_count" :min="1" :max="100000" :precision="0"></InputNumber>
                            </i-col>
                            <i-col span="8">
                                <span>{{$t('project_effective_execution_time')}}(S)：</span>
                                <InputNumber v-model="item.receive_expire" :min="1" :max="100000" :precision="0"></InputNumber>
                            </i-col>
                            <i-col span="8" v-if="item.step_type != '3'">
                                <Button type="primary" @click="handleClick(item.id, index)" :disabled="item.is_public && item.step_type == '0'">{{ $t('project_setting_person') + '(' + item.user_count + ')'}}</Button>
                                <Checkbox v-model="item.is_public" v-if="item.step_type == '0'" style="margin-left: 20px">公开任务</Checkbox>
                                <Tooltip v-if="item.step_type == '0'">
                                  <Icon type="ios-help-circle-outline" :size="18" />
                                  <div slot="content"  >
                                    所有人都可以领取,包括新增用户
                                  </div>
                                </Tooltip>
                            </i-col>
                        </Row>
                    </div>
                </div>
                <p class="hint">{{$t('project_hint')}}</p>
            </div>
        </div>
        <Spin fix v-if="showSpin"></Spin>
        <Modal
            v-model="tableModel"
            :width="800"
            :mask-closable="false"
            :closable="false">
            <div slot="header" style="height: 30px">
                <Button type="primary" style="float: right" @click="modalConfrim" :disabled="task_user_ids.length == 0">{{$t('project_sure')}}</Button>
                <Button type="default" style="float: right;margin-right: 10px" @click="tableModel = false;lock=true">{{$t('project_close')}}</Button>
            </div>
            <Row style="margin: 5px 0 15px 0">
                <Button v-if="task_user_ids.length != all_user_ids.length" style="float:left;margin-right:5px" @click="selAllUser" :disabled="selAllUserLoading">{{$t('project_check_all')}}</Button>
                <Button v-else style="float:left;margin-right:5px" @click="cancelAllUser" :disabled="selAllUserLoading">{{$t('project_cancel_check_all')}}</Button>
                <div class="search_input">
                    <Input v-model="keyword"
                        @on-enter="changeKeyword"
                        @on-search="changeKeyword"
                        :placeholder="$t('admin_input_user_info')"
                        clearable
                        search
                        :enter-button="true" />
                </div>
            </Row>
            <Table
                size="large"
                highlight-row
                ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                stripe
                show-header
                @on-filter-change="filterChange"
                @on-selection-change="selChange"
            >
            </Table>
            <div style="margin: 10px;overflow: hidden">
                <!-- <div style="float: left">已分配人数：<span class="allot">{{task_user_ids.length}}</span></div> -->
                <div style="float: right;">
                    <Page
                        :total="count"
                        :current="page"
                        :page-size ="limit"
                        :page-size-opts="[5,10,15,20,25,50]"
                        show-total
                        show-elevator
                        show-sizer
                        placement = "top"
                        @on-change="changePage"
                        transfer
                        @on-page-size-change = "changePageSize"
                        ></Page>
                </div>
            </div>
            <div slot="footer">
            </div>
        </Modal>
    </Row>
</template>

<script>
    import api from '@/api';
    import util from '@/libs/util';
    import cloneDeep from 'lodash.clonedeep';

    export default {
        name: 'project-configuration',
        data () {
            return {
                projectId: this.$route.params.id,
                category: {}, // 项目所属分类信息
                project: {}, // 项目总体信息
                formData: [],
                step_types: {},
                roles: [],
                showSpin: true,
                tableModel: false,
                loading:false,
                saveTaskLoading: false,
                startProjectLoading: false,
                selAllUserLoading: false,
                lock: true,
                currTaskId: '',
                currIndex: '',
                keyword: '',
                count: 0,
                page: 1,
                limit: 5,
                role_id: '',
                group_id: '',
                task_user_ids: [],
                tableData:[],
                selItem: [],
                tabIds: [],
                all_user_ids: [],
                tableOption: [
                    {
                        type: 'selection',
                        width: 60,
                        align: 'center'
                    },
                    {
                        title: 'ID',
                        align:'center',
                        key: 'id'
                    },
                    {
                        title: this.$t('admin_nickname'),
                        align: 'center',
                        key: 'nickname'
                    },
                    {
                        title: this.$t('user_email'),
                        align: 'center',
                        key:'email'
                    },
                    {
                        title: this.$t('admin_role'),
                        align: 'center',
                        key: 'role_id',
                        render: (h, para) => {
                            return h('span', {},
                                this.formatRole(para.row.roles));
                        },
                        filterMultiple: false,
                        filters: [],
                        filterMethod: () => true
                    },
                    {
                        title: this.$t('admin_group'),
                        align: 'center',
                        key: 'group_id',
                        render: (h, para) => {
                            return h('span', {}, para.row.group.name);
                        },
                        filters: [],
                        filterMultiple: false,
                        filterMethod: () => true
                    },
                ],
            };
        },
        mounted () {
            this.initForm();
        },
        methods: {
            formatRole (role) {
                let arr = [];
                $.each(role, (k, v) => {
                    arr.push(this.roles[v.item_name]);
                });
                return arr.toString();
            },
            changeKeyword () {
                this.page = 1;
                this.getUserList();
            },
            filterChange (filter) {
                let key = filter.key;
                this[key] = filter._filterChecked.toString();
                this.page = 1;
                this.getUserList();
            },
            changePage (page) {
                this.page = page;
                this.getUserList();
            },
            selChange (selection, row) {
                let arr = [];
                $.each(selection, function (k, v) {
                    arr.push(v.id);
                });
                this.selItem = arr;
                this.chengeSelectedUsers(arr);
            },
            chengeSelectedUsers (arr) {
                $.each(this.tabIds, (k, v) => {
                    if((this.selItem.indexOf(v) > -1) && (this.task_user_ids.indexOf(v) == -1)) {
                        this.task_user_ids.push(v)
                    } else if (this.selItem.indexOf(v) == -1 && (this.task_user_ids.indexOf(v) > -1)) {
                        this.task_user_ids.splice(this.task_user_ids.indexOf(v), 1)
                    }
                })

            },
            changePageSize (size) {
                this.limit = size;
                this.getUserList();
            },
            projectSetting () {
                this.$router.push({
                    name: 'project-create',
                    id: this.$route.params.id
                })
            },
            handleClick (task_id, index) {
                this.currIndex = index;
                this.currTaskId = task_id;
                this.task_user_ids = [];
                this.getUserList();
            },
            getUserList () {
                $.ajax({
                    url: api.task.assignedUser,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        task_id: this.currTaskId,
                        keyword: this.keyword,
                        limit: this.limit,
                        page: this.page,
                        group_id: this.group_id,
                        role_id: this.role_id,
                    },
                    success: res => {
                        this.selAllUserLoading = false;
                        let data = res.data;
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.all_user_ids = res.data.all_user_ids;
                            this.selItem = []
                            this.getTableData(res.data);
                            !this.tableModel && (this.tableModel = true);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        this.selAllUserLoading = false;
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                })
            },
            getTableData (data) {
                this.tabIds = [];
                this.tableData = data.list;
                if(this.lock) {
                    this.task_user_ids = [...data.task_user_ids];
                    this.lock = false;
                }
                $.each(this.tableData, (k, v) => {
                    this.tabIds.push(v.id);
                    if(this.task_user_ids.indexOf(v.id) > -1) {
                        v._checked = true
                        this.selItem.push(v.id);
                    } else {
                        v._checked = false
                    }
                })
                this.count = +data.count; // 整数
                let groups = data.groups;
                let groupMap = [];
                for (const key in groups) {
                    if (groups.hasOwnProperty(key)) {
                        let group = {
                            label: groups[key].name,
                            value: groups[key].id
                        };
                        groupMap.push(group);
                    }
                }
                let roleMap = [];
                this.roles = data.roles;
                Object.keys(this.roles).forEach((v, k) => {
                    let role = {
                        label: data.roles[v],
                        value: v
                    };
                    roleMap.push(role);
                });
                this.changeTagFilter(roleMap, groupMap);
            },
            changeTagFilter (roleMap, groupMap) {
            // 动态调整项目类型过滤器
                let roleIndex = util.getKeyIndexFromTableOption(this.tableOption, 'role_id');
                let groupIndex = util.getKeyIndexFromTableOption(this.tableOption, 'group_id');
                if (groupIndex < 0 || roleIndex < 0) {
                    return;
                }
                let roleType = this.tableOption[roleIndex];
                roleType.filters = roleMap;
                let groupType = this.tableOption[groupIndex];
                groupType.filters = groupMap;
                // hack 动态filter
                this.$nextTick(() => {
                    if (this.group_id) {
                        this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_filterChecked', [this.group_id]);
                        this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_isFiltered', true);
                    }
                    if (this.role_id) {
                        this.$set(this.$refs.userTable.cloneColumns[roleIndex], '_filterChecked', [this.role_id]);
                        this.$set(this.$refs.userTable.cloneColumns[roleIndex], '_isFiltered', true);
                    }
                });
            },
            initForm () {
                $.ajax({
                    url: api.project.detail,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.params.id
                    },
                    success: res => {
                        this.showSpin = false;
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.project = res.data.project;
                            this.formData = [];
                            $.each(res.data.project.tasks, (k, v) => {
                                this.formData.push({
                                    id: v.id,
                                    name: v.name,
                                    step_type: v.step.type,
                                    receive_count: +v.receive_count,
                                    receive_expire: +v.receive_expire,
                                    user_count: v.user_count,
                                    is_public:v.is_public == '1' ? true : false,
                                });
                            });
                            this.step_types = res.data.stepTypes;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        this.showSpin = false;
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            },
            modalConfrim () {
                $.ajax({
                    url: api.task.assignUsers,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        task_id: this.formData[this.currIndex].id,
                        user_id: this.task_user_ids.toString()
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.success({
                                content: this.$t('project_save_success'),
                                duration: 3
                            });
                            this.formData[this.currIndex].user_count = this.task_user_ids.length;
                            this.task_user_ids = [];
                            this.lock = true;
                            this.tableModel = false;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                })
            },
            selAllUser () {
                this.selAllUserLoading = true;
                this.task_user_ids = [...this.all_user_ids];
                this.getUserList('all')
            },
            cancelAllUser () {
                this.selAllUserLoading = true;
                this.task_user_ids = [];
                this.getUserList('cancel')
            },
            saveSetting () {
                this.saveTaskLoading = true;
                this.formData.forEach((item)=>{
                  if(item.is_public){
                    item.is_public = '1';
                  }else{
                    item.is_public = '0';
                  }
                })
                $.ajax({
                    url: api.project.submit,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.params.id,
                        tasks: this.formData,
                        is_start: '0'
                    },
                    success: res => {
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.destroy();
                            this.$Message.success(this.$t('project_save_success'));
                            this.$router.push({
                                name: 'project-management',
                            });
                        }
                        this.saveTaskLoading = false;
                    },
                    error: (res, textStatus, responseText) => {
                        this.saveTaskLoading = false;
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            },
            startProject () {
                this.startProjectLoading = true;
                this.formData.forEach((item)=>{
                  if(item.is_public){
                    item.is_public = '1';
                  }else{
                    item.is_public = '0';
                  }
                })
                $.ajax({
                    url: api.project.submit,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        project_id: this.$route.params.id,
                        tasks: this.formData,
                        is_start: '2'
                    },
                    success: res => {
                        this.startProjectLoading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.$Message.destroy();
                            this.$Message.success(this.$t('project_save_success'));
                            this.$router.push({
                                name: 'project-management',
                            });
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        this.startProjectLoading = false;
                        util.handleAjaxError(this, res, textStatus, responseText);
                    }
                });
            }
        }
    };
</script>
<style scoped>
    .date-picker {
        max-width: 606px;
    }
    .project-setting-con {
        /* background: #ffffff; */
        background: #eee;
        padding: 0 0 50px
    }
    .page-title {
        font-size: 18px;
        color: #464c5b;
        line-height: 56px;
        padding-left: 20px;
        font-weight: 400;
        border-bottom: 1px solid #e5e5e5;
    }
    .nextBtn {
        position: absolute;
        width: 100%;
        top: 12px;
    }
    .demo-spin-icon-load{
        animation: ani-demo-spin 1s linear infinite;
    }
    .supports {
        font-size: 14px
    }
    .layout-content-header {
        border-bottom: 1px solid #e5e5e5;
        padding: 0 0 15px;
        background:#ffffff;
    }
    .page-title {
        font-size: 16px;
        color: #464c5b;
    }
    .project-info{
        border:1px solid #ccc;
        border-radius: 20px;
        min-height: 180px;
        margin-left: 20px;
        margin-bottom: 20px;
        line-height: 50px;
        font-size: 13px;
        width: 90%;
    }
    .hint{
        margin-left: 20px;
        padding-bottom: 50px;
        color:red;
        font-size: 13px;
    }
    .footer{
        margin-top: 10px;
        overflow: hidden;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .allot{
        font-size: 14px;
        font-weight: 600;
        color:#009900;
    }
</style>






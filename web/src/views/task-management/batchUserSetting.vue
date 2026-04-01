<template>
    <div class="wrapper-content">
        <div class="layout-content">
            <Row class="setting-user-header">
                <Button type="default" @click="selectAllUser(currentBtn.target)" :disabled="isdisabled" style="position: absolute;left: 0">{{currentBtn.name}}</Button>
                <div style="float: right;height: 32px;line-height: 32px;margin-right: 10px">
                    <span>{{$t('admin_recommend')}}： <Button size="small" v-for="(item, index) in recomend_users" :key="index" @click="searchUser(item.id)">{{item.nickname}}</Button></span>
                </div>
                <div class="search_input">
                    <Input v-model="keyword"
                        @on-enter="changeKeyword"
                        @on-search="changeKeyword"
                        :placeholder="$t('admin_input_user_info')"
                        clearable
                        search 
                        :enter-button="true"/>
                </div>
            </Row>
            <Row>
                <Table
                    size="large"
                    ref="userTable"
                    disabled-hover
                    :columns="tableOption"
                    :data="tableData"
                    :loading="loading"
                    show-header
                    :row-class-name="tabClass"
                    @on-select-cancel="handleCan"
                    @on-select="handleSel"
                    @on-selection-change="selChange"
                    @on-row-click="clickRow"
                    @on-filter-change = "filterChange"
                ></Table>
                <div style="margin: 10px;overflow: hidden">
                    <div style="float: right;">
                        <Page
                            :total="count"
                            :current="page"
                            :page-size ="limit"
                            :page-size-opts="[10,15,20,25,30,50]"
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
            </Row>
        </div>
    </div>
</template>
<script>
import api from '@/api';
import util from '@/libs/util';
import Vue from 'vue';
export default {
    data () {
        return {
            task_id: this.$route.params.task_id,
            team_name: '',
            loading: false,
            isdisabled: false,
            count: 0,
            keyword: '',
            page: 1,
            limit: +((localStorage.getItem('userSettingParams') && JSON.parse(localStorage.getItem('userSettingParams')).limit) || 10),
            group_id: '',
            tag_id: [],
            ids: [],
            groups: [],
            sel_ids: [],
            roles: [],
            recomend_users: [],
            current_ids: [],
            currentBtn: {
                name: this.$t('admin_set_user_sel_all'),
                target: 'selectAll'
            },
            userCount: '',
            task_name: '',
            tableOption: [
                {
                    type: 'selection',
                    width: 80,
                    align: 'center',
                },
                {
                    title: 'ID',
                    key: 'id',
                    width: 80,
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {},
                            para.row.user.id);
                    }
                },
                {
                    title: this.$t('admin_nickname'),
                    key: 'nickname',
                    align: 'left',
                    render: (h, params) => {
                        return h('div', [
                            h('span', {}, params.row.user.nickname)
                        ]);
                        // if (params.row._checked) {
                        //     return h('div', [
                        //         h('span', {}, params.row.user.nickname),
                        //         h('Icon', {
                        //             props: {
                        //                 type: 'md-checkmark'
                        //             },
                        //             style: {
                        //                 color: 'green',
                        //                 marginLeft: '20px'
                        //             }
                        //         })
                        //     ]);
                        // } else {
                        //     return h('div', [
                        //         h('span', {}, params.row.user.nickname)
                        //     ]);
                        // }
                    }
                },
                {
                    title: this.$t('admin_role'),
                    key: 'type_name',
                    align: 'center',
                    render: (h, params) => {
                        return h('span', {
                            domProps: {
                                innerHTML: this.smarty(params.row.user.roles, params.row.user.type)
                            }
                        });
                    }
                },
                {
                    title: this.$t('admin_email'),
                    key: 'email',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {},
                            para.row.user.email);
                    }
                },
                {
                    title: this.$t('admin_mobile'),
                    key: 'mobile',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {},
                            para.row.user.mobile);
                    }
                },
                {
                    title: this.$t('admin_group'),
                    key: 'group_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {}, para.row.group.name);
                    },
                    filters: [],
                    filterMultiple: false,
                    filterMethod: () => true
                },
                {
                    title: this.$t('admin_user_label'),
                    key: 'tag_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {
                            domProps: {
                                innerHTML: this.smartArr(para.row.user.tags)
                            }
                        });
                    },
                    // filterMultiple: false,
                    filters: [],
                    filterMethod: () => true

                },
                {
                    title: this.$t('admin_create_time'),
                    key: 'task_num',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {},
                            util.timeFormatter(new Date(+para.row.created_at * 1000), 'MM-dd hh:mm'));
                    }
                },
            ],
            tableData: [],
        };
    },
    watch: {
        keyword () {
            if (!this.keyword) {
                this.page = 1;
                this.getData();
            }
        }
    },
    methods: {
        tabClass (row, index) {
            if (row._checked) {
                return 'tab-class-name';
            } else {
                return '';
            }
        },
        searchUser (user) {
            this.page = 1;
            this.keyword = user;
            this.getData();
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
            let tagMap = [];
            let tags = data.tags;
            for (const key in tags) {
                if (tags.hasOwnProperty(key)) {
                    let tag = {
                        label: tags[key].name,
                        value: tags[key].id
                    };
                    tagMap.push(tag);
                }
            }
            let groupMap = [];
            let groups = data.groups;
            for (const key in groups) {
                if (groups.hasOwnProperty(key)) {
                    let group = {
                        label: groups[key].name,
                        value: groups[key].id
                    };
                    groupMap.push(group);
                }
            }
            this.changeTagFilter(tagMap, groupMap);
        },
        changeTagFilter (tagMap, groupMap) {
            // 动态调整项目类型过滤器
            let tagIndex = util.getKeyIndexFromTableOption(this.tableOption, 'tag_id');
            let groupIndex = util.getKeyIndexFromTableOption(this.tableOption, 'group_id');
            if (tagIndex < 0 || groupIndex < 0) {
                return;
            }
            let cateType = this.tableOption[tagIndex];
            cateType.filters = tagMap;
            let groupType = this.tableOption[groupIndex];
            groupType.filters = groupMap;
            // hack 动态filter
            Vue.nextTick(() => {
                if (this.tag_id.toString() !== '') {
                    this.$set(this.$refs.userTable.cloneColumns[tagIndex], '_filterChecked', this.tag_id);
                    this.$set(this.$refs.userTable.cloneColumns[tagIndex], '_isFiltered', true);
                }
                if (this.group_id !== '') {
                    this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_filterChecked', [this.group_id]);
                    this.$set(this.$refs.userTable.cloneColumns[groupIndex], '_isFiltered', true);
                }
            });
        },
        filterChange (filter) {
            let key = filter.key;
            if (key == 'tag_id') {
                this[key] = filter._filterChecked;
            } else {
                this[key] = filter._filterChecked.toString();
            }

            this.page = 1;
            this.getData();
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        selectAllUser (target) {
            this.updateIds([], target);
        },
        handleSel (selection, row) {
            if (this.sel_ids.length === this.ids.length - 1) {
                return;
            }
            this.updateIds(row.user.id, 'add');
        },
        handleCan (selection, row) {
            if (this.sel_ids.length === 1) {
                return;
            }
            this.updateIds(row.user.id, 'delete');
        },
        selChange (selection, row) {
            if ((selection.length === this.tableData.length)) {
                this.updateIds(this.ids, 'add');
            } else if ((selection.length === 0)) {
                this.updateIds(this.ids, 'delete');
            }
        },
        clickRow (row, index) {
            if ($.inArray(row.user.id, this.sel_ids) !== -1) {
                this.updateIds(row.user.id, 'delete');
            } else {
                this.updateIds(row.user.id, 'add');
            }
        },
        updateIds (selection, type) {
            this.isdisabled = true;
            let opt;
            if (type === 'add') {
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id,
                    op: 'add',
                    user_id: selection.toString()
                };
            } else if (type == 'delete') {
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id,
                    op: 'delete',
                    user_id: selection.toString()
                };
            } else if (type == 'selectAll') {
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id,
                    team_id: this.$store.state.user.userInfo.team.id,
                    op: 'add',
                };
            } else if (type == 'cancelAll') {
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id,
                    team_id: this.$store.state.user.userInfo.team.id,
                    op: 'delete',
                };
            }
            this.loading = true;
            $.ajax({
                url: api.task.assignUser,
                type: 'post',
                data: opt,
                success: (res) => {
                    this.loading = false;
                    this.isdisabled = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.$Message.destroy();
                        this.$Message.info({
                            content: this.$t('admin_updated'),
                            duration: 2
                        });

                        this.ids = [];
                        this.sel_ids = [];
                        $.each(this.tableData, (k, v) => {
                            this.ids.push(v.user.id);
                            this.$set(this.tableData[k], '_checked', false);
                            if ($.inArray(v.user.id, res.data.user_ids) !== -1) {
                                this.$set(this.tableData[k], '_checked', true);
                                this.sel_ids.push(v.user.id);
                            }
                        });
                        if (opt.team_id && (opt.op == 'add')) {
                            this.currentBtn = {
                                name: this.$t('admin_set_user_cancel_all'),
                                target: 'cancelAll'
                            };
                        } else if (opt.team_id && (opt.op == 'delete')) {
                            this.currentBtn = {
                                name: this.$t('admin_set_user_sel_all'),
                                target: 'selectAll'
                            };
                        }
                        if (opt.team_id) {
                            this.keyword = '';
                            this.group_id = '';
                            this.tag_id = [];
                            this.keyword = '';
                        }
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                        this.isdisabled = false;
                    });
                }
            });
        },
        getData () {
            this.loading = true;
            this.$store.state.app.userInfoRequest.then((res) => {
                this.requestData(res.data.user.team.id);
            });
        },
        requestData (team_id) {
            $.ajax({
                url: api.task.assignedUser,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    team_id: team_id,
                    task_id: this.task_id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    tag_id: this.tag_id.toString(),
                    group_id: this.group_id,
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.roles = res.data.roles;
                        this.getTableData(res.data);
                        this.ids = [];
                        this.sel_ids = [];
                        this.recomend_users = res.data.recomend_users;
                        $.each(this.tableData, (k, v) => {
                            this.ids.push(v.user.id);
                            this.$set(this.tableData[k], '_checked', false);
                            // let lock = true;
                            if (v.selected === '1') {
                                this.$set(this.tableData[k], '_checked', true);
                                this.sel_ids.push(v.user.id);
                            }
                            // if (this.task_id.split(',').length == 1) {
                            //     if ($.inArray(v.user.id, res.data.user_ids) == -1) {
                            //         lock = false;
                            //     }
                            // } else {
                            //     $.each(res.data.user_ids, (i, t) => {
                            //         if ($.inArray(v.user.id, t) == -1) {
                            //             lock = false;
                            //         }
                            //     });
                            // }
                            // if (lock) {
                            //     this.$set(this.tableData[k], '_checked', true);
                            //     this.sel_ids.push(v.user.id);
                            // }
                        });
                        if ((res.data.count === res.data.task_user_count) && (res.data.task_user_count > 0)) {
                            this.currentBtn = {
                                name: this.$t('admin_set_user_cancel_all'),
                                target: 'cancelAll'
                            };
                        }
                        localStorage.setItem('userSettingParams', JSON.stringify({
                            limit: this.limit
                        }));
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        initUsers () {
            $.ajax({
                url: api.task.assignedUser,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.ids = [];
                        this.sel_ids = [];
                        this.recomend_users = res.data.recomend_users;
                        $.each(this.tableData, (k, v) => {
                            this.ids.push(v.user.id);
                            this.$set(this.tableData[k], '_checked', false);
                            let lock = true;
                            if (this.task_id.split(',').length == 1) {
                                if ($.inArray(v.user.id, res.data.user_ids) == -1) {
                                    lock = false;
                                }
                            } else {
                                $.each(res.data.user_ids, (i, t) => {
                                    if ($.inArray(v.user.id, t) == -1) {
                                        lock = false;
                                    }
                                });
                            }
                            if (lock) {
                                this.$set(this.tableData[k], '_checked', true);
                                this.sel_ids.push(v.user.id);
                            }
                        });
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        smarty (roles, type) {
            let arrStr = '';
            roles.forEach((k, v) => {
                arrStr += this.roles[k.item_name] + '<br/>';
            });
            return arrStr;
        },
        // 处理数组
        smartArr (tags) {
            let showArr = '';
            for (const key in tags) {
                if (tags.hasOwnProperty(key)) {
                    const element = tags[key];
                    showArr += tags[key].name + '<br/>';
                }
            }
            return showArr;
        },
        getTaskDetail () {
            $.ajax({
                url: api.task.detail,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    task_id: this.task_id
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
                        this.userCount = res.data.info.user_count;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        }
    },

    mounted () {
        this.task_id = this.$route.params.task_id;
        this.getData();
        this.getTaskDetail();
    },
};
</script>
<style scoped>
    .setting-user-header {
        position: relative;
        margin-bottom:10px;
        display: flex;
        justify-content: flex-end;
        align-items:flex-end;
    }
    .project_name {
        font-size: 16px;
        color: #464c5b;
        flex-basis: 60%;
        line-height: 32px;
        text-align: center;
        font-weight: 400;
    }
    .user_count {
        font-size: 14px;
        position: absolute;
        left: 0;
    }
    .user_count strong{
        color: green;
        font-size: 16px;
    }
</style>




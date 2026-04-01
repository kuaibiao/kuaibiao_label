<template>
    <div>
        <Row class="margin-bottom-10">
            <i-col span="4">
                <Button @click.native="addUser" type="primary">
                    <!-- 添加成员 -->
                    {{$t('admin_add_member')}}
                </Button>
            </i-col>
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
                highlight-row
                ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                @on-select="handleSel"
                @on-select-cancel="handleCan"
                @on-selection-change="selChange"
                stripe
                show-header
            ></Table>
            <div style="margin: 10px;overflow: hidden">
                <!-- <Button :disabled="!btnBol" @click="massTransfer" type="primary">
                    {{$t('admin_mass_transfer')}}
                </Button> -->
                <div style="float: right;">
                    <Page
                        :total="count"
                        :current="page"
                        :page-size ="limit"
                        :page-size-opts="[10,15,20,25,50]"
                        show-total
                        show-elevator
                        show-sizer
                        transfer
                        placement = "top"
                        @on-change="changePage"
                        @on-page-size-change = "changePageSize"
                    ></Page>
                </div>
            </div>
        </Row>
        <Modal
            :width="850"
            v-model="addModal"
            :title="$t('admin_add_member')">
            <userSel :loadData="loadData" :groupId="this.$route.params.id" v-on:get-data="getData"></userSel>
            <span slot="footer"></span>
        </Modal>
        <Modal
                v-model="delModel"
                :title="$t('admin_operation_tips')">
            <p>{{$t('admin_remove_current_member')}}</p>
            <div slot="footer">
                <Button type="text" @click="delModel = false">{{$t('admin_cancel')}}</Button>
                <Button type="error" @click="remove" :loading="removeLoading">{{$t('admin_remove')}}</Button>
            </div>
        </Modal>
        <Modal v-model="teamModal" :title="$t('admin_select_group')">
            <div class="spin-container" v-if="spinStatus">
                <Spin fix></Spin>
            </div>
            <RadioGroup v-model="groupId" v-else>
                <Radio v-for="item in teams" :label="item.id" :key="item.id">{{item.name}}</Radio>
            </RadioGroup>

            <div slot="footer">
                <Button type="text" @click="teamModal = false">{{$t('admin_cancel')}}</Button>
                <Button type="error" @click="sureMass" :loading="sureMassLoading">{{$t('admin_deter_transfer')}}</Button>
            </div>
        </Modal>
    </div>
</template>

<script>
    import api from '@/api';
    import util from '@/libs/util';
    import userSel from './components/user-sel';
    export default {
        name: 'group-detail',
        data () {
            return {
                team_name: '',
                loading: false,
                keyword: '',
                count: 0,
                page: 1,
                limit: 10,
                orderby: '',
                sort: 'desc',
                addModal: false,
                delModel: false,
                loadData: false,
                removeLoading: false,
                sureMassLoading: false,
                currIndex: -1,
                del_id: 0,
                roles: [],
                team_id: '',
                userList_role: [],
                btnBol: false,
                teamModal: false,
                spinStatus: true,
                select_user_ids: [],
                groupId: '',
                teams: [],
                user_ids: '',
                tableOption: [
                    {
                        type: 'selection',
                        width: 60,
                        align: 'center'
                    },
                    {
                        title: 'ID',
                        key: 'id',
                        align: 'center',
                    },
                    {
                        title: this.$t('admin_nickname'),
                        key: 'nickname',
                        align: 'center',
                    },
                    {
                        title: this.$t('admin_role'),
                        key: 'type_name',
                        align: 'center',
                        render: (h, para) => {
                            return h('span', {}, this.formatRole(para.row.roles)
                            );
                        }
                    },
                    {
                        title: this.$t('admin_email'),
                        key: 'email',
                        align: 'center',
                    },
                    {
                        title: this.$t('admin_created_time'),
                        key: 'created_at',
                        align: 'center',
                        render: (h, para) => {
                            return h('span',
                                util.timeFormatter(new Date(+para.row.created_at * 1000), 'yyyy-MM-dd hh:mm'));
                        }
                    },
                    {
                        title: this.$t('admin_handle'),
                        align: 'center',
                        render: (h, params) => {
                            return h('div', [
                                h('Button', {
                                    props: {
                                        size: 'small'
                                    },
                                    style: {
                                        margin: '5px'
                                    },
                                    nativeOn: {
                                        click: () => {
                                            this.del_id = params.row.id;
                                            this.delModel = true;
                                        }
                                    }
                                }, this.$t('admin_remove')),
                            ]);
                        }
                    }
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
        mounted () {
            this.getData();
        },
        methods: {
            changePage (page) {
                this.page = page;
                this.getData();
            },
            changePageSize (size) {
                this.limit = size;
                this.getData();
            },
            genTableData (data) {
                let tableData = [];
                this.tableData = data.list;
                this.count = +data.count; // 整数
            },
            changeKeyword () {
                this.getData();
            },
            getData () {
                this.loading = true;
                $.ajax({
                    url: api.user.list,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        group_id: this.$route.params.id,
                        keyword: this.keyword,
                        limit: this.limit,
                        page: this.page
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
                            this.userList_role = res.data.roles;
                            this.genTableData(res.data);
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.loading = false;
                        });
                    }
                });
            },
            addUser () {
                this.loadData = true;
                this.addModal = true;
            },
            formatRole (role) {
                let arr = [];
                $.each(role, (k, v) => {
                    arr.push(this.userList_role[v.item_name]);
                });
                return arr.toString();
            },
            remove () {
                this.removeLoading = true;
                $.ajax({
                    url: api.group.userDelete,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        group_id: this.$route.params.id,
                        user_id: this.del_id,
                    },
                    success: (res) => {
                        this.loading = false;
                        this.removeLoading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.delModel = false;
                            this.getData();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.removeLoading = false;
                        });
                    }
                });
            },
            handleSel (selection, row) {
                this.getUserIds(selection);
            },
            handleCan (selection, row) {
                this.getUserIds(selection);
            },
            selChange (selection, row) {
                if (selection.length === 0) {
                    this.btnBol = false;
                } else {
                    this.btnBol = true;
                }
                this.getUserIds(selection);
            },
            getUserIds (selection) {
                this.user_ids = '';
                let user_id = [];
                selection.forEach(ele => {
                    user_id.push(ele.user_id);
                });
                this.user_ids = user_id.toString();
            },
            massTransfer () {
                this.teamModal = true;
                this.groupId = '';
                this.$store.state.app.userInfoRequest.then(res => {
                    this.requestTeamData(res.data.user.team.id);
                });
            },
            requestTeamData (team_id) {
                this.spinStatus = true;
                $.ajax({
                    url: api.team.groupList,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        team_id: team_id,
                        limit: this.limit,
                        page: this.page,
                        keyword: this.keyword,
                        orderby: this.orderby,
                        sort: this.sort,
                    },
                    success: res => {
                        this.spinStatus = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.teams = res.data.list;
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.spinStatus = false;
                        });
                    }
                });
            },
            sureMass () {
                this.$store.state.app.userInfoRequest.then(res => {
                    this.handleData(res.data.user.team.id);
                });
            },
            handleData (team_id) {
                this.sureMassLoading = true;
                $.ajax({
                    url: api.team.moveGroupUser,
                    type: 'post',
                    data: {
                        access_token: this.$store.state.user.userInfo.accessToken,
                        group_id: this.groupId,
                        team_id: team_id,
                        user_ids: this.user_ids,
                    },
                    success: res => {
                        this.sureMassLoading = false;
                        this.loading = false;
                        if (res.error) {
                            this.$Message.destroy();
                            this.$Message.warning({
                                content: res.message,
                                duration: 3
                            });
                        } else {
                            this.teamModal = false;
                            this.getData();
                        }
                    },
                    error: (res, textStatus, responseText) => {
                        util.handleAjaxError(this, res, textStatus, responseText, () => {
                            this.sureMassLoading = false;
                            this.loading = false;
                        });
                    }
                });
            }
        },
        components: {
            userSel
        }
    };
</script>

<style scoped>
    .project_name {
        font-size: 18px;
        color: #464c5b;
        line-height: 35px;
        padding-bottom: 15px;
        font-weight: 400;
        position: absolute;
        left: 50%;
        transform:translateX(-50%)
    }
    .spin-container{
        min-height: 20px;
        position: relative;
    }
</style>

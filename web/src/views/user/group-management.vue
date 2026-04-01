<template>
  <div>
        <Row style="margin-bottom:10px">
            <Button type="primary" @click="edit(-1)">{{$t('admin_add_group')}}</Button>
            <div class="search_input">
                    <Input v-model="keyword"
                        @on-enter="changeKeyword"
                        @on-search="changeKeyword"
                        :placeholder="$t('admin_enter_group_name')"
                        clearable
                        search 
                        :enter-button="true"/>
                </div>
        </Row>
        <Table
                size="large"
                highlight-row ref="userTable"
                :columns="tableOption"
                :data="tableData"
                :loading="loading"
                stripe
                @on-sort-change="sortChange"
                show-header>
        </Table>
        <div style="margin: 10px;overflow: hidden">
            <div style="float: right;">
                <Page
                        :total="count"
                        :current="page"
                        :page-size="limit"
                        :page-size-opts="[10,15,20,25,30,50]"
                        show-total
                        show-elevator
                        show-sizer
                        placement="top"
                        @on-change="changePage"
                        transfer
                        @on-page-size-change="changePageSize">
                </Page>
            </div>
        </div>
        <Modal v-model="editModal" width="500">
            <p slot="header" style="text-align:center">
                <Icon type="ios-help-circle" />
                <span v-if="currIndex === -1">{{$t('admin_team_info')}}</span>
                <span v-if="currIndex !== -1">{{$t('admin_edit_group_info')}}</span>
            </p>
            <div style="text-align:left">
                <Form ref="formValidate" :model="currData" :label-width="120" :rules="ruleValidate" @submit.native.prevent>
                    <Form-item :label="$t('admin_group_name')" prop="name">
                        <Row>
                            <i-col span="20">
                                <Input v-model="currData.name" :placeholder="$t('admin_enter_group_name')" @on-enter="saveBatch"/>
                            </i-col>
                        </Row>
                    </Form-item>
                </Form>
            </div>
            <div slot="footer">
                <Button type="success" size="large" long @click.native="saveBatch" :loading="editLoading">
                    <!-- 保存 -->
                    {{$t('admin_save')}}
                </Button>
            </div>
        </Modal>
        <Modal
                v-model="delModel"
                :title="$t('admin_operation_tips')">
            <p>{{$t('admin_sure_deleting_group')}}</p>
            <div slot="footer">
                <Button type="text" @click="delModel = false">{{$t('admin_cancel')}}</Button>
                <Button type="error" @click="remove" :loading="removeLoading">{{$t('admin_delete')}}</Button>
            </div>
        </Modal>
  </div>
</template>

<script>
import api from "@/api";
import util from "@/libs/util";
export default {
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            removeLoading: false,
            editModal: false,
            delModel: false,
            editLoading: false,
            editModel: false,
            currIndex: -1,
            currData: {
                name: ''
            },
            tableOption: [
                {
                    title: "ID",
                    key: 'id',
                    align: 'center'
                },
                {
                    title: this.$t('admin_group_name'),
                    key: 'name',
                    align: 'center'
                },
                {
                    title: this.$t('admin_number_people'),
                    key: 'count',
                    align: 'center'
                },
                {
                    title: this.$t('admin_creation_time'),
                    key: 'created_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('admin_update_time'),
                    key: 'updated_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.updated_at * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    }
                },
                {
                    title: this.$t('admin_handle'),
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('Button', {
                                style: {
                                    margin: '3px',
                                },
                                props: {
                                    type: 'primary',
                                    size: 'small'
                                },
                                on: {
                                    click: () => {
                                        this.currIndex = this.tableData[para.index].id;
                                        this.edit(this.tableData[para.index].id, this.tableData[para.index]);
                                    }
                                }
                            }, this.$t('admin_edit')),
                            h('Button', {
                                style: {
                                    margin: '3px',
                                },
                                props: {
                                    type: 'warning',
                                    size: 'small'
                                },
                                on: {
                                    click: () => {
                                        this.$router.push({
                                            name: 'group-detail',
                                            params: {
                                                id: para.row.id
                                            }
                                        });
                                    }
                                }
                            }, this.$t('admin_crew')),
                            h('Button', {
                                props: {
                                    type: 'default',
                                    size: 'small'
                                },
                                style: {
                                    margin: '3px',
                                },
                                on: {
                                    click: () => {
                                        this.del_id = this.tableData[para.index].id;
                                        this.delModel = true;
                                    }
                                }
                            }, this.$t('admin_delete'))
                        ]);
                    }
                }
            ],
            tableData: [],
            ruleValidate: {
                name: [
                    { required: true, message: this.$t('admin_enter_group_name'), trigger: 'blur' },
                    {
                        type: 'string',
                        max: 25,
                        message: this.$t('admin_group_name_format'),
                        trigger: 'blur'
                    }
                ]
            },
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
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
        edit (index, data) {
            this.$refs.formValidate.resetFields();
            if (index === -1) { // 新增
                this.currData = {
                    name: '',
                };
                this.currIndex = index;
            } else { // 编辑
                this.currData = {
                    name: data.name,
                };
            }
            this.editModal = true;
        },
        saveBatch () {
            let saveApi = '';
            let opt = null;
            if (this.editLoading) {
                return;
            }
            this.editLoading = true;
            if (this.currIndex === -1) {
                saveApi = api.group.create;
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    name: this.currData.name,
                };
            } else {
                saveApi = api.group.update;
                opt = {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    group_id: this.currIndex,
                    name: this.currData.name,
                };
            }
            this.$refs.formValidate.validate((valid) => {
                if (valid) {
                    $.ajax({
                        url: saveApi,
                        type: 'post',
                        data: opt,
                        success: res => {
                            this.editLoading = false;
                            if (res.error) {
                                this.$Message.destroy();
                                this.$Message.warning({
                                    content: res.message,
                                    duration: 3
                                });
                            } else {
                                this.$Message.destroy();
                                this.$Message.success(this.$t('admin_saved'));
                                this.editModal = false;
                                this.getData();
                            }
                        },
                        error: (res, textStatus, responseText) => {
                            util.handleAjaxError(this, res, textStatus, responseText, () => {
                                this.editLoading = false;
                            });
                        }
                    });
                } else {
                    this.editLoading = false;
                }
            });
        },
        remove () {
            if (this.removeLoading) {
                return;
            }
            this.removeLoading = true;
            $.ajax({
                url: api.group.delete,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    group_id: this.del_id,
                },
                success: res => {
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
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.group.groups,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    limit: this.limit,
                    page: this.page,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.destroy();
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getTableData(res.data);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
    },
    mounted () {
        this.getData();
    }

};
</script>

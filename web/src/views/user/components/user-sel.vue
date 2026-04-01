<template>
    <div>
        <Row class="margin-bottom-10">
            <!-- <i-col span="4">
                <Button type="primary" icon="md-add">
                    批量添加
                </Button>
            </i-col> -->
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
                    ref="prejectTable"
                    :columns="tableOption"
                    :data="tableData"
                    :loading="loading"
                    stripe
                    show-header
            ></Table>
            <div style="margin: 10px;overflow: hidden">
                <div style="float: right;">
                    <Page
                        :total="count"
                        :current="page"
                        :page-size ="limit"
                        :page-size-opts="[5,8,10,20]"
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
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';

export default {
    props: {
        loadData: {
            type: Boolean
        },
        groupId: {
            type: String
        },
    },
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 5,
            status: [],
            orderby: '',
            sort: '',
            type: '',
            userList_role: [],
            currentTemplate: [],
            tableOption: [
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
                // {
                //     title: this.$t('admin_mobile'),
                //     key: 'mobile',
                //     align: 'center',
                //     render: (h, para) => {
                //         return h('span', {},
                //             para.row.user.mobile);
                //     }
                // },
                {
                    title: this.$t('admin_handle'),
                    align: 'center',
                    render: (h, params) => {
                        return h('div', [
                            h('Button', {
                                props: {
                                    type: 'primary',
                                    size: 'small'
                                },
                                nativeOn: {
                                    click: () => {
                                        this.addUser(params.row.id);
                                    }
                                }
                            }, this.$t('admin_add')),
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
        },
        loadData () {
            if (this.loadData) {
                this.getData();
            }
        }
    },
    mounted () {
        // this.getData();
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
        getTableData (data) {
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.user.list,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    group_id: 0,
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
        addUser (id) {
            $.ajax({
                url: api.group.userCreate,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    group_id: this.groupId,
                    user_id: id,
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
                        this.$Message.destroy();
                        this.$Message.success({
                            content: this.$t('admin_added_successfully'),
                            duration: 2
                        });
                        this.$emit('get-data');
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        formatRole (role) {
            let arr = [];
            $.each(role, (k, v) => {
                arr.push(this.userList_role[v.item_name]);
            });
            return arr.toString();
        }
    },
};
</script>

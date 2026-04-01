<template>
    <div class="subcontent" id="project_index">
        <div class="main-con">
            <Card>
                <div slot="title" class="item_title"><span class="blue-icon"></span>{{$t('project_operation_records')}}</div>
                <Row class="setting-user-header">
                    <div class="search_input">
                        <Input v-model="keyword"
                            @on-enter="changeKeyword"
                            @on-search="changeKeyword"
                            :placeholder="$t('project_input_operator')"
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
                        stripe
                        show-header
                        @on-sort-change = "sortChange"
                    ></Table>
                    <div style="margin: 10px;overflow: hidden;padding: 1px;">
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
            </Card>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util';
export default {
    name: 'operation-record',
    data () {
        return {
            project_id: this.$route.params.id,
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: '',
            types: {},
            roles: {},
            tableOption: [
                {
                    title: this.$t('project_time'),
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
                    title: this.$t('project_operator'),
                    key: 'nickname',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('Tooltip', {
                                props: {
                                    placement: 'top',
                                    transfer: true,
                                },
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + para.row.user.id),
                                            h('div', this.$t('project_email') + ': ' + para.row.user.email),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', para.row.user.nickname)
                            ]),
                        ]);
                    },
                },
                {
                    title: this.$t('user_role'),
                    key: 'role',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', {},
                            this.formatRole(para.row.userAuth));
                    },
                },
                {
                    title: this.$t('operator_operating_type'),
                    key: 'type',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', this.types[para.row.type]);
                    },
                },
                {
                    title: this.$t('project_content'),
                    key: 'message',
                    align: 'center'
                },

            ],
            tableData: []
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
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        changeKeyword () {
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.project.records,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    project_id: this.$route.params.id,
                    keyword: this.keyword,
                    orderby: this.orderby,
                    sort: this.sort,
                    limit: this.limit,
                    page: this.page
                },
                success: res => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getTableData(res.data);
                        this.types = res.data.types;
                        this.roles = res.data.roles;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
        formatRole (role) {
            let arr = [];
            $.each(role, (k, v) => {
                arr.push(this.roles[v.item_name]);
            });
            return arr.toString();
        },
    }
};
</script>

<style scoped>
    .setting-user-header {
        margin-bottom:10px;
    }
    .subcontent{
        background:#efefef;
    }
    .main-con{
        background:#efefef;
        padding: 20px 25px 10px;
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
</style>
<style>
    #project_index .item_title {
        margin-left: 8px;
        font-size: 15px;
        font-weight: 700;
    }
</style>

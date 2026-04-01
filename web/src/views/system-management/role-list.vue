<template>
    <div>
        <Row style="margin-bottom:10px">
            
        </Row>
        <Row>
            <Table
                size="large"
                highlight-row
                ref="roleListTable"
                :columns="tableColumns"
                :data="tableData"
                :loading="loading"
                stripe
                show-header>
                <div slot="footer">
                    <div style="float:right;margin-right:10px;font-size:12px">
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
            </Table>
        </Row>
    </div>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
import Vue from 'vue';
export default {
    name: 'role-list',
    data () {
        return {
            loading: false,
            keyword: '',
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: 'desc',
            tableData: [],
            tableColumns: [
                {
                    type: 'selection',
                    width: 100,
                    align: 'center'
                },
                {
                    title: this.$t('role_name'),
                    key: 'name',
                    align: 'center',
                },
                {
                    title: this.$t('role_description'),
                    key: 'description',
                    align: 'center',
                },
                {
                    title: this.$t('role_member_count'),
                    key: 'member_count',
                    align: 'center',
                },
                {
                    title: this.$t('role_created_time'),
                    key: 'created_at',
                    align: 'center',
                    render: (h, para) => {
                        return h('span',
                            util.timeFormatter(
                                new Date(+para.row.created_at * 1000),
                                'MM-dd hh:mm'
                            )
                        );
                    },
                    //sortable: 'custom',
                },
                {
                    title: this.$t('role_operation'),
                    align: 'center',
                    minWidth: 120,
                    //slot: 'operation',
                    render: (h, para) => {
                        return h('div', [
                            h('Button', {
                                    props: {
                                        type: 'primary',
                                        size: 'small'
                                    },
                                    style: {
                                        margin: '5px'
                                    },
                                    on: {
                                        click: () => {
                                            this.$router.push({
                                                name: 'system/role-edit',
                                                params: {
                                                    id: para.row.id,
                                                    tab: 'record'
                                                }
                                            });
                                        }
                                    }
                                },
                                this.$t('site_list_column_edit')
                            ),
                        ]);
                    }
                }
            ],
        };
    },
    computed: {
    },
    mounted () {
        this.getRoles();
    },
    components: {
    },
    methods: {
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        getRoles () {
            if (this.loading)
            {
                return;
            }

            this.loading = true;
            $.ajax({
                url: api.auth.roles,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
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
                        this.tableData = res.data.list;
                        this.count = +res.data.count;
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
};
</script>
<style scoped>
   
</style>
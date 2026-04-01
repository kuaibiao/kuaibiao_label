<template>
  <div>
    <Row>
        <i-col span="8" push="16">
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('user_input_equipment')"
                    clearable
                    search 
                    :enter-button="true"/>
            </div>
        </i-col>
    </Row>
    <div style="margin-top:10px">
      <Table
          size="large"
          highlight-row ref="deviceTable"
          :columns="tableOption"
          :data="tableData"
          :loading="loading"
          @on-filter-change = "filterChange"
          @on-sort-change = "sortChange"
          stripe
          show-header>
      </Table>
    </div>
     <div style="margin: 10px;overflow: hidden">
        <div style="float: right;">
            <Page
                :total="count"
                :current="page"
                :page-size="limit"
                :page-size-opts="[5,10,15,25]"
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
  </div>
</template>

<script>
import $ from 'jquery';
import api from '@/api';
import util from '@/libs/util';
export default {
    name: 'use-device',
    data () {
        return {
            loading: false,
            keyword: '',
            orderby: '',
            sort: '',
            count: 0,
            page: 1,
            limit: 5,
            tableOption: [
                {
                    title: 'ID',
                    key: 'id',
                    align: 'center',
                },
                {
                    title: this.$t('user_equipment_name'),
                    key: 'device_name',
                    align: 'center',
                },
                {
                    title: this.$t('user_equipment_number'),
                    key: 'device_number',
                    align: 'center',
                },
                {
                    title: 'APP KEY',
                    key: 'app_key',
                    align: 'center',
                },
                {
                    title: this.$t('user_version'),
                    key: 'app_version',
                    align: 'center',
                },
                {
                    title: this.$t('user_requests'),
                    key: 'request_count',
                    sortable: 'custom',
                    align: 'center',
                },
                {
                    title: this.$t('user_create_time'),
                    key: 'created_at',
                    sortable: 'custom',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', util.timeFormatter(
                            new Date(+para.row.created_at * 1000),
                            'yyyy-MM-dd hh:mm'
                        ));
                    }
                },
                {
                    title: this.$t('user_update_time'),
                    key: 'updated_at',
                    sortable: 'custom',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', util.timeFormatter(
                            new Date(+para.row.updated_at * 1000),
                            'yyyy-MM-dd hh:mm'
                        ));
                    }
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
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.page = 1;
            this.getData();
        },
        changeKeyword () {
            this.page = 1;
            this.getData();
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        getTableData (data) {
            let tableData = [];
            this.tableData = data.list;
            this.count = +data.count; // 整数
        },
        getData () {
            this.loading = true;
            $.ajax({
                url: api.user.userDevice,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                },
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
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
};
</script>

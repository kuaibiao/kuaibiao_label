<template>
  <div>
    <Row>
        <i-col span="12">
            <RadioGroup v-model="selDate" @on-change="setCurrentMesType(selDate)" type="button">
                <Radio v-for="(item,index) in dates" :key="index" :label="item">{{item | dateFormatter}}</Radio>
            </RadioGroup>
        </i-col>
        <i-col span="12">
            <div class="search_input">
                <Input v-model="keyword"
                    @on-enter="changeKeyword"
                    @on-search="changeKeyword"
                    :placeholder="$t('user_input_event_name')"
                    clearable
                    search 
                    :enter-button="true"/>
            </div>
        </i-col>
    </Row>
    <div style="margin-top:10px">
        <Table
            size="large"
            highlight-row ref="recordTable"
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
import api from '@/api';
import util from '@/libs/util';
export default {
    name: 'operating-record',
    data () {
        return {
            loading: false,
            keyword: '',
            orderby: '',
            sort: '',
            count: 0,
            page: 1,
            limit: 5,
            types: {},
            dates: [],
            selDate: '',
            tableOption: [
                {
                    title: this.$t('user_time'),
                    key: 'created_at',
                    align: 'center',
                    sortable: 'custom',
                    render: (h, para) => {
                        return h('span', util.timeFormatter(
                            new Date(+para.row.created_at * 1000),
                            'MM-dd hh:mm'
                        ));
                    }
                },
                {
                    title: this.$t('operator_operating_type'),
                    key: 'event',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', para.row.event);
                    }
                },
                {
                    title: 'IP',
                    key: 'ip',
                    align: 'center',
                },
                {
                    title: this.$t('project_content'),
                    key: 'message',
                    align: 'center',
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
    mounted () {
        this.getData();
    },
    filters: {
        dateFormatter (str) {
            return "20" + str.substring(0,2) + "年" + str.substring(2,4) + "月";
        }
    },
    methods: {
        setCurrentMesType (date) {
            this.selDate = date;
            this.page = 1;
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
                url: api.user.userRecord,
                type: 'post',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    user_id: this.$route.params.id,
                    keyword: this.keyword,
                    limit: this.limit,
                    page: this.page,
                    orderby: this.orderby,
                    sort: this.sort,
                    date: this.selDate,
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
                        if (res.data.date) {
                            this.selDate = res.data.date;
                        }
                        this.types = res.data.types;
                        this.dates = res.data.dates;
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
                    });
                }
            });
        },
    }
};
</script>


<template>
    <div id="notice-list">
        <Row v-for="(notice, index) in noticeList" :key="index" style="background:#eee;padding: 10px 20px">
            <i-col span="24">
                <Card :bordered="false">
                    <p slot="title" style="padding: 0 120px 0 20px;height: 100%">
                        <Icon type="md-checkbox-outline" style="position: absolute;left:15px;top: 18px"/>
                        <span style="white-space: normal">{{notice.title}}</span>
                    </p>
                    <span slot="extra">
                        <Icon type="ios-loop-strong"></Icon>
                        {{getTime(notice)}}
                    </span>
                    <p v-html="notice.content" style="padding: 0 20px;word-break:break-all"></p>
                </Card>
            </i-col>
        </Row>
        <div style="margin: 10px;overflow: hidden">
            <div style="float: right;">
                <Page
                    :total="count"
                    :current="page"
                    :page-size ="limit"
                    :page-size-opts="[5,10,15,20]"
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
        <Spin fix v-if="showLoading"></Spin>
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util.js';
export default {
    name: 'notice_index',
    data () {
        return {
            showLoading: true,
            count: 0,
            page: 1,
            limit: 5,
            orderby: '',
            sort: 'desc',
            type: '',
            noticeList: [],
        };
    },
    mounted () {
        this.getData();
    },
    methods: {
        getTime (notice) {
            return util.timeFormatter(new Date(+notice.created_at * 1000), 'yyyy-MM-dd hh:mm');
        },
        changePage (page) {
            this.page = page;
            this.getData();
        },
        changePageSize (size) {
            this.limit = size;
            this.getData();
        },
        changeKeyword () {
            this.getData();
        },
        getData (id) {
            this.showLoading = true;
            const accessToken = this.$store.state.user.userInfo.accessToken;
            let opt;
            opt = {
                access_token: accessToken,
                page: this.page,
                limit: this.limit,
                type: '2',
                show_time_limit: 1,
                sort: this.sort,
                orderby: this.orderby,
            };
            $.ajax({
                url: api.notice.list,
                method: 'POST',
                data: opt,
                success: (res) => {
                    this.showLoading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.noticeList = res.data.list;
                        this.types = res.data.types;
                        this.count = parseInt(res.data.count);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.showLoading = false;
                    });
                }
            });
        },
        changeCatrgoryFilter (typeMap) {
            // 动态调整项目类型过滤器
            let typeIndex = util.getKeyIndexFromTableOption(this.tableOption, 'type');
            let createIndex = util.getKeyIndexFromTableOption(this.tableOption, 'created_at');
            if (typeIndex < 0 || createIndex < 0) {
                return;
            }
            let type = this.tableOption[typeIndex];
            type.filters = typeMap;
            // hack 动态filter
            this.$nextTick(() => {
                if (this.type !== '') {
                    this.$set(this.$refs.messageTable.cloneColumns[typeIndex], '_filterChecked', [this.type]);
                    this.$set(this.$refs.messageTable.cloneColumns[typeIndex], '_isFiltered', true);
                }
                if (this.orderby === 'created_at') {
                    this.$set(this.$refs.messageTable.cloneColumns[createIndex], '_sortType', this.sort);
                }
            });
        },
        filterChange (filter) {
            let key = filter.key;
            this[key] = filter._filterChecked.toString();
            this.page = 1;
            this.getData();
        },
        sortChange ({ column, key, order }) {
            this.orderby = key;
            this.sort = order;
            this.getData();
        },
    },
};
</script>
<style>
#notice-list .ivu-card-body p img {
    max-width: 100%
}
</style>
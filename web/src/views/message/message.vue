
<template>
    <div>
        <div>
            <ButtonGroup>
                <Button v-for="(item,index) in dates"
                    :type="currMonth === item ? 'primary' : 'default'"
                     @click="setCurrentMesType(item)"
                     :key="index">{{item | dateFormatter}}
                </Button>
            </ButtonGroup>
        </div>
        <div style="margin-top: 10px">
            <Table
                size="large"
                ref="messageTable"
                :columns="tableOption"
                :data="currentMesList"
                :loading="loading"
                @on-filter-change = "filterChange"
                @on-sort-change = "sortChange"
                :no-data-text="noDataText">
            </Table>
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
                        transfer
                        placement = "top"
                        @on-change="changePage"
                        @on-page-size-change = "changePageSize"
                    ></Page>
                </div>
            </div>
        </div>
    </div>
</template>

<script>
import api from '@/api';
import util from '@/libs/util.js';
import noticeRow from './components/noticeRow.vue';
export default {
    name: 'message_index',
    data () {
        const markAsreadBtn = (h, params) => {
            return h('div', [
                h('Button', {
                    props: {
                        size: 'small',
                        type: 'error'
                    },
                    style: {
                        marginLeft: '5px',
                    },
                    on: {
                        click: () => {
                            this.messageDel(params.row.message.id);
                        }
                    }
                }, this.$t('message_delete'))
            ]);
        };
        return {
            loading: false,
            dates: [],
            currMonth: '',
            types: [],
            count: 0,
            page: 1,
            limit: 10,
            orderby: '',
            sort: 'desc',
            type: '',
            currentMesList: [],
            noDataText: this.$t('message_no_message'),
            mes: {
                title: '',
                time: '',
                content: ''
            },
            tableOption: [
                {
                    type: 'expand',
                    width: 50,
                    render: (h, params) => {
                        return h(noticeRow, {
                            props: {
                                row: params.row
                            }
                        });
                    }
                },
                {
                    title: this.$t('message_messages_content'),
                    key: 'title',
                    align: 'left',
                    ellipsis: true,
                    // render: (h, params) => {
                    //     return h('span', {
                    //         'class': 'tool_tip',
                    //     }, params.row.message.content.content ? params.row.message.content.content : JSON.parse(params.row.message.content));
                    // }
                    render: (h, params) => {
                        if (!params.row.message.content.action) {
                            return h('span', params.row.message.content);
                        } else {
                            let arr = params.row.message.content.content.split('%s');
                            let index = params.row.message.content.content.indexOf('%s');
                            if (params.row.message.content.action === 'task_execute') {
                                return h('span', {
                                    style: {
                                        whiteSpace: 'pre-wrap'
                                    }
                                }, [
                                    arr[0],
                                    h('router-link', {
                                        attrs: {
                                            to: {
                                                name: 'perform-task',
                                                query: params.row.message.content.params
                                            }
                                        }
                                    }, arr[1]), arr[2]
                                ]);
                            } else if (params.row.message.content.action === 'mytask_detail') {
                                return h('span', {
                                    style: {
                                        whiteSpace: 'pre-wrap'
                                    }
                                }, [
                                    arr[0],
                                    h('router-link', {
                                        attrs: {
                                            to: {
                                                name: 'my-task-detail',
                                                params: {
                                                    id: params.row.message.content.params.task_id,
                                                    tab: 'work-list',
                                                    index: (params.row.message.content.params.type === '6') ? '5' : '4'
                                                }
                                            }
                                        }
                                    }, arr[1]), arr[2]
                                ]);
                            } else {
                                return h('span', {
                                    style: {
                                        whiteSpace: 'pre-wrap'
                                    }
                                }, params.row.message.content.content);
                            }
                        }
                    }
                },
                {
                    title: this.$t('message_type'),
                    key: 'type',
                    align: 'center',
                    width: 180,
                    render: (h, params) => {
                        return h('span', {
                        }, this.types[params.row.type]);
                    },
                    filterMultiple: false,
                    filters: [],
                    filterMethod: () => true
                },
                {
                    title: this.$t('message_time'),
                    key: 'created_at',
                    align: 'center',
                    width: 180,
                    render: (h, params) => {
                        return h('span', [
                            h('Icon', {
                                props: {
                                    type: 'md-time',
                                    size: 12
                                },
                                style: {
                                    margin: '0 5px'
                                }
                            }),
                            h('span', {
                                props: {
                                    type: 'md-time',
                                    size: 12
                                }
                            }, util.timeFormatter(new Date(+params.row.created_at * 1000), 'yyyy-MM-dd hh:mm'))
                        ]);
                    },
                    sortable: 'custom',
                },
                // {
                //     title: '操作',
                //     key: 'asread',
                //     align: 'center',
                //     width: 150,
                //     render: (h, params) => {
                //         return h('div', [
                //             markAsreadBtn(h, params)
                //         ]);
                //     }
                // }
            ]
        };
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
        setCurrentMesType (date) {
            this.currMonth = date;
            this.page = 1;
            this.getData();
        },
        getData () {
            this.loading = true;
            this.$store.state.app.userInfoRequest.then(res => {
                this.requestData(res.data.user.id);
            });
        },
        requestData (id) {
            this.loading = true;
            const accessToken = this.$store.state.user.userInfo.accessToken;
            let opt;
            if (this.currMonth == '') {
                opt = {
                    access_token: accessToken,
                    page: this.page,
                    limit: this.limit,
                    type: this.type,
                    sort: this.sort,
                    orderby: this.orderby,
                    user_id: id
                };
            } else {
                opt = {
                    access_token: accessToken,
                    page: this.page,
                    limit: this.limit,
                    type: this.type,
                    sort: this.sort,
                    orderby: this.orderby,
                    date: this.currMonth,
                    user_id: id
                };
            }
            $.ajax({
                url: api.message.userMessages,
                method: 'POST',
                data: opt,
                success: (res) => {
                    this.loading = false;
                    if (res.error) {
                        this.$Message.warning({
                            content: this.$t('message_failed_get_message_data'),
                            duration: 3
                        });
                    } else {
                        this.dates = res.data.dates;
                        this.currentMesList = res.data.list;
                        if (res.data.date) {
                            this.currMonth = res.data.date;
                        }
                        this.types = res.data.types;
                        this.count = parseInt(res.data.count);
                        let typeMap = [];
                        Object.keys(res.data.types).forEach(v => {
                            let type = {
                                label: res.data.types[v],
                                value: v
                            };
                            typeMap.push(type);
                        });
                        this.changeCatrgoryFilter(typeMap);
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText, () => {
                        this.loading = false;
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
        messageRead (id) {
            $.ajax({
                url: api.message.messageRead,
                method: 'POST',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    date: this.currMonth,
                    message_id: id
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        },
        messageDel (id) {
            $.ajax({
                url: api.message.messageDel,
                method: 'POST',
                data: {
                    access_token: this.$store.state.user.userInfo.accessToken,
                    date: this.currMonth,
                    message_id: id
                },
                success: (res) => {
                    if (res.error) {
                        this.$Message.warning({
                            content: res.message,
                            duration: 3
                        });
                    } else {
                        this.getData();
                    }
                },
                error: (res, textStatus, responseText) => {
                    util.handleAjaxError(this, res, textStatus, responseText);
                }
            });
        }
    },
    components: {
        noticeRow
    },
};
</script>
<style scoped>
    .activeClass{
        color: #2d8cf0;
        background: rgb(220, 228, 241);
        outline: none !important
    }
</style>

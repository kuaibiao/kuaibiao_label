<template>
    <Table
        size="large"
        highlight-row
        ref="userTable"
        :columns="recordOption"
        :data="recordData"
        stripe
        show-header
        height="400"
        :loading="modelLoading"
        >
    </Table>
</template>
<script>
import api from "@/api";
import util from "@/libs/util";
export default {
    props: {
        recordData: {
            type: Array
        },
        types: {
            type: Object
        },
        stepTypes: {
            type: Object
        },
        modelLoading: {
            type: Boolean
        },
        workStatus: {
            type: Object
        },
    },
    data () {
        return {
            recordOption: [
                {
                    title: this.$t('admin_operator'),
                    key: 'data_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', [
                            h('Tooltip', {
                                props: {
                                    placement: 'right',
                                    transfer: true
                                },
                                'class': 'tool_tip',
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + (para.row.afterUser.id || this.$t('admin_no_data'))),
                                            h('div', this.$t('admin_email') + ': ' + (para.row.afterUser.email || this.$t('admin_no_data'))),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', {
                                    style: {
                                        wordBreak: 'break-all',
                                        whiteSpace: 'pre-wrap',
                                        wordWrap: 'break-word'
                                    }
                                }, para.row.afterUser.nickname || this.$t('admin_no_data'))
                            ]),
                        ]);
                    }
                },
                {
                    title: this.$t('operator_step'),
                    key: 'step',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('span', {
                            }, this.stepTypes[para.row.step.type]),
                        ]);
                    }
                },
                {
                    title: this.$t('admin_operation_type'),
                    key: 'type',
                    align: 'center',
                    render: (h, para) => {
                        return h('div', [
                            h('span', {
                            }, this.types[para.row.type]),
                        ]);
                    }
                },
                {
                    title: this.$t('operator_previous_operator'),
                    key: 'data_id',
                    align: 'center',
                    render: (h, para) => {
                        return h('span', [
                            h('Tooltip', {
                                props: {
                                    placement: 'right',
                                    transfer: true
                                },
                                'class': 'tool_tip',
                                scopedSlots: {
                                    content: () => {
                                        return h('span', {
                                        }, [
                                            h('div', 'ID: ' + (para.row.beforeUser.id || this.$t('admin_no_data'))),
                                            h('div', this.$t('admin_email') + ': ' + (para.row.beforeUser.email || this.$t('admin_no_data'))),
                                        ]);
                                    }
                                }
                            }, [
                                h('span', {
                                    style: {
                                        wordBreak: 'break-all',
                                        whiteSpace: 'pre-wrap',
                                        wordWrap: 'break-word'
                                    }
                                }, para.row.beforeUser.nickname || this.$t('admin_no_data'))
                            ]),
                        ]);
                    }
                },
                {
                    title: this.$t('operator_before_workstatus'),
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            this.workStatus[para.row.before_work_status]
                        );
                    }
                },
                {
                    title: this.$t('operator_after_workstatus'),
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            this.workStatus[para.row.after_work_status]
                        );
                    }
                },
                {
                    title: this.$t('admin_operate_time'),
                    align: 'center',
                    render: (h, para) => {
                        return h(
                            'span',
                            util.timeFormatter(
                                new Date(+para.row.updated_at * 1000),
                                'yyyy-MM-dd hh:mm:ss'
                            )
                        );
                    }
                },
            ],
        };
    }
};
</script>

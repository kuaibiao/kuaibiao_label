// 驳回重置理由 填写组件
<template>
    <div class="reason-wrapper" :class="resetClassName">
        <div class="reason-content">
            <h2 class="reason-header">{{$t('tool_error_task_reason_fill_tips')}}: </h2>
            <Input type="textarea"
                   :autosize="{minRows: 2, maxRows: 2}"
                   :rows="2"
                   v-model="reason"
                   :clearable="true"
                   ref="InputFiled"
                   :placeholder="$t('tool_error_task_reason_fill_placeholder')">
            </Input>
        </div>
        <div class="reason-footer">
            <Button class="reason-btn" @click="cancel">{{$t('tool_cancel')}}</Button>
            <Button class="reason-btn" :type="btnType" @click="confirm">{{$t('tool_determine')}}</Button>
        </div>
    </div>
</template>

<script>
    export default {
        name: "error-task-reason-fill",
        props: {
            type: {
                type: String,
                default: 'reject' // 默认驳回
            }
        },
        data () {
            return {
                reason: '', // 理由
            };
        },
        computed: {
            resetClassName () {
                return this.type === 'reset' ? 'reason-reset' : '';
            },
            btnType () {
                return this.type === 'reset' ? 'error' : 'warning';
            }
        },
        watch: {
            type () {
                this.$refs.InputFiled.focus();
            }
        },
        mounted () {
            this.$nextTick(() => {
                this.$refs.InputFiled.focus();
            });
        },
        methods: {
            cancel () {
                this.$emit('cancel'); // 取消
            },
            confirm () {
                if (this.reason.trim() === '') {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_reason_empty'),
                        top: 150,
                        duration: 2
                    });
                    return;
                }
                this.$emit('confirm', this.type, this.reason); // 确认 错误类型 驳回 重置 原因
            }
        }
    };
</script>

<style lang="scss">
    $rejectColor: #f90;
    $resetColor: #ed3f14;
    .reason-wrapper {
        min-width: 300px;
    }

    .reason-reset {
        .reason-content {
            border: 1px solid $resetColor;

            .reason-header {
                background-color: $resetColor;
            }
        }
    }

    .reason-content {
        border: 1px solid $rejectColor;
        border-radius: 4px;

        .reason-header {
            font-size: 14px;
            padding: 4px 15px;
            background-color: $rejectColor;
            border-radius: 4px 4px 0 0;
            color: #fff;
        }

        .ivu-input, .ivu-input:focus, .ivu-input:hover {
            border: none;
        }

        .ivu-input:focus {
            outline: 0;
            box-shadow: none;
        }
    }

    .reason-footer {
        margin-top: 5px;
        text-align: right;

        .reason-btn {
            width: 8em;
            margin: 5px;
        }
    }

</style>

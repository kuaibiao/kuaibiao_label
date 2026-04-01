<template>
    <div>
        <Modal v-model="isShow"
               closable
               @on-ok="onOk"
        >
            <div class="batch-add-header" slot="header">
                <h2>{{$t('template_batch_add_option')}}<br>
                    <span>{{$t('template_batch_add_option_tips')}}</span>
                </h2>
            </div>
            <slot name="tips"></slot>
            <div class="batch-add-content">
                <Input
                        type="textarea"
                        v-model="optionString"
                        :autosize="{minRows: 6, maxRows: 12}"
                        autofocus
                        ref="inputRef"
                >
                </Input>
            </div>
        </Modal>
    </div>
</template>

<script>
    export default {
        name: 'batch-add-option',
        props: {
            optionList: {
                type: Array,
                default: ''
            },
        },
        watch: {
            optionList () {
                if (this.optionList.length > 0) {
                    this.optionString = this.optionList.join('\n') + '\n';
                } else {
                    this.optionString = '';
                }
            }
        },
        data () {
            return {
                isShow: false,
                optionString: ''
            };
        },
        methods: {
            show () {
                this.isShow = true;
                this.$nextTick(() => {
                    let ref = this.$refs.inputRef;
                    if (ref) {
                        ref.focus();
                        ref.$el.querySelector('textarea').setAttribute('wrap', 'off');
                    }
                });
            },
            onOk () {
                let optionStr = this.optionString;
                let list = optionStr.split('\n');
                list = list.map((v) => v.trim());
                list = list.filter(v => {
                    return v.length > 0;
                });
                list = Array.from(new Set(list));
                this.$emit('update', list);
            }
        }
    };
</script>

<style scoped lang="scss">
    .batch-add-header {
        h2 {
            font-size: 14px;
            color: rgba(0, 0, 0, 0.65);
            font-weight: 600;
            span {
                font-size: 12px;
                font-weight: 400;
                color: rgba(0,0,0,0.42);
                margin-left: 8px;
            }
        }
    }
</style>

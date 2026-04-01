<template>
    <div style="position:relative;top:12px;">
        <Form :model="formData" label-position="left" inline :label-width="100">
            <FormItem :label="$t('tool_review_operator')">
                <Select v-model="formData.SelectUser" @on-change="handleChange">
                    <Option 
                        v-for="item in userListCopy" 
                        :value="item.user.id" 
                        :label="item.user.email" 
                        :key="item.user.id"
                        clearable
                    ><span>{{ item.user.email }} </span>
                    </Option>
                </Select>
            </FormItem>
            <FormItem :label="$t('tool_review_order')">
                <RadioGroup v-model="dataSort" @on-change="handleTypeChange">
                    <Radio :label="0">{{$t('tool_review_type_order')}}</Radio>
                    <Radio :label="1">{{$t('tool_review_type_random')}}</Radio>
                </RadioGroup>
            </FormItem>
        </Form>
    </div>
</template>
<script>
import EventBus from '@/common/event-bus';
export default {
    props: {
        userList: {
            type: Array,
            required: true
        }
    },
    computed: {
        userListCopy () {
            return [{
                // 默认用户 即全部
                user: {
                    id: '0',
                    email: this.$t('tool_default_all')
                }
            }].concat(this.userList);
        }
    },
    data () {
        return {
            oldUserId: '',
            formData: {
                SelectUser: '0'
            },
            dataSort: 0
            // userListCopy: [],
        };
    },
    methods: {
        handleChange (value) {
            EventBus.$emit('clear-fetchTask', { 
                type: 'workerChange',
                data: {
                    pre: this.oldUserId,
                    cur: value
                }
            });
            this.oldUserId = value;
        },
        handleTypeChange (type) {
            EventBus.$emit('clear-fetchTask', {
                type: 'orderChange',
                data: {
                    cur: type
                }
            });
        }
    }
};
</script>


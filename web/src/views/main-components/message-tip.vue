<template>
    <div @click="showMessage" class="message-con">
        <Tooltip :transfer="true" :content="value > 0 ? $t('message_unread_messages',{num: value}) : $t('message_no_unreadMessage')" placement="bottom">
            <Badge :count="value" dot>
                <Icon type="ios-notifications-outline" :size="23"></Icon>
            </Badge>
        </Tooltip>
    </div>
</template>

<script>
import util from '@/libs/util.js';
import Cookies from 'js-cookie';
import api from '@/api';
export default {
    name: 'messageTip',
    props: {
        value: {
            type: [Number, String],
            default: 0
        }
    },
    methods: {
        showMessage () {
            this.$store.commit('setMessageCount', 0);
            this.$router.push({
                name: 'message_index'
            });
        }
    }
};
</script>

<template>
<div>
    <div class="notice-containter" v-if="noticeTitle">
        <Icon
        type="md-alert"
        color="#ff9900"
        style="font-size: 15px;padding: 0 8px;"
        />
        <div class="notice-board" ref="noticeBoard">
            <div class="notice-show" ref="noticeShow">
                <span @click="toNoticeList" ref="noticeShowA">{{noticeTitle}}</span>
            </div>
        </div>
    </div>
</div>
    
</template>
<script>
import api from '@/api';
export default {
    name: 'notice-board',
    data () {
        return {
            notice: {},
            noticeTitle: '',
        };
    },
    mounted () {
        this.getMessageCount();
    },
    methods: {
        getMessageCount () {
            const userInfo = this.$store.state.user.userInfo;
            console.log('notice-board.js.getMessageCount.userInfo: ')
            console.log(userInfo)

            if (!userInfo)
            {
                console.log('notice-board.js.getMessageCount no userInfo')
                return false;
            }

            $.ajax({
                url: api.user.stat,
                method: 'POST',
                data: {
                    access_token: userInfo.accessToken
                },
                success: res => {
                    if (!res.error) {
                        this.$store.commit('setMessageCount', res.data.new_message_count);
                        this.noticeTitle = res.data.notice.title;
                    }
                }
            });
        },
        toNoticeList () {
            this.$router.push({
                name: 'notice_index'
            });
        }
    }
};
</script>
<style>
.notice-containter {
  position: absolute;
  top: 50%;
  -moz-transform: translateY(-50%);
  -webkit-transform: translateY(-50%);
  -ms-transform: translateY(-50%);
  -o-transform: translateY(-50%);
  transform: translateY(-50%);
  margin: auto;
    margin-left: 220px;
  background-color: #fffcef;
  width: 98%;
  display: flex;
  align-items: center;
}
.notice-containter>span{
  background-color: #fff;
}
.notice-board {
  display: inline-block;
  vertical-align: middle;
  background: #fffcef;
  font-size: 0;
  width: 100%;
  height: 28px;
  overflow: hidden;
  white-space: nowrap;
  /* 相对定位 */
  position: relative;
}
.notice-show {
  position: absolute;
  top: 0;
  left: 0;
  width: 100%;
  height: 100%;
  line-height: 26px
}
.notice-show span {
  color: #4a4a4a;
  display: inline-block;
  font-size: 14px;
  cursor: pointer;
  width: 90%;
  overflow: hidden;
  white-space: nowrap;
  text-overflow: ellipsis;
}
</style>
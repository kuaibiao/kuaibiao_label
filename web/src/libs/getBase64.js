import api from '@/api';
import util from "@/libs/util";
function getBatch (vm, token, count, totle, key, mime, videoUrl, obj, requestTimes) {
    $.ajax({
        url: api.site.fetchFile,
        type: 'post',
        data: {
            access_token: token,
            batch: count,
            key: key,
            size: 1024 * 1024
        },
        success: (res) => {
            if (res.error) {
                //
                vm.$Message.destroy();
                vm.$Message.warning({
                    content: res.message,
                    duration: 2
                });
            } else {
                vm.$store.commit('changeGetBase64Process', count / totle * 100);
                let url = videoUrl + res;
                count++;
                if (count <= totle) {
                    getBatch(vm, token, count, totle, key, mime, url, obj, 0);
                } else {
                    vm.$store.commit('changeGetBase64Process', 100);
                    obj.resolve('data:' + mime + ';base64,' + url);
                }
            }
        },
        error: (res, textStatus, responseText) => {
            // 错误处理
            let message = '';
            switch (textStatus) {
                case 'timeout' : {
                    message = vm.$t('tool_request_timeout');
                    let times = requestTimes + 1;
                    if (times < 3) {
                        getBatch(vm, token, count, totle, key, mime, videoUrl, obj, times);
                    }
                    break;
                }
                default: {
                    message = (res.responseJSON && res.responseJSON.message) || responseText;
                }
            }
            message = message || vm.$t('tool_request_error');
            vm.$Message.destroy();
            vm.$Message.error({
                content: message,
                duration: 1
            });
        }
    });
}
function getBase64 (vm, voice_url) {
    vm.$store.commit('changeGetBase64Process', 0);
    if (Object.prototype.toString.call(voice_url) === '[object Object]') {
        let videoUrl = '';
        let count = 1;
        let obj = $.Deferred();
        let totle = Math.ceil((voice_url.size * 1) / (1024 * 1024));
        getBatch(vm, vm.$store.state.user.userInfo.accessToken, 1, totle, voice_url.key, voice_url.mime, videoUrl, obj, 0);

        return obj;
    } else {
        let obj = $.Deferred();
        obj.resolve(voice_url);
        return obj;
    }
}
export default getBase64;

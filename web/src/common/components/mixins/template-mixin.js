import { mapState } from 'vuex';
const mixin = {
    computed :{
        ...mapState({
            userId: state => state.user.userInfo.id,
        })
    },
    methods: {
        handleDelete () {
            this.$store.commit('deleteTemplateModule', this.path);
            this.$nextTick(() => {
                $('.selected-module').removeClass('selected-module');
                let currentId = this.$store.state.template.currentModuleData.data.id;
                currentId && $('[data-id=' + this.$store.state.template.currentModuleData.data.id + ']').addClass('selected-module');
            });
        },
        getCurrentTimeStampSec () {
            return Math.floor(Date.now() / 1000);
        },
        updateWorkerInfo (info) {
            this.$set(this.dConfig, 'cBy', info.cBy);
            this.$set(this.dConfig, 'cTime', info.cTime);
            this.$set(this.dConfig, 'mBy', info.mBy);
            this.$set(this.dConfig, 'mTime', info.mTime);
        }
    },
};
export default mixin;

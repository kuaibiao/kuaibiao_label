import Vue from 'vue';

const mixin = {
    methods: {
        handleDelete () {
            this.$store.commit('deleteTemplateModule', this.path);
            Vue.nextTick(() => {
                $('.selected-module').removeClass('selected-module');
                let currentId = this.$store.state.template.currentModuleData.data.id;
                currentId && $('[data-id=' + this.$store.state.template.currentModuleData.data.id + ']').addClass('selected-module');
            });
        },
    },
};
export default mixin;

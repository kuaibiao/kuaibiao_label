import Vue from 'vue';
import Vuex from 'vuex';

import app from './modules/app';
import user from './modules/user';
import template from './modules/template';

Vue.use(Vuex);

const store = new Vuex.Store({
    state: {
        //
        list_reload: false,
    },
    mutations: {
        //
    },
    actions: {

    },
    modules: {
        app,
        user,
        template,
    }
});

export default store;

import Cookies from 'js-cookie';
import app from './app';

const user = {
    state: {
        userInfo: {
            accessToken: Cookies.get('labelToolAccessToken') || '',
            userName: '',
        },
    },
    mutations: {
        //
        'login': (state, data) => {
            state.userInfo = data;
        },
        'logout': (state) => {
            let domain = location.hostname.split('.');
            domain = [domain[domain.length - 2], domain[domain.length - 1]].join('.');
            Cookies.remove('labelToolAccessToken', { domain: domain });
            Cookies.remove('access'); // todo
            Cookies.remove('labelToolMenuShrink'); // todo
            state.userInfo = {};
            app.state.settings = {};
        },
    },
    actions: {

    }
};

export default user;

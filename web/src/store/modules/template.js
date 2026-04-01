import cloneDeep from 'lodash.clonedeep';
const template = {
    state: {
        editModuleList: [],
        currentModuleData: {
            data: {},
            path: ''
        },
        placeholderCounter: {
            image: 0,
            audio: 0,
            video: 0,
            text: 0,
        },
        usedKeyMap: {},
    },
    mutations: {
        clearUserKeyMap (state) {
            state.usedKeyMap = {};
        },
        updateUsedKeyMap (state, keyInfo) {
            keyInfo.oldKeyInfo.forEach((key) => {
                delete state.usedKeyMap[key];
            });
            keyInfo.newKeyInfo.forEach((key) => {
                state.usedKeyMap[key] = key;
            });
        },
        updatePlaceHolderCounter (state, action) {
            let type = action.type;
            switch (type) {
                case 'image':
                case 'audio':
                case 'video':
                case 'text' : {
                    if (action.add) {
                        state.placeholderCounter[type]++;
                    } else {
                        state.placeholderCounter[type]--;
                    }
                    break;
                }
            }
        },
        setCurrentModule (state, value) {
            state.currentModuleData = value;
        },
        saveModule (state, info) {
            let path = info.path;
            let moduleData = info.moduleData;
            let patharr = path.split(',');
            let data = state.editModuleList;
            let i, l;
            for (i = 0, l = patharr.length - 2; i < l; i++) {
                data = data[patharr[i]];
            }
            data.splice(patharr[i], 1, moduleData);
        },
        deleteTemplateModule (state, path) {
            let patharr = path.split(',');
            let data = state.editModuleList;
            let i, l;
            for (i = 0, l = patharr.length - 2; i < l; i++) {
                data = data[patharr[i]];
            }
            data.splice(patharr[i], 1);
            if (~state.currentModuleData.path.indexOf(path)) { // 判断删除的组件是不是正在编辑以及是否包含正在编辑的
                state.currentModuleData = {
                    data: {},
                    path: ''
                };
            }
        },
        chooseModule (state, path) {
            let pathArr = path.split(',');
            let data = cloneDeep(state.editModuleList); // 深度copy 避免对象污染
            for (let i = 0, l = pathArr.length - 1; i < l; i++) {
                data = data[pathArr[i]];
            }
            state.currentModuleData = {
                data,
                path
            };
        },
        updateList (state, value) {
            state.editModuleList = value;
        }
    }
};

export default template;

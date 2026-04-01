import encodeKeyCode from '../../encodeKeyCode';

const mixin = {
    methods: {
        clearKeyCode (itemList) {
            let oldKeyInfo = [];
            let newKeyInfo = [];
            itemList.forEach((item) => {
                let oldKeyBoard = item.keyBoard;
                if (oldKeyBoard) {
                    // ctrl alt shift meta 0000 + keyCode
                    let key = encodeKeyCode(oldKeyBoard);
                    oldKeyInfo.push(key);
                }
            });
            this.$store.commit('updateUsedKeyMap', {
                oldKeyInfo,
                newKeyInfo,
            });
        },
        setKeyCode (index, e) {
            let forbiddenKeyCode = [16, 17, 18, 91, 93, 68, 13]; // shift ctrl atl meta D  enter等
            e.preventDefault();
            if (~forbiddenKeyCode.indexOf(e.keyCode)) {
                // this.$Message.destroy();
                // this.$Message.warning({
                //     content: '快捷键已使用',
                //     duration: 1,
                // });
                return;
            }
            let item = this.listData[index];
            let oldKeyBoard = item.keyBoard;
            let newKeyBoard = {
                keyCode: e.keyCode,
                altKey: e.altKey,
                ctrlKey: e.ctrlKey,
                shiftKey: e.shiftKey,
                metaKey: e.metaKey,
            };
            let oldKeyInfo = [];
            let newKeyInfo = [];
            if (oldKeyBoard) {
                // ctrl alt shift meta 0000 + keyCode
                let key = encodeKeyCode(oldKeyBoard);
                oldKeyInfo.push(key);
            }
            if (newKeyBoard) {
                let key = encodeKeyCode(newKeyBoard);
                newKeyInfo.push(key);
                // 检测是否已使用，只限于在模板设置部分
                if (this.$store.state.template.usedKeyMap[key]) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('template_shortcut_used'),
                        duration: 1,
                    });
                    return;
                }
            }
            this.$store.commit('updateUsedKeyMap', {
                oldKeyInfo,
                newKeyInfo,
            });
            item.keyBoard = newKeyBoard;
            this.listData.splice(index, item);
            this.saveChange();
        },
        handleItemDel (index) {
            let item = this.listData[index];
            let oldKeyBoard = item.keyBoard;
            let oldKeyInfo = [];
            let newKeyInfo = [];
            if (oldKeyBoard) {
                // ctrl alt shift meta 0000 + keyCode
                let key = encodeKeyCode(oldKeyBoard);
                oldKeyInfo.push(key);
                this.$store.commit('updateUsedKeyMap', {
                    oldKeyInfo,
                    newKeyInfo,
                });
            }
            this.listData.splice(index, 1);
            this.saveChange();
        },
        handleSortEnd () {
            this.saveChange();
        },
    },
};
export default mixin;

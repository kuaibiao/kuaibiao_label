const imageLabelMixin = {
    methods: {
        checkRequiredTagGroup: function (data) {
            if (this.requiredTagGroup.length < 1) {
                return true;
            }
            let returnValue = data.every((item) => {
                if (item.type === 'bonepoint') {
                    return true;
                }
                return this.requiredTagGroup.every((requiredLabel) => {
                    if (typeof requiredLabel === 'string') { // 标签组 分类 字符串
                        return item.category.some(category => {
                            return category === requiredLabel;
                        });
                    } else { // 非标签组 包含所有标签的数组 item的标签只要有一个包含在内就满足
                        return requiredLabel.some((l) => {
                            return item.category.some(category => {
                                return category === l;
                            });
                        });
                    }
                });
            });
            if (!returnValue) {
                this.$Message.destroy();
                this.$Message.warning({
                    content: this.$t('tool_asterisk_tag_group'),
                    duration: 2,
                });
            }
            return returnValue;
        },
        checkRequiredTag: function (data) {
            let selLabels = [];
            data.forEach((item) => {
                if (item.type !== 'bonepoint') {
                    let labels = item.label.map((t, i) => {
                        return {
                            label: t,
                            category: item.category[i]
                        };
                    });
                    selLabels = selLabels.concat(labels);
                }
            });
            let returnValue = true;
            for (let i = 0; i < selLabels.length; i++) {
                let item = selLabels[i];
                let labelArr = this.requiredTagForSingleTag[item.category]; // 该标签所在标签组的 所有必选标签
                let flag = false;
                labelArr && labelArr.forEach((v) => {
                    if (v.isRequired && v.text !== item.label) { // 某必需标签是否已选择
                        flag = !selLabels.some((sItem) => {
                            return sItem.category === v.category && v.text === sItem.label;
                        });
                    }
                });
                if (flag) {
                    this.$Message.destroy();
                    this.$Message.warning({
                        content: this.$t('tool_tags_not_selected'),
                        duration: 2,
                    });
                    returnValue = false;
                    break;
                }
            }
            return returnValue;
        },
    }
};

export default imageLabelMixin;

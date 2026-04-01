// 作业执行 审核 质检 mixin 用于公共方法
import EventBus from "@/common/event-bus";
const commonMixin = {
    computed: {
        tasksInfo () {
            return {
                ...this.taskInfo,
                stepName: this.stepInfo && this.stepInfo.name
            };
        }
    },
    data () {
        return {
            requiredTagForSingleTag: {},
            requiredTagGroup: []
        };
    },
    mounted () {
        EventBus.$on("requiredTagForSingle", this.saveRequiredTagForSingleTag); // 格式化的标签数据
        EventBus.$on("requiredTagGroup", this.saveRequiredTagGroup); // 必选一个标签的标签组
    },
    destroyed () {
        EventBus.$off("requiredTagForSingle", this.saveRequiredTagForSingleTag); // 格式化的标签数据
        EventBus.$off("requiredTagGroup", this.saveRequiredTagGroup); // 必选一个标签的标签组
    },
    methods: {
        prepareResult (taskItem) {
            let result = {};
            // if (taskItem.workResult.result) {
            //     result = JSON.parse(taskItem.workResult.result);
            // } else if (
            //     this.taskInfo.is_load_result !== "0" &&
            //     taskItem.dataResult.ai_result
            // ) {
            //     result = taskItem.dataResult.ai_result;
            // }
            if (taskItem.workResult.result) {
                result = taskItem.workResult.result;
            }
            return result;
        },
        saveRequiredTagGroup: function (requiredTagGroup, type) {
            if (type === "single") {
                this.requiredTagGroup.push(requiredTagGroup);
            } else {
                this.requiredTagGroup.push(...requiredTagGroup);
            }
        },
        saveRequiredTagForSingleTag: function (requiredTagForSingleTag) {
            if (requiredTagForSingleTag.subType === "single") {
                let requiredLabels = [];
                Object.keys(requiredTagForSingleTag).forEach(key => {
                    if (key !== "subType") {
                        requiredLabels.push(...requiredTagForSingleTag[key]);
                        this.requiredTagForSingleTag[key] = requiredLabels;
                    }
                });
            } else {
                this.requiredTagForSingleTag = {
                    ...this.requiredTagForSingleTag,
                    ...requiredTagForSingleTag
                };
                delete this.requiredTagForSingleTag.subType;
            }
        }
    }
};
export default commonMixin;

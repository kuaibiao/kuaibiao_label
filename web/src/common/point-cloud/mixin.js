export default {
    methods: {
        // 处理编辑后的修改人信息;
        getWorkerInfo (target) {
            let now = Date.now();
            let step = this.taskInfo?this.taskInfo.step_id: '';
            let cBy = target.cBy || this.gmixin_userInfo.id;
            let cTime = target.cTime || now;
            let cStep = target.cStep || step;
            let mBy =  this.gmixin_userInfo.id;
            let mTime = now;
            let mStep = step;
             // 同一个任务下：对已标注的结果，自己改自己做的都算编辑，改别人做的结果才算修改
            // 不同的任务下，就认为是两个人了，因此在审核工序改自己在执行工序的结果，也算修改
            // 第一次标 只有 c类字段， 第一次修改后 才有m类字段， 第二次修改 之前的c类 要换成二次修改前m类 然后再更新 m 类
            let ownerStep = target.mBy ? target.mStep : target.cStep;
            let ownerBy = target.mBy || target.cBy;
            if (ownerStep === step) { // 相同工序
                if (ownerBy === mBy) { // 自己改自己
                    mBy = target.mBy; // 取原值
                    mTime = mBy ? mTime : target.mTime;
                    mStep = target.mStep;
                } else {
                    cBy = target.mBy || target.cBy; // 
                    cTime = target.mTime || target.cTime;
                    cStep = target.mStep || target.cStep;
                }
            } else { // 不同工序 被修改过
                if (ownerBy !== mBy &&  target.mBy) {
                    cBy = target.mBy;
                    cTime = target.mTime;
                    cStep = target.mStep;
                }
            }
            return {
                cBy,
                cTime,
                cStep,
                mBy,
                mTime,
                mStep
            }
        },
    }
}
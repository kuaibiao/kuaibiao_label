// 可细化到不同种类的任务类型的统计结果
<template>
    <div class="task-stat">
        <span>{{$t('tool_jmvt')}}:</span>
        <span >{{$t('tool_number_job')}}: <span class="task-stat-item">{{userStat.work_count || 0}}</span></span>
        <span v-if="+userStat.label_count">{{$t('tool_mark_number')}}: <span class="task-stat-item">{{userStat.label_count || 0}}</span></span>
        <span v-if="+userStat.point_count">{{$t('tool_point')}}: <span class="task-stat-item">{{ userStat.point_count || 0 }}</span></span>
    </div>
</template>
<script>
export default {
    name: 'user-stat',
    props: {
        userStat: {
            type: Object,
            required: true
        }
    }
};
</script>
<style lang="scss" scoped>
    .task-stat-item {
        color: #2d8cf0;
        font-size: 1.25em;
        padding: 4px;
    }
</style>



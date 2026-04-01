<template>
    <div class="task-info-wrapper">
        <h4 class="task-info-header">{{$t('tool_job_information')}}:</h4>
        <div class="task-items">
            <span class="task-ids-item" v-if="taskInfo.stepName">{{$t('admin_process')}}: {{taskInfo.stepName}}</span>

            <span class="task-ids-item" v-if="taskInfo.dataId" >{{$t('tool_job_id')}}: {{ taskInfo.dataId }}</span>
            <span class="task-ids-item">{{$t('tool_item_id')}}: {{ taskInfo.project_id }}</span>
            <span class="task-ids-item">{{$t('tool_task_id')}}: {{ taskInfo.id }}</span>
            <span class="task-ids-item" v-if="taskInfo.user && taskInfo.user.email ">{{$t('tool_operator')}}: {{ taskInfo.user.email }}</span>
        </div>
        <div class="task-items">
            {{$t('tool_task_name')}} ： {{ taskInfo.name }}
        </div>
        <div class="task-items" v-if="taskInfo.dataName">
            {{$t('tool_job_file_name')}} ：<ellipsis-text :text="taskInfo.dataName">{{taskInfo.dataName}}</ellipsis-text>
             <!--{{ taskInfo.dataName }}-->
        </div>
    </div>
</template>
<script>
export default {
    name: 'task-info',
    props: {
        taskInfo: {
            type: Object,
            required: true,
        }
    }
};
</script>
<style lang="scss" scoped>
.task-info-wrapper {
    border-radius: 8px 8px 0 0;
    text-align: left;
    .task-info-header {
        border-radius: 8px 8px 0 0;
        background: #ccc;
        padding: 4px 5px;
    }
    .task-items {
        margin: 5px 8px;
    }
    .task-ids-item {
        display: block;
    }
}

</style>



<template>
    <div>
    <span>
        <p><Button type="primary" size="small" style="margin: 3px" @click="settingUser">{{$t('admin_settings')}} ({{user_count}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refused_revise * 1 > 0" @click="toPage('5')">{{$t('admin_rejected_work')}} ({{refused_revise}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="difficult_revise * 1 > 0" @click="toPage('7')">{{$t('admin_difficult_work')}} ({{difficult_revise}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refuse_revised * 1 > 0" @click="toPage('6')">{{$t('admin_rework_work')}} ({{refuse_revised}})</Button></p>

    </span>
    </div>
</template>

<script>
    import api from '@/api';

    export default {
        name: 'task-management-op',
        props: {
            project_id: {
                type: String,
                required: true
            },
            task_id: {
                type: String,
                required: true
            },
            user_count: {
                type: String,
                required: true
            },
            refused_revise: {
                type: String,
                required: true
            },
            difficult_revise: {
                type: String,
                required: true
            },
            refuse_revised: {
                type: String,
                required: true
            }
        },
        data () {
            return {};
        },
        methods: {
            settingUser () {
                this.$router.push({
                    name: 'set-task-user',
                    params: {
                        task_id: this.task_id
                    }
                });
            },
            toPage (index) {
                this.$router.push({
                    name: 'task-management-detail',
                    params: {
                        id: this.task_id,
                        tab: 'work-list',
                        index: index
                    }
                });
            },
        }
    };
</script>

<style scoped>
</style>

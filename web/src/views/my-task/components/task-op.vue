<template>
  <div>
    <span v-if="(file_type === '2') && (step_type === '1')">
        <p><Button type="primary" size="small" style="margin: 3px" @click="execute">{{$t('operator_audit')}}</Button></p>
        <!-- <p><Button type="primary" size="small" style="margin: 3px" @click="textAuditBatch">{{$t('operator_audit_accuracy')}}</Button></p> -->
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refuse_revised * 1 > 0" @click="toPage('6')">{{$t('operator_rework')}} ({{refuse_revised}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refused_revise * 1 > 0" @click="toPage('5')">{{$t('operator_rejected')}} ({{refused_revise}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="difficult_revise * 1 > 0" @click="toPage('7')">{{$t('operator_difficult')}} ({{difficult_revise}})</Button></p>
    </span>
    <span v-else>
        <p><Button type="primary" size="small" style="margin: 3px" v-if="step_type == '0'" @click="execute">{{$t('operator_execute')}}</Button></p>
        <p><Button type="primary" size="small" style="margin: 3px" v-if="step_type == '1'" @click="execute">{{$t('operator_audit')}}</Button></p>
        <p><Button type="primary" size="small" style="margin: 3px" v-if="step_type == '2'" @click="execute">{{$t('operator_qc')}}</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refused_revise * 1 > 0" @click="toPage('5')">{{$t('operator_rejected')}} ({{refused_revise}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="difficult_revise * 1 > 0" @click="toPage('7')">{{$t('operator_difficult')}} ({{difficult_revise}})</Button></p>
        <p><Button type="warning" size="small" style="margin: 3px" v-if="refuse_revised * 1 > 0" @click="toPage('6')">{{$t('operator_rework')}} ({{refuse_revised}})</Button></p>
    </span>
  </div>
</template>

<script>
import api from '@/api';

export default {
    name: 'task-op',
    props: {
        project_id: {
            type: String,
            required: true
        },
        task_id: {
            type: String,
            required: true
        },
        step_type: {
            type: String,
            required: true
        },
        file_type: {
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
        return {

        };
    },
    methods: {
        textAuditBatch () {
            this.$router.push({
                name: 'perform-batch-audit',
                query: {
                    project_id: this.project_id,
                    task_id: this.task_id
                }
            });
        },
        execute () {
            this.$router.push({
                name: 'perform-task',
                query: {
                    project_id: this.project_id,
                    task_id: this.task_id
                }
            });
        },
        toPage (index) {
            this.$router.push({
                name: 'my-task-detail',
                params: {
                    id: this.task_id,
                    tab: 'work-list',
                    index: index
                },
            });
        },
    }
};
</script>

<style scoped>
</style>

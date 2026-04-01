<template>
    <ul v-if="judge">
        <li v-for="(item, index) in tasks" :key="index">
            <table>
                <tbody>
                    <td style="font-weight:bold;">{{item.batch.sort_name}}  -  {{item.step.type_name}}</td>
                    <td>{{item.stat.work_count}}  /   {{item.batch.amount}}</td>
                </tbody>
            </table>
        </li>
    </ul>
</template>

<script>
export default {
    name: 'step-op',
    props: {
        tasks: {
            type: Array,
            required: true
        }
    },
    data () {
        return {};
    },
    methods: {
        judge () {
            for (var i in this.steps) {
                return true;
            }
            return false;
        }
    }
};
</script>
<style scoped>
ul li {
  list-style: none;
}
table{
    width:100%
}
 table td{
     border:none;
     text-align: center;
     padding: 5px 0 !important;
 }
</style>

<template>
<div class="text-annotation-list">
    <result-item-annotation
        v-for="( item, index) in formatResultList" :key="index"
        :data="item.result.data || []"
        :info="item.result.info || []"
        :index="index"
        :user="workerList[index].user"
        :baseData="baseItem"
    />
</div>
</template>
<script>
    import sortBy from 'lodash.sortby';
    import cloneDeep from 'lodash.clonedeep';
    import resultItemAnnotation from '@/views/task-perform/components/text-annotation-result.vue';
    export default {
        name: "text-annotation-result-list",
        props: {
            resultList: {
                type: Array,
                require: true,
            },
            workerList: {
                type: Array,
                require: true,
            }
        },
        computed: {
            formatResultList () {
                let parseResult = this.resultList.map((item) => {
                    let cp = cloneDeep(item);
                    cp.result = typeof cp.result === 'string' ? JSON.parse(cp.result) : cp.result;
                    return cp;
                });
                parseResult.map(item => {
                    item.result.data = sortBy(item.result.data, (t) => {
                        return +t.start;
                    });
                });
                return parseResult;
            },
            baseItem () {
                let cp = {};
                cp.result = typeof this.resultList[0].result === 'string' ? JSON.parse(this.resultList[0].result) : this.resultList[0].result;
                cp.result.data = sortBy(cp.result.data, (t) => {
                    return +t.start;
                });
                return cp;
            }
        },
        data () {
            return {

            };
        },
        mounted () {
        },
        methods: {
        },
        components: {
            resultItemAnnotation,
        }
    };
</script>

<style scoped>

</style>

<template>
<div>
    <div v-if="row" class="pro-main borderRadius">
        <p :style="{width: getProcess2(row) + '%', height: '10px', background: 'skyblue', position: 'absolute', left: 0, zIndex: 2, display: 'inline-block'}" class="borderRadius"></p>
        <p :style="{width: getProcess1(row) + '%', height: '10px', background: getColor(), position: 'absolute', left: 0, zIndex: 3, display: 'inline-block'}" class="borderRadius"></p>
    </div>
    <span style="margin-left: 2px;font-size: 12px;">{{getProcess1(row)}}%</span>
</div>

</template>
<script>
export default {
    props: {
        row: {
            type: Object
        }
    },
    data () {
        return {

        };
    },
    methods: {
        getProcess1 (row) {
            let work_count = row.stat !== '' ? row.stat.work_count : 0;
            let batchAmount = row.batch.amount * 1;
            if (!batchAmount || (batchAmount === 0)) {
                return 0;
            }
            return +(work_count / batchAmount * 100).toFixed(2);
        },
        getProcess2 (row) {
            let taskAmount = row.stat.amount * 1;
            let batchAmount = row.batch.amount * 1;
            return +(taskAmount / batchAmount * 100).toFixed(2);
        },
        getColor () {
            if ((this.getProcess1(this.row) === 100) && (this.getProcess2(this.row) === 100)) {
                return '#52C41A';
            }
            if ((this.getProcess2(this.row) !== 100) && (this.getProcess2(this.row) === this.getProcess1(this.row))) {
                return '#FFBF00';
            } else {
                return '#1890FF';
            }
        }
    }
};
</script>
<style scoped>
.pro-main {
    position: relative;
    background: #eeeeee;
    width: 65%;
    height: 10px;
    display: inline-block;
}
.borderRadius {
    border-radius: 6px;
}
</style>



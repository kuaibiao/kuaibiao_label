<template>
<div style="display: flex;
    justify-content: flex-start;
    flex-wrap: wrap;
    align-items: center">
    <div v-if="row" class="pro-main borderRadius">
        <p :style="{
            width: getProcess1(row) + '%', 
            height: '10px', 
            background: '#52C41A', 
            position: 'absolute', 
            left: 0, 
            zIndex: 3, 
            display: 'inline-block'
            }" class="borderRadius">
        </p>
        <p :style="{
            width: getProcess2(row) + '%', 
            height: '10px', 
            background: 'red', 
            position: 'absolute', 
            left: 0, 
            zIndex: 2, 
            display: 'inline-block'
        }" class="borderRadius"></p>
    </div>
    <span style="margin-left: 2px;font-size: 12px;">{{getProcess2(row)}}%</span>
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
            if (!row.pack_item_total || !row.pack_item_succ) {
                return 0;
            }
            if (!row.pack_item_total || (row.pack_item_total == '0')) {
                return 0;
            }
            let taskAmount = row.pack_item_succ * 1;
            let batchAmount = row.pack_item_total * 1;
            return +(taskAmount * 1 / batchAmount * 100).toFixed(2);
        },
        getProcess2 (row) {
            if (!row.pack_item_total || !row.pack_item_succ || !row.pack_item_fail) {
                return 0;
            }
            if (!row.pack_item_total || (row.pack_item_total == '0')) {
                return 0;
            }
            let taskAmount = row.pack_item_succ * 1 + row.pack_item_fail * 1;
            let batchAmount = row.pack_item_total * 1;
            return +(taskAmount * 1 / batchAmount * 100).toFixed(2);
        },
        // getColor () {
        //     if ((this.getProcess1(this.row) === 100) && (this.getProcess2(this.row) === 100)) {
        //         return '#52C41A';
        //     };
        //     if ((this.getProcess2(this.row) !== 100) && (this.getProcess2(this.row) === this.getProcess1(this.row))) {
        //         return '#FFBF00';
        //     } else {
        //         return '#1890FF';
        //     }
        // }
    }
};
</script>
<style scoped>
.pro-main {
    position: relative;
    background: #eeeeee;
    width: 60%;
    height: 10px;
    display: inline-block;
}
.borderRadius {
    border-top-left-radius: 6px;
    border-top-right-radius: 6px;
    border-bottom-left-radius: 6px;
    border-bottom-right-radius: 6px;
}
</style>



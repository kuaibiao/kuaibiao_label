<template>
    <div class="segment-attr-wrapper">
        <div class="segment-attr-item" v-for="item in data.filter(t => t.value.length > 0)" :key="item.id">
            <span class="attr-item-header">{{item.header}} :</span>
            <span class="attr-item-value">{{item.value.toString()}}</span>
        </div>
    </div>
</template>

<script>
    export default {
        name: "segment-attr",
        props: {
            data: {
                type: Array,
                default: []
            }
        }
    };
</script>

<style scoped lang="scss">
    .segment-attr-wrapper {
        display: flex;
        flex-wrap: wrap;
        .segment-attr-item {
            padding-right: 15px;
        }
        .attr-item-header {
            color: #333;
        }
        .attr-item-value {
            color: #000;
        }
    }

</style>

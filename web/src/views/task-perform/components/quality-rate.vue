<template>
    <div class="quality-info">
        <span class> {{$t('tool_qc')}} <span class="item-value"> {{qualityRate.audit_rate || 0}} </span></span>
        <span class> {{$t('tool_percent_pass')}} <span class="item-value"> {{qualityRate.pass_rate || 0}} </span></span>
    </div>
</template>
<script>
export default {
    name: 'quality-rate',
    props: {
        qualityRate: {
            type: Object,
            required: true,
        }
    }
};
</script>
<style lang="scss" scoped>
    .quality-info {
        margin-left: 20px;
        font-size: 12px;
        .item-value {
            padding: 0 5px;
            color: #57a3f3;
            font-size: 1.2em;
        }
    }
</style>

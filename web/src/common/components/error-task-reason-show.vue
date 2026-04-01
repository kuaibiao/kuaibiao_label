<template>
    <div class="reason-show-wrapper">
        <pre>{{reason}}</pre>
    </div>
</template>

<script>
    export default {
        name: "error-task-reason-show",
        props: {
            reason: {
                type: String,
                required: true
            }
        }
    };
</script>

<style scoped lang="scss">
    .reason-show-wrapper {
        max-height: 553px;
        max-width: 320px;
        overflow: auto;
        padding: 4px 6px;

        pre {
            margin: 0;
            white-space: pre-wrap;
        }
    }
</style>

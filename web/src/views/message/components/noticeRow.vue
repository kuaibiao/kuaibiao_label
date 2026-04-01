<template>
  <div style="background:#eee;" class="expandRow">
    <Row class="expand-row">{{row.message.content.content}}</Row>
  </div>
</template>
<script>
export default {
    props: {
        row: Object
    },
};
</script>
<style scoped>
.expandRow {
    white-space: normal;
    word-break:break-word;
    word-wrap:break-word;
    background-color: #f8f8f9 !important;
    padding: 0px 18px !important;
}
</style>

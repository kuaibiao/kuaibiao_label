<template>
    <div class="ellipsis-text-wrapper" :style="{width:'90%',maxWidth: width + 'px'}">
        <Tooltip :content="text" :style="{width:'90%'}" :transfer="true" placement="bottom-start">
            <span class="ellipsis-text" :style="{maxWidth: width + 'px',minWidth: '100px'}">
                {{text}}
            </span>
        </Tooltip>
    </div>
</template>
<script>
    export default {
        name: 'ellipsis-text',
        props: {
            text: {
                type: String,
                default: '',
            },
            width: {
                type: Number,
                default: 150,
            }
        }
    };
</script>

<style >
    .ellipsis-text {
        display: inline-block;
        overflow: hidden;
        white-space: nowrap;
        text-overflow: ellipsis;
        vertical-align: middle;
    }
    .ellipsis-text-wrapper {
        display: inline-block;
    }
</style>

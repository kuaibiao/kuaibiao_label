<style lang="less">
    @import './styles/infor-card.less';
</style>

<template>
    <Card :padding="0" class="home-card">
        <Row class="infor-card-con" :gutter="20">
            <!-- <i-col class="infor-card-icon-con" span="6">
                <Row class="height-100" type="flex" align="middle" justify="center">
                    <Icon :custom="iconType" :size="iconSize" class="card-icon"></Icon>
                </Row>
            </i-col> -->
            <i-col span="24" class="height-100">
                <Row type="flex" align="middle" justify="center" class="height-100" style="padding-bottom：15px">
                    <count-up 
                        class="infor-card-count user-created-count" 
                        :id-name="idName" 
                        :end-val="endVal" 
                        :color="color"
                        :countSize="countSize"
                        :countWeight="countWeight">
                        <p class="infor-intro-text" slot="intro"><span class="infor-intro-icon"></span>{{ introText }}</p>
                    </count-up>
                    <Poptip trigger="hover" transfer width="300" word-wrap placement="bottom-end" style="position: absolute; right: 20px; top: 20px">
                        <Icon type="ios-help-circle-outline" class="card-help"/>
                        <div slot="content">
                            <p v-for="(item, index) in desc" :key="index">{{item}}</p>
                        </div>
                    </Poptip>
                </Row>
            </i-col>
        </Row>
    </Card>
</template>

<script>
import countUp from './countUp.vue';

export default {
    name: 'inforCard',
    components: {
        countUp
    },
    props: {
        idName: String,
        endVal: Number,
        color: String,
        iconType: String,
        introText: String,
        countSize: {
            type: String,
            default: '35px'
        },
        countWeight: {
            type: Number,
            default: 400
        },
        iconSize: {
            type: Number,
            default: 50
        },
        desc: {
            type: Array,
            default: []
        },
    }
};
</script>



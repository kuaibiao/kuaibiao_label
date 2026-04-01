<template>
    <div style="display: flex; align-items: center">
        <i-input v-model="colorValue"
                 @on-focus="showPicker()"
                 @on-change="updateFromInput"
                 ref="colorpicker"
                 size="small" :style="{width:width + 'px'}">
            <span class="input-group-addon color-picker-container" slot="append">
                <span class="current-color" :style="'background-color: ' + colorValue" @click="togglePicker()"></span>
                <chrome-picker :value="colors"
                               @input="updateFromPicker"
                               disableAlpha
                               class="color-picker"
                               :presetColors="presetColors"
                               v-if="displayPicker"/>
            </span>
        </i-input>
        <slot name="tips"></slot>
    </div>
</template>
<script>
    import {Sketch} from 'vue-color';

    export default {
        name: 'color-picker',
        props: {
            color: {
                type: String,
                required: true,
            },
            width: {
                type: Number,
                default: 200,
            }
        },
        data () {
            return {
                colors: {
                    hex: '#ffff00',
                },
                colorValue: '#ffff00',
                displayPicker: false,
                presetColors: [
                    '#eb2f96', '#722ed1', '#2f54eb', '#1890ff', '#13c2c2', '#52c41a', '#a0d911', '#fadb14',
                    '#faad14', '#fa8c16', '#fa541c', '#f5222d', '#ffd6e7', '#efdbff', '#d6e4ff', '#bae7ff',
                    '#b5f5ec', '#d9f7be', '#f4ffb8', '#ffffb8', '#fff1b8', '#ffe7ba', '#ffd8bf', '#ffccc7'
                ],
            };
        },
        mounted () {
            this.setColor(this.color || '#ffff00');
        },
        methods: {
            setColor (color) {
                this.updateColors(color);
                this.colorValue = color;
            },
            updateColors (color) {
                if (color.slice(0, 1) === '#') {
                    this.colors = {
                        hex: color
                    };
                } else if (color.slice(0, 4) === 'rgba') {
                    let rgba = color.replace(/^rgba?\(|\s+|\)$/g, '').split(',');
                    let hex = '#' + ((1 << 24) + (parseInt(rgba[0]) << 16) + (parseInt(rgba[1]) << 8) + parseInt(rgba[2])).toString(16).slice(1);
                    this.colors = {
                        hex: hex,
                        a: rgba[3],
                    };
                }
            },
            showPicker () {
                document.addEventListener('click', this.documentClick);
                this.displayPicker = true;
            },
            hidePicker () {
                document.removeEventListener('click', this.documentClick);
                this.displayPicker = false;
            },
            togglePicker () {
                this.displayPicker ? this.hidePicker() : this.showPicker();
            },
            updateFromInput () {
                this.updateColors(this.colorValue);
                this.$emit('input', this.colorValue);
            },
            updateFromPicker (color) {
                this.colors = color;
                if (color.rgba.a === 1) {
                    this.colorValue = color.hex;
                } else {
                    this.colorValue = 'rgba(' + color.rgba.r + ', ' + color.rgba.g + ', ' + color.rgba.b + ', ' + color.rgba.a + ')';
                }
                this.$emit('input', this.colorValue);
            },
            documentClick (e) {
                let el = this.$refs.colorpicker.$el;
                let target = e.target;
                if (el !== target && !el.contains(target)) {
                    this.hidePicker();
                }
            }
        },
        watch: {
            colorValue (val) {
                if (val) {
                    this.updateColors(val);
                }
            },
            color (val) {
                this.setColor(val);
            }
        },
        components: {
            'chrome-picker': Sketch
        },
        destroyed () {
            this.hidePicker();
        }
    };
</script>
<style lang="scss">
    .color-picker.vc-sketch {
        position: absolute;
        top: 28px;
        right: 10px;
        z-index: 9;
        width: 168px;
        .vc-sketch-presets {
            margin: 0;
            padding: 4px 0;
            display: flex;
            flex-wrap: wrap;
            justify-content: flex-start;
        }
        .vc-sketch-field .vc-input__input {
            padding: 4px 6% 3px;
            text-align: center;
        }
        .vc-sketch-presets-color {
            margin: 0 2px 5px 3px;
        }
        .vc-sketch-field--single {
            padding-left: 0;
        }
    }

    .current-color {
        display: inline-block;
        width: 16px;
        height: 16px;
        background-color: #000;
        cursor: pointer;
    }
</style>

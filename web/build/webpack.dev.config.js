const webpack = require('webpack');
const os = require('os');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const merge = require('webpack-merge');
const webpackBaseConfig = require('./webpack.base.config.js');
const fs = require('fs');
var openInEditor = require('launch-editor-middleware');
const path = require('path');
const package = require('../package.json');
const Ip = require('./ip').Ip;
fs.open('./build/env.js', 'w', function(err, fd) {
    const buf = 'export default "development";';
    //fs.write(fd, buf, 0, buf.length, 0, function(err, written, buffer) {});
    fs.write(fd, buf, 0, 'utf-8', function(err, written, buffer) {});
});
module.exports = merge(webpackBaseConfig, {
    // devtool: '#eval-source-map',
    output: {
        publicPath: '/dist/',
        filename: '[name].js',
        chunkFilename: '[name].chunk.js'
    },
    devServer: {
        before (app) {
            app.use('/__open-in-editor', openInEditor())
        },
        contentBase: './',
        compress: true,
        host: Ip,
        open: true,
        // port: 2000,
        hot: true,
        inline: true,
        progress: true,
        historyApiFallback: true,
    },
    plugins: [
        new ExtractTextPlugin({
            filename: '[name].css',
            allChunks: true
        }),
        new webpack.optimize.CommonsChunkPlugin({
            name: ['vender-exten', 'vender-base', 'polyfill'],
            minChunks: Infinity
        }),
        new HtmlWebpackPlugin({
            title: 'LabelTool v' + package.version,
            filename: '../index.html',
            inject: false
        }),
        new CopyWebpackPlugin([
            {
                from: 'favicon.ico'
            },
            {
                from: 'src/styles/fonts',
                to: 'fonts'
            },
            {
                from: 'src/api/config.js'
            },
            {
                from: 'src/styles/bffonts',
                to: 'bffonts'
            },
            {
                from: 'src/views/main-components/theme-switch/theme'
            },
        ], {
            ignore: [
                'text-editor.vue'
            ]
        })
    ]
});

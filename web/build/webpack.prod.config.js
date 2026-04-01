const webpack = require('webpack');
const HtmlWebpackPlugin = require('html-webpack-plugin');
const ExtractTextPlugin = require('extract-text-webpack-plugin');
const CopyWebpackPlugin = require('copy-webpack-plugin');
const cleanWebpackPlugin = require('clean-webpack-plugin');
const UglifyJsParallelPlugin = require('webpack-uglify-parallel');
const merge = require('webpack-merge');
const webpackBaseConfig = require('./webpack.base.config.js');
const os = require('os');
const fs = require('fs');
const path = require('path');
const package = require('../package.json');

let now = new Date();
const version = [now.getFullYear(),
    now.getMonth() + 1,
    now.getDate(),
    now.getHours(),
    now.getMinutes(),
    now.getSeconds()].map(t => {
    return t > 9 ? t : '0' + t;
}).join('');
const buf = JSON.stringify({
    version,
});
fs.writeFile('./web/version.json', buf, function(err, written, buffer) {});
fs.open('./build/version.js', 'w', function(err, fd) {
    const buf = `module.exports = {
        version: ${version}
     }`;
    //fs.write(fd, buf, 0, buf.length, 0, function(err, written, buffer) {});
    fs.write(fd, buf, 0, 'utf-8', function(err, written, buffer) {});
});
fs.open('./build/env.js', 'w', function(err, fd) {
    const buf = 'export default "production";';
    //fs.write(fd, buf, 0, buf.length, 0, function(err, written, buffer) {});
    fs.write(fd, buf, 0, 'utf-8', function(err, written, buffer) {});
});

module.exports = merge(webpackBaseConfig, {
    output: {
        publicPath: '/'+ version +'/', // 修改 https://iv...admin 这部分为你的服务器域名
        path: path.resolve(__dirname, '../web/' + version),
        filename: '[name].[chunkhash].js',
        chunkFilename: '[name].[chunkhash].chunk.js'
    },
    plugins: [
        // new cleanWebpackPlugin(['./web/dist/*'], {
        //     root: path.resolve(__dirname, '../')
        // }),
        new ExtractTextPlugin({
            filename: '[name].[contenthash].css',
            allChunks: true
        }),
        new webpack.optimize.CommonsChunkPlugin({
            // name: 'vendors',
            // filename: 'vendors.[hash].js'
            name: ['vender-exten', 'vender-base', 'polyfill'],
            minChunks: Infinity
        }),
        // new webpack.optimize.CommonsChunkPlugin({
        //     name: 'runtime',
        //     minChunks: Infinity,
        // }),
        new webpack.DefinePlugin({
            'process.env': {
                NODE_ENV: '"production"'
            }
        }),
        new webpack.optimize.UglifyJsPlugin({
            compress: {
                warnings: false
            }
        }),
        // new UglifyJsParallelPlugin({
        //     workers: os.cpus().length,
        //     mangle: true,
        //     compressor: {
        //       warnings: false,
        //       drop_console: true,
        //       drop_debugger: true
        //      }
        // }),
        new CopyWebpackPlugin([
            {
                from: 'favicon.ico'
            },
            {
                from: 'src/styles/fonts',
                to: 'fonts'
            },
            {
                from: 'src/api/config.js',
                to: '../',
                force: true,
            },
            {
                from: 'src/styles/bffonts',
                to: 'bffonts'
            },
            {
                from: 'src/views/main-components/theme-switch/theme'
            },

        ]),
        new HtmlWebpackPlugin({
            title: 'LabelTool v' + package.version,
            // favicon: './bf_icon.ico',
            filename: '../index.html',
            template: '!!ejs-loader!./src/template/index.ejs',
            inject: false
        })
    ]
});

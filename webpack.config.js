const path = require('path');
const Dotenv = require('dotenv-webpack');
const webpack = require("webpack");
const autoprefixer = require("autoprefixer");
const MiniCssExtractPlugin = require('mini-css-extract-plugin');
const CompressionPlugin = require('compression-webpack-plugin');

module.exports = {
    context: __dirname + '/public/assets',
    entry: {
      'js/cover': './src/js/main/index.js',
      'js/maps': './src/js/maps/index.js',
      'css/cover': './src/sass/light/_all.sass',
      'css/cover-dark': './src/sass/dark/_all.sass'
    },
    output: {
        path: __dirname + '/public/assets/dist',
        publicPath: '/assets/dist/',
        filename: '[name].js',
    },
    module: {
        rules: [
            {
                test: /\.m?js$/,
                use: {
                    loader: 'babel-loader',
                    options: {
                        presets: [[
                            '@babel/preset-env',
                            {
                                targets: {
                                    'node': "6.10",
                                    'esmodules': true
                                }
                            },
                        ]],
                        plugins: ['@babel/plugin-proposal-object-rest-spread']
                    }
                }
            },
            {
                test: /\.(sa|sc|c)ss$/,
                use: [MiniCssExtractPlugin.loader, 'css-loader', 'postcss-loader', 'sass-loader']
            }
        ]
    },
    plugins: [
        new Dotenv(),
        new MiniCssExtractPlugin({ filename: '[name].css', }),
        new CompressionPlugin({ exclude: /.+\.html/ }),
        new webpack.LoaderOptionsPlugin({
            options: {
                postcss: [
                    autoprefixer()
                ]
            }
        }),
    ],
};

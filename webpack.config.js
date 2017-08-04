const path = require('path');
const webpack = require('webpack');

module.exports = {

  entry: './js/calista-page.es6.js',

  output: {
    filename: './Resources/public/calista.min.js'
  },

  devtool: 'source-map',

  plugins: [
    /* new webpack.optimize.UglifyJsPlugin({
      sourceMap: 1 // @todo options.devtool && (options.devtool.indexOf("sourcemap") >= 0 || options.devtool.indexOf("source-map") >= 0)
    }) */
  ],

  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader?cacheDirectory=true',
          options: {
            presets: ['env'],
            plugins: ['transform-runtime']
          }
        }
      },
      {
        test: /\.js$/, // include .js files
        enforce: "pre", // preload the jshint loader
        exclude: /node_modules/, // exclude any and all files in the node_modules folder
        use: {
          loader: "jshint-loader"
        }
      }
    ]
  }
};

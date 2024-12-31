  
const mix = require('laravel-mix');

const webpack = require('webpack')
/*
 |--------------------------------------------------------------------------
 | Mix Asset Management
 |--------------------------------------------------------------------------
 |
 | Mix provides a clean, fluent API for defining some Webpack build steps
 | for your Laravel applications. By default, we are compiling the CSS
 | file for the application as well as bundling up all the JS files.
 |
 */
mix.setPublicPath('../public_html');
mix.webpackConfig({
    
    plugins: [
        new webpack.DefinePlugin({
          '__THEME': '"mat"' //or whatever your theme is - also note the extra quotes
        }),
    ],
    resolve: {
        extensions: [ '.js', '.vue' ],
        alias     : { '@': `${ __dirname  }/resources` },
    },
    output: {
        publicPath   : '../public_html',
        chunkFilename: 'js/[name].[chunkhash].js',
    },
});
mix.js('resources/assets/js/app.js','../public_html/elixir/app.js')
.sass('resources/assets/sass/app.scss', '../public_html/elixir/app_sass.css')
.vue() // <- Add this
.styles(['main.css'], '../public_html/elixir/app.css');

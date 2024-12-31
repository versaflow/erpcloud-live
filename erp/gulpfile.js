const elixir = require('laravel-elixir');

require('laravel-elixir-vue-2');

/*
 |--------------------------------------------------------------------------
 | Elixir Asset Management
 |--------------------------------------------------------------------------
 |
 | Elixir provides a clean, fluent API for defining some basic Gulp tasks
 | for your Laravel application. By default, we are compiling the Sass
 | file for your application as well as publishing vendor resources.
 |
 */

elixir(function(mix) {
    
    mix.sass('resources/assets/sass/app.scss', '../public_html/elixir/app_sass.css');
    mix.webpack('app.js','../public_html/elixir/app.js');
    mix.styles([
        'main.css'
    ], '../public_html/elixir/app.css');
});
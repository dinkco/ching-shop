/*jslint node: true */
'use strict';

var elixir = require('laravel-elixir');
var gulp = require('gulp');
var shell = require('gulp-shell');

elixir(function (mix) {
    mix.sass('ching-shop.scss');
});

elixir(function (mix) {
    mix.styles([
        'ching-shop.css'
    ], 'public/css/ching-shop.css', 'public/css');
});

elixir(function (mix) {
    mix.scripts([
        'bower_components/jquery/dist/jquery.js',
        'bower_components/bootstrap-sass/assets/javascripts/bootstrap.js'
    ], 'public/js/ching-shop.js', 'resources/assets');
});

elixir(function (mix) {
    mix.version([
        'css/ching-shop.css',
        'js/ching-shop.js'
    ]);
});

elixir(function (mix) {
    mix.copy('resources/assets/img', 'public/img');
});

elixir(function (mix) {
    mix.copy(
        'resources/assets/bower_components/bootstrap-sass/assets/fonts',
        'public/build/fonts/'
    );
});

elixir(function (mix) {
    mix.task('generate-test-db');
});

gulp.task('generate-test-db', shell.task(
    [
        'rm -f ./database/test_db.sqlite',
        'touch ./database/test_db.sqlite',
        'php artisan migrate:refresh --seed --database="testing" --env="testing"'
    ],
    {
        verbose: true
    }
));
/*jslint node: true */
"use strict";

//noinspection JSUnresolvedVariable
process.env.DISABLE_NOTIFIER = true;

var elixir   = require("laravel-elixir");
var gulp     = require("gulp");
var clean    = require("gulp-clean");
var shell    = require("gulp-shell");
var typings  = require("gulp-typings");
var ts       = require("gulp-typescript");
var unzip    = require('gulp-unzip');
var filter   = require('gulp-filter');
var replace  = require('gulp-replace');
var rename   = require('gulp-rename');
var scssLint = require('gulp-scss-lint');
var tsLint   = require("gulp-tslint");

var Task = elixir.Task;

elixir.config.css.autoprefix = {
    enabled: true,
    options: {
        cascade:  true,
        browsers: ["last 3 versions", "> 1%"]
    }
};

elixir.extend("typings", function () {
    new Task("typings", function () {
        return gulp.src("./resources/assets/ts/typings.json").pipe(typings());
    });
});

elixir.extend("typescript", function () {
    new Task("typescript", function () {
        return gulp.src("./resources/assets/ts/src/**/*.ts")
            .pipe(ts(ts.createProject("./resources/assets/ts/tsconfig.json")))
            .pipe(gulp.dest("./resources/assets/js/"));
    });
});

elixir(function (mix) {
    mix.task("test-database");
    mix.task("clean")
        .sass("staff.scss")
        .sass("customer.scss")
        .styles(
            ["staff.css"],
            "public/css/staff.css",
            "public/css"
        )
        .styles(
            ["customer.css"],
            "public/css/customer.css",
            "public/css"
        )
        .copy(
            "resources/assets/scripts/**/*.js",
            "resources/assets/js/"
        )
        .typings()
        .typescript()
        .browserify("staff.js")
        .browserify("staff/product-options.js")
        .browserify("staff/colours.js")
        .browserify("customer.js")
        .browserify("analytics.js")
        .scripts("../components/staff/Telegram.vue")
        .version([
            "css/staff.css",
            "css/customer.css",
            "js/staff.js",
            "js/product-options.js",
            "js/colours.js",
            "js/customer.js",
            "js/analytics.js",
            "js/Telegram.js"
        ])
        .copy("resources/assets/img", "public/img")
        .copy("resources/assets/fonts", "public/fonts")
        .copy(
            "./node_modules/bootstrap-sass/assets/fonts",
            "public/build/fonts/"
        );
});

gulp.task("clean", function () {
    if (elixir.config.production) {
        return;
    }
    return gulp.src(
        [
            "./resources/assets/js/*",
            "./public/build/*",
            "./public/css/*",
            "./public/js/*"
        ],
        {read: false}
    ).pipe(clean());
});

gulp.task("test-database", shell.task(
    [
        "rm -f ./database/test_db.sqlite",
        "curl -X DELETE 'localhost:9200/ching-shop-test'",
        "sqlite3 ./database/test_db.sqlite ''",
        "ELASTICSEARCH_INDEX=ching-shop-test php artisan migrate:refresh --seed --database=testing --env=testing"
    ],
    {
        verbose: true
    }
));

gulp.task("import", function () {
    // Icon files.
    gulp.src("./import/icomoon.zip")
        .pipe(unzip())
        .pipe(filter("fonts/icomoon.*"))
        .pipe(gulp.dest("./resources/assets/"));

    // Selection config.
    gulp.src("./import/icomoon.zip")
        .pipe(unzip())
        .pipe(filter("selection.json"))
        .pipe(gulp.dest("./resources/config/icomoon/"));

    // CSS file.
    gulp.src("./import/icomoon.zip")
        .pipe(unzip())
        .pipe(filter("style.css"))
        .pipe(replace(/\[class\^="icon-"], \[class\*=" icon-"]/g, ".icon"))
        .pipe(replace("fonts/", "/fonts/"))
        .pipe(rename("_icomoon.scss"))
        .pipe(gulp.dest("./resources/assets/sass/vendor/"));
});

gulp.task("scss-lint", function () {
    return gulp.src([
        "resources/assets/sass/**/*.scss",
        "!**/vendor/**",
        "!**/bootstrap-variables.scss"
    ])
        .pipe(scssLint({config: "./tests/analysis/scss-lint.yml"}))
        .pipe(scssLint.failReporter());
});

gulp.task("ts-lint", function () {
    return gulp.src([
        "resources/assets/ts/**/*.ts",
        "!**/*.d.ts"
    ])
        .pipe(tsLint())
        .pipe(tsLint.report());
});

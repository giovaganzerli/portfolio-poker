"use strict";

var fs = require('fs');
var path = require('path');
var merge = require('merge-stream');
var gulp = require('gulp');
var sass = require('gulp-sass');
//var minify = require('gulp-minify');
var watch = require('gulp-watch');
var concat = require('gulp-concat');
var uglify = require('gulp-uglify-es').default;
var rename = require('gulp-rename');

var scssOptions = {
        errLogToConsole: true,
        outputStyle: 'compressed'
    },
    scssAdminPath = {
        orig: 'admin/assets/scss/',
        min: 'admin/assets/css/'
    },
    jsAdminPath = {
        orig: 'admin/assets/js/',
        min: 'admin/assets/js/min/'
    },
    scssFrontendPath = {
        orig: 'frontend/assets/scss/',
        min: 'frontend/assets/css/'
    },
    jsFrontendPath = {
        orig: 'frontend/assets/js/',
        min: 'frontend/assets/js/min/'
    };

function getFolders(dir) {
    return fs.readdirSync(dir)
        .filter(function(file) {
            return fs.statSync(path.join(dir, file)).isDirectory();
        });
}

// ADMIN

gulp.task('scssAdmin', function () {
    
    var tasks = gulp.src(scssAdminPath.orig +'templates/*.scss')
        .pipe(sass(scssOptions))
        .pipe(gulp.dest(scssAdminPath.min +'templates/'));

    var root = gulp.src(scssAdminPath.orig +'*.scss')
        .pipe(sass(scssOptions))
        .pipe(gulp.dest(scssAdminPath.min));
    
    return merge(tasks, root);
});

gulp.task('jsAdmin', function() {
    
    var folders = getFolders(jsAdminPath.orig +'templates/');
    
    var tasks = folders.map(function(folder) {
        return gulp.src(jsAdminPath.orig +'templates/'+ folder +'/*.js', { base: jsAdminPath.orig +'templates/' })
            .pipe(uglify())
            .pipe(concat(folder +'.min.js'))
            .pipe(gulp.dest(jsAdminPath.orig +'min/templates/'));
    });
    
    var root = gulp.src(jsAdminPath.orig +'*.js')
        .pipe(uglify())
        .pipe(concat('main.min.js'))
        .pipe(gulp.dest(jsAdminPath.orig +'min/'));
    
    return merge(tasks, root);
});

// FRONTEND

gulp.task('scssFrontend', function () {
    
    var tasks = gulp.src(scssFrontendPath.orig +'templates/*.scss')
        .pipe(sass(scssOptions))
        .pipe(gulp.dest(scssFrontendPath.min +'templates/'));

    var root = gulp.src(scssFrontendPath.orig +'*.scss')
        .pipe(sass(scssOptions))
        .pipe(gulp.dest(scssFrontendPath.min));
    
    return merge(tasks, root);
});

gulp.task('jsFrontend', function() {
    
    var folders = getFolders(jsFrontendPath.orig +'templates/');
    
    var tasks = folders.map(function(folder) {
        return gulp.src(jsFrontendPath.orig +'templates/'+ folder +'/*.js', { base: jsFrontendPath.orig +'templates/' })
            .pipe(concat(folder +'.min.js'))
            .pipe(uglify())
            .pipe(gulp.dest(jsFrontendPath.orig +'min/templates/'));
    });
    
    var api = gulp.src(jsFrontendPath.orig +'api/*.js')
        .pipe(uglify())
        .pipe(concat('api.min.js'))
        .pipe(gulp.dest(jsFrontendPath.orig +'min/api/'));
    
    var root = gulp.src(jsFrontendPath.orig +'*.js')
        .pipe(concat('main.min.js'))
        .pipe(uglify())
        .pipe(gulp.dest(jsFrontendPath.orig +'min/'));
    
    return merge(tasks, api, root);
});

// TASKS

gulp.task('watch', function() {
    
    // ADMIN
    
    watch(scssAdminPath.orig +'**/*.scss', gulp.series(['scssAdmin']));
    
    watch(jsAdminPath.orig +'*.js', gulp.series(['jsAdmin']));
    
    watch(jsAdminPath.orig +'templates/**/*.js', gulp.series(['jsAdmin']));
    
    // FRONTEND
    
    watch(scssFrontendPath.orig +'**/*.scss', gulp.series(['scssFrontend']));
    
    watch(jsFrontendPath.orig +'*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'api/**/*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'templates/**/*.js', gulp.series(['jsFrontend']));
});

gulp.task('watchFrontend', function() {
    
    // FRONTEND
    
    watch(scssFrontendPath.orig +'**/*.scss', gulp.series(['scssFrontend']));
    
    watch(jsFrontendPath.orig +'*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'api/**/*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'templates/**/*.js', gulp.series(['jsFrontend']));
});

gulp.task('watchAdmin', function() {
    
    // ADMIN
    
    watch(scssAdminPath.orig +'**/*.scss', gulp.series(['scssAdmin']));
    
    watch(jsAdminPath.orig +'*.js', gulp.series(['jsAdmin']));
    
    watch(jsAdminPath.orig +'templates/**/*.js', gulp.series(['jsAdmin']));
});

gulp.task('watchStyle', function() {

    // ADMIN
    
    watch(scssAdminPath.orig +'**/*.scss', gulp.series(['scssAdmin']));

    // FRONTEND
    
    watch(scssFrontendPath.orig +'**/*.scss', gulp.series(['scssFrontend']));
});

gulp.task('watchScript', function() {

    // ADMIN
    
    watch(jsAdminPath.orig +'*.js', gulp.series(['jsAdmin']));
    
    watch(jsAdminPath.orig +'templates/**/*.js', gulp.series(['jsAdmin']));
    
    // FRONTEND
    
    watch(jsFrontendPath.orig +'*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'api/**/*.js', gulp.series(['jsFrontend']));
    
    watch(jsFrontendPath.orig +'templates/**/*.js', gulp.series(['jsFrontend']));
});

gulp.task('default', gulp.series(['scssFrontend', 'jsFrontend', 'scssAdmin', 'jsAdmin', 'watch']));
gulp.task('defaultFrontend', gulp.series(['scssFrontend', 'jsFrontend', 'watchFrontend']));
gulp.task('defaultAdmin', gulp.series(['scssAdmin', 'jsAdmin', 'watchAdmin']));
gulp.task('style', gulp.series(['scssFrontend', 'scssAdmin', 'watchStyle']));
gulp.task('script', gulp.series(['jsFrontend', 'jsAdmin', 'watchScript']));
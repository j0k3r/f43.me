var gulp = require('gulp');
    gutil = require('gulp-util'),
    concat = require('gulp-concat'),
    compass = require('gulp-compass'),
    cssnano = require('gulp-cssnano'),
    uglify = require('gulp-uglify'),
    rimraf = require('rimraf')
;

var paths = {
    vendors: [
        'templates/lib/foundation/js/vendor/zepto.js',
        'templates/lib/foundation/js/vendor/custom.modernizr.js'
    ],
    app: [
        'templates/lib/foundation/js/foundation/foundation.js',
        'templates/lib/foundation/js/foundation/foundation.alerts.js',
        'templates/lib/foundation/js/foundation/foundation.dropdown.js',
        'templates/lib/foundation/js/foundation/foundation.forms.js',
        'templates/lib/foundation/js/foundation/foundation.placeholder.js',
        'templates/lib/foundation/js/foundation/foundation.reveal.js',
        'templates/lib/foundation/js/foundation/foundation.section.js',
        'node_modules/moment/min/moment.min.js',
        'src/**/app.js'
    ],
    morris: [
        'public/js/morris.js'
    ],
    sassFoundation: [
        'templates/lib/foundation/scss/normalize.scss',
        'templates/lib/foundation/scss/foundation.scss'
    ],
    sassApp: [
        'templates/sass/app.sass'
    ],
    css: [
        'public/build/css/normalize.css',
        'public/build/css/foundation.css',
        'public/build/css/app.css',
    ]
};

// cleanup the build folder
gulp.task('clean', function(cb) {
    rimraf('public/build/**/*.*', cb);
});

gulp.task('js-vendor', function() {
    return gulp.src(paths.vendors)
        .pipe(uglify())
        .pipe(concat({ path: 'zepto.modernizr.js', stat: { mode: 0666 }}))
        .pipe(gulp.dest('public/build'));
});

gulp.task('js-app', function() {
    return gulp.src(paths.app)
        .pipe(concat({ path: 'app.js', stat: { mode: 0666 }}))
        .pipe(uglify())
        .pipe(gulp.dest('public/build'))
        .on('error', gutil.log);
});

// morris is alone because we don't need it on every page, only on the dashboard
gulp.task('js-morris', function() {
    return gulp.src(paths.morris)
        .pipe(concat({ path: 'morris.js', stat: { mode: 0666 }}))
        .pipe(uglify())
        .pipe(gulp.dest('public/build'))
        .on('error', gutil.log);
});

// there are 2 sass definition, because compass() doesn't handle 2 differents folders ...
gulp.task('css-foundation', function () {
    return gulp.src(paths.sassFoundation)
        .pipe(compass({
            css: 'public/build/css',
            sass: 'templates/lib/foundation/scss'
        }))
        .pipe(gulp.dest('public/build/css'));
});

gulp.task('css-app', function () {
    return gulp.src(paths.sassApp)
        .pipe(compass({
            css: 'public/build/css',
            sass: 'templates/sass'
        }))
        .pipe(gulp.dest('public/build/css'));
});

gulp.task('css', ['css-app', 'css-foundation'], function () {
    return gulp.src(paths.css)
        .pipe(cssnano())
        .pipe(concat({ path: 'main.css', stat: { mode: 0666 }}))
        .pipe(gulp.dest('public/build'));
});

// Rerun tasks when a file changes
gulp.task('watch', function() {
    gulp.watch(paths.app, ['js-app']);
    gulp.watch(paths.sassApp, ['css']);
});

gulp.task('default', ['clean', 'js-vendor', 'js-app', 'js-morris', 'css']);

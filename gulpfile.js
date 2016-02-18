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
        'app/Resources/lib/foundation/js/vendor/zepto.js',
        'app/Resources/lib/foundation/js/vendor/custom.modernizr.js'
    ],
    app: [
        'app/Resources/lib/foundation/js/foundation/foundation.js',
        'app/Resources/lib/foundation/js/foundation/foundation.alerts.js',
        'app/Resources/lib/foundation/js/foundation/foundation.dropdown.js',
        'app/Resources/lib/foundation/js/foundation/foundation.forms.js',
        'app/Resources/lib/foundation/js/foundation/foundation.placeholder.js',
        'app/Resources/lib/foundation/js/foundation/foundation.reveal.js',
        'app/Resources/lib/foundation/js/foundation/foundation.section.js',
        'node_modules/moment/min/moment.min.js',
        'src/**/app.js'
    ],
    morris: [
        'src/**/morris.js'
    ],
    sassFoundation: [
        'app/Resources/lib/foundation/scss/normalize.scss',
        'app/Resources/lib/foundation/scss/foundation.scss'
    ],
    sassApp: [
        'src/**/*.sass'
    ],
    css: [
        'web/build/css/normalize.css',
        'web/build/css/foundation.css',
        'web/build/css/app.css',
    ]
};

// cleanup the build folder
gulp.task('clean', function(cb) {
    rimraf('web/build/**/*.*', cb);
});

gulp.task('js-vendor', function() {
    return gulp.src(paths.vendors)
        .pipe(uglify())
        .pipe(concat({ path: 'zepto.modernizr.js', stat: { mode: 0666 }}))
        .pipe(gulp.dest('web/build'));
});

gulp.task('js-app', function() {
    return gulp.src(paths.app)
        .pipe(concat({ path: 'app.js', stat: { mode: 0666 }}))
        .pipe(uglify())
        .pipe(gulp.dest('web/build'))
        .on('error', gutil.log);
});

// morris is alone because we don't need it on every page, only on the dashboard
gulp.task('js-morris', function() {
    return gulp.src(paths.morris)
        .pipe(concat({ path: 'morris.js', stat: { mode: 0666 }}))
        .pipe(uglify())
        .pipe(gulp.dest('web/build'))
        .on('error', gutil.log);
});

// there are 2 sass definition, because compass() doesn't handle 2 differents folders ...
gulp.task('css-foundation', function () {
    return gulp.src(paths.sassFoundation)
        .pipe(compass({
            css: 'web/build/css',
            sass: 'app/Resources/lib/foundation/scss'
        }))
        .pipe(gulp.dest('web/build/css'));
});

gulp.task('css-app', function () {
    return gulp.src(paths.sassApp)
        .pipe(compass({
            css: 'web/build/css',
            sass: 'src/FeedBundle/Resources/sass'
        }))
        .pipe(gulp.dest('web/build/css'));
});

gulp.task('css', ['css-app', 'css-foundation'], function () {
    return gulp.src(paths.css)
        .pipe(cssnano())
        .pipe(concat({ path: 'main.css', stat: { mode: 0666 }}))
        .pipe(gulp.dest('web/build'));
});

// Rerun tasks when a file changes
gulp.task('watch', function() {
    gulp.watch(paths.app, ['js-app']);
    gulp.watch(paths.sassApp, ['css']);
});

gulp.task('default', ['clean', 'js-vendor', 'js-app', 'js-morris', 'css']);

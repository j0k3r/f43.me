var gulp = require('gulp');
var concat = require('gulp-concat');
var compass = require('gulp-compass');
var minifycss = require('gulp-minify-css');
var uglify = require('gulp-uglify');
var csslint = require('gulp-csslint');

var paths = {
    vendors: [
        './app/Resources/lib/foundation/js/vendor/zepto.js',
        './app/Resources/lib/foundation/js/vendor/custom.modernizr.js'
    ],
    app: [
        './app/Resources/lib/foundation/js/foundation/foundation.js',
        './app/Resources/lib/foundation/js/foundation/foundation.alerts.js',
        './app/Resources/lib/foundation/js/foundation/foundation.dropdown.js',
        './app/Resources/lib/foundation/js/foundation/foundation.forms.js',
        './app/Resources/lib/foundation/js/foundation/foundation.placeholder.js',
        './app/Resources/lib/foundation/js/foundation/foundation.reveal.js',
        './app/Resources/lib/foundation/js/foundation/foundation.section.js',
        './app/Resources/lib/foundation/js/foundation/foundation.tooltips.js',
        './app/Resources/lib/foundation/js/foundation/foundation.topbar.js',
        './src/**/*.js'
    ],
    sassFoundation: [
        './app/Resources/lib/foundation/scss/normalize.scss',
        './app/Resources/lib/foundation/scss/foundation.scss'
    ],
    sassApp: [
        './src/**/*.sass'
    ]
};

gulp.task('js-vendor', function() {
    return gulp.src(paths.vendors)
        .pipe(uglify())
        .pipe(concat({ path: 'zepto.modernizr.js', stat: { mode: 0666 }}))
        .pipe(gulp.dest('./web/build'));
});

gulp.task('js-app', function() {
    return gulp.src(paths.app)
        .pipe(uglify())
        .pipe(concat({ path: 'foundation.app.js', stat: { mode: 0666 }}))
        .pipe(gulp.dest('./web/build'));
});

gulp.task('css-foundation', function () {
    return gulp.src(paths.sassFoundation)
        .pipe(compass({
            css: 'web/build/css',
            sass: 'app/Resources/lib/foundation/scss'
        }))
        .pipe(minifycss())
        .pipe(gulp.dest('./web/build/css'));
});

gulp.task('css-app', function () {
    return gulp.src(paths.sassApp)
        .pipe(compass({
            css: 'web/build/css',
            sass: 'src/j0k3r/FeedBundle/Resources/sass'
        }))
        .pipe(minifycss())
        .pipe(gulp.dest('./web/build/css'));
});

gulp.task('css', ['css-app', 'css-foundation'], function () {
    return gulp.src('web/build/css/*.css')
        .pipe(minifycss())
        .pipe(concat({ path: 'main.css', stat: { mode: 0666 }}))
        .pipe(gulp.dest('./web/build'));
});

gulp.task('default', ['js-vendor', 'js-app', 'css']);

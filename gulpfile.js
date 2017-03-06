var gulp  = require('gulp'),
  cssmin  = require('gulp-clean-css'),
  less    = require('gulp-less')
;

// LESS compilation
gulp.task('less', function () {
  var pipe = gulp.src('less/udashboard.less');
  pipe = pipe
    .pipe(less())
    .pipe(cssmin())
  ;
  return pipe
    .pipe(gulp.dest('js/'))
    .on('error', errorHandler)
  ;
});

gulp.task('default', ['less']);

// Handle the error
function errorHandler(error) {
  console.log(error.toString());
  this.emit('end');
}

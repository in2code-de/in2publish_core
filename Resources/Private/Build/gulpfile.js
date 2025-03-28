const { src, dest, watch, series, parallel } = require('gulp');
const sass = require('gulp-sass')(require('sass'));
const plumber = require('gulp-plumber');

const project = {
    base: __dirname + '/../../Public',
    css: __dirname + '/../../Public/Css',
};

// SCSS zu css
function css() {
    const config = {};
    config.outputStyle = 'compressed';

    return src(__dirname + '/Assets/Sass/**/*.scss')
        .pipe(plumber())
        .pipe(sass(config))
        .pipe(dest(project.css))
}

const watchStyleguide = () => watch(['Assets/**/*.*'], series(css, styleguide));

module.exports = {
    default: css,
    css,
}

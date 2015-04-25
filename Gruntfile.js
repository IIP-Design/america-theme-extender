module.exports = function(grunt) {
    require("matchdep").filterDev("grunt-*").forEach(grunt.loadNpmTasks);

    grunt.initConfig({
        pkg: grunt.file.readJSON('package.json'),

        sass: {
          dist: {
            options: {
              style: 'compressed'
            },
            files: {
              'misinfo/style.css': 'misinfo/style.scss',
              'climate/style.css': 'climate/style.scss'
            },
          }
        },

        uglify: {
          build: {
            files: {
              'js/file.js': ['js/file.min.js']
            }
          }
        },

        imagemin: {
          dynamic: {
            files: [
              {
                expand: true,
                cwd: 'misinfo/images-src/',
                src: ['**/*.{png,jpg,gif}'],
                dest: 'misinfo/images/'
              },
              {
                expand: true,
                cwd: 'climate/images-src/',
                src: ['**/*.{png,jpg,gif}'],
                dest: 'climate/images/'
              }
            ]
          }
        },

        // Requires SVGO
        svgmin: {
          options: {
            plugins: [
              { collapseGroups: false }
            ]
          },
          dist: {
            files: [{
              expand: true,
              cwd: 'images-src',
              src: ['*.svg'],
              dest: 'images',
              ext: '.min.svg'
            }]
          }
        },

        // Requires gzip
        compress: {
          main: {
            options: {
              mode: 'gzip'
            },
            files: [
              {expand: true,
               cwd: 'images',
               src: ['*.svg'],
               dest: 'images',
               ext: '.svgz'}
            ]
          }
        },
        watch: {
          css: {
            files: '**/*.scss',
            tasks: ['sass']
          }
        }
      });

    grunt.task.registerTask('default', [
      'sass',
      'optimages'
    ]);

    grunt.task.registerTask('optimages', [
      'imagemin',
      'svgmin',
      'compress'
    ]);
};

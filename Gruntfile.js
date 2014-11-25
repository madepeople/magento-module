/*global module require */

module.exports = function(grunt) {
    "use strict";

    // load all grunt tasks
    require('matchdep').filterDev('grunt-*').forEach(grunt.loadNpmTasks);

    // Project configuration.
    grunt.initConfig({
        jasmine : {
            // Project's source files
            test: {
                options: {
                    vendor: [

                        // jquery and jasmine-jquery used in testing
                        'tests/js/bower_components/jquery/dist/jquery.js',
                        'tests/js/bower_components/jasmine-jquery/lib/jasmine-jquery.js',
                        'tests/js/dependencies/jquery-noconflict.js', // Calls jQuery.noConflict()

                        // Libraries that are loaded by magento
                        "tests/js/dependencies/js/prototype/prototype.js",
                        "tests/js/dependencies/js/lib/ccard.js",
                        "tests/js/dependencies/js/prototype/validation.js",
                        "tests/js/dependencies/js/scriptaculous/builder.js",
                        "tests/js/dependencies/js/scriptaculous/effects.js",
                        "tests/js/dependencies/js/scriptaculous/dragdrop.js",
                        "tests/js/dependencies/js/scriptaculous/controls.js",
                        "tests/js/dependencies/js/scriptaculous/slider.js",
                        "tests/js/dependencies/js/varien/js.js",
                        "tests/js/dependencies/js/varien/form.js",
                        "tests/js/dependencies/js/varien/menu.js",
                        "tests/js/dependencies/js/mage/translate.js",
                        "tests/js/dependencies/js/mage/cookies.js",

                        'src/js/svea.js', // svea library
                    ],
                    specs: 'tests/js/spec/**/*.spec.js',
                    helpers: 'tests/js/spec/**/*.helper.js'
                }
            }
        }
    });

};

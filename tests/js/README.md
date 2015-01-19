# Javascript tests

## Running tests

Tests should be run from the root directory:

    > npm install # Install npm modules
    > grunt jasmine # Run tests

## Adding helpers

Helpers needs to be named `*.helper.js` and can be put anywhere in the `spec/` directory.

## Writing tests

Tests needs to be named `*.spec.js` and can be put anywhere in the `spec/` directory.

### Testing DOM

[jasmine-jquery](https://github.com/velesin/jasmine-jquery) is loaded and can be used to test DOM

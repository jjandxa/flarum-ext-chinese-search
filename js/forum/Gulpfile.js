var flarum = require('flarum-gulp');

flarum({
    modules: {
        'jjandxa/hello': [
            'src/**/*.js'
        ]
    }
});
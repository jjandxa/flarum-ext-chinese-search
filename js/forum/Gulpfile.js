var flarum = require('flarum-gulp');

flarum({
    modules: {
        'jjandxa/flarum-ext-chinese-search': [
            'src/**/*.js'
        ]
    }
});
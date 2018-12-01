<?php

namespace Plugin\XunSearch;

use Flarum\Extend;
use Illuminate\Contracts\Events\Dispatcher;
use Plugin\XunSearch\Controller\XunSearchController;

return [
    (new Extend\Frontend('forum'))
        ->js(__DIR__ . '/js/dist/forum.js'),
    (new Extend\Routes('api'))
        ->get('/xun/discussions', 'xun.discussions.index', XunSearchController::class),
    function (Dispatcher $events) {
        $events->subscribe(XunSearchDispatcher::class);
    }
];

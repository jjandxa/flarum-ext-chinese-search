<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-11
 * Time: 下午9:14
 */

namespace Plugin\XunSearch;

use Illuminate\Contracts\Events\Dispatcher;

return function (Dispatcher $events) {

    $events->subscribe(XunSearchDispatcher::class);

};
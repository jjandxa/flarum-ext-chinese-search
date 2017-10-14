<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-11
 * Time: 下午9:14
 */

namespace Plugin\XunSearch;

require_once "src/XunSearchDispatcher.php";

use Flarum\Event\ConfigureWebApp;
use Flarum\Event\DiscussionWasHidden;
use Flarum\Event\DiscussionWasRenamed;
use Flarum\Event\DiscussionWasRestored;
use Plugin\XunSearch\XunSearchDispatcher;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRevised;
use Flarum\Event\PostWasDeleted;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasRestored;
use Flarum\Event\ConfigureApiRoutes;
use Illuminate\Contracts\Events\Dispatcher;

class BootStrap {
    public static $rootDir = __DIR__;
}

return function (Dispatcher $event) {
    // 添加帖子到搜索引擎索引
    $event->listen(PostWasPosted::class, array(XunSearchDispatcher::class, "posted"));

    // 更新帖子到搜索引擎索引
    $event->listen(PostWasRevised::class, array(XunSearchDispatcher::class, "revised"));

    // 隐藏帖子话题到搜索引擎索引
    $event->listen(PostWasHidden::class, array(XunSearchDispatcher::class, "hidden"));

    // 恢复帖子到搜索引擎索引
    $event->listen(PostWasRestored::class, array(XunSearchDispatcher::class, "restored"));

    // 修改话题到搜索引擎索引
    $event->listen(DiscussionWasRenamed::class, array(XunSearchDispatcher::class, "discussionRenamed"));

    // 隐藏话题到搜索引擎索引
    $event->listen(DiscussionWasHidden::class, array(XunSearchDispatcher::class, "discussionHidden"));

    // 恢复话题到搜索引擎索引
    $event->listen(DiscussionWasRestored::class, array(XunSearchDispatcher::class, "discussionRestored"));

    $event->listen(ConfigureApiRoutes::class,
        array(XunSearchDispatcher::class, "registerApi"));
    $event->listen(ConfigureWebApp::class,
        array(XunSearchDispatcher::class, "initView"));
};
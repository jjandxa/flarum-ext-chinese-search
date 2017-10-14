<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-12
 * Time: 上午8:55
 */

namespace Plugin\XunSearch;

require_once "controller/XunSearchController.php";
require_once "utils/XunSearchUtils.php";
require_once "service/XunSearchService.php";

use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureWebApp;
use Flarum\Event\DiscussionWasHidden;
use Flarum\Event\DiscussionWasRenamed;
use Flarum\Event\DiscussionWasRestored;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Event\PostWasRevised;
use Plugin\XunSearch\Controller\XunSearchController;

class XunSearchDispatcher
{
    // 添加后台Api
    static function registerApi(ConfigureApiRoutes $event) {
        $event->get(
          "/xun/discussions",
          "xun.discussions.index",
            XunSearchController::class
        );
    }

    // 添加前台逻辑
    static function initView(ConfigureWebApp $event) {
        if ($event->isForum()) {
            $event->addAssets(BootStrap::$rootDir.'/js/forum/dist/extension.js');
            $event->addBootstrapper('jjandxa/hello/main');
        }
    }

    // 添加帖子到索引
    static function posted(PostWasPosted $event) {
        XunSearchService::addPostToIndex($event->post);
    }

    // 修改帖子到索引
    static function revised(PostWasRevised $event) {
        XunSearchUtils::getIndex()->update(XunSearchUtils::getDocument($event->post->discussion,
            $event->post, $event->post->discussion->comments_count));
    }

    // 隐藏帖子到索引
    static function hidden(PostWasHidden $event) {
        XunSearchService::deletePostToIndex($event->post);
    }

    // 恢复帖子到索引
    static function restored(PostWasRestored $event) {
        XunSearchService::addPostToIndex($event->post);
    }

    // 话题修改名称到索引
    static function discussionRenamed(DiscussionWasRenamed $event) {
        XunSearchService::renameDiscussion($event->discussion);
    }

    // 话题隐藏到索引
    static function discussionHidden(DiscussionWasHidden $event) {
        XunSearchService::deleteDiscussion($event->discussion);
    }

    // 话题恢复到索引
    static function discussionRestored(DiscussionWasRestored $event) {
        XunSearchService::addDiscussion($event->discussion);
    }
}
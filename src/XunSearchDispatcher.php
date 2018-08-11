<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-12
 * Time: 上午8:55
 */

namespace Plugin\XunSearch;

use Flarum\Event\ConfigureApiRoutes;
use Flarum\Event\ConfigureWebApp;
use Flarum\Event\DiscussionWasHidden;
use Flarum\Event\DiscussionWasRenamed;
use Flarum\Event\DiscussionWasRestored;
use Flarum\Event\ExtensionWasEnabled;
use Flarum\Event\PostWasHidden;
use Flarum\Event\PostWasPosted;
use Flarum\Event\PostWasRestored;
use Flarum\Event\PostWasRevised;
use Plugin\XunSearch\Controller\XunSearchController;
use Plugin\XunSearch\Service\XunSearchService;
use Plugin\XunSearch\Utils\XunSearchUtils;
use Illuminate\Contracts\Events\Dispatcher;

class XunSearchDispatcher
{

    private $xunSearchUtils;

    private $xunSearchService;

    /**
     * XunSearchDispatcher constructor.
     * @param $xunSearchUtils
     * @param $xunSearchService
     */
    public function __construct(XunSearchUtils $xunSearchUtils, XunSearchService $xunSearchService)
    {
        $this->xunSearchUtils = $xunSearchUtils;
        $this->xunSearchService = $xunSearchService;
    }


    /**
     * @param Dispatcher $events
     */
    public function subscribe(Dispatcher $events)
    {
        // 添加帖子到搜索引擎索引
        $events->listen(PostWasPosted::class, [$this, "posted"]);

        // 更新帖子到搜索引擎索引
        $events->listen(PostWasRevised::class, [$this, "revised"]);

        // 隐藏帖子话题到搜索引擎索引
        $events->listen(PostWasHidden::class, [$this, "hidden"]);

        // 恢复帖子到搜索引擎索引
        $events->listen(PostWasRestored::class, [$this, "restored"]);

        // 修改话题到搜索引擎索引
        $events->listen(DiscussionWasRenamed::class, [$this, "discussionRenamed"]);

        // 隐藏话题到搜索引擎索引
        $events->listen(DiscussionWasHidden::class, [$this, "discussionHidden"]);

        // 恢复话题到搜索引擎索引
        $events->listen(DiscussionWasRestored::class, [$this, "discussionRestored"]);

        $events->listen(ConfigureApiRoutes::class,
            array($this, "registerApi"));
        $events->listen(ConfigureWebApp::class,
            array($this, "initView"));
    }

    // 添加后台Api
    function registerApi(ConfigureApiRoutes $event) {
        $event->get(
          "/xun/discussions",
          "xun.discussions.index",
            XunSearchController::class
        );
    }

    // 添加前台逻辑
    function initView(ConfigureWebApp $event) {
        if ($event->isForum()) {
            $event->addAssets(dirname(__DIR__).'/js/forum/dist/extension.js');
            $event->addBootstrapper('jjandxa/flarum-ext-chinese-search/main');
        }
    }

    // 添加帖子到索引
    function posted(PostWasPosted $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->addPostToIndex($event->post);
        }

    }

    // 修改帖子到索引
    function revised(PostWasRevised $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchUtils->getIndex()->update(XunSearchUtils::getDocument($event->post->discussion,
                $event->post, $event->post->discussion->comments_count));
        }

    }

    // 隐藏帖子到索引
    function hidden(PostWasHidden $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->deletePostToIndex($event->post);
        }

    }

    // 恢复帖子到索引
    function restored(PostWasRestored $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->addPostToIndex($event->post);
        }
    }

    // 话题修改名称到索引
    function discussionRenamed(DiscussionWasRenamed $event) {
        $this->xunSearchService->renameDiscussion($event->discussion);
    }

    // 话题隐藏到索引
    function discussionHidden(DiscussionWasHidden $event) {
        $this->xunSearchService->deleteDiscussion($event->discussion);
    }

    // 话题恢复到索引
    function discussionRestored(DiscussionWasRestored $event) {
        $this->xunSearchService->addDiscussion($event->discussion);
    }
}
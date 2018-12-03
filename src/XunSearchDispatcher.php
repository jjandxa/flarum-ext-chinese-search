<?php

namespace Plugin\XunSearch;

use Flarum\Event\ConfigureApiRoutes;
use Flarum\Frontend\Event\Rendering;
use Flarum\Discussion\Event\Hidden as DiscussionHidden;
use Flarum\Discussion\Event\Renamed;
use Flarum\Discussion\Event\Restored as DiscussionRestored;
use Flarum\Extension\Event\Enabled;
use Flarum\Post\Event\Hidden;
use Flarum\Post\Event\Posted;
use Flarum\Post\Event\Restored;
use Flarum\Post\Event\Revised;
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
        $events->listen(Posted::class, [$this, "posted"]);
        // 更新帖子到搜索引擎索引
        $events->listen(Revised::class, [$this, "revised"]);
        // 隐藏帖子话题到搜索引擎索引
        $events->listen(Hidden::class, [$this, "hidden"]);
        // 恢复帖子到搜索引擎索引
        $events->listen(Restored::class, [$this, "restored"]);
        // 修改话题到搜索引擎索引
        $events->listen(Renamed::class, [$this, "discussionRenamed"]);
        // 隐藏话题到搜索引擎索引
        $events->listen(DiscussionHidden::class, [$this, "discussionHidden"]);
        // 恢复话题到搜索引擎索引
        $events->listen(DiscussionRestored::class, [$this, "discussionRestored"]);
    }

    // 添加帖子到索引
    function posted(Posted $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->addPostToIndex($event->post);
        }
    }

    // 修改帖子到索引
    function revised(Revised $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchUtils->getIndex()->update($this->xunSearchUtils->getDocument($event->post->discussion,
                $event->post, $event->post->discussion->comments_count));
        }
    }

    // 隐藏帖子到索引
    function hidden(Hidden $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->deletePostToIndex($event->post);
        }
    }

    // 恢复帖子到索引
    function restored(Restored $event) {
        if ($event->post->type === "comment") {
            $this->xunSearchService->addPostToIndex($event->post);
        }
    }

    // 话题修改名称到索引
    function discussionRenamed(Renamed $event) {
        $this->xunSearchService->renameDiscussion($event->discussion);
    }

    // 话题隐藏到索引
    function discussionHidden(DiscussionHidden $event) {
        $this->xunSearchService->deleteDiscussion($event->discussion);
    }

    // 话题恢复到索引
    function discussionRestored(DiscussionRestored $event) {
        $this->xunSearchService->addDiscussion($event->discussion);
    }
}

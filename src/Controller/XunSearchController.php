<?php

namespace Plugin\XunSearch\Controller;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Plugin\XunSearch\Service\XunSearchService;
use Flarum\Discussion\Search\DiscussionSearcher;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;
use Flarum\Http\UrlGenerator;

class XunSearchController extends ListDiscussionsController
{

    private $searchService;

    /**
     * XunSearchController constructor.
     * @param XunSearchService $searchService
     * @param DiscussionSearcher $searcher
     * @param UrlGenerator $url
     */
    public function __construct(XunSearchService $searchService,
                                DiscussionSearcher $searcher, UrlGenerator $url)
    {
        parent::__construct($searcher, $url);
        $this->searchService = $searchService;
    }

    // 查询数据
    protected function data(ServerRequestInterface $request, Document $document)
    {
        // 关键词
        $query = array_get($this->extractFilter($request), 'q');
        // 分页数据
        $limit = $this->extractLimit($request);
        $offset = $this->extractOffset($request);
        // 排序
        $sort = $this->extractSort($request);

        $load = array_merge($this->extractInclude($request), ['state']);

        $results = $this->searchService->search($query, $limit, $offset, $sort);

        $document->addPaginationLinks(
            $this->url->to('api')->route('xun.discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $results->areMoreResults() ? null : 0
        );

        $results = $results->getResults()->load($load);

        if ($relations = array_intersect($load, ['firstPost', 'lastPost'])) {
            foreach ($results as $discussion) {
                foreach ($relations as $relation) {
                    if ($discussion->$relation) {
                        $discussion->$relation->discussion = $discussion;
                    }
                }
            }
        }

        return $results;
    }
}

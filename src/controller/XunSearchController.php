<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-12
 * Time: 上午10:47
 */

namespace Plugin\XunSearch\Controller;

use Flarum\Api\Controller\ListDiscussionsController;
use Flarum\Core\Discussion;
use Flarum\Core\Search\SearchResults;
use Plugin\XunSearch\XunSearchService;
use Psr\Http\Message\ServerRequestInterface;
use Tobscure\JsonApi\Document;

class XunSearchController extends ListDiscussionsController
{

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

        $result = XunSearchService::search($query, $limit, $offset, $sort);

        $document->addPaginationLinks(
            $this->url->toRoute('xun.discussions.index'),
            $request->getQueryParams(),
            $offset,
            $limit,
            $result->areMoreResults() ? null : 0
        );

        return $result->getResults();
    }

}
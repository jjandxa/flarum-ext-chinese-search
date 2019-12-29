<?php

namespace Plugin\XunSearch\Service;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Flarum\Search\SearchResults;
use Illuminate\Database\Eloquent\Collection;
use Plugin\XunSearch\Utils\XunSearchUtils;

class XunSearchService
{

    private $xunSearchUtils;

    /**
     * XunSearchService constructor.
     * @param $xunSearchUtils
     */
    public function __construct(XunSearchUtils $xunSearchUtils)
    {
        $this->xunSearchUtils = $xunSearchUtils;
    }


    function search($query, $limit, $offset, $sort) {
        $convertData = $this->convertDiscussion($query, $limit + 1, $offset, $sort);

        $discussions = new Collection();
        if (count($convertData) > 0) {
            $query = Discussion::query()->whereIn("id", $this->getDiscussionIds($convertData));

            if ($sort !== null) {
                // 最新回复
                if (array_key_exists("lastPostedAt", $sort)) {
                    $query = $query->orderBy("last_posted_at", $sort["lastPostedAt"]);
                }

                // 热门话题
                if (array_key_exists("commentCount", $sort)) {
                    $query = $query->orderBy("comment_count", $sort["commentCount"]);
                }

                // 近期话题
                if (array_key_exists("createdAt", $sort)) {
                    $query = $query->orderBy("created_at", $sort["createdAt"]);
                }

                // 历史话题
                if (array_key_exists("createdAt", $sort)) {
                    $query = $query->orderBy("created_at", $sort["createdAt"]);
                }

            }

            $discussions = $query->get();

            $this->loadRelevantPosts($discussions, $convertData);
        }

        $areMoreResults = $limit > 0 && count($convertData) > $limit;

        if ($areMoreResults) {
            $discussions->pop();
        }

        $result = new SearchResults($discussions, $areMoreResults);

        return $result;
    }

    function getDiscussionIds($convertData) {
        $temp = [];
        foreach ($convertData as $data) {
            array_push($temp, $data["id"]);
        }
        return $temp;
    }

    // 转换数据
    function convertDiscussion($query, $limit, $offset, $sort) {
        $tempData = [];
        // 搜索逻辑
        $search = $this->xunSearchUtils->getSearch();
        $search->setLimit($limit, $offset);

        // 折叠字段
        $search->setCollapse("discId");


        if ($sort !== null) {
            // 最新回复
            if (array_key_exists("lastPostedAt", $sort)) {
                $search->setSort("time", $sort["lastPostedAt"] === "desc" ? false : true);
            }

            // 热门话题
            if (array_key_exists("commentCount", $sort)) {
                $search->setSort("count", $sort["commentCount"] === "desc" ? false : true);
            }

            // 近期话题
            if (array_key_exists("createdAt", $sort)) {
                $search->setSort("discTime", $sort["createdAt"] === "desc" ? false : true);
            }

            // 历史话题
            if (array_key_exists("createdAt", $sort)) {
                $search->setSort("discTime", $sort["createdAt"] === "desc" ? false : true);
            }

        }

        try {
            $tempDiscData = $search->search("title:$query OR $query");
        } catch (\XSException $e) {
            $tempDiscData = [];
        }

        // 取消折叠
        $search->setCollapse(null);

        foreach ($tempDiscData as $item) {
            $discId = $item->getFields()["discId"];

            $search->setLimit(2, 0);
            $tempPostData =
                $search->search("discId:\"$discId\" $query");
            $tempData[$discId] = array("id" => $discId, "postIds" => array());

            foreach ($tempPostData as $post) {
                array_push($tempData[$discId]["postIds"], $post->getFields()["id"]);
            }
        }

        return $tempData;
    }

    // 处理帖子回复内容
    function loadRelevantPosts(Collection $discussions, $relevantPostIds) {
        $postIds = [];
        foreach ($relevantPostIds as $ids) {
            $postIds = array_merge($postIds, array_slice($ids["postIds"], 0, 2));
        }

        $posts = $postIds ? Post::query()->whereIn("id", $postIds)->get() : [];
        $temp = [];
        foreach ($posts as $post) {
            array_push($temp, $post);
        }

        foreach ($discussions as $discussion) {
            $discussion->relevantPosts = array_filter($temp, function ($post) use ($discussion) {
                return $post["discussion_id"] == $discussion->id;
            });
        }
    }

    // 根据话题id获取所有的帖子
    function getPostsByDiscussionId($discussionId) {
        return Post::query()->where("discussion_id", $discussionId)->where("type", "comment")->get();
    }

    // 添加帖子到索引
    function addPostToIndex(Post $post) {
        $posts = $this->getPostsByDiscussionId($post->discussion_id);
        // 打开索引
        $index = $this->xunSearchUtils->getIndex();
        // 打开缓冲区
        $index->openBuffer();

        foreach ($posts as $item) {
            // 需要新增的索引
            if ($item->id === $post->id) {
                $doc = $this->xunSearchUtils->getDocument($post->discussion,
                    $item, $posts->count());
                $index->add($doc);
            } else {
                // 需要更新的索引
                $doc = $this->xunSearchUtils->getDocument($post->discussion,
                    $item, $posts->count());
                $index->update($doc);
            }
        }
        $index->closeBuffer();
    }

    // 删除帖子到索引
    function deletePostToIndex(Post $post) {
        // 获取索引
        $index = $this->xunSearchUtils->getIndex();
        $index->openBuffer();

        // 删除当前记录
        $this->xunSearchUtils->getIndex()->del($post->id);
        // 更新其他记录的count
        $posts = $this->getPostsByDiscussionId($post->discussion_id);
        foreach ($posts as $item) {
            $doc = $this->xunSearchUtils->getDocument($post->discussion, $item, $posts->count());
            $index->update($doc);
        }
        $index->closeBuffer();
    }

    // 话题重命名
    function renameDiscussion(Discussion $discussion) {
        // 获取索引
        $index = $this->xunSearchUtils->getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = $this->getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $doc = $this->xunSearchUtils->getDocument($discussion, $item, $posts->count());
            $index->update($doc);
        }
        $index->closeBuffer();
    }

    // 删除话题
    function deleteDiscussion(Discussion $discussion) {
        // 获取索引
        $index = $this->xunSearchUtils->getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = $this->getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $index->del($item->id);
        }
        $index->closeBuffer();
    }

    // 添加话题
    function addDiscussion(Discussion $discussion) {
        // 获取索引
        $index = $this->xunSearchUtils->getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = $this->getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $doc = $this->xunSearchUtils->getDocument($discussion, $item, $posts->count());
            $index->add($doc);
        }
        $index->closeBuffer();
    }
}

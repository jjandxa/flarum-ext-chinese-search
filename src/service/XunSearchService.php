<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-14
 * Time: 上午12:51
 */

namespace Plugin\XunSearch;


use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Flarum\Core\Search\SearchResults;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class XunSearchService
{
    static function search($query, $limit, $offset, $sort) {

        $convertData = static::convertDiscussion($query, $limit + 1, $offset, $sort);

        $discussions = new Collection();
        if (count($convertData) > 0) {
            $query = Discussion::query()->
            whereIn("id", static::getDiscussionIds($convertData));

            if ($sort !== null) {
                // 最新回复
                if (array_key_exists("lastTime", $sort)) {
                    $query = $query->orderBy("last_time", $sort["lastTime"]);
                }

                // 热门话题
                if (array_key_exists("commentsCount", $sort)) {
                    $query = $query->orderBy("comments_count", $sort["commentsCount"]);
                }

                // 近期话题
                if (array_key_exists("startTime", $sort)) {
                    $query = $query->orderBy("start_time", $sort["startTime"]);
                }

                // 历史话题
                if (array_key_exists("startTime", $sort)) {
                    $query = $query->orderBy("start_time", $sort["startTime"]);
                }

            }

            $discussions = $query->get();

            static::loadRelevantPosts($discussions, $convertData);
        }

        $areMoreResults = $limit > 0 && count($convertData) > $limit;

        if ($areMoreResults) {
            $discussions->pop();
        }

        $result = new SearchResults($discussions, $areMoreResults);

        return $result;
    }

    static function getDiscussionIds($convertData) {
        $temp = [];
        foreach ($convertData as $data) {
            array_push($temp, $data["id"]);
        }
        return $temp;
    }

    // 转换数据
    static function convertDiscussion($query, $limit, $offset, $sort) {
        $tempData = [];
        // 搜索逻辑
        $search = XunSearchUtils::getSearch();
        $search->setLimit($limit, $offset);

        // 折叠字段
        $search->setCollapse("discId");


        if ($sort !== null) {
            // 最新回复
            if (array_key_exists("lastTime", $sort)) {
                $search->setSort("time", $sort["lastTime"] === "desc" ? false : true);
            }

            // 热门话题
            if (array_key_exists("commentsCount", $sort)) {
                $search->setSort("count", $sort["commentsCount"] === "desc" ? false : true);
            }

            // 近期话题
            if (array_key_exists("startTime", $sort)) {
                $search->setSort("discTime", $sort["startTime"] === "desc" ? false : true);
            }

            // 历史话题
            if (array_key_exists("startTime", $sort)) {
                $search->setSort("discTime", $sort["startTime"] === "desc" ? false : true);
            }

        }

        $tempDiscData = $search->search($query);

        // 取消折叠
        $search->setCollapse(null);

        foreach ($tempDiscData as $item) {
            $discId = $item->getFields()["discId"];

            $search->setLimit(2, 0);
            $tempPostData =
                $search->search("discId:\"".$item->getFields()["discId"]."\" ".$query);
            $tempData[$discId] = array("id" => $discId, "postIds" => array());

            foreach ($tempPostData as $post) {
                array_push($tempData[$discId]["postIds"], $post->getFields()["id"]);
            }
        }

        return $tempData;
    }

    // 处理帖子回复内容
    static function loadRelevantPosts(Collection $discussions, $relevantPostIds)
    {
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
    static function getPostsByDiscussionId($discussionId) {
        return Post::query()->where("discussion_id", $discussionId)->get();
    }

    // 添加帖子到索引
    static function addPostToIndex(Post $post) {
        $posts = static::getPostsByDiscussionId($post->discussion_id);
        // 打开索引
        $index = XunSearchUtils::getIndex();
        // 打开缓冲区
        $index->openBuffer();

        foreach ($posts as $item) {
            // 需要新增的索引
            if ($item->id === $post->id) {
                $doc = XunSearchUtils::getDocument($post->discussion,
                    $item, $posts->count());
                $index->add($doc);
            } else {
                // 需要更新的索引
                $doc = XunSearchUtils::getDocument($post->discussion,
                    $item, $posts->count());
                $index->update($doc);
            }
        }
        $index->closeBuffer();
    }

    // 删除帖子到索引
    static function deletePostToIndex(Post $post) {
        // 获取索引
        $index = XunSearchUtils::getIndex();
        $index->openBuffer();

        // 删除当前记录
        XunSearchUtils::getIndex()->del($post->id);
        // 更新其他记录的count
        $posts = static::getPostsByDiscussionId($post->discussion_id);
        foreach ($posts as $item) {
            $doc = XunSearchUtils::getDocument($post->discussion, $item, $posts->count());
            $index->update($doc);
        }
        $index->closeBuffer();
    }

    // 话题重命名
    static function renameDiscussion(Discussion $discussion) {
        // 获取索引
        $index = XunSearchUtils::getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = static::getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $doc = XunSearchUtils::getDocument($discussion, $item, $posts->count());
            $index->update($doc);
        }
        $index->closeBuffer();
    }

    // 删除话题
    static function deleteDiscussion(Discussion $discussion) {
        // 获取索引
        $index = XunSearchUtils::getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = static::getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $index->del($item->id);
        }
        $index->closeBuffer();
    }

    // 删除话题
    static function addDiscussion(Discussion $discussion) {
        // 获取索引
        $index = XunSearchUtils::getIndex();
        $index->openBuffer();

        // 更新所有记录
        $posts = static::getPostsByDiscussionId($discussion->id);
        foreach ($posts as $item) {
            $doc = XunSearchUtils::getDocument($discussion, $item, $posts->count());
            $index->add($doc);
        }
        $index->closeBuffer();
    }
}
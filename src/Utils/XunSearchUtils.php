<?php
/**
 * Created by PhpStorm.
 * User: aixiaoai
 * Date: 17-10-12
 * Time: 上午11:26
 */

namespace Plugin\XunSearch\Utils;

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;

class XunSearchUtils
{
    function getXuSearch(): \XS {
        return new \XS(dirname(dirname(__DIR__))."/app.ini");
    }

    // 获取搜索引擎索引
    function getIndex(): \XSIndex {
        return $this->getXuSearch()->index;
    }

    // 获取搜索
    function getSearch(): \XSSearch {
        return $this->getXuSearch()->search;
    }

    // 获取文档
    function getDocument(Discussion $discussion, Post $post, $count): \XSDocument {
        $data = array(
            "id" => $post->id,
            "discId" => $discussion->id,
            "title" => $discussion->title,
            "content" => $post->content,
            "time" => strtotime($post->created_at),
            "discTime" => strtotime($post->discussion->created_at),
            "count" => $count
        );
        $doc = new \XSDocument;
        $doc->setFields($data);
        return $doc;
    }
}

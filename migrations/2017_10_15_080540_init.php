<?php

use Flarum\Discussion\Discussion;
use Flarum\Post\Post;
use Illuminate\Database\Schema\Builder;
use Plugin\XunSearch\Utils\XunSearchUtils;

@set_time_limit(0);
@ini_set('max_execution_time', 0);
$xunSearchUtils = new XunSearchUtils;

return [
    'up' => function (Builder $schema) use ($xunSearchUtils) {
        $index = $xunSearchUtils->getIndex();
        $index->clean();
        $index->openBuffer(8);

        $discussions = Discussion::query()->where("hidden_at", null)->get();
        foreach ($discussions as $discussion) {
            $posts = Post::query()->where("hidden_at", null)
                ->where("discussion_id", $discussion->id)
                ->where("type", "comment")->get();

            foreach ($posts as $post) {
                $doc = $xunSearchUtils->getDocument($discussion, $post, $posts->count());
                $index->add($doc);
            }
        }

        $index->closeBuffer();
    },
    'down' => function (Builder $schema) use ($xunSearchUtils) {
        $index = $xunSearchUtils->getIndex();
        $index->clean();
    }
];

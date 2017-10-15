<?php


use Flarum\Core\Discussion;
use Flarum\Core\Post;
use Illuminate\Database\Schema\Builder;
use Plugin\XunSearch\Utils\XunSearchUtils;

return [
    'up' => function (Builder $schema) {
        $index = XunSearchUtils::getIndex();
        $index->clean();

        $index->openBuffer(8);

        $discussions = Discussion::query()->where("hide_time", null)->get();

        foreach ($discussions as $discussion) {
            $posts = Post::query()->where("hide_time", null)
                ->where("discussion_id", $discussion->id)
                ->where("type", "comment")->get();

            foreach ($posts as $post) {
                $doc = XunSearchUtils::getDocument($discussion, $post, $posts->count());
                echo $discussion->title."->".$post->id."===";
                $index->add($doc);
            }


        }



        $index->closeBuffer();
    },
    'down' => function (Builder $schema) {
        $index = XunSearchUtils::getIndex();
        $index->clean();
    }
];

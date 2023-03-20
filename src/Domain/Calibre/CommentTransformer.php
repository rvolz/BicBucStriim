<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class CommentTransformer - produces a view of a book comment
 * @package BicBucStriim
 */
class CommentTransformer
{
    public function transform($comment)
    {
        return [
            'id' => (int)$comment->id,
            'text' => $comment->text,
        ];
    }
}

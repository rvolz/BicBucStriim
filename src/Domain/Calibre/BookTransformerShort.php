<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class BookTransformerShort - produces a reduced view of a book, for lists etc
 * @package BicBucStriim
 */
class BookTransformerShort extends TransformerAbstract
{
    public function transform($book)
    {
        return [
            'id' => (int)$book->id,
            'title' => $book->sort,
            'pubdate' => $book->pubdate,
            'author' => $book->author_sort,
            'additional' => $book->addInfo,
            'links' => [
                'rel' => 'self',
                'uri' => '/titles/' . $book->id,
            ]
        ];
    }
}
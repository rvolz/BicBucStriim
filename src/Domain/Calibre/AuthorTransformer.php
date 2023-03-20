<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class AuthorTransformer - produces a view of an author
 * @package BicBucStriim
 */
class AuthorTransformer extends TransformerAbstract
{
    /**
     * List of resources that could be included
     * @var array
     */
    protected $availableIncludes = [];

    // TODO include related information, so that authors can navigate to books etc
    public function transform($author)
    {
        return [
            'id' => (int)$author->id,
            'name' => $author->name,
            'sort' => $author->sort,
            //'link' => $author->link,
            'links' => [
                'rel' => 'self',
                'uri' => '/authors/' . $author->id,
            ],
        ];
    }
}

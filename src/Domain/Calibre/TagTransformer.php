<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class TagTransformer - produces a view of a tag
 * @package BicBucStriim
 */
class TagTransformer extends TransformerAbstract
{
    // TODO include related information, so that tags can navigate to books etc
    /**
     * List of resources that could be included
     */
    protected array $availableIncludes = [];

    public function transform($tag)
    {
        return [
            'id' => (int)$tag->id,
            'name' => $tag->name,
            'links' => [
                'rel' => 'self',
                'uri' => '/tags/' . $tag->id,
            ],
        ];
    }
}

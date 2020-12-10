<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class SeriesTransformer - produces a view of a book series
 * @package BicBucStriim
 */
class SeriesTransformer extends TransformerAbstract
{
    // TODO include related information, so that series can navigate to books etc
    /**
     * List of resources that could be included
     * @var array
     */
    protected $availableIncludes = [];

    public function transform($series)
    {
        return [
            'id' => (int)$series->id,
            'name' => $series->name,
            'sort' => $series->sort,
            'links' => [
                'rel' => 'self',
                'uri' => '/series/' . $series->id,
            ]
        ];
    }
}
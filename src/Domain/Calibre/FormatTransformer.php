<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class FormatTransformer - produces a view of a book format
 * @package BicBucStriim
 */
class FormatTransformer extends TransformerAbstract
{
    public function transform($format)
    {
        return [
            'id' => (int)$format->id,
            'format' => $format->format,
            'size' => $format->uncompressed_size,
        ];
    }
}
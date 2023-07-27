<?php
/**
 * This file is part of BicBucStriim, a web frontend for Calibre.
 */

namespace App\Domain\Calibre;

use League\Fractal\TransformerAbstract;

/**
 * Class IdentifierTransformer - produces a view of a book identifier like a ISBN
 * @package BicBucStriim
 */
class IdentifierTransformer extends TransformerAbstract
{
    public function transform($identifier)
    {
        return [
            'id' => (int)$identifier->id,
            'type' => $identifier->type,
            'value' => $identifier->val,
        ];
    }
}

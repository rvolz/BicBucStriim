<?php
declare(strict_types=1);

namespace App\Application\Actions\Statics;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Psr7\Stream;

/**
 * Return a PNG thumbnail of an author. An ETag for client caching is calculated.
 * If there is no thumbnail, return 404.
 * @package App\Application\Actions\Statics
 */
class ViewAuthorThumbnailAction extends StaticsAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $thumbnail = $this->bbs->getAuthorThumbnail($id);
        if (!empty($thumbnail)) {
            $fh = fopen($thumbnail, 'rb');
            $stream = new Stream($fh);
            return $this->respondWithArtefact($stream, $this->calcEtag($thumbnail), 'image/png;base64');
        } else {
            throw new DomainRecordNotFoundException();
        }
    }
}
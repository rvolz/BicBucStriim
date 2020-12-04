<?php
declare(strict_types=1);

namespace App\Application\Actions\Statics;


use App\Domain\Calibre\CoverNotFoundException;
use BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Psr7\Stream;

/**
 * Return a PNG thumbnail of the book's cover. An ETag for client caching is calculated.
 * If there is no cover or thumbnail, return 404.
 * @package App\Application\Actions\Statics
 */
class ViewThumbnailAction extends StaticsAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        if ($this->bbs->isTitleThumbnailAvailable($id)) {
            $thumbnail = $this->bbs->getExistingTitleThumbnail($id);
        } else {
            $book = $this->calibre->title($id);
            if ($book->has_cover) {
                $clipped = $this->config[AppConstants::THUMB_GEN_CLIPPED];
                $cover = $this->calibre->titleCover($id);
                $thumbnail = $this->bbs->titleThumbnail($id, $cover, $clipped);
            } else {
                // TODO send default thumbnail if there is none?
                throw new CoverNotFoundException();
            }
        }
        $fh = fopen($thumbnail, 'rb');
        $stream = new Stream($fh);
        $this->respondWithArtefact($stream, calcEtag($thumbnail), 'image/png;base64');
    }
}
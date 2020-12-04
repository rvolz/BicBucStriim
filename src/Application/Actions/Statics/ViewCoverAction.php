<?php
declare(strict_types=1);

namespace App\Application\Actions\Statics;


use App\Domain\Calibre\CoverNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use GuzzleHttp\Psr7\Stream;

/**
 * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
 * If there is no cover, return 404. An ETag for client caching is calculated.
 * @package App\Application\Actions\Statics
 */
class ViewCoverAction extends StaticsAction
{
    /**
     * {@inheritdoc}
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $book = $this->calibre->title($id);
        if ($book->has_cover) {
            $cover = $this->calibre->titleCover($id);
            $fh = fopen($cover, 'rb');
            $stream = new Stream($fh);
            return $this->respondWithArtefact($stream, $this->calcEtag($cover), 'image/jpeg;base64');
        } else {
            // TODO send default cover if there is none?
            throw new CoverNotFoundException();
        }
    }
}
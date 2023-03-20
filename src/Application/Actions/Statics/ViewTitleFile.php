<?php

namespace App\Application\Actions\Statics;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\TitleNotFoundException;
use App\Domain\Calibre\Utilities;
use App\Domain\Epub\MetadataEpub;
use GuzzleHttp\Psr7\LazyOpenStream;
use Psr\Http\Message\ResponseInterface as Response;

class ViewTitleFile extends StaticsAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $format = (string) $this->resolveArg('format');
        $fname = (string) $this->resolveArg('file');
        $details = $this->calibre->titleDetails($this->l10n->user_lang, $id);
        // for people trying to circumvent filtering by direct access
        if ($this->title_forbidden($this->user->getLanguages(), $this->user->getTags(), $details)) {
            $this->logger->warning("book: requested book not allowed for user: " . $id);
            throw new TitleNotFoundException();
        }

        $file = "{$fname}.{$format}";
        $real_bookpath = $this->calibre->titleFile($id, $file);
        $contentType = Utilities::titleMimeType($real_bookpath);
        $this->logger->info("book download by " . $this->user->getUsername() . " for " . $real_bookpath .
                " with metadata update = " . $this->config[AppConstants::METADATA_UPDATE]);

        if ($contentType == Utilities::MIME_EPUB && $this->config[AppConstants::METADATA_UPDATE]) {
            if ($details['book']->has_cover == 1) {
                $cover = $this->calibre->titleCover($id);
            } else {
                $cover = null;
            }
            // If an EPUB update the metadata
            $mdep = new MetadataEpub($real_bookpath);
            $mdep->updateMetadata($details, $cover);
            $bookpath = $mdep->getUpdatedFile();
        } else {
            // Else send the file as is
            $bookpath = $real_bookpath;
        }
        // uses Guzzle LazyStream for files
        // see https://www.slimframework.com/docs/v4/objects/response.html#the-response-body
        $stream = new LazyOpenStream($bookpath, 'rb');
        return $this->respondWithDownload($stream, $file, $contentType);
    }
}

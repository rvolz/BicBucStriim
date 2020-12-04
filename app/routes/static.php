<?php

$app->group('/static', function () {
    /**
     * Return the cover for the book with ID. Calibre generates only JPEGs, so we always return a JPEG.
     * If there is no cover, return 404. An ETag for client caching is calculated.
     */
    $this->get('/covers/{id}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $id = $args['id'];
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warn('cover: invalid title id ' . $id);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $book = $this->calibre->title($id);
        if (is_null($book)) {
            $this->logger->debug('cover: book not found: ' . $id);
            return $response->withStatus(404)->write('Book for cover not found');
        }
        if ($book->has_cover) {
            $cover = $this->calibre->titleCover($id);
            $responseE = $this->cache->withEtag($response, calcEtag($cover));
            $fh = fopen($cover, 'rb');
            $stream = new \Slim\Http\Stream($fh); // create a stream instance for the response body
            return $responseE->withStatus(200)
                ->withHeader('Content-Type', 'image/jpeg;base64')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Content-Length', filesize($cover))
                ->withBody($stream); // all stream contents will be sent to the response
        } else {
            return $response->withStatus(404)->write('No cover');
        }
    });

    // TODO HEAD impl covers?
    //$this->head('/cover/{id}/', check_thumbnail($request, $response, $args));


    /**
     * Return a PNG thumbnail of the book's cover. An ETag for client caching is calculated.
     * If there is no cover, return 404.
     */
    $this->get('/thumbnails/{id}/', function (Psr\Http\Message\ServerRequestInterface $request, $response, $args) {
        $id = $args['id'];
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warn('thumbnail: invalid title id ' . $id);
            return $response->withStatus(400)->write('Bad parameter');
        }
        $thumb = '';
        $error = '';
        if ($this->bbs->isTitleThumbnailAvailable($id)) {
            $thumb = $this->bbs->getExistingTitleThumbnail($id);
        } else {
            $book = $this->calibre->title($id);
            if (is_null($book)) {
                $this->logger->debug('thumbnail: book not found: ' . $id);
                $error = 'Book not found';
            } else {
                if ($book->has_cover) {
                    $clipped = $this->config[\BicBucStriim\AppConstants::THUMB_GEN_CLIPPED];
                    $cover = $this->calibre->titleCover($id);
                    $thumb = $this->bbs->titleThumbnail($id, $cover, $clipped);
                } else {
                    $error = 'Book has no cover';
                }
            }
        }
        if ($thumb != '') {
            $responseE = $this->cache->withEtag($response, calcEtag($thumb));
            $fh = fopen($thumb, 'rb');
            $stream = new \Slim\Http\Stream($fh);
            return $responseE->withHeader('Content-Type', 'image/png;base64')
                ->withHeader('Content-Transfer-Encoding', 'binary')
                ->withHeader('Content-Length', filesize($thumb))
                ->withBody($stream);
        } else {
            return $response->withStatus(404)->write($error);
        }
    });

    // TODO HEAD impl thumbnails?
});

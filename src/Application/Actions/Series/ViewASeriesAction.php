<?php

namespace App\Application\Actions\Series;

use App\Domain\BicBucStriim\AppConstants;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewASeriesAction extends SeriesAction
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int) $this->resolveQueryParam('index');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewASeriesAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $filter = $this->getFilter();
        $tl = $this->calibre->seriesDetailsSlice(
            $this->l10n->user_lang,
            $id,
            $index,
            $this->config[AppConstants::PAGE_SIZE],
            $filter
        );
        if (empty($tl)) {
            $msg = sprintf("ViewASeriesAction: no series data found for id %d", $id);
            $this->logger->error($msg);
            throw new DomainRecordNotFoundException($msg);
        }

        $books = array_map([$this, 'checkThumbnail'], $tl['entries']);
        return $this->respondWithPage('series_detail.twig', [
            'page' => $this->mkPage($this->getMessageString('series_details'), 5, 2),
            'url' => 'series/' . $id,
            'series' => $tl['series'],
            'books' => $books,
            'curpage' => $tl['page'],
            'pages' => $tl['pages']]);
    }
}

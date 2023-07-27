<?php

namespace App\Application\Actions\Series;

use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewSeriesAction extends SeriesAction
{
    /**
     * @inheritdoc
     */
    protected function action(): Response
    {
        $index = 0;
        if ($this->hasQueryParam('index')) {
            $index = (int) $this->resolveQueryParam('index');
        }
        // parameter checking
        if ($index < 0) {
            $this->logger->warning('ViewSeriesAction: invalid page id ' . $index);
            throw new HttpBadRequestException($this->request);
        }

        $search = '';
        if ($this->hasQueryParam('search')) {
            $search = $this->resolveQueryParam('search');
            $tl = $this->calibre->seriesSlice($index, $this->config[AppConstants::PAGE_SIZE], trim($search));
        } else {
            $tl = $this->calibre->seriesSlice($index, $this->config[AppConstants::PAGE_SIZE]);
        }

        return $this->respondWithPage('series.html', [
            'page' => $this->mkPage($this->getMessageString('series'), 5, 1),
            'url' => 'series',
            'series' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search]);
    }
}

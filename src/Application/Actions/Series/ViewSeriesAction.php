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
        $index = $this->getIndexParam(__CLASS__);
        $jumpTarget = $this->getJumpTargetParam(__CLASS__);
        $search = $this->checkAndGenSearchOptions();
        $pg_size = $this->config[AppConstants::PAGE_SIZE];

        // Jumping overrides normal navigation
        if (!empty($jumpTarget)) {
            $pos = $this->calibre->calcInitialPos('name', 'series', $jumpTarget, $search);
            $index = $pos / $pg_size;
        }

        $tl = $this->calibre->seriesSlice($index, $this->config[AppConstants::PAGE_SIZE], $search);

        return $this->respondWithPage('series.twig', array(
            'page' => $this->mkPage($this->getMessageString('series'), 5, 1),
            'url' => 'series',
            'series' => $tl['entries'],
            'curpage' => $tl['page'],
            'pages' => $tl['pages'],
            'search' => $search->getSearchTerm(),
            'search_options' => $search->toMask()));
    }
}
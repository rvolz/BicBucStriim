<?php

namespace App\Application\Actions\Series;

use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;

class ViewSeriesInitialsAction extends SeriesAction
{
    /**
     * Returns a Json document with a sorted list of author initials
     */
    protected function action(): Response
    {
        $search = $this->checkAndGenSearchOptions();
        $list = $this->calibre->seriesInitials($search);
        $appData = new ActionPayload(200, array_map(function ($item) {
            return $item->initial;
        }, $list));
        return $this->respondWithData($appData);
    }
}

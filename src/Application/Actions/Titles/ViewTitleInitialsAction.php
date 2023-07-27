<?php

namespace App\Application\Actions\Titles;

use App\Application\Actions\ActionPayload;
use App\Domain\Calibre\SearchOptions;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTitleInitialsAction extends TitlesAction
{
    /**
     * Returns a Json document with a sorted list of title initials
     */
    protected function action(): Response
    {
        $search = $this->checkAndGenSearchOptions();
        $list = $this->calibre->titlesInitials($search);
        $appData = new ActionPayload(200, array_map(function ($item) {
            return $item->initial;
        }, $list));
        return $this->respondWithData($appData);
    }
}

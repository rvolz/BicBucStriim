<?php


namespace App\Application\Actions\Titles;


use App\Application\Actions\ActionPayload;
use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\SearchOptions;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewTitleYearsAction extends TitlesAction
{

    /**
     * Returns a Json document with a sorted list of publication years
     */
    protected function action(): Response
    {
        $search = $this->checkAndGenSearchOptions();
        $timeSort = $this->config[AppConstants::TITLE_TIME_SORT];
        $list = $this->calibre->titlesYears($search, $timeSort);
        $appData = new ActionPayload(200, array_map(function($item) { return $item->initial;}, $list));
        return $this->respondWithData($appData);
    }
}
<?php


namespace App\Application\Actions\Search;


use App\Domain\BicBucStriim\AppConstants;
use App\Domain\Calibre\SearchOptions;
use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewSearchAction extends SearchAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {


        return $this->respondWithPage('global_search.twig', array(
            'page' => $this->mkPage($this->getMessageString('pagination_search'), 0),
            'search' => '',
            'case' => 0,
            'transliteration' => $this->config[AppConstants::SEARCH_ASCII_TRANSLITERATION]
        ));
    }
}
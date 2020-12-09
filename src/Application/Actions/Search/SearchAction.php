<?php


namespace App\Application\Actions\Search;


use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

abstract class SearchAction extends \App\Application\Actions\CalibreHtmlAction
{

}
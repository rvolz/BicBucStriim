<?php

declare(strict_types=1);

namespace App\Application\Actions\Start;

use App\Application\Actions\CalibreHtmlAction;
use App\Domain\BicBucStriim\AppConstants;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpInternalServerErrorException;

class ViewLast30Action extends CalibreHtmlAction
{
    /**
     * @inheritDoc
     * @throws HttpInternalServerErrorException
     */
    protected function action(): Response
    {
        $filter = $this->getFilter();
        $books1 = $this->calibre->last30Books($this->l10n->user_lang, $this->config[AppConstants::PAGE_SIZE], $filter);
        $books = array_map([$this,'checkThumbnail'], $books1);
        $stats = $this->calibre->libraryStats($filter);
        return $this->respondWithPage('index_last30.html', [
            'page' => $this->mkPage($this->getMessageString('dl30'), 1, 1),
            'books' => $books,
            'stats' => $stats]);
    }
}

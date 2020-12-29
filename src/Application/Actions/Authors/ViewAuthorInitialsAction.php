<?php


namespace App\Application\Actions\Authors;


use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;

class ViewAuthorInitialsAction extends AuthorsAction
{

    /**
     * Returns a Json document with a sorted list of author initials
     */
    protected function action(): Response
    {
        $search = $this->checkAndGenSearchOptions();
        $list = $this->calibre->authorsInitials($search);
        $appData = new ActionPayload(200, array_map(function($item) { return $item->initial;}, $list));
        return $this->respondWithData($appData);
    }
}
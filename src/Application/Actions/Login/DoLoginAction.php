<?php

declare(strict_types=1);

namespace App\Application\Actions\Login;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class DoLoginAction extends LoginAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        if ($this->user->isValid()) {
            return $this->response
                ->withHeader('Location', ($_ENV['BBS_BASE_PATH'] ?? '') . '/')
                ->withStatus(302);
            /*
             * TODO use urlFor for redirect
             * https://stackoverflow.com/questions/23404355/how-to-use-slim-redirect
             * use Slim\Routing\RouteContext;

$routeParser = RouteContext::fromRequest($request)->getRouteParser();
$url = $routeParser->urlFor('login');

return $response->withHeader('Location', $url);

             */
        } else {
            // TODO add error message
            return $this->respondWithPage('login.twig', [
                'page' => $this->mkPage($this->getMessageString('login')),
                'flash' => ['error' => $this->getMessageString('invalid_password')],
            ]);
        }
    }
}

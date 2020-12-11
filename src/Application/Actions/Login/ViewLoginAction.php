<?php
declare(strict_types=1);

namespace App\Application\Actions\Login;


use Psr\Http\Message\ResponseInterface as Response;

class ViewLoginAction extends LoginAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        return $this->respondWithPage('login.twig', array(
            'page' => $this->mkPage($this->getMessageString('login'))
        ));
    }
}
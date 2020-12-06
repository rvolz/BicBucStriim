<?php


namespace App\Application\Actions\Login;


use Psr\Http\Message\ResponseInterface as Response;

class ViewLogoutAction extends LoginAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        return $this->respondWithPage('logout.html', array(
            'page' => $this->mkPage($this->getMessageString('login'))
        ));
    }
}
<?php


namespace App\Application\Actions\Login;


use Psr\Http\Message\ResponseInterface as Response;

class ViewLoginAction extends LoginAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $this->logger->alert("ViewLogin reached");
        return $this->respondWithPage('login.html', array(
            'page' => $this->mkPage($this->getMessageString('login'))
        ));
    }
}
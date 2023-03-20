<?php

namespace App\Application\Actions\Admin;

use App\Domain\DomainException\DomainRecordNotFoundException;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ViewUsersAction extends AdminAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $users = $this->bbs->users();
        return $this->respondWithPage('admin_users.html', [
            'page' => $this->mkPage($this->getMessageString('admin_users'), 0, 2),
            'users' => $users,
            'isadmin' => $this->is_admin()]);
    }
}

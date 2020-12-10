<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\ActionPayload;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;

class CreateUserAction extends AdminAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $user_data = $this->request->getParsedBody();
        $this->logger->debug('admin_add_user: ' . var_export($user_data, true));
        try {
            $user = $this->bbs->addUser($user_data['username'], $user_data['password']);
        } catch (Exception $e) {
            $this->logger->error('admin_add_user: error for adding user ' . var_export($user_data, true));
            $this->logger->error('admin_add_user: exception ' . $e->getMessage());
            $user = null;
        }
        if (isset($user)) {
            $ap = new ActionPayload(200, array(
                    'user' => $user->getProperties(),
                    'msg' => $this->getMessageString('admin_modified')
                )
            );
        } else {
            $ap = new ActionPayload(500, array(
                    'msg' => $this->getMessageString('admin_modify_error')
                )
            );
        }
        return $this->respondWithData($ap);
    }
}
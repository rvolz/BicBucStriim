<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\ActionPayload;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class ChangeUserAction extends AdminAction
{

    /**
     * Change user data and return it
     */
    protected function action(): Response
    {
        if (!$this->is_admin())
            return $this->refuseNonAdmin();
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warning('admin_modify_user: invalid user id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $user_data = $this->request->getParsedBody();
        $this->logger->debug('admin_modify_user: ' . var_export($user_data, true));
        $user = $this->bbs->changeUser($id, $user_data['password'],
            $user_data['languages'], $user_data['tags'], $user_data['role']);
        $this->logger->debug('admin_modify_user: ' . var_export($user, true));
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
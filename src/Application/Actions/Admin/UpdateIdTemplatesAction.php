<?php

namespace App\Application\Actions\Admin;

use App\Application\Actions\ActionPayload;
use Exception;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

class UpdateIdTemplatesAction extends AdminAction
{
    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!preg_match('/^\w+$/u', $id)) {
            $this->logger->warning('admin_modify_idtemplate: invalid template id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $template_data = $this->request->getParsedBody();
        $this->logger->debug('admin_modify_idtemplate: ' . var_export($template_data, true));
        try {
            $template = $this->bbs->idTemplate($id);
            if (is_null($template)) {
                $ntemplate = $this->bbs->addIdTemplate($id, $template_data['url'], $template_data['label']);
            } else {
                $ntemplate = $this->bbs->changeIdTemplate($id, $template_data['url'], $template_data['label']);
            }
        } catch (Exception $e) {
            $this->logger->error('admin_modify_idtemplate: error while adding template' . var_export($template_data, true));
            $this->logger->error('admin_modify_idtemplate: exception ' . $e->getMessage());
            $ntemplate = null;
        }
        if (!is_null($ntemplate)) {
            $ap = new ActionPayload(200, [
                    'template' => $ntemplate->getProperties(),
                    'msg' => $this->getMessageString('admin_modified'),
                ]);
        } else {
            $ap = new ActionPayload(500, [
                'msg' => $this->getMessageString('admin_modify_error'),
            ]);
        }
        return $this->respondWithData($ap);
    }
}

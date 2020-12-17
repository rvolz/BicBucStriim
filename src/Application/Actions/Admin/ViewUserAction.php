<?php


namespace App\Application\Actions\Admin;


use App\Application\Actions\CalibreHtmlAction;
use App\Domain\Calibre\Language;
use App\Domain\Calibre\Tag;
use Psr\Http\Message\ResponseInterface as Response;
use Slim\Exception\HttpBadRequestException;

/**
 * Class ViewUserAction needs Calibre therefore it extends a different class.
 * @package App\Application\Actions\Admin
 */
class ViewUserAction extends CalibreHtmlAction
{

    /**
     * @inheritDoc
     */
    protected function action(): Response
    {
        $id = (int) $this->resolveArg('id');
        // parameter checking
        if (!is_numeric($id)) {
            $this->logger->warning('admin_get_user: invalid user id ' . $id);
            throw new HttpBadRequestException($this->request);
        }

        $user = $this->bbs->user($id);
        $languages = $this->calibre->languages();
        foreach ($languages as $language) {
            $language->key = $language->lang_code;
        }
        $nl = new Language();
        $nl->lang_code = $this->getMessageString('admin_no_selection');
        $nl->key = '';
        array_unshift($languages, $nl);
        $tags = $this->calibre->tags();
        foreach ($tags as $tag) {
            $tag->key = $tag->name;
        }
        $nt = new Tag();
        $nt->name = $this->getMessageString('admin_no_selection');
        $nt->key = '';
        array_unshift($tags, $nt);
        $this->logger->debug('admin_get_user: ' . var_export($user, true));
        return $this->respondWithPage('admin_user.twig', array(
            'page' => $this->mkPage($this->getMessageString('admin_users'), 0, 3),
            'user' => $user,
            'languages' => $languages,
            'tags' => $tags,
            'isadmin' => $this->is_admin()));
    }
}
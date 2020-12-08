<?php
declare(strict_types=1);

// Register routes
use App\Application\Actions\Admin\ChangeUserAction;
use App\Application\Actions\Admin\DeleteIdTemplatesAction;
use App\Application\Actions\Admin\UpdateConfigurationAction;
use App\Application\Actions\Admin\UpdateIdTemplatesAction;
use App\Application\Actions\Admin\ViewAdminAction;
use App\Application\Actions\Admin\ViewConfigurationAction;
use App\Application\Actions\Admin\ViewIdTemplatesAction;
use App\Application\Actions\Admin\ViewUserAction;
use App\Application\Actions\Admin\ViewUsersAction;
use App\Application\Actions\Admin\ViewVersionCheckAction;
use App\Application\Actions\Authors\CreateAuthorLinkAction;
use App\Application\Actions\Authors\CreateAuthorThumbnailAction;
use App\Application\Actions\Authors\DeleteAuthorLinkAction;
use App\Application\Actions\Authors\DeleteAuthorThumbnailAction;
use App\Application\Actions\Authors\ViewAuthorAction;
use App\Application\Actions\Authors\ViewAuthorsAction;
use App\Application\Actions\Login\DoLoginAction;
use App\Application\Actions\Login\ViewLoginAction;
use App\Application\Actions\Login\ViewLogoutAction;
use App\Application\Actions\Series\ViewASeriesAction;
use App\Application\Actions\Series\ViewSeriesAction;
use App\Application\Actions\Start\ViewLast30Action;
use App\Application\Actions\Statics\ViewAuthorThumbnailAction;
use App\Application\Actions\Statics\ViewCoverAction;
use App\Application\Actions\Statics\ViewTitleThumbnailAction;
use App\Application\Actions\Statics\ViewTitleFile;
use App\Application\Actions\Tags\ViewTagAction;
use App\Application\Actions\Tags\ViewTagsAction;
use App\Application\Actions\Titles\ViewTitleAction;
use App\Application\Actions\Titles\ViewTitlesAction;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', ViewLast30Action::class)->setName('start');
    $app->get('/login/', ViewLoginAction::class);
    $app->post('/login/', DoLoginAction::class);
    $app->get('/logout/', ViewLogoutAction::class);
    $app->group('/admin', function (Group $group) {
        $group->get('/', ViewAdminAction::class);
        $group->get('/configuration/', ViewConfigurationAction::class);
        $group->post('/configuration/', UpdateConfigurationAction::class);
        $group->get('/idtemplates/', ViewIdTemplatesAction::class);
        $group->put('/idtemplates/{id}/', UpdateIdTemplatesAction::class);
        $group->delete('/idtemplates/{id}/', DeleteIdTemplatesAction::class);
        $group->get('/mail/', ViewConfigurationAction::class);
        $group->post('/mail/', UpdateConfigurationAction::class);
        $group->get('/users/', ViewUsersAction::class);
        $group->get('/users/{id}/', ViewUserAction::class);
        $group->put('/users/{id}/', ChangeUserAction::class);
        $group->delete('/users/{id}/', DeleteIdTemplatesAction::class);
        $group->get('/version/', ViewVersionCheckAction::class);
    });
    $app->group('/authors', function (Group $group) {
        $group->get('/', ViewAuthorsAction::class);
        $group->get('/{id}/', ViewAuthorAction::class);
        $group->post('/{id}/thumbnail/', CreateAuthorThumbnailAction::class);
        $group->delete('/{id}/thumbnail/', DeleteAuthorThumbnailAction::class);
        $group->post('/{id}/link/', CreateAuthorLinkAction::class);
        $group->delete('/{id}/link/{link}/', DeleteAuthorLinkAction::class);
    });
    $app->group('/series', function (Group $group) {
        $group->get('/', ViewSeriesAction::class);
        $group->get('/{id}/', ViewASeriesAction::class);
    });
    $app->group('/static', function (Group $group) {
        $group->get('/authorthumbs/{id}/', ViewAuthorThumbnailAction::class);
        // TODO HEAD request for thumbnails?
        $group->get('/covers/{id}/', ViewCoverAction::class);
        // TODO HEAD request for covers?
        $group->get('/files/{id}/{file}/', ViewTitleFile::class);
        $group->get('/titlethumbs/{id}/', ViewTitleThumbnailAction::class);
        // TODO HEAD request for thumbnails?
    });
    $app->group('/tags', function (Group $group) {
        $group->get('/', ViewTagsAction::class);
        $group->get('/{id}/', ViewTagAction::class);
    });
    $app->group('/titles', function (Group $group) {
        $group->get('/', ViewTitlesAction::class);
        $group->get('/{id}/', ViewTitleAction::class);
    });
};

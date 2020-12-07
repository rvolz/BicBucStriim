<?php
declare(strict_types=1);

// Register routes
use App\Application\Actions\Admin\DeleteIdTemplatesAction;
use App\Application\Actions\Admin\UpdateConfigurationAction;
use App\Application\Actions\Admin\UpdateIdTemplatesAction;
use App\Application\Actions\Admin\ViewAdminAction;
use App\Application\Actions\Admin\ViewConfigurationAction;
use App\Application\Actions\Admin\ViewIdTemplatesAction;
use App\Application\Actions\Login\DoLoginAction;
use App\Application\Actions\Login\ViewLoginAction;
use App\Application\Actions\Login\ViewLogoutAction;
use App\Application\Actions\Start\ViewLast30Action;
use App\Application\Actions\Statics\ViewCoverAction;
use App\Application\Actions\Statics\ViewThumbnailAction;
use App\Application\Actions\Statics\ViewTitleFile;
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
        $group->get('/users/', \App\Application\Actions\Admin\ViewUsersAction::class);
        $group->get('/users/{id}/', \App\Application\Actions\Admin\ViewUserAction::class);
        $group->put('/users/{id}/', \App\Application\Actions\Admin\ChangeUserAction::class);
        $group->delete('/users/{id}/', DeleteIdTemplatesAction::class);
    });
    $app->group('/static', function (Group $group) {
        $group->get('/covers/{id}/', ViewCoverAction::class);
        // TODO HEAD request for covers?
        $group->get('/thumbnails/{id}/', ViewThumbnailAction::class);
        // TODO HEAD request for thumbnails?
        $group->get('/files/{id}/{file}/', ViewTitleFile::class);
    });

};

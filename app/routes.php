<?php
declare(strict_types=1);

// Register routes
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
    $app->get('/', ViewLast30Action::class);
    $app->get('/login/', ViewLoginAction::class);
    $app->post('/login/', DoLoginAction::class);
    $app->get('/logout/', ViewLogoutAction::class);
    $app->group('/static', function (Group $group) {
        $group->get('/covers/{id}/', ViewCoverAction::class);
        // TODO HEAD request for covers?
        $group->get('/thumbnails/{id}/', ViewThumbnailAction::class);
        // TODO HEAD request for thumbnails?
        $group->get('/files/{id}/{file}/', ViewTitleFile::class);
    });

};

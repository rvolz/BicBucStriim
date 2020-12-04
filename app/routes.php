<?php
declare(strict_types=1);

// Register routes
use App\Application\Actions\Statics\ViewCoverAction;
use App\Application\Actions\Statics\ViewThumbnailAction;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->group('/static', function (Group $group) {
        $group->get('/covers/{id}/', ViewCoverAction::class);
        // TODO HEAD request for covers?
        $group->get('/thumbnails/{id}/', ViewThumbnailAction::class);
        // TODO HEAD request for thumbnails?
    });

};

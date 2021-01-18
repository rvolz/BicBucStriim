<?php
declare(strict_types=1);

// Register routes
use App\Application\Actions\Admin\ChangeUserAction;
use App\Application\Actions\Admin\CreateUserAction;
use App\Application\Actions\Admin\DeleteIdTemplatesAction;
use App\Application\Actions\Admin\UpdateConfigurationAction;
use App\Application\Actions\Admin\UpdateIdTemplatesAction;
use App\Application\Actions\Admin\UpdateMailAction;
use App\Application\Actions\Admin\ViewAdminAction;
use App\Application\Actions\Admin\ViewConfigurationAction;
use App\Application\Actions\Admin\ViewIdTemplatesAction;
use App\Application\Actions\Admin\ViewMailAction;
use App\Application\Actions\Admin\ViewUserAction;
use App\Application\Actions\Admin\ViewUsersAction;
use App\Application\Actions\Admin\ViewVersionCheckAction;
use App\Application\Actions\Authors\CreateAuthorLinkAction;
use App\Application\Actions\Authors\CreateAuthorThumbnailAction;
use App\Application\Actions\Authors\DeleteAuthorLinkAction;
use App\Application\Actions\Authors\DeleteAuthorThumbnailAction;
use App\Application\Actions\Authors\DoAuthorAction;
use App\Application\Actions\Authors\ViewAuthorAction;
use App\Application\Actions\Authors\ViewAuthorInitialsAction;
use App\Application\Actions\Authors\ViewAuthorsAction;
use App\Application\Actions\Authors\ViewOpdsByAuthorAction;
use App\Application\Actions\Authors\ViewOpdsByAuthorInitialsAction;
use App\Application\Actions\Authors\ViewOpdsByAuthorNamesForInitialAction;
use App\Application\Actions\Login\DoLoginAction;
use App\Application\Actions\Login\ViewLoginAction;
use App\Application\Actions\Login\ViewLogoutAction;
use App\Application\Actions\Search\UpdateSearchAction;
use App\Application\Actions\Search\ViewOpdsSearchAction;
use App\Application\Actions\Search\ViewOpdsSearchDescriptorAction;
use App\Application\Actions\Search\ViewSearchAction;
use App\Application\Actions\Series\ViewASeriesAction;
use App\Application\Actions\Series\ViewOpdsSeriesAction;
use App\Application\Actions\Series\ViewOpdsSeriesByInitial;
use App\Application\Actions\Series\ViewOpdsSeriesNames4Initials;
use App\Application\Actions\Series\ViewSeriesAction;
use App\Application\Actions\Series\ViewSeriesInitialsAction;
use App\Application\Actions\Start\ViewLast30Action;
use App\Application\Actions\Start\ViewOpdsNewestAction;
use App\Application\Actions\Start\ViewOpdsRootCatalogAction;
use App\Application\Actions\Statics\ViewAuthorThumbnailAction;
use App\Application\Actions\Statics\ViewCoverAction;
use App\Application\Actions\Statics\ViewTitleThumbnailAction;
use App\Application\Actions\Statics\ViewTitleFile;
use App\Application\Actions\Tags\ViewOpdsTag;
use App\Application\Actions\Tags\ViewOpdsTagNames4Initial;
use App\Application\Actions\Tags\ViewOpdsTagsByInitial;
use App\Application\Actions\Tags\ViewTagAction;
use App\Application\Actions\Tags\ViewTagsAction;
use App\Application\Actions\Tags\ViewTagInitialsAction;
use App\Application\Actions\Titles\ViewOpdsTitlesAction;
use App\Application\Actions\Titles\ViewTitleAction;
use App\Application\Actions\Titles\ViewTitleInitialsAction;
use App\Application\Actions\Titles\ViewTitlesAction;
use App\Application\Actions\Titles\ViewTitleYearsAction;
use Slim\App;
use Slim\Interfaces\RouteCollectorProxyInterface as Group;

return function (App $app) {
    $app->get('/', ViewLast30Action::class)->setName('start');
    $app->get('/login/', ViewLoginAction::class);
    $app->post('/login/', DoLoginAction::class);
    $app->get('/logout/', ViewLogoutAction::class);
    $app->get('/search/', ViewSearchAction::class);
    $app->post('/search/', UpdateSearchAction::class);
    $app->group('/admin', function (Group $group) {
        $group->get('/', ViewAdminAction::class);
        $group->get('/configuration/', ViewConfigurationAction::class);
        $group->post('/configuration/', UpdateConfigurationAction::class);
        $group->get('/idtemplates/', ViewIdTemplatesAction::class);
        $group->put('/idtemplates/{id}/', UpdateIdTemplatesAction::class);
        $group->delete('/idtemplates/{id}/', DeleteIdTemplatesAction::class);
        $group->get('/mail/', ViewMailAction::class);
        $group->post('/mail/', UpdateMailAction::class);
        $group->get('/users/', ViewUsersAction::class);
        $group->post('/users/', CreateUserAction::class);
        $group->get('/users/{id}/', ViewUserAction::class);
        $group->post('/users/{id}/', ChangeUserAction::class);
        $group->get('/version/', ViewVersionCheckAction::class);
    });
    $app->group('/authors', function (Group $group) {
        $group->get('/', ViewAuthorsAction::class);
        $group->get('/{id}/', ViewAuthorAction::class);
        $group->post('/{id}/', DoAuthorAction::class);
        $group->post('/{id}/thumbnail/', CreateAuthorThumbnailAction::class);
        $group->delete('/{id}/thumbnail/', DeleteAuthorThumbnailAction::class);
        $group->post('/{id}/link/', CreateAuthorLinkAction::class);
        $group->delete('/{id}/link/{link}/', DeleteAuthorLinkAction::class);
    });
    $app->group('/params', function (Group $group) {
        $group->get('/authors/initials/', ViewAuthorInitialsAction::class);
        $group->get('/series/initials/', ViewSeriesInitialsAction::class);
        $group->get('/tags/initials/', ViewTagInitialsAction::class);
        $group->get('/titles/initials/', ViewTitleInitialsAction::class);
        $group->get('/titles/years/', ViewTitleYearsAction::class);
    });
    $app->group('/opds', function (Group $group) {
        $group->get('/', ViewOpdsRootCatalogAction::class);
        $group->get('/newest/', ViewOpdsNewestAction::class);
        $group->get('/authors/', ViewOpdsByAuthorAction::class);
        $group->get('/authors/{initial}/', ViewOpdsByAuthorInitialsAction::class);
        $group->get('/authors/{initial}/{id}/', ViewOpdsByAuthorNamesForInitialAction::class);
        $group->get('/search/', ViewOpdsSearchAction::class);
        // TODO route doesn't work due to trailing slash requirements
        $group->get('/opensearch.xml', ViewOpdsSearchDescriptorAction::class);
        $group->get('/tags/', ViewOpdsTagsByInitial::class);
        $group->get('/tags/{initial}/', ViewOpdsTagNames4Initial::class);
        $group->get('/tags/{initial}/{id}/', ViewOpdsTag::class);
        $group->get('/series/', ViewOpdsSeriesByInitial::class);
        $group->get('/series/{initial}/', ViewOpdsSeriesNames4Initials::class);
        $group->get('/series/{initial}/{id}/', ViewOpdsSeriesAction::class);
        $group->get('/titles/', ViewOpdsTitlesAction::class);
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
        $group->get('/files/{id}/{format}/{file}/', ViewTitleFile::class);
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
        $group->post('/', ViewTitleAction::class);
    });
};

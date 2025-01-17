<?php

use App\View;
use Slim\Routing\RouteCollectorProxy;

return function (Slim\App $app) {
    /*
     * Special Event Pages
     */
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get(
            '/defective',
            App\Controller\Events\DefectiveAction::class
        )->setName('defective');

        $group->get('/vector', View::staticPage('events/vector'))
            ->setName('vector');
    })->add(App\Middleware\EnableView::class)
        ->add(App\Middleware\GetCurrentUser::class)
        ->add(App\Middleware\EnableSession::class);

    /*
     * View-enabled, user-enabled routes
     */
    $app->group('', function (RouteCollectorProxy $group) {
        $group->get('/', View::staticPage('index'))
            ->setName('home');

        $group->get('/about', View::staticPage('about'))
            ->setName('about');

        $group->get('/calendar', View::staticPage('calendar'))
            ->setName('calendar');

        $group->group('/dashboard', function (RouteCollectorProxy $group) {
            $group->get('', View::staticPage('dashboard/index'))
                ->setName('dashboard');

            $group->group('/admin', function (RouteCollectorProxy $group) {
                $group->group('/groups', function (RouteCollectorProxy $group) {
                    $group->get(
                        '',
                        App\Controller\Dashboard\Admin\GroupsController::class . ':listAction'
                    )->setName('dashboard:admin:groups');

                    $group->map(
                        ['GET', 'POST'],
                        '/create',
                        App\Controller\Dashboard\Admin\GroupsController::class . ':createAction'
                    )->setName('dashboard:admin:groups:create');

                    $group->map(
                        ['GET', 'POST'],
                        '/edit[/{id}]',
                        App\Controller\Dashboard\Admin\GroupsController::class . ':editAction'
                    )->setName('dashboard:admin:groups:edit');

                    $group->get(
                        '/delete[/{id}]',
                        App\Controller\Dashboard\Admin\GroupsController::class . ':deleteAction'
                    )->setName('dashboard:admin:groups:delete');
                });

                $group->group('/poster_types', function (RouteCollectorProxy $group) {
                    $group->get(
                        '',
                        App\Controller\Dashboard\Admin\PosterTypesController::class . ':listAction'
                    )->setName('dashboard:admin:poster_types');

                    $group->map(
                        ['GET', 'POST'],
                        '/create',
                        App\Controller\Dashboard\Admin\PosterTypesController::class . ':createAction'
                    )->setName('dashboard:admin:poster_types:create');

                    $group->map(
                        ['GET', 'POST'],
                        '/edit[/{id}]',
                        App\Controller\Dashboard\Admin\PosterTypesController::class . ':editAction'
                    )->setName('dashboard:admin:poster_types:edit');

                    $group->get(
                        '/delete[/{id}]',
                        App\Controller\Dashboard\Admin\PosterTypesController::class . ':deleteAction'
                    )->setName('dashboard:admin:poster_types:delete');
                });

                $group->get(
                    '/users',
                    App\Controller\Dashboard\Admin\UsersAction::class
                )->setName('dashboard:admin:users');

                $group->group('/worlds', function (RouteCollectorProxy $group) {
                    $group->get(
                        '',
                        App\Controller\Dashboard\Admin\WorldsController::class . ':listAction'
                    )->setName('dashboard:admin:worlds');

                    $group->map(
                        ['GET', 'POST'],
                        '/create',
                        App\Controller\Dashboard\Admin\WorldsController::class . ':createAction'
                    )->setName('dashboard:admin:worlds:create');

                    $group->get(
                        '/delete[/{id}]',
                        App\Controller\Dashboard\Admin\WorldsController::class . ':deleteAction'
                    )->setName('dashboard:admin:worlds:delete');
                });
            })->add(new App\Middleware\Auth\RequireAdmin());

            $group->map(
                ['GET', 'POST'],
                '/avatar[/{type}]',
                App\Controller\Dashboard\AvatarAction::class
            )->setName('dashboard:avatar');

            $group->map(
                ['GET', 'POST'],
                '/password',
                App\Controller\Dashboard\PasswordAction::class
            )->setName('dashboard:password');

            $group->group('/posters', function (RouteCollectorProxy $group) {
                $group->get(
                    '',
                    App\Controller\Dashboard\PostersController::class . ':listAction'
                )->setName('dashboard:posters');

                $group->map(
                    ['GET', 'POST'],
                    '/create',
                    App\Controller\Dashboard\PostersController::class . ':createAction'
                )->setName('dashboard:posters:create');

                $group->map(
                    ['GET', 'POST'],
                    '/edit[/{id}]',
                    App\Controller\Dashboard\PostersController::class . ':editAction'
                )->setName('dashboard:posters:edit');

                $group->get(
                    '/delete[/{id}]',
                    App\Controller\Dashboard\PostersController::class . ':deleteAction'
                )->setName('dashboard:posters:delete');
            });

            $group->map(
                ['GET', 'POST'],
                '/profile[/{id}]',
                App\Controller\Dashboard\EditProfileAction::class
            )->setName('dashboard:profile');

            $group->group('/short_urls', function (RouteCollectorProxy $group) {
                $group->get(
                    '',
                    App\Controller\Dashboard\ShortUrlsController::class . ':listAction'
                )->setName('dashboard:short_urls');

                $group->map(
                    ['GET', 'POST'],
                    '/create',
                    App\Controller\Dashboard\ShortUrlsController::class . ':createAction'
                )->setName('dashboard:short_urls:create');

                $group->map(
                    ['GET', 'POST'],
                    '/edit[/{id}]',
                    App\Controller\Dashboard\ShortUrlsController::class . ':editAction'
                )->setName('dashboard:short_urls:edit');

                $group->get(
                    '/delete[/{id}]',
                    App\Controller\Dashboard\ShortUrlsController::class . ':deleteAction'
                )->setName('dashboard:short_urls:delete');
            })->add(new App\Middleware\Auth\RequireMod());

            $group->map(
                ['GET', 'POST'],
                '/skills',
                App\Controller\Dashboard\SkillsController::class
            )->setName('dashboard:skills');
        })->add(new App\Middleware\Auth\RequireLoggedIn());

        $group->get('/donate', View::staticPage('donate.twig'))
            ->setName('donate');

        $group->map(['GET', 'POST'], '/forgot', App\Controller\Account\ForgotAction::class)
            ->setName('forgot');

        $group->get('/live', View::staticPage('live'))
            ->setName('live');

        $group->map(['GET', 'POST'], '/login', App\Controller\Account\LoginAction::class)
            ->setName('login');

        $group->get('/logout', App\Controller\Account\LogoutAction::class)
            ->setName('logout');

        $group->get('/portals', View::staticPage('portals'))
            ->setName('portals');

        $group->get('/posters/faq', App\Controller\Posters\GetFaqAction::class)
            ->setName('posters:faq');

        $group->map(['GET', 'POST'], '/profile[/{user}]', App\Controller\ProfileAction::class)
            ->setName('profile');

        $group->map(['GET', 'POST'], '/recover', App\Controller\Account\RecoverAction::class)
            ->setName('recover');

        $group->map(['GET', 'POST'], '/register', App\Controller\Account\RegisterAction::class)
            ->setName('register');

        $group->get('/talent', App\Controller\TalentAction::class)
            ->setName('talent');

        $group->get('/team', App\Controller\TeamAction::class)
            ->setName('team');

        $group->group('/wwradio', function (RouteCollectorProxy $group) {
            $group->get('', View::staticPage('wwradio/index'))
                ->setName('wwradio');

            $group->get('/info', View::staticPage('wwradio/info'))
                ->setName('wwradio:info');
        });

        $group->get('/worlds', App\Controller\WorldsController::class . ':listAction')
            ->setName('worlds');

        $group->map(['GET', 'POST'], '/world[/{id}]', App\Controller\WorldsController::class . ':getAction')
            ->setName('world');
    })->add(App\Middleware\EnableView::class)
        ->add(App\Middleware\GetCurrentUser::class)
        ->add(App\Middleware\EnableSession::class);

    /*
     * No view, public-facing APIs
     */
    $app->group('/api', function (RouteCollectorProxy $group) {
        $group->get('/json', App\Controller\Api\JsonAction::class)
            ->setName('api:json');

        $group->get('/vrc_acl/{type}', App\Controller\Api\VrcAclAction::class)
            ->setName('api:vrc_acl');

        $group->group('/posters', function (RouteCollectorProxy $group) {
            $group->get('/spec', App\Controller\Api\PosterSpecAction::class)
                ->setName('api:posters:spec');
        });
    });

    $app->get('/posters[/{id}]', App\Controller\Posters\GetPosterAction::class)
        ->setName('posters');

    $app->get('/short_url[/{url}]', App\Controller\GetShortUrlAction::class)
        ->setName('short_url');

    /*
     * Catch-all handler for base URLs to check the Short URL database.
     */

    $app->get('/{url}', App\Controller\GetShortUrlAction::class);
};

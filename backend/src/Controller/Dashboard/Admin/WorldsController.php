<?php

namespace App\Controller\Dashboard\Admin;

use App\Exception\NotFoundException;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use App\Service\VrcApi;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Intervention\Image\ImageManager;
use League\Flysystem\UnableToDeleteFile;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\UploadedFileInterface;

final readonly class WorldsController
{
    private Client $vrcApiClient;

    public function __construct(
        private Connection $db,
        private ImageManager $imageManager,
        private Client $httpClient,
        VrcApi $vrcApi
    ) {
        $this->vrcApiClient = $vrcApi->getHttpClient();
    }

    public function listAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $worlds = $this->db->fetchAllAssociative(
            <<<'SQL'
                SELECT w.*
                FROM web_worlds AS w
                ORDER BY id DESC
            SQL
        );

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/admin/worlds/list',
            [
                'worlds' => $worlds,
            ]
        );
    }

    public function createAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $error = null;

        if ($request->isPost()) {
            try {
                $worldId = $request->getParam('id');
                if (empty($worldId)) {
                    throw new \InvalidArgumentException('World ID not specified.');
                }

                $worldId = VrcApi::parseWorldId($worldId);

                // Fetch world info from the VRC API.
                $worldInfo = VrcApi::processResponse(
                    $this->vrcApiClient->get(sprintf('worlds/%s', $worldId))
                );

                // Pull the world image
                $imageUrl = $worldInfo['imageUrl'];
                $imageData = $this->httpClient->get($imageUrl)->getBody()->getContents();

                $imageRelativePath = Media::worldPath($worldId . '.png');

                $image = $this->imageManager->read($imageData);

                $fs = Media::getFilesystem();
                $fs->write($imageRelativePath, $image->encodeByPath($imageRelativePath)->toString());

                // Add the DB record
                $this->db->insert(
                    'web_worlds',
                    [
                        'title' => $worldInfo['name'],
                        'creator' => $currentUser['id'],
                        'image' => $imageRelativePath,
                        'description' => $worldInfo['description'],
                        'world_id' => $worldId,
                        'world_creator' => $worldInfo['authorName'],
                    ]
                );

                $request->getFlash()->success('World successfully imported!');
                return $response->withRedirect(
                    $request->getRouter()->urlFor('dashboard:admin:worlds')
                );
            } catch (\Throwable $e) {
                $error = $e->getMessage();
            }
        }

        return $request->getView()->renderToResponse(
            $response,
            'dashboard/admin/worlds/create',
            [
                'error' => $error,
            ]
        );
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $params['id'];

        $world = $this->db->fetchAssociative(
            <<<'SQL'
                SELECT id, image
                FROM web_worlds
                WHERE id = :id
            SQL,
            [
                'id' => $id,
            ]
        );

        if ($world === false) {
            throw NotFoundException::world($request);
        }

        $fs = Media::getFilesystem();
        $fs->delete($world['image']);

        $this->db->delete(
            'web_worlds',
            [
                'id' => $world['id'],
            ]
        );

        $request->getFlash()->success('World removed.');
        return $response->withRedirect(
            $request->getRouter()->urlFor('dashboard:admin:worlds')
        );
    }
}

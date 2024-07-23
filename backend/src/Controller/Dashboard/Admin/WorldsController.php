<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\AbstractCrudController;
use App\Http\Response;
use App\Http\ServerRequest;
use App\Media;
use App\Service\VrcApi;
use Doctrine\DBAL\Connection;
use GuzzleHttp\Client;
use Intervention\Image\ImageManager;
use Psr\Http\Message\ResponseInterface;

final class WorldsController extends AbstractCrudController
{
    private Client $vrcApiClient;

    public function __construct(
        private ImageManager $imageManager,
        private Client $httpClient,
        Connection $db,
        VrcApi $vrcApi
    ) {
        parent::__construct(
            $db,
            'Spotlighted World',
            'dashboard:admin:worlds',
            'dashboard/admin/worlds/list',
            'dashboard/admin/worlds/create',
            'web_worlds'
        );

        $this->vrcApiClient = $vrcApi->getHttpClient();
    }

    public function editAction(ServerRequest $request, Response $response, array $params): ResponseInterface
    {
        throw new \RuntimeException('Not available!');
    }

    protected function modifyFromRequest(ServerRequest $request, array $row = [], bool $isEditMode = false): array
    {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

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

        return [
            'title' => $worldInfo['name'],
            'creator' => $currentUser['id'],
            'image' => $imageRelativePath,
            'description' => $worldInfo['description'],
            'world_id' => $worldId,
            'world_creator' => $worldInfo['authorName'],
        ];
    }

    public function deleteAction(
        ServerRequest $request,
        Response $response,
        array $params
    ): ResponseInterface {
        $id = $this->requireId($request, $params);

        $world = $this->getRecord($id);

        $fs = Media::getFilesystem();
        $fs->delete($world['image']);

        $this->db->delete(
            $this->tableName,
            [
                $this->idField => $world[$this->idField],
            ]
        );

        $request->getFlash()->success(sprintf('%s removed.', $this->itemName));
        return $response->withRedirect(
            $request->getRouter()->urlFor($this->listRouteName)
        );
    }
}

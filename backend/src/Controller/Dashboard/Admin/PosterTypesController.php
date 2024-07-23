<?php

namespace App\Controller\Dashboard\Admin;

use App\Controller\Dashboard\AbstractCrudController;
use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;

final class PosterTypesController extends AbstractCrudController
{
    public function __construct(
        Connection $db
    ) {
        parent::__construct(
            $db,
            'Poster Type',
            'dashboard:admin:poster_types',
            'dashboard/admin/poster_types/list',
            'dashboard/admin/poster_types/edit',
            'web_poster_types'
        );
    }

    protected function modifyFromRequest(ServerRequest $request, array $row = [], bool $isEditMode = false): array
    {
        $postData = $request->getParsedBody();

        if (!$isEditMode) {
            $row['id'] = $postData['id'] ?? null;
            if (empty($row['id'])) {
                throw new \InvalidArgumentException('ID is required.');
            }
        }

        $row['name'] = $postData['name'] ?? null;

        if (empty($row['name'])) {
            throw new \InvalidArgumentException('Name is required.');
        }

        return $row;
    }
}

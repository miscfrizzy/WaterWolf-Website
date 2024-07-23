<?php

namespace App\Controller\Dashboard;

use App\Http\ServerRequest;
use Doctrine\DBAL\Connection;

final class ShortUrlsController extends AbstractCrudController
{
    public function __construct(
        Connection $db
    ) {
        parent::__construct(
            $db,
            'Short URL',
            'dashboard:short_urls',
            'dashboard/short_urls/list',
            'dashboard/short_urls/edit',
            'web_short_urls'
        );
    }

    protected function modifyFromRequest(ServerRequest $request, array $row = [], bool $isEditMode = false): array
    {
        $currentUser = $request->getCurrentUser();
        assert($currentUser !== null);

        $row['creator'] = $currentUser['id'];

        $postData = $request->getParsedBody();
        $row['short_url'] = trim($postData['short_url'] ?? '', '/');
        $row['long_url'] = $postData['long_url'] ?? null;

        if (empty($row['short_url']) || empty($row['long_url'])) {
            throw new \InvalidArgumentException('Short and Long URL are required.');
        }

        return $row;
    }
}

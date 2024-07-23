<?php

declare(strict_types=1);

use Phinx\Db\Table\Column;
use Phinx\Migration\AbstractMigration;

final class ExpandShortUrl extends AbstractMigration
{
    /**
     * Expand short URL values
     */
    public function change(): void
    {
        $this->table('web_short_urls')
            ->changeColumn(
                'long_url',
                (new Column())
                    ->setType(Column::TEXT)
                    ->setNull(true)
            )->update();
    }
}

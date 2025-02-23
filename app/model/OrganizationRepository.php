<?php

namespace App\Model;

use App\Services\FileStorage;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Utils\ArrayHash;
use Nette\Utils\Strings;

class OrganizationRepository
{
    public const
        TABLE_PRIMARY = 'organization',

        COL_ID = 'id',
        COL_TITLE = 'title',
        COL_TITLE_SHORT = 'title_short',
        COL_CITY = 'city',
        COL_STREET = 'street',
        COL_ZIP = 'zip',
        COL_LOGO = 'logo',
        COL_EMAIL = 'email',
        COL_PHONE = 'phone',
        COL_WEB = 'link',
        COL_TEXT = 'text',
        COL_CREATED = 'created',
        COL_LAT = 'lat',
        COL_LNG = 'lng',

        IMAGE_NAMESPACE = 'organization';

    public function __construct(
        private Explorer    $database,
        private FileStorage $fileStorage,
    )
    {
    }

    public function findAll(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findOrgById(int $id)
    {
        return $this->findAll()->wherePrimary($id)->fetch();
    }

    public function getOrgsArray(): array
    {
        return $this->findAll()->fetchPairs(self::COL_ID, self::COL_TITLE);
    }

    public function upsert(array $values): int
    {
        if ($id = $values[self::COL_ID]) {
            $this->findAll()
                ->wherePrimary($id)
                ->update($values);
        } else {
            unset($values[self::COL_ID]);
            $row = $this->findAll()->insert($values);
            $id = $row->{self::COL_ID};
        }

        return $id;
    }

    public static function getAddress(ActiveRow $row): string
    {
        return "{$row->street} {$row->city}, {$row->zip}";
    }
}
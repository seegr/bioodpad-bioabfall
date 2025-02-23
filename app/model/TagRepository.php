<?php

namespace App\Model;

use App\Services\LocaleService;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Security\User;
use Nette\Utils\Strings;

class TagRepository
{
    public const
        TABLE_PRIMARY = 'tag',
        COLUMN_PRIMARY_ID = 'id',
        COLUMN_TITLE = 'title',
        COLUMN_PRIMARY_USER_ID = 'user_id',
        COLUMN_PRIMARY_DATE_CREATED = 'date_created',
        COLUMN_PRIMARY_DATE_UPDATED = 'date_updated';


    public function __construct(
        private Explorer $database,
        private User $user,
    ) {}

    public function findAll(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findTagById(int $id): ?ActiveRow
    {
        return $this->findAll()->wherePrimary($id)->fetch();
    }

    public function getInputOptions(): array
    {
        return $this->findAll()
            ->fetchPairs(self::COLUMN_PRIMARY_ID, self::COLUMN_TITLE);
    }

    public function deleteTag(int $id): void
    {
        $this->findAll()->wherePrimary($id)->delete();
    }

    public function upsert(array $values): ?int
    {
        if ($id = $values[self::COLUMN_PRIMARY_ID]) {
            $this->findAll()
                ->wherePrimary($id)
                ->update($values);
        } else {
            $values[self::COLUMN_PRIMARY_USER_ID] = $this->user->getId();
            unset($values[self::COLUMN_PRIMARY_ID]);
            $row = $this->findAll()->insert($values);
            $id = $row->{self::COLUMN_PRIMARY_ID};
        }

        return $id;
    }

    public function getTagsByIds(array $ids): array
    {
        return $this->findAll()
            ->where(self::COLUMN_PRIMARY_ID, $ids)
            ->fetchPairs(self::COLUMN_PRIMARY_ID, self::COLUMN_TITLE);
    }



}

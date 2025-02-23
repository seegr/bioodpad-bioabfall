<?php

namespace App\Model;

use App\Exceptions\ExistingRelationException;
use App\Filter\StaticFilters;
use App\Services\FileStorage;
use Nette\Application\BadRequestException;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Http\FileUpload;
use Nette\Security\User;
use Nette\Utils\Strings;
use Nette\Utils\Validators;


class FileRepository
{
    public const
        FILE_NAMESPACE = 'files',

        TABLE_PRIMARY = 'file',
        COLUMN_PRIMARY_ID = 'id',
        COLUMN_PRIMARY_TITLE = 'title',
        COLUMN_PRIMARY_SORT = 'sort',
        COLUMN_PRIMARY_SOURCE = 'source',
        COLUMN_PRIMARY_THUMBNAIL = 'thumbnail',
        COLUMN_PRIMARY_TYPE = 'type',
        COLUMN_PRIMARY_SIZE = 'size',
        COLUMN_PRIMARY_WIDTH = 'width',
        COLUMN_PRIMARY_HEIGHT = 'height',
        COLUMN_PRIMARY_USER_ID = 'user_id',
        COLUMN_PRIMARY_DATE_CREATED = 'date_created',
        COLUMN_PRIMARY_DATE_UPDATED = 'date_updated',

        TABLE_TAG = "file_has_tag",
        COLUMN_TAG_FILE = "file_id",
        COLUMN_TAG_PARENT = "tag_id",

        FILE_TYPE_IMAGE = 'i',
        FILE_TYPE_VIDEO = 'v',
        FILE_TYPE_FILE = 'f',
        FILE_TYPE_YOUTUBE = 'y',
        FILE_TYPE_VIMEO = 'o',
        FILE_TYPE_COMPARISON = 'c',

        COLUMN_CUSTOM_TAGS = "tags",

        VIDEO_MIME_TYPES = ['video/mp4', 'video/webm', 'video/ogg'];


    public function __construct(
        private Explorer $database,
        private FileStorage $fileStorage,
        private User $user,
    ) {}

    public function findFiles(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findFileTags(): Selection
    {
        return $this->database->table(self::TABLE_TAG);
    }

    public function deleteFile(int $id)
    {
        $row = $this->findFiles()->wherePrimary($id);
        $data = $row->fetch();

        if (in_array(
            $data->{self::COLUMN_PRIMARY_TYPE},
            [self::FILE_TYPE_IMAGE, self::FILE_TYPE_FILE, self::FILE_TYPE_VIDEO]
        )) {
            $this->fileStorage->removeFile($data->{self::COLUMN_PRIMARY_SOURCE});
        }
        $row->delete();
    }

    public function getFileList(?int $tagId = null): array
    {
        $selection = $this->findFiles()
            ->select(
                sprintf(
                    "GROUP_CONCAT(%s) AS %s",
                    Utils::createPath([self::COLUMN_TAG_PARENT, self::TABLE_TAG, true]),
                    self::COLUMN_CUSTOM_TAGS
                )
            )
            ->select(
                Utils::createPath(
                    ['*', self::TABLE_PRIMARY],
                )
            )
            ->group(self::COLUMN_PRIMARY_ID)
            ->order(self::COLUMN_PRIMARY_DATE_CREATED . ' DESC');

        if ($tagId) {
            $selection->where(Utils::createPath([self::COLUMN_TAG_PARENT, self::TABLE_TAG, true]), $tagId);
        }
        return $selection->fetchAll();
    }

    public function getInputOptions(): array
    {
        return $this->findFiles()
            ->select(
                Utils::createPath(
                    [self::COLUMN_PRIMARY_ID, self::TABLE_PRIMARY],
                )
            )
            ->order(self::COLUMN_PRIMARY_SORT)
            ->fetchPairs(self::COLUMN_PRIMARY_ID, self::COLUMN_PRIMARY_TITLE);
    }

    public function getFileLink(ActiveRow $row): string
    {
        $source = $row->{self::COLUMN_PRIMARY_SOURCE};
        switch ($row->{self::COLUMN_PRIMARY_TYPE}) {
            case self::FILE_TYPE_YOUTUBE:
            case self::FILE_TYPE_VIMEO:
            {
                return $source;
            }
            case self::FILE_TYPE_COMPARISON:
            {
                [$firstImage] = FileRepository::getComparisonList($source);
                return $this->fileStorage->getRelativeUrl($firstImage);
            }
            default:
            {
                return $this->fileStorage->getRelativeUrl($source);
            }
        }
    }

    public function handleUpload(FileUpload $fileUpload, ?int $id): int
    {
        $this->fileStorage->setNamespace(FileRepository::FILE_NAMESPACE);
        $sourceName = $this->fileStorage->uploadFileUpload($fileUpload);
        [$width, $height] = $fileUpload->getImageSize();
        $data = [
            self::COLUMN_PRIMARY_SOURCE => $sourceName,
            self::COLUMN_PRIMARY_TYPE => FileRepository::getFileType($fileUpload),
            self::COLUMN_PRIMARY_SIZE => $fileUpload->getSize(),
            self::COLUMN_PRIMARY_WIDTH => $width,
            self::COLUMN_PRIMARY_HEIGHT => $height,
            self::COLUMN_PRIMARY_USER_ID => $this->user->getId(),
        ];
        if ($id) {
            $this->findFiles()->wherePrimary($id)->update($data);
        } else {
            $row = $this->findFiles()->insert($data);
        }

        return $id ?? $row->id;
    }

    public function insertEmbedVideo(string $link, string $type): void
    {
        if (!Validators::isUrl($link)) {
            throw new BadRequestException('Link is invalid');
        }
        $row = $this->findFiles()->insert(
            [
                self::COLUMN_PRIMARY_SOURCE => $link,
                self::COLUMN_PRIMARY_TYPE => $type,
                self::COLUMN_PRIMARY_USER_ID => $this->user->getId(),
            ]
        );
    }

    public function insertComparison(FileUpload $first, FileUpload $second): void
    {
        $this->fileStorage->setNamespace(FileRepository::FILE_NAMESPACE);
        $firstId = $this->fileStorage->uploadFileUpload($second);
        $secondId = $this->fileStorage->uploadFileUpload($first);
        $row = $this->findFiles()->insert(
            [
                self::COLUMN_PRIMARY_SOURCE => $firstId . ';' . $secondId,
                self::COLUMN_PRIMARY_TYPE => self::FILE_TYPE_COMPARISON,
                self::COLUMN_PRIMARY_SIZE => $first->getSize() + $second->getSize(),
                self::COLUMN_PRIMARY_USER_ID => $this->user->getId(),
            ]
        );
    }

    public static function getComparisonList(string $source): array
    {
        return explode(';', $source);
    }

    public function upsertTag(int $fileId, int $tagId)
    {
        $row = $this->findFileTags()->where(self::COLUMN_TAG_FILE, $fileId);
        if ($row->fetch()) {
            $row->update([self::COLUMN_TAG_PARENT => $tagId]);
        } else {
            $row->insert([self::COLUMN_TAG_PARENT => $tagId, self::COLUMN_TAG_FILE => $fileId]);
        }
    }

    public function updateSort(array $ids): void
    {
        foreach ($ids as $sort => $id) {
            $this->findFiles()->wherePrimary($id)->update([self::COLUMN_PRIMARY_SORT => $sort]);
        }
    }

    public static function getFileType(FileUpload $fileUpload): string
    {
        if ($fileUpload->isImage()) {
            return self::FILE_TYPE_IMAGE;
        }
        if (in_array($fileUpload->getContentType(), self::VIDEO_MIME_TYPES, true)) {
            return self::FILE_TYPE_VIDEO;
        }
        return self::FILE_TYPE_FILE;
    }

    public function updateFile(int $id, array $tags, ?string $thumb = null)
    {
        // update thumb
        $this->fileStorage->setNamespace(self::FILE_NAMESPACE);
        $this->findFiles()->wherePrimary($id)->update(
            [self::COLUMN_PRIMARY_THUMBNAIL => $thumb ? $this->fileStorage->uploadBase64Image($thumb, uniqid()) : null]
        );
        // update tags
        Utils::updateRelations(
            $this->findFileTags(),
            [FileRepository::COLUMN_TAG_FILE => $id],
            $tags,
            FileRepository::COLUMN_TAG_PARENT
        );
    }

    public function getFilesByPageId(int $pageId): Selection
    {
        return $this->findFiles()
            ->select(
                Utils::createPath(
                    ['*', self::TABLE_PRIMARY],
                )
            )
            ->where(Utils::createPath([PageRepository::COLUMN_FILE_PARENT, PageRepository::TABLE_FILE, true]), $pageId)
            ->order(Utils::createPath([PageRepository::COLUMN_FILE_SORT, PageRepository::TABLE_FILE, true]), 'ASC');
    }

    public function getMediaByIds(array $mediaIds, string $locale): Selection
    {
        return $this->findFiles()
            ->select(
                Utils::createPath(
                    ['*', self::TABLE_PRIMARY],
                )
            )
            ->where(Utils::createPath([self::COLUMN_PRIMARY_ID, self::TABLE_PRIMARY]), $mediaIds)
            ->order(Utils::createPath([self::COLUMN_PRIMARY_SORT, self::TABLE_PRIMARY]), 'ASC');
    }
}

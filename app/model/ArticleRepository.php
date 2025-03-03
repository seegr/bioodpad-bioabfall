<?php

namespace App\Model;

use App\Services\FileStorage;
use App\Services\PageValidator;
use Exception;
use Nette\Database\Explorer;
use Nette\Database\Table\ActiveRow;
use Nette\Database\Table\Selection;
use Nette\Security\User;
use Nette\Utils\DateTime;

class ArticleRepository
{
    public const
        TABLE_PRIMARY = 'article',
        COLUMN_PRIMARY_ID = 'id',
        COLUMN_PRIMARY_ORG = 'org',
        COLUMN_PRIMARY_TITLE = 'title',
        COLUMN_PRIMARY_PEREX = 'perex',
        COLUMN_PRIMARY_TEXT = 'text',
        COLUMN_PRIMARY_SORT = 'sort',
        COLUMN_PRIMARY_LINK = 'link',
        COLUMN_PRIMARY_IS_VISIBLE = 'is_visible',
        COLUMN_PRIMARY_IMAGE_THUMB = 'image_thumb',
        COLUMN_PRIMARY_IMAGE = 'image',
	    COLUMN_PRIMARY_IMAGE_FIT = 'image_fit',
        COLUMN_PRIMARY_USER_ID = 'user_id',
        COLUMN_PRIMARY_DATE_CREATED = 'date_created',
        COLUMN_PRIMARY_DATE_UPDATED = 'date_updated',
        COLUMN_PRIMARY_DATE_START = 'date_start',
        COLUMN_PRIMARY_DATE_END = 'date_end',

	    PRIMARY_IMAGES = 'images',

        TABLE_CATEGORY = 'article_category',
        COLUMM_CATEGORY_ID = 'id',
        COLUMM_CATEGORY_SLUG = 'slug',
        COLUMN_CATEOGRY_TITLE = 'title',

        TABLE_FILE = 'article_has_file',
        COLUMN_FILE_PARENT = 'article_id',
        COLUMN_FILE_ID = 'file_id',

        TABLE_CATEGORY_REL = 'article_has_category',
        COLUMN_CATEGORY_REL_PARENT = 'article_id',
        COLUMN_CATEGORY_REL_ID = 'category_id',

        TABLE_TAG = 'article_has_tag',
        COLUMN_TAG_PARENT = 'article_id',
        COLUMN_TAG_ID = 'tag_id',
        IMAGE_NAMESPACE = 'article';

    public function __construct(
        private Explorer $database,
        private FileStorage $fileStorage,
        private User $user
    ) {}

    public function findArticles(): Selection
    {
        return $this->database->table(self::TABLE_PRIMARY);
    }

    public function findArticleById(int $id): Selection
    {
        return $this->findArticles()->wherePrimary($id);
    }

    public function findOrgArticles(int $id): Selection
    {
        return $this->findArticles()->where(
            Utils::createChainPath(self::COLUMN_PRIMARY_USER_ID, UserRepository::COLUMN_ORGANIZATION),
            $id
        );
    }

    public function findArticleFiles(): Selection
    {
        return $this->database->table(self::TABLE_FILE);
    }

    public function findArticleTags(): Selection
    {
        return $this->database->table(self::TABLE_TAG);
    }

    public function findArticleCategories(): Selection
    {
        return $this->database->table(self::TABLE_CATEGORY_REL);
    }

    public function findCategoryTags(int $categoryId): array
    {
        $tags = $this->findArticleTags()
            ->where(sprintf('%s:%s.%s', self::COLUMN_TAG_PARENT, self::TABLE_CATEGORY_REL, self::COLUMN_CATEGORY_REL_ID), $categoryId)
            ->fetchPairs(null, self::COLUMN_TAG_ID);

        return array_unique($tags);
    }

    public function getLatestArticles(int $limit = null): array
    {
        $today = new DateTime();

        return $this->findVisibleArticles()
            ->where('
                (date_start IS NULL OR date_start <= ?) 
                AND (date_end IS NULL OR date_end >= ?)',
                $today, $today
            )
            ->limit($limit)
            ->order(self::COLUMN_PRIMARY_DATE_CREATED . ' DESC')
            ->fetchAll();
    }

    public function fetchArticles(
        int $categoryId,
        int $limit = 8,
        array $tags = null,
        string $text = null,
        int $offset = null,
    ): Selection
    {
        $sel = $this->findVisibleArticles()
            ->select("
                article.*
            ")
            ->where(sprintf(':%s.%s', self::TABLE_CATEGORY_REL, self::COLUMN_CATEGORY_REL_ID), $categoryId);

        if ($tags) {
            $sel->where(sprintf(':%s.%s', self::TABLE_TAG, self::COLUMN_TAG_ID), $tags);
        }

        if ($text) {
            $text = "%".$text."%";

            $sel->whereOr([
                sprintf('%s.%s LIKE', self::TABLE_PRIMARY, self::COLUMN_PRIMARY_TITLE) => $text
            ]);
        }

        $sel->limit($limit);

        return $sel;
    }

    public function getArticleFiles(int $articleId): array
    {
        return $this->findArticleFiles()
            ->where(self::COLUMN_FILE_PARENT, $articleId)
            ->fetchPairs(self::COLUMN_FILE_ID, self::COLUMN_FILE_ID);
    }

    public function getArticleCategories(int $articleId): array
    {
        return $this->findArticleCategories()
            ->where(self::COLUMN_CATEGORY_REL_PARENT, $articleId)
            ->fetchPairs(self::COLUMN_CATEGORY_REL_ID, self::COLUMN_CATEGORY_REL_ID);
    }

    public function getArticleTags(int $articleId): array
    {
        return $this->findArticleTags()
            ->where(self::COLUMN_TAG_PARENT, $articleId)
            ->fetchPairs(self::COLUMN_TAG_ID, self::COLUMN_TAG_ID);
    }

    public function upsertArticle(array $values, ?array $categories = null, ?array $tags = null, ?array $files = null): int
    {
	    [$values, $images] = Utils::extractValues($values, self::PRIMARY_IMAGES);
		bdump($values);
		bdump($images);

        $values[self::COLUMN_PRIMARY_IMAGE] = empty($values[self::COLUMN_PRIMARY_IMAGE]) ? null : $values[self::COLUMN_PRIMARY_IMAGE];
        $values[self::COLUMN_PRIMARY_IMAGE_THUMB] = empty($values[self::COLUMN_PRIMARY_IMAGE_THUMB]) ? null : $values[self::COLUMN_PRIMARY_IMAGE_THUMB];
        $values[self::COLUMN_PRIMARY_PEREX] = empty($values[self::COLUMN_PRIMARY_PEREX]) ? null : $values[self::COLUMN_PRIMARY_PEREX];
        $values[self::COLUMN_PRIMARY_TEXT] = empty($values[self::COLUMN_PRIMARY_TEXT]) ? null : $values[self::COLUMN_PRIMARY_TEXT];
        $values[self::COLUMN_PRIMARY_ORG] = $values[self::COLUMN_PRIMARY_ORG] ?? $this->user->getIdentity()->organization;

        foreach ([
            self::COLUMN_PRIMARY_DATE_START,
            self::COLUMN_PRIMARY_DATE_END
        ] as $col) {
            $values[$col] = !empty($values[$col]) ? new DateTime($values[$col]) : null;
        }

        if ($id = $values[self::COLUMN_PRIMARY_ID]) {
            $this->findArticleById($id)->update($values);
            $this->findArticleFiles()->where(self::COLUMN_FILE_PARENT, $id)->delete();
            $this->findArticleCategories()->where(self::COLUMN_CATEGORY_REL_PARENT, $id)->delete();
            $this->createArticleCategories($id, $categories);
            $this->findArticleTags()->where(self::COLUMN_TAG_PARENT, $id)->delete();
            $this->createArticleTags($id, $tags);
        } else {
			bdump($values);
			unset($values[self::COLUMN_PRIMARY_ID]);

            $values[self::COLUMN_PRIMARY_USER_ID] = $this->user->getId();
            $row = $this->findArticles()->insert($values);
            $this->createArticleCategories($row->id, $categories);
            $this->createArticleTags($row->id, $tags);

            $id = $row->id;
        }

	    $this->createArticleFiles($id, $files);

		return $id;
    }

    private function createArticleFiles(int $articleId, array $fileIds): void
    {
        $fileIds = array_values(array_filter($fileIds));
        if (count($fileIds) > 0) {
            $this->findArticleFiles()
                ->insert(
                    array_map(
                        fn($fileId) => [self::COLUMN_FILE_PARENT => $articleId, self::COLUMN_FILE_ID => (int) $fileId],
                        $fileIds
                    )
                );
        }
    }

    private function createArticleTags(int $articleId, ?array $tagIds = []): void
    {
        if ($tagIds && count($tagIds) > 0) {
            $this->findArticleTags()
                ->insert(
                    array_map(
                        fn($tagId) => [self::COLUMN_TAG_PARENT => $articleId, self::COLUMN_TAG_ID => $tagId],
                        $tagIds
                    )
                );
        }
    }

    private function createArticleCategories(int $articleId, ?array $categoryIds = []): void
    {
        if ($categoryIds && count($categoryIds) > 0) {
            $this->findArticleCategories()
                ->insert(
                    array_map(
                        fn($tagId) => [self::COLUMN_CATEGORY_REL_PARENT => $articleId, self::COLUMN_CATEGORY_REL_ID => $tagId],
                        $categoryIds
                    )
                );
        }
    }

    public function deleteArticle(int $id): void
    {
        $row = $this->findArticleById($id);
        $article = $row->fetch();
        if (!$article) {
            throw new Exception('Record does not exist');
        }
        $this->fileStorage->setNamespace(self::IMAGE_NAMESPACE);
        $this->fileStorage->removeFile($article->image);
        $row->delete();
    }

    public function updateArticleSort(array $articleIds): void
    {
        foreach ($articleIds as $sort => $articleId) {
            $this->findArticles()
                ->wherePrimary($articleId)
                ->update([self::COLUMN_PRIMARY_SORT => $sort]);
        }
    }

    public function findCategories(): Selection
    {
        return $this->database->table(self::TABLE_CATEGORY);
    }

    public function getCategoryInputOptions(): array
    {
        return $this->findCategories()
            ->fetchPairs(self::COLUMM_CATEGORY_ID, self::COLUMN_CATEOGRY_TITLE);
    }

    public function getCategories(): array
    {
        return $this->findCategories()
            ->fetchAssoc('slug->');
    }

    public function getCategoryBySlug(string $slug): ?ActiveRow
    {
        return $this->findCategories()
            ->where(self::COLUMM_CATEGORY_SLUG, $slug)
            ->fetch();
    }

	public function findVisibleArticles(): Selection
	{
		return $this->findArticles()
			->where(self::COLUMN_PRIMARY_IS_VISIBLE, true);
	}

}

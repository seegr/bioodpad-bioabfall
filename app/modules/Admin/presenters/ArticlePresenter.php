<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\Components\DataGrid;
use App\Components\DynamicForm;
use App\Model\ArticleRepository;
use App\Model\OrganizationRepository;
use App\Model\TagRepository;
use App\Model\UserRepository;
use App\Model\Utils;
use App\Services\FileStorage;
use Exception;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Utils\DateTime;
use Nette\Utils\Html;

final class ArticlePresenter extends BasePresenter
{
    const FIELD_FILES = 'files';
    const FIELD_CATEGORIES = 'categories';
    const FIELD_TAGS = 'tags';
    const FIELD_VIDEO = 'video';
    const GRID_COL_ORG = 'org';

    private ?ActiveRow $record = null;


    public function __construct(
        private ArticleRepository $articleRepository,
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private FileStorage $fileStorage,
        private TagRepository $tagRepository,
        private DynamicFormFactory $dynamicFormFactory
    ) {
        parent::__construct();
        $this->fileStorage->setNamespace(ArticleRepository::IMAGE_NAMESPACE);
    }

    public function actionEdit(?int $id = null)
    {
        $this->record = empty($id) ? null : $this->articleRepository->findArticleById($id)->fetch();
        if ($id && !$this->record) {
            throw new BadRequestException('Záznam neexistuje');
        }
    }

    public function actionShow(int $id): void
    {
        $this->articleRepository->findArticleById($id)->update([ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE => true]);
        $this->flashMessage('Článek byl publikován');
        $this->redirect('default');
    }

    public function actionHide(int $id): void
    {
        $this->articleRepository->findArticleById($id)->update([ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE => false]);
        $this->flashMessage('Článek byl skryt');
        $this->redirect('default');
    }

    public function actionDelete(int $id): void
    {
        try {
            $this->articleRepository->deleteArticle($id);
            $this->flashMessage("Článek byl smazán.");
        } catch (Exception $e) {
            $this->flashMessage($e->getMessage(), 'warning');
        }
        $this->redirect('default');
    }

    public function handleSortArticles(?string $idList)
    {
        $this->articleRepository->updateArticleSort(explode(",", $idList));
    }

    public function createComponentGrid(string $name): DataGrid
    {
        if ($this->isSuperAdmin()) {
            $articles = $this->articleRepository->findArticles();
        } else {
            $orgId = $this->getUserOrgId();
            $articles = $this->articleRepository->findOrgArticles($orgId);
        }

        $grid = new DataGrid;
        $grid->setDataSource($articles);
        $grid->addColumnLink(ArticleRepository::COLUMN_PRIMARY_TITLE, 'Titulek', 'edit');
        $grid->addFilterText(ArticleRepository::COLUMN_PRIMARY_TITLE, 'Titulek');
        $grid->addColumnText('image', '')
            ->setRenderer(function($item) {
                $image = $item->{ArticleRepository::COLUMN_PRIMARY_IMAGE_THUMB} ?? $item->{ArticleRepository::COLUMN_PRIMARY_IMAGE};

                if (!$image) {
                    return null;
                }

                $el = Html::el('img');
                $el->width = 100;
                $el->src = $this->fileStorage->getRelativeUrl($image);

                return $el;
            });
        $grid->addColumnDateTime(ArticleRepository::COLUMN_PRIMARY_DATE_CREATED, 'Datum');
        $grid->addFilterSelect(ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE, 'Publikováno?', [1 => 'Ano', 0 => 'Ne']);
        if ($this->isSuperAdmin()) {
            $grid->addColumnText(self::GRID_COL_ORG, 'Organizace')
                ->setRenderer(function($item) {
                    return $item->ref(ArticleRepository::COLUMN_PRIMARY_USER_ID)
                        ?->ref(UserRepository::COLUMN_ORGANIZATION)
                        ?->{OrganizationRepository::COL_TITLE};
                })
                ->setFilterSelect(
                    $this->organizationRepository->getOrgsArray(),
                    Utils::createChainPath(ArticleRepository::COLUMN_PRIMARY_USER_ID, UserRepository::COLUMN_ORGANIZATION)
                );
        }
        $grid->addColumnText(ArticleRepository::COLUMN_PRIMARY_USER_ID, 'Autor')
            ->setReplacement($this->userRepository->getNamesArray());
        $grid->addEditAction();
        $grid->addDeleteAction(ArticleRepository::COLUMN_PRIMARY_TITLE);
        $grid->addIconAction('show', 'Zobrazit', 'check');
        $grid->addIconAction('hide', 'Skrýt', 'close');
        return $grid;
    }

    public function createComponentArticleForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
          function (DynamicForm $form) {
              $form->addHidden(ArticleRepository::COLUMN_PRIMARY_ID);
              if ($this->isSuperAdmin()) {
                  $form->addSelect(ArticleRepository::COLUMN_PRIMARY_ORG, 'Organizace', $this->organizationRepository->getOrgsArray())
                    ->setRequired();
              }
              $form->addText(ArticleRepository::COLUMN_PRIMARY_TITLE,'Titulek')
                ->setRequired();
              $form->addWysiwyg(ArticleRepository::COLUMN_PRIMARY_TEXT,'Text')
                ->setRequired();
              $form->addImageUpload(ArticleRepository::COLUMN_PRIMARY_IMAGE, 'Úvodní obrázek')
                  ->setRequired('Úvodní obrázek je povinný');
	          $form->addCheckbox(ArticleRepository::COLUMN_PRIMARY_IMAGE_FIT, 'Přizpůsobit obrázek boxu');
              $form->addImageUpload(ArticleRepository::COLUMN_PRIMARY_IMAGE_THUMB, 'Náhledový obrázek');
              $form->addMultiSelect(self::FIELD_CATEGORIES, 'Kategorie', $this->articleRepository->getCategoryInputOptions())
                ->setRequired('Vyber alespoň jednu kategorii');
              $form->addMultiSelect(self::FIELD_TAGS, 'Tagy', $this->tagRepository->getInputOptions());
			  $form->addFileUpload('images', 'Galerie', true);
              $form->addText(ArticleRepository::COLUMN_PRIMARY_DATE_START, 'Publikovat od')
                  ->setHtmlAttribute('class', 'js-date');
              $form->addText(ArticleRepository::COLUMN_PRIMARY_DATE_END, 'Publikovat do')
                  ->setHtmlAttribute('class', 'js-date');
              $form->addCheckbox(ArticleRepository::COLUMN_PRIMARY_IS_VISIBLE, 'Je článek viditelný?')
                  ->setDefaultValue(true);
          },
            function (array $values) {
              $id = $values[ArticleRepository::COLUMN_PRIMARY_ID];
                $id ? $this->canUpdate() : $this->canCreate();

                [$values] = $this->fileStorage->uploadFormFiles($values);
                [$values, $categories, $tags, $files] = Utils::extractValues(
                    $values, self::FIELD_CATEGORIES, self::FIELD_TAGS, self::FIELD_FILES
                );

                $id = $this->articleRepository->upsertArticle($values, $categories, $tags, $files);
                $this->flashMessage('Záznam byl úspěšně uložen');

                $this->redirect('edit', $this->record->id ?? $id);
            },
            "článek",
            $this->record ? $this->record->toArray() + [
                    ArticleRepository::COLUMN_PRIMARY_DATE_CREATED => (new DateTime('2024-08-01')),
                    self::FIELD_CATEGORIES => $this->articleRepository->getArticleCategories($this->record->id),
                    self::FIELD_TAGS => $this->articleRepository->getArticleTags($this->record->id),
                ] : null
        );
    }
}

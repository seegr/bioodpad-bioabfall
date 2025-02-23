<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\AdminModule\Factories\FileManagerFactory;
use App\AdminModule\Factories\TagManagerFactory;
use App\Components\DataGrid;
use App\Components\DynamicForm;
use App\Components\FileManager;
use App\Components\TagManager;
use App\Model\ArticleRepository;
use App\Model\FileRepository;
use App\Model\OrganizationRepository;
use App\Model\TagRepository;
use App\Model\UserRepository;
use App\Model\Utils;
use App\Services\FileStorage;
use App\Services\ImageService;
use App\Services\LocaleService;
use App\Services\TusService;
use Exception;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Container;
use Nette\Forms\Controls\TextArea;
use Nette\Forms\Controls\TextInput;

final class OrganizationPresenter extends BasePresenter
{
    private ?ActiveRow $record = null;


    public function __construct(
        private OrganizationRepository $organizationRepository,
        private FileStorage $fileStorage,
        private DynamicFormFactory $dynamicFormFactory
    ) {
        parent::__construct();
        $this->fileStorage->setNamespace(OrganizationRepository::IMAGE_NAMESPACE);
    }

    public function actionEdit(?int $id = null)
    {
        $this->record = empty($id) ? null : $this->organizationRepository->findOrgById($id);
        if ($id && !$this->record) {
            throw new BadRequestException('Záznam neexistuje');
        }
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

    public function createComponentGrid(string $name): DataGrid
    {
        $grid = new DataGrid;
        $grid->setDataSource($this->organizationRepository->findAll());
        $grid->addColumnLink(OrganizationRepository::COL_TITLE, 'Titulek', 'edit');
        $grid->addColumnDateTime(OrganizationRepository::COL_CREATED, 'Vytvořeno')
            ->setFormat('j. n. Y');
        $grid->addEditAction();
        $grid->addDeleteAction(ArticleRepository::COLUMN_PRIMARY_TITLE);
        return $grid;
    }

    public function createComponentOrganizationForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
          function (DynamicForm $form) {
              $form->addHidden(OrganizationRepository::COL_ID);
              $form->addText(OrganizationRepository::COL_TITLE,'Název');
              $form->addText(OrganizationRepository::COL_TITLE_SHORT,'Název (krátký)');
              $form->addText(OrganizationRepository::COL_CITY, 'Město');
              $form->addText(OrganizationRepository::COL_STREET, 'Ulice');
              $form->addText(OrganizationRepository::COL_ZIP, 'PSČ');
              $form->addText(OrganizationRepository::COL_EMAIL, 'E-mail')
                ->setHtmlType('email');
              $form->addText(OrganizationRepository::COL_PHONE, 'Telefon')
                ->setHtmlType('phone');
              $form->addText(OrganizationRepository::COL_WEB, 'Web odkaz')
                  ->setHtmlType('url');
              $form->addImageUpload(OrganizationRepository::COL_LOGO, 'Logo');
              $form->addWysiwyg(OrganizationRepository::COL_TEXT, 'Text');
              $form->addText(OrganizationRepository::COL_LAT, 'lat');
              $form->addText(OrganizationRepository::COL_LNG, 'lng');
          },
            function (array $values) {
                $id = $values[OrganizationRepository::COL_ID];
                $id ? $this->canUpdate() : $this->canCreate();

                [$values] = $this->fileStorage->uploadFormFiles($values);

                $id = $this->organizationRepository->upsert($values);
                $this->flashMessage('Záznam byl úspěšně uložen');

                $this->redirect('edit', $this->record->id ?? $id);
            },
            "organizace",
            $this->record ? $this->record->toArray() : null
        );
    }
}

<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\AdminModule\Factories\TagManagerFactory;
use App\Components\DataGrid;
use App\Components\DynamicForm;
use App\Components\TagManager;
use App\Model\ArticleRepository;
use App\Model\OrganizationRepository;
use App\Model\TagRepository;
use App\Model\UserRepository;
use App\Model\Utils;
use Latte\Compiler\Tag;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Forms\Form;

final class TagPresenter extends BasePresenter
{

    private ?ActiveRow $record = null;

    public function __construct(
        private TagRepository $tagRepository,
        private UserRepository $userRepository,
        private DynamicFormFactory $dynamicFormFactory,
    )
    {
        parent::__construct();
    }

    public function actionEdit(?int $id = null)
    {
        $this->record = empty($id) ? null : $this->tagRepository->findTagById($id);
        if ($id && !$this->record) {
            throw new BadRequestException('Záznam neexistuje');
        }
    }

    public function createComponentGrid(string $name): DataGrid
    {
        $grid = new DataGrid;
        $grid->setDataSource($this->tagRepository->findAll());
        $grid->addColumnLink(TagRepository::COLUMN_TITLE, 'Text', 'edit');
        $grid->addColumnDateTime(TagRepository::COLUMN_PRIMARY_DATE_CREATED, 'Datum')
            ->setFormat('j. n. Y');
        $grid->addColumnText(ArticleRepository::COLUMN_PRIMARY_USER_ID, 'Autor')
            ->setReplacement($this->userRepository->getNamesArray());
        $grid->addEditAction();
        $grid->addDeleteAction(ArticleRepository::COLUMN_PRIMARY_TITLE);
        $grid->addIconAction('show', 'Zobrazit', 'check');
        $grid->addIconAction('hide', 'Skrýt', 'close');
        return $grid;
    }

    public function createComponentTagForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function(DynamicForm $form) {
                $form->addHidden(TagRepository::COLUMN_PRIMARY_ID);
                $form->addText(TagRepository::COLUMN_TITLE, 'Text');
            },
            function($values) {
                $id = $this->tagRepository->upsert($values);
                $this->flashMessage('Záznam byl úspěšně uložen');
                $this->redirect('default');
            },
            'Tag',
            $this->record?->toArray()
        );
    }

}

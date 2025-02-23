<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\AdminModule\Factories\DynamicFormFactory;
use App\Components\DataGrid;
use App\Components\DynamicForm;
use App\Model\OrganizationRepository;
use App\Model\UserRepository;
use Nette\Application\BadRequestException;
use Nette\Database\Table\ActiveRow;
use Nette\Database\UniqueConstraintViolationException;
use Nette\Forms\Form;
use Nette\Utils\AssertionException;

final class UserPresenter extends BasePresenter
{
    private ?ActiveRow $record = null;

    public function __construct(
        private UserRepository $userRepository,
        private OrganizationRepository $organizationRepository,
        private DynamicFormFactory $dynamicFormFactory
    )
    {
        parent::__construct();
    }

    public function actionEdit(int $id = null): void
    {
        if ($id) {
            $this->canUpdate();
            $record = $this->userRepository->findAll()->wherePrimary($id)->fetch();
            if (!$record)
                throw new BadRequestException();
            $this->record = $record;
            $this->template->isEdit = true;
        } else {
            $this->canCreate();
            $this->template->isEdit = false;
        }
    }

    public function actionDelete(int $id): void
    {
        $this->canDelete();
        $row = $this->userRepository->findAll()->wherePrimary($id);
        if (!$row->fetch())
            throw new BadRequestException();
        $this->flashMessage('Záznam úspěšně smazán!');
        $row->delete();
        $this->redirect('default');
    }

    public function createComponentUserGrid(string $name): void
    {
        $allUsers = $this->userRepository
            ->findAll()
            ->where(UserRepository::COLUMN_USER_ID . ' IS NOT NULL');

        if ($this->isSuperAdmin()) {
            $users = $allUsers;
        } else {
            $org = $this->getUserOrgId();
            $users = $this->userRepository->getUsersByOrg($org);
        }

        $grid = new DataGrid($this, $name);
        $grid->setDataSource($users);
        $grid->addColumnLink(UserRepository::COLUMN_EMAIL, 'Email', 'edit');
        if ($this->isSuperAdmin()) {
            $grid->addColumnText(UserRepository::COLUMN_ORGANIZATION, 'Organizace')
                ->setReplacement($this->organizationRepository->getOrgsArray());
        }
        $grid->addColumnText(UserRepository::COLUMN_FIRSTNAME, 'Jméno');
        $grid->addColumnText(UserRepository::COLUMN_LASTNAME, 'Příjmení');
        $grid->addColumnText(UserRepository::COLUMN_ROLE, 'Role')
            ->setReplacement($this->userRepository->roles)
            ->setSortable();
        $grid->addColumnDateTime(UserRepository::COLUMN_DATE_LOGGED, 'Datum přihlášení')
            ->setFormat('j. n. Y H:i:s')
            ->setSortable();
        $grid->addEditAction();
        $grid->addDeleteAction(UserRepository::COLUMN_EMAIL);
    }

    protected function createComponentUserForm(): DynamicForm
    {
        return $this->dynamicFormFactory->create(
            function (DynamicForm $form) {
                $form->addText(UserRepository::COLUMN_EMAIL, 'Email')
                    ->setRequired('Email je povinný');
                $form->addText(UserRepository::COLUMN_FIRSTNAME, 'Jméno');
                $form->addText(UserRepository::COLUMN_LASTNAME, 'Příjmení');
                if (!$this->record || $this->isSuperAdmin()) {
                    $form->addRadioList(UserRepository::COLUMN_ROLE, 'Role', $this->userRepository->userRoles)
                        ->setDefaultValue(UserRepository::ROLE_ADMIN)
                        ->setRequired();
                }
                if ($this->isSuperAdmin()) {
                    $form->addSelect(
                        UserRepository::COLUMN_ORGANIZATION,
                        'Organizace',
                        [null => '- Vyber organizaci -'] + $this->organizationRepository->getOrgsArray()
                    )->setRequired();
                }
            },
            function (array $values, ?int $id, Form $form) {
                $id ? $this->canUpdate() : $this->canCreate();
                try {
                    $this->userRepository->upsert($values, $id);
                    $this->flashMessage("Záznam byl " . ($this->record ? "upraven" : "vytvořen"));
                } catch (UniqueConstraintViolationException $e) {
                    $form->addError('Uživatel se zadaným emailem již existuje');
                } catch (AssertionException $e) {
                    $form->addError('Data mají nesprávný tvar');
                }
                $this->redirect($id ? "edit" : "default", $id ? [$id] : []);
            },
            'uživatele',
            $this->record ? $this->record->toArray() : null
        );
    }

}

<?php

declare(strict_types=1);

namespace App\AdminModule\Presenters;

use App\Model\UserRepository;
use App\Services\Authorizator;
use Nette\Application\ForbiddenRequestException;
use Nette\Utils\Arrays;

class BasePresenter extends \App\BaseModule\Presenters\BasePresenter
{

	protected function startup()
	{
		parent::startup();

        if (!$this->user->isLoggedIn())
            $this->redirect('Sign:in', ['backlink' => $this->storeRequest()]);

        $this->canRead();
	}

    private function isAllowed($privilege, $resource = null): bool
    {
        $resource = $resource ?? $this->getCurrentPresenterName(); // current presenter name as fallback
        return $this->user->isAllowed($resource, $privilege);
    }

    public function canDelete($throwable = true, $resource = null): bool
    {
        $isAllowed = $this->isAllowed('delete', $resource);
        if (!$isAllowed && $throwable) {
            throw new ForbiddenRequestException('Na mazání nemáte práva');
        }
        return $isAllowed;
    }

    public function canUpdate($throwable = true, $resource = null): bool
    {
        $isAllowed = $this->isAllowed('update', $resource);
        if (!$isAllowed && $throwable) {
            throw new ForbiddenRequestException('Na úpravu nemáte práva');
        }
        return $isAllowed;
    }

    public function canCreate($throwable = true, $resource = null): bool
    {
        $isAllowed = $this->isAllowed('create', $resource);
        if (!$isAllowed && $throwable) {
            throw new ForbiddenRequestException('Na vytváření nemáte práva');
        }
        return $isAllowed;
    }

    public function canRead($throwable = true, $resource = null): bool
    {
        $isAllowed = $this->isAllowed('read', $resource);
        if (!$isAllowed && $throwable) {
            throw new ForbiddenRequestException('Na čtení nemáte práva');
        }
        return $isAllowed;
    }

    public function isSuperAdmin(): bool
    {
        return $this->user->isInRole(Authorizator::ROLE_SUPERADMIN);
    }

    public function getUserOrgId(): int
    {
        return $this->getUser()->getIdentity()->{UserRepository::COLUMN_ORGANIZATION};
    }

    public function beforeRender()
    {
        parent::beforeRender();
        $navItems = [
            'Organization' => (object) [
                'presenter' => 'Organization',
                'title' => 'Organizace',
                'cta' => 'Vytvořit organizaci',
                'icon' => 'home'
            ],
            'Article' => (object) [
                'presenter' => 'Article',
                'title' => 'Články',
                'cta' => 'Vytvořit článek',
                'icon' => 'file-text'
            ],
            'Tag' => (object) [
                'presenter' => 'Tag',
                'title' => 'Tagy',
                'cta' => 'Vytvořit tag',
                'icon' => 'tag'
            ],
            'User' => (object) [
                'presenter' => 'User',
                'title' => 'Uživatelé',
                'cta' => 'Vytvořit uživatele',
                'icon' => 'users'
            ],
        ];
        $flatItems = Arrays::flatten(
            array_map(
                fn($i) => isset($i->items) ? $i->items : [$i],
                $navItems
            )
        );
        $currentPresenterName = $this->getCurrentPresenterName();
        [$navItem] = array_values(
            array_filter(
                $flatItems,
                fn($i) => $i->presenter === $currentPresenterName
            ) + [(object) ['title' => 'Nástěnka']]
        );
        $this->template->navItems = $navItems;
        $this->template->user = (object)$this->user->getIdentity()->data;
        $this->template->navItem = $navItem;
    }

    public function getCurrentPresenterName(): string
    {
        return explode(':', $this->name)[1];
    }

    public function handleLogout()
    {
        $this->user->logout();
        $this->redirect('Sign:in');
    }
}

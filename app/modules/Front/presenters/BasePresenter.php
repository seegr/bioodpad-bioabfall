<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\Components\FileManager;
use App\Filter\FileFilter;
use App\FrontModule\Components\Breadcrumbs;
use App\FrontModule\Components\ChangeLocale;
use App\FrontModule\Components\ContactForm;
use App\FrontModule\Types\BaseTemplate;
use App\Model\ArticleRepository;
use App\Model\FileRepository;
use App\Model\OrganizationRepository;
use App\Services\FileStorage;
use App\Services\ParamService;
use App\Services\LocaleService;
use App\FrontModule\Factories\BreadcrumbsFactory;
use App\FrontModule\Factories\ContactFormFactory;
use App\FrontModule\Factories\ChangeLocaleFactory;

/* @property-read BaseTemplate $template */
class BasePresenter extends \App\BaseModule\Presenters\BasePresenter
{
    protected ParamService $paramService;
    private LocaleService $localeService;
    private BreadcrumbsFactory $breadcrumbsFactory;
    private ContactFormFactory $contactFormFactory;
    private ChangeLocaleFactory $changeLocaleFactory;
    protected ArticleRepository $articleRepository;
    protected OrganizationRepository $organizationRepository;
    protected FileStorage $fileStorage;
	protected FileFilter $fileFilter;

    public function injectRepository(
        ParamService $paramService,
        LocaleService $localeService,
        BreadcrumbsFactory $breadcrumbsFactory,
        ContactFormFactory $contactFormFactory,
        ChangeLocaleFactory $changeLocaleFactory,
        ArticleRepository $articleRepository,
        OrganizationRepository $organizationRepository,
        FileStorage $fileStorage,
        FileFilter $fileFilter,
    ) {
        $this->paramService = $paramService;
        $this->localeService = $localeService;
        $this->breadcrumbsFactory = $breadcrumbsFactory;
        $this->contactFormFactory = $contactFormFactory;
        $this->changeLocaleFactory = $changeLocaleFactory;
        $this->articleRepository = $articleRepository;
        $this->organizationRepository = $organizationRepository;
        $this->fileStorage = $fileStorage;
        $this->fileFilter = $fileFilter;
    }

    protected function beforeRender()
    {
        parent::beforeRender();
        $this->template->contact = $this->paramService->getContact();
        $this->template->socialLinks = $this->paramService->getSocial();
    }

    public function createComponentBreadcrumbs(): Breadcrumbs
    {
        return $this->breadcrumbsFactory->create();
    }

    public function createComponentContactForm(): ContactForm
    {
        return $this->contactFormFactory->create();
    }

    public function createComponentChangeLocale(): ChangeLocale
    {
        return $this->changeLocaleFactory->create();
    }

    public function translate(string $message, ...$args): string
    {
        return $this->localeService->translate($message, ...$args);
    }

    public function isCategory(string $slug): bool
    {
        return !in_array($slug, ['articles', 'logos']);
    }

    public function handleGetArticleData()
    {
        $type = $this->getParameter('type');
        $itemId = (int)$this->getParameter('itemId');

        if ($type === 'article') {
            $row = $this->articleRepository->findArticleById($itemId)->fetch();
            $image = $row->image;
			$gallery = $this->articleRepository->findArticleGallery($itemId)->fetchAll();
			$files = $this->articleRepository->findArticleFiles($itemId)->fetchAll();
        }

        if ($type === 'org') {
            $row = $this->organizationRepository->findOrgById($itemId);
            $image = $row->logo;
        }


        if (isset($row)) {
            $data = $row->toArray();
            $data['image'] = $image ? $this->fileStorage->getRelativeUrl($image) : null;

			if (isset($gallery)) {
				$data['gallery'] = $gallery ? array_map(
					fn($image) => $this->fileFilter->srcset($image['source']),
					$gallery
				) : null;
			}

	        if (isset($files)) {
				$filesArr = [];
				foreach ($files as $file) {
					$srcArr = explode('/', $file['source']);

					$filesArr[] = [
						'name' => end($srcArr),
						'src' => $this->fileFilter->file($file['source']),
					];
				}
		        $data['files'] = $filesArr;
	        }

            $this->sendJson($data);
        }

    }
}
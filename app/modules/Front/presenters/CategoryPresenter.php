<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\Model\OrganizationRepository;
use App\Model\TagRepository;
use App\Services\FacebookService;
use Nette\Application\UI\Form;
use Nette\Utils\Json;

final class CategoryPresenter extends BasePresenter
{

    const FILTER_FORM = 'filterForm';
    const FORM_TAGS = 'tags';
    const FORM_TEXT = 'text';


    /* @persistent */
    public ?array $tags = null;

    /* @persistent */
    public ?string $text = null;


    public function __construct(
        private TagRepository $tagRepository,
	    private FacebookService $facebookService
    )
    {
        parent::__construct();
    }

    public function actionArticles(string $slug): void
    {
        if ($category = $this->articleRepository->getCategoryBySlug($slug)) {
            $this->template->heading = $category->title;
            $categoryTags = $this->articleRepository->findCategoryTags($category->id);
            $this->template->tags = $tags = $this->tagRepository->getTagsByIds($categoryTags);
            $this->template->filter = true;
            $this->template->text = $category->text;

            $this[self::FILTER_FORM][self::FORM_TAGS]->setItems($tags);
        }

        if ($slug === 'organizace') {
            $this->template->setFile(__DIR__ . '/../templates/Category/orgs.latte');
            $this->template->orgs = $orgs = $this->organizationRepository->findAll();
            $this->template->mapData = Json::encode(array_map(fn($i) => [
                OrganizationRepository::COL_TITLE => $i->{OrganizationRepository::COL_TITLE},
                OrganizationRepository::COL_LAT => $i->{OrganizationRepository::COL_LAT},
                OrganizationRepository::COL_LNG => $i->{OrganizationRepository::COL_LNG},
                OrganizationRepository::COL_WEB => $i->{OrganizationRepository::COL_WEB},
                'address' => OrganizationRepository::getAddress($i),
            ], $orgs->fetchAll()));
        }

        if ($slug === 'akce') {
            $this->template->setFile(__DIR__ . '/../templates/Category/events.latte');
            $this->template->heading = 'Akce';
            $fb = $this->facebookService;
            $this->template->events = $fb->getEvents();
            bdump($this->template->events);
        }

        $this->template->slug = $slug;
    }

    public function renderArticles(string $slug): void
    {
        if ($category = $this->articleRepository->getCategoryBySlug($slug)) {
            $articles = $this->articleRepository->fetchArticles(
                $category->id,
                8,
                $this->tags,
                $this->text
            );

            $this->template->articles = $articles;
            $this->template->reset = $this->tags || $this->text;
        }
    }

    public function createComponentFilterForm(): Form
    {
        $form = new Form();

        $form->addCheckboxList(self::FORM_TAGS);
        $form->addText(self::FORM_TEXT);
        $form->addSubmit('submit');

        $form->onSuccess[] = function($form, $vals) {
            bdump($vals);

            $this->text = $vals[self::FORM_TEXT];
            $this->tags = $vals[self::FORM_TAGS];

            $this->redrawControl('articles');
            $this->redrawControl('reset');
        };

        return $form;
    }

    public function handleResetFilter(): void
    {
        $this->tags = null;
        $this->text = null;

        $this->redrawControl();
    }

}

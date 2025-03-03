<?php

declare(strict_types=1);

namespace App\FrontModule\Presenters;

use App\FrontModule\Components\Calendar;
use App\Model\ArticleRepository;
use App\Model\FileRepository;
use App\Services\FacebookService;
use Nette\Utils\ArrayHash;
use Nette\Utils\DateTime;

final class HomepagePresenter extends BasePresenter
{

	public function __construct(
		private FacebookService $facebookService
	)
	{
	}

	public function renderDefault()
	{
        $this->template->grid = $this->getGrid();
//        $categoryText = $this->articleRepository->findCategories()->fetchPairs('slug', 'text');
//        $this->template->categoryText = $categoryText;
        $this->template->articleList = $this->articleRepository->getLatestArticles(6);
        $this->template->articles = $this->articleRepository->getLatestArticles();
        $this->template->gallery = $this->getGalleryImages();
	}

    public function createComponentCalendarBox(): Calendar
    {
        $calendar = new Calendar();
        $calendar->showEvents(false);

        $facebookService = $this->facebookService;

        try {
            $events = $facebookService->getEvents();
        } catch (\Exception $e) {
            $events = [];
        }

        foreach ($events as $event) {
            $calendar->addEvent(
             $event->title,
             $event->start,
             $event->end ?? null
            );
        }

        return $calendar;
    }

    public function getGrid(): ArrayHash
    {
        $categories = $this->articleRepository->getCategories();

        return ArrayHash::from($categories + [
            'articles' => [
				'title' => 'Nejnovější články',
	            'title_de' => 'Letzte posten',
            ],
	        'logos' => [
				'title' => 'Logos'
	        ],
        ]);
    }

    public function getGalleryImages(): array
    {
        return array_map(fn($i) => "static/gallery-{$i}.jpg", range(1, 6));
    }

}

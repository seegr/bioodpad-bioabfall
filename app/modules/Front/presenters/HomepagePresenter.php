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
        $categoryText = $this->articleRepository->findCategories()->fetchPairs('slug', 'text');
        $categoryText['organizace'] = $this->paramService->getSettings()['orgsText'];
        $this->template->categoryText = $categoryText;
        $this->template->articleList = $this->articleRepository->getLatestArticles(6);
        $this->template->articles = $this->articleRepository->getLatestArticles();
        $this->template->gallery = $this->getGalleryImages();
        $this->template->ecoSubjects = $this->getEcoSubjects();
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

    public function getGrid(): array
    {
        $categories = $this->articleRepository->getCategories();

        return
            [
                'organizace' => 'Organizace',
            ] +
            $categories +
            [
                'empty' => '',
                'articles' => 'Články',
                'calendar' => 'Kalendář'
            ];
    }

    public function getSlider(): ArrayHash
    {
        return ArrayHash::from([
            [
                'title' => 'Koncepce EVVO',
                'image' => 'koncepce-evvo.jpg'
            ],
            [
                'title' => 'Kalendář Vzdělávacích akcí',
                'text' => 'Libereckého kraje',
                'image' => 'vzdelavaci-akce.jpg'
            ],
            [
                'title' => 'M.R.K.E.V',
                'text' => 'Metodika a Realizace Komplexní Ekologické Výchovy',
                'image' => 'mrkev.jpg'
            ],
            [
                'title' => 'MRKVIČKA',
                'text' => 'Projekt na podporu předškolní ekologické výchovy',
                'image' => 'mrkvicka.jpg'
            ],
            [
                'title' => 'Lesní pedagogika',
                'text' => 'O lese učit v lese',
                'image' => 'lesni-pedagogika.jpg'
            ],
            [
                'title' => 'Ekoškola',
                'text' => 'Mezinárodní vzdělávací program',
                'image' => 'ekoskola.jpg'
            ],
            [
                'title' => 'Škola pro udržitelný život',
                'image' => 'skola-pro-udrzitelny-zivot.jpg'
            ],
            [
                'title' => 'Zelené úřadování',
                'image' => 'zelene-uradovani.jpg'
            ],
            [
                'title' => 'Lesní mateřské školky',
                'image' => 'lesni-skolky.jpg'
            ],
        ]);
    }

    public function getEvents(): ArrayHash
    {
        return ArrayHash::from([
            [
                'title' => 'Ukliďme Česko',
                'start' => new DateTime('9.8.2024 9:00'),
                'end' => new DateTime('9.8.2024 11:00')
            ],
            [
                'title' => 'Angličtina pro děti',
                'start' => new DateTime('14.8.2024 9:30'),
                'end' => new DateTime('14.8.2024 11:30'),
            ],
            [
                'title' => 'Konverzace v angličtině pro dospělé',
                'start' => new DateTime('14.8.2024 16:30'),
                'end' => new DateTime('14.8.2024 18:30'),
            ],
            [
                'title' => 'Den Země v Horním Maršově',
                'start' => new DateTime('16.8.2024 9:00'),
                'end' => new DateTime('16.8.2024 11:00'),
            ],
            [
                'title' => 'Ukliďme svět, ukliďme Česko',
                'start' => new DateTime('27.8.2024 9:00'),
                'end' => new DateTime('27.8.2024 11:00')
            ],
        ]);
    }

    public function getGalleryImages(): array
    {
        return array_map(fn($i) => "static/gallery-{$i}.jpg", range(1, 12));
    }

    public function getEcoSubjects(): array
    {
        return [
            'Ekoporadna Orsej',
            'Ekocentrum Brniště',
            'Čmelák',
            'Armillaria',
            'Zoo Liberec',
            'SEV Libereckého kraje',
            'DDM Vikýř',
            'Společnost pro Jizerské hory',
            'SEV Český ráj',
            'Semínko Země',
        ];
    }

}

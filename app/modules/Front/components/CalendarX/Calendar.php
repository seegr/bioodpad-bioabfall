<?php

declare(strict_types=1);

namespace App\FrontModule\Components;

use DateInterval;
use DatePeriod;
use Nette\Application\UI\Control;
use Nette\Utils\DateTime;

/* @property-read ContactFormTemplate $template*/
class CalendarX extends Control {

    private int $month;
    private int $year;

    public function render(): void
    {
        $this->template->setFile(__DIR__ . '/Calendar.latte');
        $this->template->days = $this->getDays();
        $this->template->render();
    }

    public function getDays(): array
    {
        $today = new DateTime();

        $year = $this->year ?? $today->format('Y');
        $month = $this->month ?? $today->format('M');

        $startDate = new DateTime("$year-$month-01");
        $daysInMonth = $startDate->format('t');
        $endDate = new DateTime("$year-$month-$daysInMonth");
        $interval = new DateInterval('P1D');
        $datePeriod = new DatePeriod($startDate, $interval, $endDate->add($interval));

        $days = [];

        foreach ($datePeriod as $dt) {
            bdump($dt);
        }

        return $days;
    }

}
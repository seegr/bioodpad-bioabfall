<?php

namespace App\Services;

use Nette\Utils\ArrayHash;

class Helper
{

    const MONTHS = [
        1 => "Leden",
        2 => "Únor",
        3 => "Březen",
        4 => "Duben",
        5 => "Květen",
        6 => "Červen",
        7 => "Červenec",
        8 => "Srpen",
        9 => "Září",
        10 => "Říjen",
        11 => "Listopad",
        12 => "Prosinec"
    ];

    public static function getMonths() {
        return self::MONTHS;
    }

    public function getMonth($month) {
        return self::MONTHS[$month];
    }

    public static function getDays() {
        $days = [
            1 => [
                "short" => "Po",
                "label" => "Pondělí"
            ],
            2 => [
                "short" => "Út",
                "label" => "Úterý"
            ],
            3 => [
                "short" => "St",
                "label" => "Středa"
            ],
            4 => [
                "short" => "Čt",
                "label" => "Čtvrtek"
            ],
            5 => [
                "short" => "Pá",
                "label" => "Pátek"
            ],
            6 => [
                "short" => "So",
                "label" => "Sobota"
            ],
            7 => [
                "short" => "Ne",
                "label" => "Neděle"
            ],
        ];

        return $days;
    }

    public static function getWeek() {
        $time = new \DateTime;

        $days = [];
        for ($i = 1; $i <= 7; $i++) {
            $dayNo = $time->format("N");
            $timestamp = $time->getTimestamp();

            $days[$dayNo]["label"] = strftime("%A", $timestamp);
            $days[$dayNo]["short"] = strftime("%a", $timestamp);

            $time->add(new \DateInterval("P1D"));
        }

        ksort($days);

        return ArrayHash::from($days);
    }

}
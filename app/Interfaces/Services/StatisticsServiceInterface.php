<?php

namespace App\Interfaces\Services;

interface StatisticsServiceInterface
{
    public function getDailyStatistics(): array;
}

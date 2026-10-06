<?php

declare(strict_types=1);

namespace Modules\Evaluation\Domain\Service;

final class RankingCalculator
{
    /**
     * @param array<int, list<float>> $totalsByGroup groupId => totaux
     * @return list<array{groupId:int, average:float, count:int, rank:int}>
     */
    public function compute(array $totalsByGroup): array
    {
        $rows = [];
        foreach ($totalsByGroup as $groupId => $totals) {
            $count = count($totals);
            $average = $count === 0 ? 0.0 : round(array_sum($totals) / $count, 2);
            $rows[] = ['groupId' => $groupId, 'average' => $average, 'count' => $count, 'rank' => 0];
        }

        usort($rows, static fn (array $a, array $b): int => $b['average'] <=> $a['average']);

        $rank = 0;
        $prevAvg = null;
        foreach ($rows as $i => $row) {
            if ($prevAvg === null || $row['average'] < $prevAvg) {
                $rank = $i + 1;
                $prevAvg = $row['average'];
            }
            $rows[$i]['rank'] = $rank;
        }

        return $rows;
    }
}
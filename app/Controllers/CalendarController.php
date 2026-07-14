<?php

namespace Controllers;

use Helpers\Auth;
use Services\TimeTrackingService;

class CalendarController extends Controller
{
    public function index(): void
    {
        $requestedMonth = (string) ($_GET['month'] ?? date('Y-m'));
        $month = \DateTimeImmutable::createFromFormat('!Y-m', $requestedMonth);
        if (!$month || $month->format('Y-m') !== $requestedMonth) {
            $month = new \DateTimeImmutable('first day of this month');
        }

        $monthStart = $month->modify('first day of this month');
        $monthEnd = $month->modify('last day of this month');
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $visibleTimeType = $roleId === 2 ? 'manager' : ($roleId === 3 ? 'executor' : null);
        $service = new TimeTrackingService();
        $entries = $service->getCalendarEntries(
            (int) Auth::id(),
            $monthStart->format('Y-m-d'),
            $monthEnd->format('Y-m-d'),
            $visibleTimeType
        );

        $days = [];
        for ($day = $monthStart; $day <= $monthEnd; $day = $day->modify('+1 day')) {
            $days[] = [
                'date' => $day->format('Y-m-d'),
                'day' => $day->format('j'),
                'weekday' => ['Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб', 'Вс'][(int) $day->format('N') - 1],
                'weekend' => (int) $day->format('N') >= 6,
                'today' => $day->format('Y-m-d') === date('Y-m-d'),
            ];
        }

        $rows = [];
        $dayTotals = [];
        $grandTotal = 0.0;
        foreach ($entries as $entry) {
            $key = $entry['task_id'] . ':' . $entry['time_type'];
            if (!isset($rows[$key])) {
                $rows[$key] = [
                    'task_id' => (int) $entry['task_id'],
                    'task_title' => $entry['task_title'],
                    'project_title' => $entry['project_title'],
                    'is_subtask' => !empty($entry['parent_id']),
                    'time_type' => $entry['time_type'],
                    'days' => [],
                    'total' => 0.0,
                ];
            }

            $hours = (float) $entry['hours'];
            $date = $entry['entry_date'];
            $rows[$key]['days'][$date] = $hours;
            $rows[$key]['total'] += $hours;
            $dayTotals[$date] = ($dayTotals[$date] ?? 0) + $hours;
            $grandTotal += $hours;
        }

        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];

        $this->view('calendar/index', [
            'title' => 'Календарь — Flowtask',
            'days' => $days,
            'rows' => array_values($rows),
            'dayTotals' => $dayTotals,
            'grandTotal' => $grandTotal,
            'monthTitle' => $monthNames[(int) $month->format('n')] . ' ' . $month->format('Y'),
            'previousMonth' => $month->modify('-1 month')->format('Y-m'),
            'nextMonth' => $month->modify('+1 month')->format('Y-m'),
            'currentMonth' => $month->format('Y-m'),
            'visibleTimeType' => $visibleTimeType,
        ]);
    }
}

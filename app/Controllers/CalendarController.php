<?php

namespace Controllers;

use Helpers\Auth;
use Helpers\Session;
use Middleware\TaskAccessMiddleware;
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
        $manualTimeType = $roleId === Auth::ROLE_MANAGER
            ? 'manager'
            : ($roleId === Auth::ROLE_EXECUTOR ? 'executor' : null);
        $entries = $service->getCalendarEntries(
            (int) Auth::id(),
            $monthStart->format('Y-m-d'),
            $monthEnd->format('Y-m-d'),
            $visibleTimeType
        );
        $recoverableTasks = $manualTimeType === null
            ? []
            : array_values(array_filter(
                $service->getRecoverableTasks((int) Auth::id(), $manualTimeType),
                static fn(array $task): bool => TaskAccessMiddleware::check((int) $task['id'])
            ));
        $manualOld = Session::getFlash('calendar_manual_old', []);

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

        $entriesByDate = [];
        $dayTotals = [];
        $grandTotal = 0.0;
        $projectsMeta = [];
        foreach ($entries as $entry) {
            $hours = (float) $entry['hours'];
            $date = $entry['entry_date'];
            $projectId = (int) $entry['project_id'];
            $entriesByDate[$date][] = [
                'task_id' => (int) $entry['task_id'],
                'task_title' => $entry['task_title'],
                'project_id' => $projectId,
                'project_title' => $entry['project_title'],
                'is_subtask' => !empty($entry['parent_id']),
                'time_type' => $entry['time_type'],
                'hours' => $hours,
            ];
            // Копим уникальные проекты для назначения цветов и легенды
            if (!isset($projectsMeta[$projectId])) {
                $projectsMeta[$projectId] = [
                    'id' => $projectId,
                    'title' => $entry['project_title'],
                ];
            }
            $dayTotals[$date] = ($dayTotals[$date] ?? 0) + $hours;
            $grandTotal += $hours;
        }

        // Назначаем каждому проекту цвет из палитры по порядку появления,
        // чтобы соседние проекты визуально различались, а цвет был стабилен в рамках месяца.
        $projectColors = $this->buildProjectColorMap(array_keys($projectsMeta));

        $leadingBlankDays = (int) $monthStart->format('N') - 1;
        $trailingBlankDays = (7 - (($leadingBlankDays + count($days)) % 7)) % 7;

        $monthNames = [
            1 => 'Январь', 2 => 'Февраль', 3 => 'Март', 4 => 'Апрель',
            5 => 'Май', 6 => 'Июнь', 7 => 'Июль', 8 => 'Август',
            9 => 'Сентябрь', 10 => 'Октябрь', 11 => 'Ноябрь', 12 => 'Декабрь',
        ];

        $this->view('calendar/index', [
            'title' => 'Календарь — Flowtask',
            'days' => $days,
            'entriesByDate' => $entriesByDate,
            'dayTotals' => $dayTotals,
            'grandTotal' => $grandTotal,
            'leadingBlankDays' => $leadingBlankDays,
            'trailingBlankDays' => $trailingBlankDays,
            'monthTitle' => $monthNames[(int) $month->format('n')] . ' ' . $month->format('Y'),
            'previousMonth' => $month->modify('-1 month')->format('Y-m'),
            'nextMonth' => $month->modify('+1 month')->format('Y-m'),
            'currentMonth' => $month->format('Y-m'),
            'visibleTimeType' => $visibleTimeType,
            'recoverableTasks' => $recoverableTasks,
            'manualOld' => $manualOld,
            'projectsMeta' => array_values($projectsMeta),
            'projectColors' => $projectColors,
        ]);
    }

    /**
     * Построить карту цветов проектов: project_id => набор CSS-классов Tailwind.
     *
     * Цвета берутся из фиксированной палитры по кругу. Порядок соответствует
     * порядку появления проектов, поэтому соседние проекты различаются,
     * а раскраска стабильна в пределах отображаемого месяца.
     *
     * @param int[] $projectIds Идентификаторы проектов
     * @return array<int, array{bg:string, dot:string}>
     */
    private function buildProjectColorMap(array $projectIds): array
    {
        // Палитра: фон+текст для записей и цвет кружка для легенды
        $palette = [
            ['bg' => 'bg-blue-100 text-blue-800 hover:bg-blue-200', 'dot' => 'bg-blue-400'],
            ['bg' => 'bg-emerald-100 text-emerald-800 hover:bg-emerald-200', 'dot' => 'bg-emerald-400'],
            ['bg' => 'bg-amber-100 text-amber-800 hover:bg-amber-200', 'dot' => 'bg-amber-400'],
            ['bg' => 'bg-purple-100 text-purple-800 hover:bg-purple-200', 'dot' => 'bg-purple-400'],
            ['bg' => 'bg-rose-100 text-rose-800 hover:bg-rose-200', 'dot' => 'bg-rose-400'],
            ['bg' => 'bg-cyan-100 text-cyan-800 hover:bg-cyan-200', 'dot' => 'bg-cyan-400'],
            ['bg' => 'bg-lime-100 text-lime-800 hover:bg-lime-200', 'dot' => 'bg-lime-400'],
            ['bg' => 'bg-orange-100 text-orange-800 hover:bg-orange-200', 'dot' => 'bg-orange-400'],
            ['bg' => 'bg-teal-100 text-teal-800 hover:bg-teal-200', 'dot' => 'bg-teal-400'],
            ['bg' => 'bg-fuchsia-100 text-fuchsia-800 hover:bg-fuchsia-200', 'dot' => 'bg-fuchsia-400'],
        ];

        $map = [];
        $index = 0;
        foreach ($projectIds as $projectId) {
            $map[(int) $projectId] = $palette[$index % count($palette)];
            $index++;
        }

        return $map;
    }

    /**
     * AJAX: список проектов и задач, доступных текущему пользователю для внесения времени.
     * GET /calendar/entry-options
     *
     * Используется контекстным меню календаря (правый клик по ячейке),
     * чтобы наполнить выпадающие списки «Проект» и «Задача».
     */
    public function ajaxEntryOptions(): void
    {
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $type = $roleId === Auth::ROLE_MANAGER
            ? 'manager'
            : ($roleId === Auth::ROLE_EXECUTOR ? 'executor' : null);

        if ($type === null) {
            $this->json(['error' => 'Учёт времени доступен руководителям и исполнителям'], 403);
            return;
        }

        $service = new TimeTrackingService();
        // Дополнительно фильтруем через middleware для полного совпадения прав доступа
        $projects = array_values(array_filter(
            array_map(static function (array $project): array {
                $project['tasks'] = array_values(array_filter(
                    $project['tasks'],
                    static fn(array $task): bool => TaskAccessMiddleware::check((int) $task['id'])
                ));
                return $project;
            }, $service->getEntryOptions((int) Auth::id(), $type)),
            static fn(array $project): bool => !empty($project['tasks'])
        ));

        $this->json(['projects' => $projects]);
    }

    /**
     * AJAX: быстрая запись времени за выбранную дату из контекстного меню календаря.
     * POST /calendar/quick-entry
     *
     * Принимает: task_id, hours, entry_date.
     * Прибавляет время к итогу задачи и создаёт дневную запись (как обычное внесение времени).
     * Возвращает JSON с данными созданной записи для обновления интерфейса.
     */
    public function storeQuickEntry(): void
    {
        $user = Auth::user();
        $userId = (int) Auth::id();
        $roleId = (int) ($user['role_id'] ?? 0);
        $type = $roleId === Auth::ROLE_MANAGER
            ? 'manager'
            : ($roleId === Auth::ROLE_EXECUTOR ? 'executor' : null);

        if ($type === null) {
            $this->json(['error' => 'Учёт времени доступен руководителям и исполнителям'], 403);
            return;
        }

        // Данные приходят JSON-ом от fetch()
        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $taskId = (int) ($input['task_id'] ?? 0);
        $rawHours = $input['hours'] ?? null;
        $entryDate = trim((string) ($input['entry_date'] ?? ''));

        if ($taskId <= 0 || !is_numeric($rawHours) || $entryDate === '') {
            $this->json(['error' => 'Выберите задачу, укажите часы и дату'], 422);
            return;
        }
        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['error' => 'Нет доступа к выбранной задаче'], 403);
            return;
        }

        try {
            $service = new TimeTrackingService();
            $result = $service->addTime($taskId, $userId, (float) $rawHours, $type, $entryDate);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Не удалось записать время. Попробуйте ещё раз'], 500);
            return;
        }

        if (!$result['success']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }

        $this->json([
            'success' => true,
            'entry_date' => $result['entry_date'],
            'added' => $result['added'],
        ]);
    }

    /**
     * AJAX: изменить количество часов дневной записи календаря.
     * POST /calendar/entry/update
     *
     * Принимает: task_id, time_type, entry_date, hours.
     */
    public function updateEntry(): void
    {
        $userId = (int) Auth::id();
        [$type, $error] = $this->resolveEntryContext();
        if ($error !== null) {
            $this->json(['error' => $error], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $taskId = (int) ($input['task_id'] ?? 0);
        $entryDate = trim((string) ($input['entry_date'] ?? ''));
        $rawHours = $input['hours'] ?? null;

        if ($taskId <= 0 || $entryDate === '' || !is_numeric($rawHours)) {
            $this->json(['error' => 'Укажите задачу, дату и часы'], 422);
            return;
        }
        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['error' => 'Нет доступа к выбранной задаче'], 403);
            return;
        }

        try {
            $service = new TimeTrackingService();
            $result = $service->updateCalendarEntry($taskId, $userId, $type, $entryDate, (float) $rawHours);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Не удалось изменить запись. Попробуйте ещё раз'], 500);
            return;
        }

        if (!$result['success']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * AJAX: удалить дневную запись календаря.
     * POST /calendar/entry/delete
     *
     * Принимает: task_id, time_type, entry_date.
     */
    public function deleteEntry(): void
    {
        $userId = (int) Auth::id();
        [$type, $error] = $this->resolveEntryContext();
        if ($error !== null) {
            $this->json(['error' => $error], 403);
            return;
        }

        $input = json_decode(file_get_contents('php://input'), true) ?: [];
        $taskId = (int) ($input['task_id'] ?? 0);
        $entryDate = trim((string) ($input['entry_date'] ?? ''));

        if ($taskId <= 0 || $entryDate === '') {
            $this->json(['error' => 'Некорректные данные записи'], 422);
            return;
        }
        if (!TaskAccessMiddleware::check($taskId)) {
            $this->json(['error' => 'Нет доступа к выбранной задаче'], 403);
            return;
        }

        try {
            $service = new TimeTrackingService();
            $result = $service->deleteCalendarEntry($taskId, $userId, $type, $entryDate);
        } catch (\Throwable $e) {
            $this->json(['error' => 'Не удалось удалить запись. Попробуйте ещё раз'], 500);
            return;
        }

        if (!$result['success']) {
            $this->json(['error' => $result['error']], 422);
            return;
        }

        $this->json(['success' => true]);
    }

    /**
     * Определить тип времени текущего пользователя для операций с записями.
     *
     * @return array{0: string, 1: string|null} [тип ('manager'|'executor'), ошибка|null]
     */
    private function resolveEntryContext(): array
    {
        $user = Auth::user();
        $roleId = (int) ($user['role_id'] ?? 0);
        $type = $roleId === Auth::ROLE_MANAGER
            ? 'manager'
            : ($roleId === Auth::ROLE_EXECUTOR ? 'executor' : null);

        if ($type === null) {
            return ['', 'Учёт времени доступен руководителям и исполнителям'];
        }

        return [$type, null];
    }

    /** Перенести ранее учтённое время в календарь, не меняя итог задачи. */
    public function storeManualEntry(): void
    {
        $user = Auth::user();
        $userId = (int) Auth::id();
        $roleId = (int) ($user['role_id'] ?? 0);
        $type = $roleId === Auth::ROLE_MANAGER
            ? 'manager'
            : ($roleId === Auth::ROLE_EXECUTOR ? 'executor' : null);

        $taskId = (int) ($_POST['task_id'] ?? 0);
        $rawHours = trim((string) ($_POST['hours'] ?? ''));
        $entryDate = trim((string) ($_POST['entry_date'] ?? ''));
        $old = ['task_id' => $taskId, 'hours' => $rawHours, 'entry_date' => $entryDate];

        if ($type === null) {
            Session::flash('error', 'Ручной перенос доступен руководителям и исполнителям');
            $this->redirect('/calendar');
            return;
        }
        if ($taskId <= 0 || !is_numeric($rawHours) || $entryDate === '') {
            Session::flash('error', 'Выберите задачу, дату и укажите количество часов');
            Session::flash('calendar_manual_old', $old);
            $this->redirect('/calendar');
            return;
        }
        if (!TaskAccessMiddleware::check($taskId)) {
            Session::flash('error', 'Нет доступа к выбранной задаче');
            $this->redirect('/calendar');
            return;
        }

        try {
            $service = new TimeTrackingService();
            $recoverableTaskIds = array_map(
                static fn(array $task): int => (int) $task['id'],
                $service->getRecoverableTasks($userId, $type)
            );
            if (!in_array($taskId, $recoverableTaskIds, true)) {
                Session::flash('error', 'У этой задачи нет доступного для переноса времени');
                $this->redirect('/calendar');
                return;
            }

            $result = $service->addHistoricalTimeEntry(
                $taskId,
                $userId,
                (float) $rawHours,
                $type,
                $entryDate
            );
        } catch (\Throwable $e) {
            Session::flash('error', 'Не удалось перенести время. Попробуйте ещё раз');
            Session::flash('calendar_manual_old', $old);
            $this->redirect('/calendar');
            return;
        }

        if (!$result['success']) {
            Session::flash('error', $result['error']);
            Session::flash('calendar_manual_old', $old);
            $this->redirect('/calendar?month=' . substr($entryDate, 0, 7));
            return;
        }

        Session::flash('success', 'Время перенесено в календарь без изменения общей суммы');
        $this->redirect('/calendar?month=' . substr($entryDate, 0, 7));
    }
}

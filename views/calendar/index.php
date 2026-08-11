<?php defined('APP_NAME') or die('Direct access not allowed'); ?>

<div class="page-header">
    <div>
        <h1 class="page-title"><?= $monthNames[$month] ?> <?= $year ?></h1>
        <p class="page-subtitle">View meetings, deadlines, and important dates</p>
    </div>
    <div class="btn-group">
        <a href="?month=<?= $prevMonth ?>&year=<?= $prevYear ?>" class="btn btn-outline"><i class="fas fa-chevron-left"></i></a>
        <a href="?month=<?= date('n') ?>&year=<?= date('Y') ?>" class="btn btn-outline">Today</a>
        <a href="?month=<?= $nextMonth ?>&year=<?= $nextYear ?>" class="btn btn-outline"><i class="fas fa-chevron-right"></i></a>
    </div>
</div>

<!-- Legend -->
<div class="card mb-4">
    <div class="card-body">
        <div class="flex gap-6 items-center flex-wrap">
            <span class="text-muted">Legend:</span>
            <span class="badge badge-primary">Meeting</span>
            <span class="badge badge-warning">Registration Expiry</span>
            <span class="badge badge-info">Task Due</span>
        </div>
    </div>
</div>

<!-- Calendar Grid -->
<div class="card">
    <div class="card-body p-0">
        <div class="calendar-grid">
            <?php foreach ($dayNames as $day): ?>
                <div class="calendar-day-header"><?= $day ?></div>
            <?php endforeach; ?>

            <?php
            $currentDay = 1;
            for ($i = 0; $i < 6; $i++):
                for ($j = 0; $j < 7; $j++):
                    $dayNumber = $i * 7 + $j;

                    if ($dayNumber < $firstDay) {
                        // Empty cells before first day
                        echo '<div class="calendar-day other-month"></div>';
                    } elseif ($currentDay > $daysInMonth) {
                        // Empty cells after last day
                        echo '<div class="calendar-day other-month"></div>';
                    } else {
                        // Actual day
                        $date = sprintf('%04d-%02d-%02d', $year, $month, $currentDay);
                        $isToday = $date === date('Y-m-d');
                        $dayEvents = $events[$date] ?? [];

                        echo '<div class="calendar-day' . ($isToday ? ' today' : '') . '">';
                        echo '<div style="font-weight: 600; margin-bottom: 0.5rem;">' . $currentDay . '</div>';

                        foreach (array_slice($dayEvents, 0, 3) as $e) {
                            echo '<a href="' . $e['link'] . '" class="calendar-event" style="background: var(--' . $e['color'] . ');">';
                            echo sanitize(truncateText($e['title'], 20));
                            echo '</a>';
                        }

                        if (count($dayEvents) > 3) {
                            echo '<div class="text-muted" style="font-size: 0.7rem;">+' . (count($dayEvents) - 3) . ' more</div>';
                        }

                        echo '</div>';
                        $currentDay++;
                    }
                endfor;

                if ($currentDay > $daysInMonth) break;
            endfor;
            ?>
        </div>
    </div>
</div>

<!-- Upcoming Events List -->
<div class="card mt-6">
    <div class="card-header"><h3 class="card-title">Upcoming Events</h3></div>
    <div class="card-body">
        <?php
        $upcoming = [];
        foreach ($events as $date => $dayEvents) {
            if ($date >= date('Y-m-d')) {
                foreach ($dayEvents as $e) {
                    $upcoming[] = array_merge($e, ['date' => $date]);
                }
            }
        }
        usort($upcoming, function($a, $b) { return $a['date'] <=> $b['date']; });
        ?>

        <?php if (empty($upcoming)): ?>
            <p class="text-muted">No upcoming events this month.</p>
        <?php else: ?>
            <div class="table-container">
                <table class="table">
                    <thead><tr><th>Date</th><th>Title</th><th>Type</th><th>Time</th></tr></thead>
                    <tbody>
                        <?php foreach (array_slice($upcoming, 0, 10) as $e): ?>
                            <tr>
                                <td><?= formatDate($e['date']) ?></td>
                                <td><a href="<?= $e['link'] ?>"><?= sanitize($e['title']) ?></a></td>
                                <td><span class="badge badge-<?= $e['color'] ?>"><?= ucwords($e['type']) ?></span></td>
                                <td><?= sanitize($e['time']) ?: '-' ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
</div>

<style>
.calendar-grid {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}
.calendar-day {
    min-height: 100px;
    padding: 0.5rem;
    border: 1px solid var(--grey);
    vertical-align: top;
}
.calendar-day.other-month {
    background: var(--light-grey);
}
.calendar-day.today {
    background: var(--info-light);
    font-weight: bold;
}
@media (max-width: 768px) {
    .calendar-day { min-height: 60px; }
    .calendar-event { font-size: 0.65rem; }
}
</style>

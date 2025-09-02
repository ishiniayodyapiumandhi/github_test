<?php
// Simple file-based API for appointments
// Endpoints (JSON):
// GET  api.php?action=month&year=YYYY&month=MM -> days summary {"YYYY-MM-DD": count}
// GET  api.php?action=list&date=YYYY-MM-DD -> [appointments]
// POST api.php?action=add  {date, time, duration, title, notes}
// POST api.php?action=delete {date, id}

declare(strict_types=1);

header('Content-Type: application/json');
header('Cache-Control: no-store');

$storagePath = dirname(__DIR__) . '/storage/appointments.json';

function loadData(string $path): array {
    if (!file_exists($path)) {
        return [];
    }
    $json = file_get_contents($path);
    if ($json === false || trim($json) === '') {
        return [];
    }
    $data = json_decode($json, true);
    return is_array($data) ? $data : [];
}

function saveData(string $path, array $data): bool {
    $tmp = $path . '.tmp';
    $bytes = file_put_contents($tmp, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    if ($bytes === false) return false;
    return rename($tmp, $path);
}

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function validateDateString(string $date): bool {
    $d = DateTime::createFromFormat('Y-m-d', $date);
    return $d && $d->format('Y-m-d') === $date;
}

function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw ?? '', true);
    return is_array($data) ? $data : [];
}

$action = $_GET['action'] ?? ($_POST['action'] ?? '');
if ($action === '') {
    respond(['error' => 'Missing action'], 400);
}

$data = loadData($storagePath);

switch ($action) {
    case 'month':
        $year = intval($_GET['year'] ?? 0);
        $month = intval($_GET['month'] ?? 0); // 1..12
        if ($year < 1970 || $year > 2100 || $month < 1 || $month > 12) {
            respond(['error' => 'Invalid year or month'], 400);
        }
        $firstDay = sprintf('%04d-%02d-01', $year, $month);
        $summary = [];
        foreach ($data as $dateKey => $appointments) {
            if (!is_array($appointments)) continue;
            if (substr($dateKey, 0, 7) === substr($firstDay, 0, 7)) {
                $summary[$dateKey] = count($appointments);
            }
        }
        respond(['days' => $summary]);

    case 'list':
        $date = $_GET['date'] ?? '';
        if (!validateDateString($date)) {
            respond(['error' => 'Invalid date'], 400);
        }
        $list = array_values($data[$date] ?? []);
        usort($list, function ($a, $b) {
            return strcmp($a['time'] ?? '', $b['time'] ?? '');
        });
        respond(['items' => $list]);

    case 'add':
        $body = getJsonBody();
        $date = $body['date'] ?? '';
        $time = $body['time'] ?? '';
        $duration = intval($body['duration'] ?? 0);
        $title = trim(strval($body['title'] ?? ''));
        $notes = trim(strval($body['notes'] ?? ''));

        if (!validateDateString($date)) respond(['error' => 'Invalid date'], 400);
        if (!preg_match('/^\d{2}:\d{2}$/', $time)) respond(['error' => 'Invalid time'], 400);
        if ($duration < 5 || $duration > 24 * 60) respond(['error' => 'Invalid duration'], 400);
        if ($title === '') respond(['error' => 'Title required'], 400);

        $id = bin2hex(random_bytes(8));
        $item = [
            'id' => $id,
            'time' => $time,
            'duration' => $duration,
            'title' => $title,
            'notes' => $notes,
            'createdAt' => date('c'),
        ];
        if (!isset($data[$date]) || !is_array($data[$date])) $data[$date] = [];
        $data[$date][$id] = $item;
        if (!saveData($storagePath, $data)) {
            respond(['error' => 'Failed to save'], 500);
        }
        respond(['ok' => true, 'item' => $item]);

    case 'delete':
        $body = getJsonBody();
        $date = $body['date'] ?? '';
        $id = $body['id'] ?? '';
        if (!validateDateString($date)) respond(['error' => 'Invalid date'], 400);
        if ($id === '') respond(['error' => 'Invalid id'], 400);
        if (!isset($data[$date][$id])) respond(['error' => 'Not found'], 404);
        unset($data[$date][$id]);
        if (empty($data[$date])) unset($data[$date]);
        if (!saveData($storagePath, $data)) {
            respond(['error' => 'Failed to save'], 500);
        }
        respond(['ok' => true]);

    default:
        respond(['error' => 'Unknown action'], 400);
}


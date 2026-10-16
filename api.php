<?php
declare(strict_types=1);
require_once __DIR__ . '/auth.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

function respond(array $body, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($body, JSON_UNESCAPED_SLASHES | JSON_INVALID_UTF8_SUBSTITUTE);
    exit;
}

function fail(string $message, int $status = 400): never
{
    respond(['error' => $message], $status);
}

if (!isset($_SESSION['user_id'])) {
    fail('Your session has expired. Sign in again.', 401);
}
$userId = (int) $_SESSION['user_id'];
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$payload = [];
if ($method === 'POST') {
    if (!verify_csrf($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null)) {
        fail('Your session has expired. Refresh the page and try again.', 403);
    }
    $raw = file_get_contents('php://input');
    $decoded = json_decode($raw ?: '', true);
    if (!is_array($decoded)) {
        fail('The request body must be valid JSON.');
    }
    $payload = $decoded;
}

function required_text(array $payload, string $key, int $maxLength, string $label): string
{
    if (!isset($payload[$key]) || !is_string($payload[$key])) {
        fail($label . ' must be text.');
    }
    $value = trim($payload[$key]);
    if ($value === '' || mb_strlen($value) > $maxLength) {
        fail($label . ' is required and must be no longer than ' . $maxLength . ' characters.');
    }
    return $value;
}

function optional_text(array $payload, string $key, int $maxLength, string $label): ?string
{
    if (!array_key_exists($key, $payload) || $payload[$key] === null) {
        return null;
    }
    if (!is_string($payload[$key])) {
        fail($label . ' must be text.');
    }
    $value = trim($payload[$key]);
    if (mb_strlen($value) > $maxLength) {
        fail($label . ' must be no longer than ' . $maxLength . ' characters.');
    }
    return $value === '' ? null : $value;
}

function positive_number(array $payload, string $key, string $label, bool $allowZero = false): float
{
    $raw = $payload[$key] ?? null;
    if (!is_numeric($raw) || !is_finite((float) $raw) || ($allowZero ? (float) $raw < 0 : (float) $raw <= 0)) {
        fail($label . ' must be ' . ($allowZero ? 'zero or greater.' : 'greater than zero.'));
    }
    return (float) $raw;
}

function nonnegative_integer(array $payload, string $key, string $label): int
{
    $value = filter_var($payload[$key] ?? null, FILTER_VALIDATE_INT);
    if ($value === false || $value < 0) {
        fail($label . ' must be a whole number of zero or greater.');
    }
    return $value;
}

function valid_date(array $payload, string $key, string $label): string
{
    if (!isset($payload[$key]) || !is_string($payload[$key])) {
        fail('Enter a valid ' . $label . '.');
    }
    $value = $payload[$key];
    $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);
    if (!$date || $date->format('Y-m-d') !== $value) {
        fail('Enter a valid ' . $label . '.');
    }
    return $value;
}

function owned_vehicle(PDO $pdo, int $userId, mixed $vehicleId): int
{
    if (!filter_var($vehicleId, FILTER_VALIDATE_INT) || (int) $vehicleId < 1) {
        fail('Choose a valid vehicle.');
    }
    $statement = $pdo->prepare('SELECT id FROM vehicles WHERE id = ? AND user_id = ?');
    $statement->execute([(int) $vehicleId, $userId]);
    if (!$statement->fetchColumn()) {
        fail('That vehicle was not found in your fleet.', 404);
    }
    return (int) $vehicleId;
}

function vehicle_list(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        "SELECT id, name, registration, type, odometer, next_service_odometer AS service, status
         FROM vehicles WHERE user_id = ? ORDER BY name"
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function maintenance_list(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        "SELECT m.id, m.vehicle_id AS vehicleId, v.name AS vehicle, v.registration,
                m.service_type AS task, m.notes, m.service_date AS dueDate, m.cost,
                m.odometer, m.next_service_odometer AS nextServiceOdometer,
                m.status, DATEDIFF(m.service_date, CURRENT_DATE()) AS daysUntil
         FROM maintenance_records m
         INNER JOIN vehicles v ON v.id = m.vehicle_id
         WHERE m.user_id = ?
         ORDER BY (m.status = 'Scheduled') DESC, m.service_date ASC, m.id DESC"
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function fuel_list(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        'SELECT f.id, f.vehicle_id AS vehicleId, v.name AS vehicle, v.registration,
                f.filled_at AS date, f.litres, f.price_per_litre AS pricePerLitre,
                f.odometer, (f.litres * f.price_per_litre) AS amount
         FROM fuel_entries f INNER JOIN vehicles v ON v.id = f.vehicle_id
         WHERE f.user_id = ? ORDER BY f.filled_at DESC, f.id DESC LIMIT 200'
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

function document_list(PDO $pdo, int $userId): array
{
    $statement = $pdo->prepare(
        "SELECT d.id, d.vehicle_id AS vehicleId, v.name AS vehicle, v.registration,
                d.document_type AS type, d.document_number AS documentNumber,
                d.expiry_date AS expiryDate, d.notes,
                DATEDIFF(d.expiry_date, CURRENT_DATE()) AS daysUntil
         FROM vehicle_documents d INNER JOIN vehicles v ON v.id = d.vehicle_id
         WHERE d.user_id = ? ORDER BY d.expiry_date ASC, d.id DESC"
    );
    $statement->execute([$userId]);
    return $statement->fetchAll();
}

try {
    $pdo = db();
    $account = $pdo->prepare('SELECT name, workspace_name, currency FROM users WHERE id = ?');
    $account->execute([$userId]);
    $currentAccount = $account->fetch();
    if (!$currentAccount) {
        $_SESSION = [];
        session_regenerate_id(true);
        fail('Your account is no longer available. Sign in again.', 401);
    }
    $_SESSION['user_name'] = $currentAccount['name'];
    $_SESSION['workspace_name'] = $currentAccount['workspace_name'];
    $_SESSION['currency'] = $currentAccount['currency'];

    if ($method === 'GET') {
        $resource = (string) ($_GET['resource'] ?? 'dashboard');
        if ($resource === 'vehicles') {
            respond(['data' => vehicle_list($pdo, $userId)]);
        }
        if ($resource === 'maintenance') {
            respond(['data' => maintenance_list($pdo, $userId)]);
        }
        if ($resource === 'fuel') {
            respond(['data' => fuel_list($pdo, $userId)]);
        }
        if ($resource === 'documents') {
            respond(['data' => document_list($pdo, $userId)]);
        }
        if ($resource === 'settings') {
            $statement = $pdo->prepare('SELECT name, workspace_name AS workspace, currency FROM users WHERE id = ?');
            $statement->execute([$userId]);
            respond(['data' => $statement->fetch()]);
        }
        if ($resource !== 'dashboard') {
            fail('Unknown data resource.', 404);
        }

        $vehicles = vehicle_list($pdo, $userId);
        $maintenance = maintenance_list($pdo, $userId);
        $fuel = fuel_list($pdo, $userId);
        $documents = document_list($pdo, $userId);
        $month = new DateTimeImmutable('first day of this month');
        $monthKey = $month->format('Y-m');
        $monthStart = $month->format('Y-m-d');
        $nextMonthStart = $month->modify('+1 month')->format('Y-m-d');
        $maintenanceSpend = 0.0;
        $dueServices = 0;
        $scheduledVehicles = [];
        foreach ($maintenance as $record) {
            if ($record['status'] === 'Completed' && str_starts_with($record['dueDate'], $monthKey)) {
                $maintenanceSpend += (float) $record['cost'];
            }
            if ($record['status'] === 'Scheduled' && (int) $record['daysUntil'] <= 30) {
                $dueServices++;
                $scheduledVehicles[(string) $record['vehicleId']] = true;
            }
        }
        $fuelStatement = $pdo->prepare(
            'SELECT COALESCE(SUM(litres * price_per_litre), 0)
             FROM fuel_entries WHERE user_id = ? AND filled_at >= ? AND filled_at < ?'
        );
        $fuelStatement->execute([$userId, $monthStart, $nextMonthStart]);
        $fuelSpend = (float) $fuelStatement->fetchColumn();

        $historyStatement = $pdo->prepare(
            'SELECT vehicle_id, filled_at, odometer FROM fuel_entries
             WHERE user_id = ? ORDER BY filled_at ASC, id ASC'
        );
        $historyStatement->execute([$userId]);
        $vehicleCosts = [];
        foreach ($vehicles as $vehicle) {
            $vehicleCosts[(string) $vehicle['id']] = [
                'vehicleId' => (int) $vehicle['id'],
                'vehicle' => $vehicle['name'],
                'registration' => $vehicle['registration'],
                'fuelSpend' => 0.0,
                'maintenanceSpend' => 0.0,
                'distance' => 0,
            ];
        }
        $latestOdometer = [];
        $distance = 0;
        foreach ($historyStatement->fetchAll() as $entry) {
            $vehicleKey = (string) $entry['vehicle_id'];
            if (isset($latestOdometer[$vehicleKey])) {
                $delta = max(0, (int) $entry['odometer'] - $latestOdometer[$vehicleKey]);
                if ($entry['filled_at'] >= $monthStart && $entry['filled_at'] < $nextMonthStart) {
                    $distance += $delta;
                    $vehicleCosts[$vehicleKey]['distance'] += $delta;
                }
            }
            $latestOdometer[$vehicleKey] = (int) $entry['odometer'];
        }
        $fuelByVehicle = $pdo->prepare(
            'SELECT vehicle_id, SUM(litres * price_per_litre) AS total
             FROM fuel_entries WHERE user_id = ? AND filled_at >= ? AND filled_at < ?
             GROUP BY vehicle_id'
        );
        $fuelByVehicle->execute([$userId, $monthStart, $nextMonthStart]);
        foreach ($fuelByVehicle->fetchAll() as $entry) {
            $vehicleCosts[(string) $entry['vehicle_id']]['fuelSpend'] = (float) $entry['total'];
        }
        $maintenanceByVehicle = $pdo->prepare(
            "SELECT vehicle_id, SUM(cost) AS total FROM maintenance_records
             WHERE user_id = ? AND status = 'Completed' AND service_date >= ? AND service_date < ?
             GROUP BY vehicle_id"
        );
        $maintenanceByVehicle->execute([$userId, $monthStart, $nextMonthStart]);
        foreach ($maintenanceByVehicle->fetchAll() as $entry) {
            $vehicleCosts[(string) $entry['vehicle_id']]['maintenanceSpend'] = (float) $entry['total'];
        }
        foreach ($vehicleCosts as &$costs) {
            $totalCost = $costs['fuelSpend'] + $costs['maintenanceSpend'];
            $costs['totalSpend'] = $totalCost;
            $costs['costPerKm'] = $costs['distance'] > 0 ? $totalCost / $costs['distance'] : null;
            $costs['fuelCostPerKm'] = $costs['distance'] > 0 ? $costs['fuelSpend'] / $costs['distance'] : null;
        }
        unset($costs);
        $mileageAlerts = [];
        foreach ($vehicles as $vehicle) {
            $targetOdometer = $vehicle['service'] === null ? null : (int) $vehicle['service'];
            if ($targetOdometer === null || $vehicle['status'] !== 'Active') {
                continue;
            }
            $remainingKm = $targetOdometer - (int) $vehicle['odometer'];
            if ($remainingKm <= 1000 && !isset($scheduledVehicles[(string) $vehicle['id']])) {
                $mileageAlerts[] = [
                    'vehicleId' => (int) $vehicle['id'],
                    'vehicle' => $vehicle['name'],
                    'registration' => $vehicle['registration'],
                    'targetOdometer' => $targetOdometer,
                    'remainingKm' => $remainingKm,
                    'overdue' => $remainingKm < 0,
                ];
            }
        }
        $chartStart = $month->modify('-5 months')->format('Y-m-d');
        $monthlyCosts = [];
        for ($offset = 5; $offset >= 0; $offset--) {
            $chartMonth = $month->modify('-' . $offset . ' months');
            $monthlyCosts[$chartMonth->format('Y-m')] = ['fuel' => 0.0, 'maintenance' => 0.0];
        }
        $monthlyFuel = $pdo->prepare(
            "SELECT DATE_FORMAT(filled_at, '%Y-%m') AS month_key, SUM(litres * price_per_litre) AS total
             FROM fuel_entries WHERE user_id = ? AND filled_at >= ? AND filled_at < ?
             GROUP BY month_key"
        );
        $monthlyFuel->execute([$userId, $chartStart, $nextMonthStart]);
        foreach ($monthlyFuel->fetchAll() as $entry) {
            $monthlyCosts[$entry['month_key']]['fuel'] = (float) $entry['total'];
        }
        $monthlyMaintenance = $pdo->prepare(
            "SELECT DATE_FORMAT(service_date, '%Y-%m') AS month_key, SUM(cost) AS total
             FROM maintenance_records
             WHERE user_id = ? AND status = 'Completed' AND service_date >= ? AND service_date < ?
             GROUP BY month_key"
        );
        $monthlyMaintenance->execute([$userId, $chartStart, $nextMonthStart]);
        foreach ($monthlyMaintenance->fetchAll() as $entry) {
            $monthlyCosts[$entry['month_key']]['maintenance'] = (float) $entry['total'];
        }
        $documentAlerts = 0;
        foreach ($documents as $document) {
            if ((int) $document['daysUntil'] <= 30) {
                $documentAlerts++;
            }
        }
        respond(['data' => [
            'vehicles' => $vehicles,
            'maintenance' => $maintenance,
            'fuel' => $fuel,
            'documents' => $documents,
            'summary' => [
                'vehicleCount' => count($vehicles),
                'activeVehicles' => count(array_filter($vehicles, static fn(array $vehicle): bool => $vehicle['status'] === 'Active')),
                'maintenanceSpend' => $maintenanceSpend,
                'fuelSpend' => $fuelSpend,
                'distance' => $distance,
                'dueSoon' => $dueServices + $documentAlerts + count($mileageAlerts),
                'monthlyCosts' => array_map(
                    static fn(string $key, array $totals): array => ['month' => $key, ...$totals],
                    array_keys($monthlyCosts),
                    array_values($monthlyCosts)
                ),
                'vehicleCosts' => array_values($vehicleCosts),
            ],
            'mileageAlerts' => $mileageAlerts,
        ]]);
    }

    if ($method !== 'POST') {
        header('Allow: GET, POST');
        fail('This request method is not supported.', 405);
    }

    $action = (string) ($payload['action'] ?? '');
    if ($action === 'create_vehicle') {
        $name = required_text($payload, 'name', 100, 'Vehicle name');
        $registration = strtoupper(required_text($payload, 'registration', 20, 'Registration number'));
        $type = required_text($payload, 'type', 30, 'Vehicle type');
        if (!in_array($type, ['Van', 'Pickup', 'Truck', 'Car', 'Bus', 'Motorcycle'], true)) {
            fail('Choose a supported vehicle type.');
        }
        $odometer = nonnegative_integer($payload, 'odometer', 'Odometer');
        $serviceValue = $payload['service'] ?? null;
        $service = $serviceValue === null || $serviceValue === '' ? null : nonnegative_integer(['value' => $serviceValue], 'value', 'Next service mileage');
        if ($service !== null && $service < $odometer) {
            fail('Next service mileage must be greater than or equal to the current odometer.');
        }
        $statement = $pdo->prepare('INSERT INTO vehicles (user_id, name, registration, type, odometer, next_service_odometer) VALUES (?, ?, ?, ?, ?, ?)');
        try {
            $statement->execute([$userId, $name, $registration, $type, $odometer, $service]);
        } catch (PDOException $exception) {
            if ($exception->getCode() === '23000') {
                fail('That registration number is already in your fleet.', 409);
            }
            throw $exception;
        }
        respond(['data' => ['id' => (int) $pdo->lastInsertId()]], 201);
    }

    if ($action === 'create_maintenance') {
        $vehicleId = owned_vehicle($pdo, $userId, $payload['vehicleId'] ?? null);
        $serviceType = required_text($payload, 'serviceType', 100, 'Service type');
        $serviceDate = valid_date($payload, 'serviceDate', 'service date');
        $status = $payload['status'] ?? null;
        if (!is_string($status) || !in_array($status, ['Scheduled', 'Completed'], true)) {
            fail('Choose whether this service is scheduled or completed.');
        }
        if ($status === 'Completed' && $serviceDate > (new DateTimeImmutable('today'))->format('Y-m-d')) {
            fail('A completed service cannot have a future date.');
        }
        $cost = positive_number($payload, 'cost', 'Cost', true);
        $odometer = ($payload['odometer'] ?? '') === '' ? null : nonnegative_integer($payload, 'odometer', 'Odometer');
        $nextService = ($payload['nextServiceOdometer'] ?? '') === '' ? null : nonnegative_integer($payload, 'nextServiceOdometer', 'Next service mileage');
        if ($odometer !== null && $nextService !== null && $nextService < $odometer) {
            fail('Next service mileage must be greater than or equal to the service odometer.');
        }
        $notes = optional_text($payload, 'notes', 500, 'Notes');
        $pdo->beginTransaction();
        $statement = $pdo->prepare(
            'INSERT INTO maintenance_records (user_id, vehicle_id, service_type, notes, service_date, cost, odometer, next_service_odometer, status)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $statement->execute([$userId, $vehicleId, $serviceType, $notes ?: null, $serviceDate, $cost, $odometer, $nextService, $status]);
        if ($odometer !== null || $nextService !== null) {
            $update = $pdo->prepare(
                'UPDATE vehicles SET odometer = GREATEST(odometer, COALESCE(?, odometer)),
                 next_service_odometer = COALESCE(?, next_service_odometer) WHERE id = ? AND user_id = ?'
            );
            $update->execute([$odometer, $nextService, $vehicleId, $userId]);
        }
        $pdo->commit();
        respond(['data' => ['id' => (int) $pdo->lastInsertId()]], 201);
    }

    if ($action === 'complete_maintenance') {
        $id = filter_var($payload['id'] ?? null, FILTER_VALIDATE_INT);
        if (!$id || $id < 1) {
            fail('Choose a valid maintenance record.');
        }
        $statement = $pdo->prepare("UPDATE maintenance_records SET status = 'Completed' WHERE id = ? AND user_id = ? AND status = 'Scheduled'");
        $statement->execute([$id, $userId]);
        if ($statement->rowCount() !== 1) {
            fail('The scheduled maintenance record was not found.', 404);
        }
        respond(['data' => ['completed' => true]]);
    }

    if ($action === 'create_fuel') {
        $vehicleId = owned_vehicle($pdo, $userId, $payload['vehicleId'] ?? null);
        $filledAt = valid_date($payload, 'filledAt', 'fuel date');
        $litres = positive_number($payload, 'litres', 'Litres');
        $price = positive_number($payload, 'pricePerLitre', 'Price per litre');
        $odometer = nonnegative_integer($payload, 'odometer', 'Odometer');
        $pdo->beginTransaction();
        $lock = $pdo->prepare('SELECT odometer FROM vehicles WHERE id = ? AND user_id = ? FOR UPDATE');
        $lock->execute([$vehicleId, $userId]);
        if ($odometer < (int) $lock->fetchColumn()) {
            $pdo->rollBack();
            fail('Fuel entry odometer cannot be lower than the vehicle’s current odometer.');
        }
        $statement = $pdo->prepare('INSERT INTO fuel_entries (user_id, vehicle_id, filled_at, litres, price_per_litre, odometer) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([$userId, $vehicleId, $filledAt, $litres, $price, $odometer]);
        $update = $pdo->prepare('UPDATE vehicles SET odometer = ? WHERE id = ? AND user_id = ?');
        $update->execute([$odometer, $vehicleId, $userId]);
        $pdo->commit();
        respond(['data' => ['id' => (int) $pdo->lastInsertId(), 'amount' => $litres * $price]], 201);
    }

    if ($action === 'create_document') {
        $vehicleId = owned_vehicle($pdo, $userId, $payload['vehicleId'] ?? null);
        $type = $payload['type'] ?? null;
        if (!in_array($type, ['Insurance', 'Inspection', 'Permit', 'Other'], true)) {
            fail('Choose a valid document type.');
        }
        $expiryDate = valid_date($payload, 'expiryDate', 'expiry date');
        $number = optional_text($payload, 'documentNumber', 100, 'Document number');
        $notes = optional_text($payload, 'notes', 500, 'Notes');
        $statement = $pdo->prepare('INSERT INTO vehicle_documents (user_id, vehicle_id, document_type, document_number, expiry_date, notes) VALUES (?, ?, ?, ?, ?, ?)');
        $statement->execute([$userId, $vehicleId, $type, $number, $expiryDate, $notes]);
        respond(['data' => ['id' => (int) $pdo->lastInsertId()]], 201);
    }

    if ($action === 'update_settings') {
        $workspace = required_text($payload, 'workspace', 120, 'Workspace name');
        $currency = $payload['currency'] ?? null;
        if (!in_array($currency, ['KES', 'USD', 'TZS'], true)) {
            fail('Choose a supported currency.');
        }
        $statement = $pdo->prepare('UPDATE users SET workspace_name = ?, currency = ? WHERE id = ?');
        $statement->execute([$workspace, $currency, $userId]);
        $_SESSION['workspace_name'] = $workspace;
        $_SESSION['currency'] = $currency;
        respond(['data' => ['workspace' => $workspace, 'currency' => $currency]]);
    }

    fail('Unknown action.', 404);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    error_log('FleetCare API failure: ' . $exception->getMessage());
    fail('The request could not be completed. Please try again.', 500);
}

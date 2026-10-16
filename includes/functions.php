<?php
declare(strict_types=1);

function e(mixed $value): string
{
    return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function url(string $path = ''): string
{
    return BASE_URL . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function redirect(string $path): never
{
    header('Location: ' . url($path));
    exit;
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!is_string($token) || !hash_equals(csrf_token(), $token)) {
        http_response_code(419);
        exit('Your form session expired. Go back, refresh the page, and try again.');
    }
}

function current_user(): ?array
{
    return $_SESSION['user'] ?? null;
}

function require_login(): array
{
    $user = current_user();
    if (!$user) {
        $_SESSION['flash'] = 'Please sign in to continue.';
        redirect('login.php');
    }
    return $user;
}

function set_flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function get_flash(): ?array
{
    $flash = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    if (is_string($flash)) {
        return ['message' => $flash, 'type' => 'notice'];
    }
    return is_array($flash) ? $flash : null;
}

function material_rates(?int $userId = null): array
{
    $sql = 'SELECT m.id, m.name, m.unit, m.quantity_factor, m.finish_sensitive, m.default_price,
                   COALESCE(ur.price, m.default_price) AS price
            FROM materials m
            LEFT JOIN user_material_rates ur ON ur.material_id = m.id AND ur.user_id = :user_id
            WHERE m.is_active = 1
            ORDER BY m.sort_order, m.id';
    $statement = db()->prepare($sql);
    $statement->execute(['user_id' => $userId]);
    return $statement->fetchAll();
}

function calculate_estimate(array $input, array $materials): array
{
    $projectTypes = [
        'house' => ['label' => 'New home', 'factor' => 1.00],
        'extension' => ['label' => 'Home extension', 'factor' => 0.88],
        'rental' => ['label' => 'Rental units', 'factor' => 1.08],
    ];
    $finishes = [
        'basic' => ['label' => 'Basic', 'labour' => 0.35, 'paint' => 0.72],
        'standard' => ['label' => 'Standard', 'labour' => 0.45, 'paint' => 1.00],
        'premium' => ['label' => 'Premium', 'labour' => 0.58, 'paint' => 1.28],
    ];
    if (!isset($projectTypes[$input['project_type']]) || !isset($finishes[$input['finish_level']])) {
        throw new InvalidArgumentException('Choose a valid project type and finish level.');
    }

    $area = (float) $input['floor_area'];
    $floors = (int) $input['floors'];
    $scope = $area * $floors * $projectTypes[$input['project_type']]['factor'];
    $finish = $finishes[$input['finish_level']];

    // Database quantities are planning factors per square metre; review locally before quoting.
    $items = [];
    $materialsCost = 0.0;
    foreach ($materials as $material) {
        $factor = (float) $material['quantity_factor'];
        if ((bool) $material['finish_sensitive']) {
            $factor *= $finish['paint'];
        }
        $quantity = round($scope * $factor, 2);
        $unitPrice = (float) $material['price'];
        $cost = round($quantity * $unitPrice, 2);
        if ($quantity <= 0) {
            continue;
        }
        $items[] = [
            'material_id' => (int) $material['id'],
            'name' => $material['name'],
            'unit' => $material['unit'],
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'total_cost' => $cost,
        ];
        $materialsCost += $cost;
    }

    $labour = round($materialsCost * $finish['labour'], 2);
    $transport = round($materialsCost * ((float) $input['transport_percent'] / 100), 2);
    $contingency = round(($materialsCost + $labour + $transport) * ((float) $input['contingency_percent'] / 100), 2);
    return [
        'items' => $items,
        'materials_cost' => round($materialsCost, 2),
        'labour_cost' => $labour,
        'transport_cost' => $transport,
        'contingency_cost' => $contingency,
        'total_cost' => round($materialsCost + $labour + $transport + $contingency, 2),
        'project_type_label' => $projectTypes[$input['project_type']]['label'],
        'finish_label' => $finish['label'],
    ];
}

function validate_estimate_input(array $source): array
{
    $name = trim((string) ($source['project_name'] ?? ''));
    $location = trim((string) ($source['location'] ?? ''));
    $projectType = (string) ($source['project_type'] ?? '');
    $finish = (string) ($source['finish_level'] ?? '');
    $areaRaw = $source['floor_area'] ?? '';
    $floorsRaw = $source['floors'] ?? '';
    $transportRaw = $source['transport_percent'] ?? '8';
    $contingencyRaw = $source['contingency_percent'] ?? '10';

    if (strlen($name) > 100 || strlen($location) > 100) {
        throw new InvalidArgumentException('Project name and location must each be 100 characters or fewer.');
    }
    if (!is_numeric($areaRaw) || (float) $areaRaw < 10 || (float) $areaRaw > 20000) {
        throw new InvalidArgumentException('Floor area must be between 10 and 20,000 square metres.');
    }
    if (filter_var($floorsRaw, FILTER_VALIDATE_INT) === false || (int) $floorsRaw < 1 || (int) $floorsRaw > 5) {
        throw new InvalidArgumentException('Choose between 1 and 5 floors.');
    }
    foreach (['transport' => $transportRaw, 'contingency' => $contingencyRaw] as $label => $value) {
        if (!is_numeric($value) || (float) $value < 0 || (float) $value > 30) {
            throw new InvalidArgumentException(ucfirst($label) . ' percentage must be between 0 and 30.');
        }
    }
    if ($location === '') {
        throw new InvalidArgumentException('Enter the project location.');
    }

    return [
        'project_name' => $name !== '' ? $name : 'Untitled project',
        'location' => $location,
        'project_type' => $projectType,
        'floor_area' => (float) $areaRaw,
        'floors' => (int) $floorsRaw,
        'finish_level' => $finish,
        'transport_percent' => (float) $transportRaw,
        'contingency_percent' => (float) $contingencyRaw,
    ];
}

function kes(float|int $amount): string
{
    return 'KSh ' . number_format((float) $amount, 0);
}

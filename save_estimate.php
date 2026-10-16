<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/includes/functions.php';
$user = require_login();
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('estimate.php');
}
verify_csrf();
try {
    $input = validate_estimate_input($_POST);
    $result = calculate_estimate($input, material_rates((int) $user['id']));
    $pdo = db();
    $pdo->beginTransaction();
    $insert = $pdo->prepare('INSERT INTO estimates (user_id, project_name, project_type, location, floor_area, floors, finish_level, transport_percent, contingency_percent, materials_cost, labour_cost, transport_cost, contingency_cost, total_cost) VALUES (:user_id, :project_name, :project_type, :location, :floor_area, :floors, :finish_level, :transport_percent, :contingency_percent, :materials_cost, :labour_cost, :transport_cost, :contingency_cost, :total_cost)');
    $insert->execute([
        'user_id' => $user['id'],
        'project_name' => $input['project_name'],
        'project_type' => $input['project_type'],
        'location' => $input['location'],
        'floor_area' => $input['floor_area'],
        'floors' => $input['floors'],
        'finish_level' => $input['finish_level'],
        'transport_percent' => $input['transport_percent'],
        'contingency_percent' => $input['contingency_percent'],
        'materials_cost' => $result['materials_cost'],
        'labour_cost' => $result['labour_cost'],
        'transport_cost' => $result['transport_cost'],
        'contingency_cost' => $result['contingency_cost'],
        'total_cost' => $result['total_cost'],
    ]);
    $estimateId = inserted_id($pdo, 'estimates');
    $itemInsert = $pdo->prepare('INSERT INTO estimate_items (estimate_id, material_id, material_name, unit, quantity, unit_price, total_cost) VALUES (:estimate_id, :material_id, :material_name, :unit, :quantity, :unit_price, :total_cost)');
    foreach ($result['items'] as $item) {
        $itemInsert->execute([
            'estimate_id' => $estimateId,
            'material_id' => $item['material_id'],
            'material_name' => $item['name'],
            'unit' => $item['unit'],
            'quantity' => $item['quantity'],
            'unit_price' => $item['unit_price'],
            'total_cost' => $item['total_cost'],
        ]);
    }
    $pdo->commit();
    set_flash('Your estimate has been saved.');
    redirect('estimate_detail.php?id=' . $estimateId);
} catch (InvalidArgumentException $exception) {
    set_flash($exception->getMessage(), 'error');
    redirect('estimate.php');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) {
        $pdo->rollBack();
    }
    throw $exception;
}

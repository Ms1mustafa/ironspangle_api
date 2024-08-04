<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(200);
    header("Allow: POST, OPTIONS");
    exit();
}

if ($method !== 'POST') {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Invalid method.']);
    exit();
}

// Decode the JSON input
$data = json_decode(file_get_contents("php://input"), true);

if (!isset($data['supply_chain_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Invalid Supply Chain ID.']);
    exit();
}

// Optional fields for new supply_chain values
$newPo = isset($data['po']) ? $data['po'] : null;
$newPr = isset($data['pr']) ? $data['pr'] : null;
$newDate = isset($data['date']) ? $data['date'] : null;

$SHId = intval($data['supply_chain_id']);

try {
    $con->beginTransaction();

    // Fetch the existing supply_chain
    $stmt = $con->prepare("SELECT po, pr, date FROM supply_chain WHERE id = :id");
    $stmt->bindParam(':id', $SHId);
    $stmt->execute();
    $supply_chain = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$supply_chain) {
        $con->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Supply Chain not found.']);
        exit();
    }



    // Use provided new values or fallback to existing values
    $newPo = $newPo !== null ? $newPo : $supply_chain['po'];
    $newPr = $newPr !== null ? $newPr : $supply_chain['pr'];
    $newDate = $newDate !== null ? $newDate : $supply_chain['date'];

    // Check if the new date already exists
    if ($newDate) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM supply_chain WHERE date = :date");
        $stmt->bindParam(':date', $newDate);
        $stmt->execute();
        $dateExists = $stmt->fetchColumn() > 0;

        if ($dateExists) {
            $con->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Supply Chain already exists for this date.']);
            exit();
        }
    }

    // Insert new supply_chain
    $stmt = $con->prepare("INSERT INTO supply_chain (po, pr, date) VALUES (:po, :pr, :date)");

    $stmt->bindParam(':po', $newPo);
    $stmt->bindParam(':pr', $newPr);
    $stmt->bindParam(':date', $newDate);

    if (!$stmt->execute()) {
        $con->rollBack();
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'Failed to insert new Supply Chain.', 'error' => $errorInfo]);
        exit();
    }

    $newSHId = $con->lastInsertId();

    // Fetch workers for the old supply_chain
    $stmt = $con->prepare("SELECT * FROM supply_chain_workers WHERE supply_chain_id = :id");
    $stmt->bindParam(':id', $SHId);
    $stmt->execute();
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert workers for the new supply_chain
    $stmt = $con->prepare("
        INSERT INTO supply_chain_workers (
            supply_chain_id, name, job, active_days, contract_salary, labor_salary, insurance, insurance2, ppe, transport, created_at, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($workers as $worker) {
        $stmt->execute([
            $newSHId,
            $worker['name'],
            $worker['job'],
            $worker['active_days'],
            $worker['contract_salary'],
            $worker['labor_salary'],
            $worker['insurance'],
            $worker['insurance2'],
            $worker['ppe'],
            $worker['transport'],
            $worker['created_at'],
            $worker['created_by']
        ]);
    }

    $con->commit();

    echo json_encode([
        'status' => 200,
        'message' => 'Supply Chain and associated workers copied successfully.',
        'new_supply_chain' => [
            'id' => $newSHId,
            'fixed_invoice_cost' => $newFixed_invoice_cost,
            'po' => $newPo,
            'pr' => $newPr,
            'date' => $newDate,
        ],
        'workers_count' => count($workers)
    ]);

} catch (Exception $e) {
    $con->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error copying Supply Chain and associated workers: ' . $e->getMessage()]);
}

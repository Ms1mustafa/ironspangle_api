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

if (!isset($data['mec_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Invalid MEC ID.']);
    exit();
}

// Optional fields for new mec values
$newFixed_invoice_cost = isset($data['fixed_invoice_cost']) ? $data['fixed_invoice_cost'] : null;
$newPo = isset($data['po']) ? $data['po'] : null;
$newPr = isset($data['pr']) ? $data['pr'] : null;
$newDate = isset($data['date']) ? $data['date'] : null;

$mecId = intval($data['mec_id']);

try {
    $con->beginTransaction();

    // Fetch the existing mec
    $stmt = $con->prepare("SELECT fixed_invoice_cost, po, pr, date FROM mec WHERE id = :id");
    $stmt->bindParam(':id', $mecId);
    $stmt->execute();
    $mec = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mec) {
        $con->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'MEC not found.']);
        exit();
    }



    // Use provided new values or fallback to existing values
    $newFixed_invoice_cost = $newFixed_invoice_cost !== null ? $newFixed_invoice_cost : $mec['fixed_invoice_cost'];
    $newPo = $newPo !== null ? $newPo : $mec['po'];
    $newPr = $newPr !== null ? $newPr : $mec['pr'];
    $newDate = $newDate !== null ? $newDate : $mec['date'];

    // Check if the new date already exists
    if ($newDate) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM mec WHERE date = :date");
        $stmt->bindParam(':date', $newDate);
        $stmt->execute();
        $dateExists = $stmt->fetchColumn() > 0;

        if ($dateExists) {
            $con->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'MEC already exists for this date.']);
            exit();
        }
    }

    // Insert new mec
    $stmt = $con->prepare("INSERT INTO mec (fixed_invoice_cost, po, pr, date) VALUES ( :fixed_invoice_cost, :po, :pr, :date)");

    $stmt->bindParam(':fixed_invoice_cost', $newFixed_invoice_cost);
    $stmt->bindParam(':po', $newPo);
    $stmt->bindParam(':pr', $newPr);
    $stmt->bindParam(':date', $newDate);

    if (!$stmt->execute()) {
        $con->rollBack();
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'Failed to insert new MEC.', 'error' => $errorInfo]);
        exit();
    }

    $newMecId = $con->lastInsertId();

    // Fetch workers for the old mec
    $stmt = $con->prepare("SELECT * FROM mec_workers WHERE mec_id = :id");
    $stmt->bindParam(':id', $mecId);
    $stmt->execute();
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert workers for the new mec
    $stmt = $con->prepare("
        INSERT INTO mec_workers (
            mec_id, name, status, active_days, contract_salary, labor_salary, insurance, ppe, transport, created_at, created_by) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($workers as $worker) {
        $stmt->execute([
            $newMecId,
            $worker['name'],
            $worker['status'],
            $worker['active_days'],
            $worker['contract_salary'],
            $worker['labor_salary'],
            $worker['insurance'],
            $worker['ppe'],
            $worker['transport'],
            $worker['created_at'],
            $worker['created_by']
        ]);
    }

    $con->commit();

    echo json_encode([
        'status' => 200,
        'message' => 'MEC and associated workers copied successfully.',
        'new_mec' => [
            'id' => $newMecId,
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
    echo json_encode(['status' => 500, 'message' => 'Error copying MEC and associated workers: ' . $e->getMessage()]);
}

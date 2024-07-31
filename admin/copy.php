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

if (!isset($data['admin_id'])) {
    http_response_code(400);
    echo json_encode(['status' => 400, 'message' => 'Invalid admin ID.']);
    exit();
}

// Optional fields for new admin values
$newPo = isset($data['po']) ? $data['po'] : null;
$newPr = isset($data['pr']) ? $data['pr'] : null;
$newDate = isset($data['date']) ? $data['date'] : null;

$adminId = intval($data['admin_id']);

try {
    $con->beginTransaction();

    // Fetch the existing admin
    $stmt = $con->prepare("SELECT po, pr, date FROM admin WHERE id = :id");
    $stmt->bindParam(':id', $adminId);
    $stmt->execute();
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$admin) {
        $con->rollBack();
        http_response_code(404);
        echo json_encode(['status' => 404, 'message' => 'Admin not found.']);
        exit();
    }



    // Use provided new values or fallback to existing values
    $newPo = $newPo !== null ? $newPo : $admin['po'];
    $newPr = $newPr !== null ? $newPr : $admin['pr'];
    $newDate = $newDate !== null ? $newDate : $admin['date'];

    // Check if the new date already exists
    if ($newDate) {
        $stmt = $con->prepare("SELECT COUNT(*) FROM admin WHERE date = :date");
        $stmt->bindParam(':date', $newDate);
        $stmt->execute();
        $dateExists = $stmt->fetchColumn() > 0;

        if ($dateExists) {
            $con->rollBack();
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'An admin already exists for this date.']);
            exit();
        }
    }

    // Insert new admin
    $stmt = $con->prepare("INSERT INTO admin (po, pr, date) VALUES (:po, :pr, :date)");
    $stmt->bindParam(':po', $newPo);
    $stmt->bindParam(':pr', $newPr);
    $stmt->bindParam(':date', $newDate);

    if (!$stmt->execute()) {
        $con->rollBack();
        $errorInfo = $stmt->errorInfo();
        http_response_code(500);
        echo json_encode(['status' => 500, 'message' => 'Failed to insert new admin.', 'error' => $errorInfo]);
        exit();
    }

    $newAdminId = $con->lastInsertId();

    // Fetch workers for the old admin
    $stmt = $con->prepare("SELECT * FROM admin_workers WHERE admin_id = :id");
    $stmt->bindParam(':id', $adminId);
    $stmt->execute();
    $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Insert workers for the new admin
    $stmt = $con->prepare("
        INSERT INTO admin_workers (
            admin_id, name, job, active_days, contract_salary, salary, insurance, ppe, rewards, insurance2,
            pr_days, pr_cost, transport, created_at, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    foreach ($workers as $worker) {
        $stmt->execute([
            $newAdminId,
            $worker['name'],
            $worker['job'],
            $worker['active_days'],
            $worker['contract_salary'],
            $worker['salary'],
            $worker['insurance'],
            $worker['ppe'],
            $worker['rewards'],
            $worker['insurance2'],
            $worker['pr_days'],
            $worker['pr_cost'],
            $worker['transport'],
            $worker['created_at'],
            $worker['created_by']
        ]);
    }

    $con->commit();

    echo json_encode([
        'status' => 200,
        'message' => 'Admin and associated workers copied successfully.',
        'new_admin' => [
            'id' => $newAdminId,
            'po' => $newPo,
            'pr' => $newPr,
            'date' => $newDate,
        ],
        'workers_count' => count($workers)
    ]);

} catch (Exception $e) {
    $con->rollBack();
    http_response_code(500);
    echo json_encode(['status' => 500, 'message' => 'Error copying admin and associated workers: ' . $e->getMessage()]);
}

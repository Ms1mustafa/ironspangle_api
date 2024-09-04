<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS");
        exit();

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));

        if (!$data || empty($data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'please select an invoice.']);
            exit();
        } elseif (!$data || empty($data->swift_id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'No swift provided.']);
            exit();
        }
        // Check if all required fields are empty
        if (empty($data->guarantee) && empty($data->tax) && empty($data->publish) && empty($data->fines)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'At least one field is required.']);
            exit();
        }

        // Check if the swift already exists
        $checkSql = "SELECT COUNT(*) FROM swift WHERE id = :swift_id";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':swift_id', $data->swift_id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count == 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Swift not found.']);
            exit();
        }

        // Update invoice
        $sql = "UPDATE invoice SET
                swift_id = :swift_id,
                guarantee = :guarantee,
                tax = :tax,
                publish = :publish,
                fines = :fines
            WHERE id = :id";
        $stmt = $con->prepare($sql);

        // Bind parameters, set optional parameters to null if not provided
        $stmt->bindParam(':swift_id', $data->swift_id);
        $stmt->bindParam(':guarantee', $data->guarantee);
        $stmt->bindParam(':tax', $data->tax);
        $stmt->bindParam(':publish', $data->publish);
        $stmt->bindParam(':fines', $data->fines);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Invoice assigned successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}

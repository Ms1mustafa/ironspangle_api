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
            echo json_encode(['status' => 400, 'message' => 'ID is required.']);
            exit();
        }

        // Check if the invoice_no already exists (excluding the current record)
        $checkSql = "SELECT COUNT(*) FROM invoice WHERE id = :id";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count == 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Invoice not exist.']);
            exit();
        }

        // Update invoice
        $sql = "UPDATE invoice SET
                swift_id = NULL
            WHERE id = :id";
        $stmt = $con->prepare($sql);

        // Bind parameters, set optional parameters to null if not provided
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'swift removed successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}

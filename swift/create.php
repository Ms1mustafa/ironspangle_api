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
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->swift)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        // Check if the swift value already exists
        $checkSql = "SELECT COUNT(*) FROM swift WHERE swift = :swift";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':swift', $data->swift);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The swift value already exists.']);
            exit();
        }

        // Proceed with the insertion
        $sql = "INSERT INTO swift (swift) VALUES (:swift)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':swift', $data->swift);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'Swift created successfully.',
                'swift' => [
                    'id' => $lastInsertId,
                    'swift' => $data->swift,
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }

        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));
        $sql = "UPDATE swift SET swift = :swift WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->swift) || empty($data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        // Check if the swift value already exists (excluding the current record)
        $checkSql = "SELECT COUNT(*) FROM swift WHERE swift = :swift AND id != :id";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':swift', $data->swift);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The swift value already exists.']);
            exit();
        }

        // Bind parameters and execute the update statement
        $stmt->bindParam(':swift', $data->swift);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Swift updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
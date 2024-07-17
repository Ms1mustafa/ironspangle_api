<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: DELETE, OPTIONS"); // Adjust allowed methods
        exit();
    case "DELETE":
        // Read input data
        $data = json_decode(file_get_contents('php://input'));

        // Check if data was successfully decoded
        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Invalid or missing data.']);
            exit();
        }

        // Prepare SQL statement
        $sql = "DELETE FROM ppe WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $data->id);

        // Execute SQL statement
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Expense deleted successfully.']);
            exit();
        } else {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Failed to delete Expense.']);
            exit();
        }
}
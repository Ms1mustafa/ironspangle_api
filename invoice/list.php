<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: GET, OPTIONS");
        exit();

    case "GET":
        $id = isset($_GET['id']) ? (int) $_GET['id'] : null;
        $swift_id = isset($_GET['swift_id']) ? (int) $_GET['swift_id'] : null;

        if ($id) {
            // Retrieve a single invoice by ID
            $sql = "SELECT * FROM invoice WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $id);
        } elseif ($swift_id) {
            // Retrieve invoices based on swift_id
            $sql = "SELECT * FROM invoice WHERE swift_id = :swift_id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':swift_id', $swift_id);
        } else {
            // Retrieve all invoices
            $sql = "SELECT * FROM invoice";
            $stmt = $con->prepare($sql);
        }

        $stmt->execute();
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($records) {
            http_response_code(200);
            echo json_encode([
                'status' => 200,
                'message' => 'Records retrieved successfully.',
                'data' => $records
            ]);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'No records found.']);
        }
        break;

    default:
        http_response_code(405);
        header("Allow: GET, OPTIONS");
        echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
        break;
}

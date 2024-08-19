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
        header("Allow: DELETE, OPTIONS");
        exit();

    case "DELETE":
        $data = json_decode(file_get_contents('php://input'));

        if (!$data || empty($data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'ID is required.']);
            exit();
        }

        // Delete invoice
        $sql = "DELETE FROM invoice WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Invoice deleted successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
        break;

    default:
        http_response_code(405);
        header("Allow: DELETE, OPTIONS");
        echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
        break;
}

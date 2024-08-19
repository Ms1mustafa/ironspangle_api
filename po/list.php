<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "GET":
        $data = json_decode(file_get_contents('php://input'));
        // can get all or filtered po
        $sql = "SELECT * FROM po";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($po = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($po);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'PO not found.']);
                exit();
            }
        } else {
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $po = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        http_response_code(200);
        echo json_encode($po);
        break;
}
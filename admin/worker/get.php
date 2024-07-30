<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "GET":
        $data = json_decode(file_get_contents('php://input'));
        // can get all or filtered workers
        $sql = "SELECT * FROM admin_workers";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['workerId']) && is_numeric($_GET['workerId'])) {
            $sql .= " WHERE id = :workerId";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':workerId', $_GET['workerId']);
            $stmt->execute();
            if ($workers = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($workers);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'Worker not found.']);
                exit();
            }
        } else {
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        http_response_code(200);
        echo json_encode($workers);
        break;
}
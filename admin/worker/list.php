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
        // can get all or filtered admins
        $sql = "SELECT * FROM admin_workers WHERE admin_id = :admin_id";
        $path = explode('/', $_SERVER['REQUEST_URI']);

        $stmt = $con->prepare($sql);
        $stmt->bindParam(':admin_id', $_GET['admin_id']);
        $stmt->execute();
        $workers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($workers);
        break;
}
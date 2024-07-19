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
        // can get all or filtered 
        $sql = "SELECT * FROM lafarge";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($expenses = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($expenses);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'expense not found.']);
                exit();
            }
        } else {
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        http_response_code(200);
        echo json_encode($expenses);
        break;
}
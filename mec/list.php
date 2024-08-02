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
        // can get all or filtered 
        $sql = "SELECT * FROM mec";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($mec = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($mec);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'MEC not found.']);
                exit();
            }
        } else {
            if (isset($_GET['date']) && !empty($_GET['date'])) {
                $sql .= " WHERE date = :date";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':date', $_GET['date']);
                $stmt->execute();
                $mec = $stmt->fetchAll(PDO::FETCH_ASSOC);
                http_response_code(200);
                echo json_encode($mec);
                exit();
            }
            $sql .= " WHERE date = DATE_FORMAT(CURDATE(), '%Y-%m');";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $mec = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        http_response_code(200);
        echo json_encode($mec);
        break;
}
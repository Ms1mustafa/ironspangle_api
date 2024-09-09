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
        $sql = "SELECT s.*, IFNULL(SUM(i.cost), 0) AS total_invoices_cost
                , IFNULL(COUNT(i.invoice_no), 0) AS invoices_no
                FROM swift s
                LEFT JOIN invoice i ON s.id = i.swift_id
                GROUP BY s.id";

        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE s.id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($swift = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($swift);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'Swift not found.']);
                exit();
            }
        } else {
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $swift = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }
        http_response_code(200);
        echo json_encode($swift);
        break;
}

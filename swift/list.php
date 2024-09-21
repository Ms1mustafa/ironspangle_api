<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Database connection
include '../includes/config.php';

// Get the request method
$method = $_SERVER['REQUEST_METHOD'];

// Handle preflight OPTIONS request
if ($method === "OPTIONS") {
    http_response_code(200);
    header("Allow: POST, GET, OPTIONS");
    exit();
}

// Handle GET request
if ($method === "GET") {
    // Initialize database connection (assuming $con is defined in config.php)
    global $con;

    // Check if ID is provided
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    if ($id !== null) {
        // Fetch specific swift record by ID
        $sql = "SELECT * FROM swift WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $id, PDO::PARAM_INT);
        $stmt->execute();
        $record = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($record) {
            http_response_code(200);
            echo json_encode($record);
        } else {
            http_response_code(404);
            echo json_encode(['status' => 404, 'message' => 'Swift not found.']);
        }
    } else {
        // Check if date is provided
        if (!isset($_GET['date'])) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Date parameter is required.']);
            exit();
        }

        // Fetch all swift records with total invoices cost and count
        $sql = "SELECT s.*, 
                        IFNULL(SUM(i.cost), 0) AS total_invoices_cost,
                        IFNULL(COUNT(i.invoice_no), 0) AS invoices_no,
                        IFNULL(SUM(CASE WHEN i.guarantee = 1 OR i.tax = 1 OR i.publish > 1 OR i.fines > 1 THEN i.cost ELSE 0 END), 0) AS overall_cost,
                        IFNULL(SUM(CASE WHEN i.guarantee = 1 THEN i.cost * 0.05 ELSE 0 END), 0) AS overall_guarantee,
                        IFNULL(SUM(CASE WHEN i.tax = 1 THEN i.cost * 0.03 ELSE 0 END), 0) AS overall_tax,
                        IFNULL(SUM(i.publish), 0) AS overall_publish,
                        IFNULL(SUM(i.fines), 0) AS overall_fines,
                        (IFNULL(SUM(i.cost), 0) - 
                        IFNULL(SUM(CASE WHEN i.guarantee = 1 THEN i.cost * 0.05 ELSE 0 END), 0) - 
                        IFNULL(SUM(CASE WHEN i.tax = 1 THEN i.cost * 0.03 ELSE 0 END), 0) - 
                        IFNULL(SUM(i.publish), 0) - 
                        IFNULL(SUM(i.fines), 0)) AS received
                FROM swift s
                LEFT JOIN invoice i ON s.id = i.swift_id 
                WHERE DATE_FORMAT(date, '%Y-%m') = :date
                GROUP BY s.id";

        $stmt = $con->prepare($sql);
        $stmt->bindParam(':date', $_GET['date']);
        $stmt->execute();
        $swift = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($swift);
    }
} else {
    http_response_code(405);
    echo json_encode(['status' => 405, 'message' => 'Method Not Allowed.']);
}
?>
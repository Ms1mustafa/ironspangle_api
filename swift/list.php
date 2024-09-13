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
    header("Allow: POST, GET, OPTIONS"); // Adjust allowed methods
    exit();
}

// Handle GET request
if ($method === "GET") {
    // Check if ID is provided
    $id = isset($_GET['id']) ? (int) $_GET['id'] : null;

    // Initialize database connection (assuming $con is defined in config.php)
    global $con;

    if ($id) {
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
        // Fetch all swift records with total invoices cost and count
        $sql = "SELECT s.*, IFNULL(SUM(i.cost), 0) AS total_invoices_cost,
                        IFNULL(COUNT(i.invoice_no), 0) AS invoices_no
                FROM swift s
                LEFT JOIN invoice i ON s.id = i.swift_id
                GROUP BY s.id";
        $stmt = $con->prepare($sql);
        $stmt->execute();
        $swift = $stmt->fetchAll(PDO::FETCH_ASSOC);

        http_response_code(200);
        echo json_encode($swift);
    }
}
?>
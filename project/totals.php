<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Include your config file for database connection
include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        // Respond to OPTIONS request
        http_response_code(200);
        header("Allow: GET, OPTIONS"); // Adjust allowed methods
        exit();

    case "GET":
        // Handle GET request to fetch project costs

        // Validate and sanitize project_id input (assuming it's passed as a query parameter)
        $project_id = isset($_GET['project_id']) ? intval($_GET['project_id']) : null;

        if (!$project_id) {
            // Handle case where project_id is not provided or invalid
            http_response_code(400);
            echo json_encode(array("error" => "Missing or invalid project_id parameter"));
            exit();
        }

        try {
            // Query to get total_items_cost from project_items
            $sql_items = "SELECT SUM(qty * unit_price + trans) AS total_items_cost
                          FROM project_items
                          WHERE project_id = :project_id";
            $stmt_items = $con->prepare($sql_items);
            $stmt_items->execute(array(':project_id' => $project_id));
            $total_items_cost = $stmt_items->fetchColumn();

            // Query to get total_workers_cost from project_workers
            $sql_workers = "SELECT SUM(((day + night) * cost_day + (hours * cost_hour)) + (day * food) + (day * transportation)) AS total_workers_cost
                            FROM project_workers
                            WHERE project_id = :project_id";
            $stmt_workers = $con->prepare($sql_workers);
            $stmt_workers->execute(array(':project_id' => $project_id));
            $total_workers_cost = $stmt_workers->fetchColumn();

            // Calculate total_project_cost
            $total_project_cost = $total_items_cost + $total_workers_cost;

            // Prepare response
            $response = array(
                "project_id" => $project_id,
                "total_items_cost" => $total_items_cost,
                "total_workers_cost" => $total_workers_cost,
                "total_project_cost" => $total_project_cost
            );

            // Return JSON response
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();
        } catch (PDOException $e) {
            // Handle database error
            http_response_code(500);
            echo json_encode(array("error" => "Database error: " . $e->getMessage()));
            exit();
        }

    default:
        // Handle unsupported HTTP methods
        http_response_code(405);
        echo json_encode(array("error" => "Method Not Allowed"));
        exit();
}
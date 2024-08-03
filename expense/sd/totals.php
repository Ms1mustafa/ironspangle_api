<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set CORS headers
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Credentials: true");

// Include your config file for database connection
include '../../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        // Respond to OPTIONS request
        http_response_code(200);
        header("Allow: GET, OPTIONS"); // Adjust allowed methods
        exit();

    case "GET":
        // Handle GET request to fetch project costs

        // Validate and sanitize sd_id input (assuming it's passed as a query parameter)
        $sd_id = isset($_GET['sd_id']) ? intval($_GET['sd_id']) : null;

        if (!$sd_id) {
            // Handle case where sd_id is not provided or invalid
            http_response_code(400);
            echo json_encode(array("error" => "Missing or invalid sd_id parameter"));
            exit();
        }

        try {
            $sql_workers = "SELECT SUM(days * transportation) AS total_transportation_cost,
            SUM(days * day_salary) AS total_salary
            FROM sd_workers
            WHERE sd_id = :sd_id";
            $stmt_workers = $con->prepare($sql_workers);
            $stmt_workers->execute([':sd_id' => $sd_id]);
            $result = $stmt_workers->fetch(PDO::FETCH_ASSOC);

            $total_transportation_cost = $result['total_transportation_cost'];
            $total_salary = $result['total_salary'];

            $total_sd_cost = $total_transportation_cost + $total_salary;

            // Prepare response
            $response = [
                "sd_id" => $sd_id,
                "total_transportation_cost" => $total_transportation_cost,
                "total_salary" => $total_salary,
                "total_sd_cost" => $total_sd_cost
            ];

            // Return JSON response
            http_response_code(200);
            header('Content-Type: application/json');
            echo json_encode($response);
            exit();

        } catch (PDOException $e) {
            // Handle database error
            http_response_code(500);
            echo json_encode(["error" => "Database error: " . $e->getMessage()]);
            exit();
        }
    default:
        // Handle unsupported HTTP methods
        http_response_code(405);
        echo json_encode(["error" => "Method Not Allowed"]);
        exit();
}
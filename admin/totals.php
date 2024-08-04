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

        // Validate and sanitize admin_id input (assuming it's passed as a query parameter)
        $admin_id = isset($_GET['admin_id']) ? intval($_GET['admin_id']) : null;

        if (!$admin_id) {
            // Handle case where admin_id is not provided or invalid
            http_response_code(400);
            echo json_encode(["error" => "Missing or invalid admin_id parameter"]);
            exit();
        }

        try {
            // Query to get total_items_cost from project_items
            $sql_workers = "SELECT SUM((pr_days * pr_cost) + COALESCE(rewards, 0)) AS total_contract_salary,
                          SUM((salary * active_days) - COALESCE(insurance2, 0)) AS total_labor_salary,
                          SUM(transport * active_days) AS transportation,
                          SUM(insurance) AS insurance,
                          SUM(COALESCE(ppe)) AS ppe,
                          SUM(active_days) AS working_days,
                          Sum(pr_days) AS pr_days
                          FROM admin_workers
                          WHERE admin_id = :admin_id";

            $stmt_workers = $con->prepare($sql_workers);
            $stmt_workers->execute([':admin_id' => $admin_id]);
            $result = $stmt_workers->fetch(PDO::FETCH_ASSOC);

            $total_contract_salary = $result['total_contract_salary'];
            $total_labor_salary = $result['total_labor_salary'];
            $transportation = $result['transportation'];
            $insurance = $result['insurance'];
            $ppe = $result['ppe'];
            $working_days = $result['working_days'];
            $pr_days = $result['pr_days'];

            // Prepare response
            $response = [
                "admin_id" => $admin_id,
                'total_contract_salary' => $total_contract_salary,
                'total_labor_salary' => $total_labor_salary,
                'transportation' => $transportation,
                'insurance' => $insurance,
                'ppe' => $ppe,
                'working_days' => $working_days,
                'pr_days' => $pr_days
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
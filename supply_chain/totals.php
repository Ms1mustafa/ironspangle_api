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

        // Validate and sanitize supply_chain_id input (assuming it's passed as a query parameter)
        $supply_chain_id = isset($_GET['supply_chain_id']) ? intval($_GET['supply_chain_id']) : null;

        if (!$supply_chain_id) {
            // Handle case where supply_chain_id is not provided or invalid
            http_response_code(400);
            echo json_encode(["error" => "Missing or invalid supply_chain_id parameter"]);
            exit();
        }

        try {
            // Query to get total_items_cost from project_items
            $sql_workers = "SELECT SUM(active_days * contract_salary) AS total_contract_salary,
                          SUM((labor_salary * active_days) - COALESCE(insurance2, 0)) AS total_labor_salary,
                          SUM(transport * active_days) AS transportation,
                          SUM(insurance) AS insurance,
                          SUM(COALESCE(ppe)) AS ppe,
                          SUM(active_days) AS working_days
                          FROM supply_chain_workers
                          WHERE supply_chain_id = :supply_chain_id";

            $stmt_workers = $con->prepare($sql_workers);
            $stmt_workers->execute([':supply_chain_id' => $supply_chain_id]);
            $result = $stmt_workers->fetch(PDO::FETCH_ASSOC);

            $total_contract_salary = $result['total_contract_salary'];
            $total_labor_salary = $result['total_labor_salary'];
            $transportation = $result['transportation'];
            $insurance = $result['insurance'];
            $ppe = $result['ppe'];
            $working_days = $result['working_days'];

            // Prepare response
            $response = [
                "supply_chain_id" => $supply_chain_id,
                'total_contract_salary' => $total_contract_salary,
                'total_labor_salary' => $total_labor_salary,
                'transportation' => $transportation,
                'insurance' => $insurance,
                'ppe' => $ppe,
                'working_days' => $working_days,
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
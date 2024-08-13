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
        try {
            // Get parameters from the request
            $supply_chain_id = isset($_GET['supply_chain_id']) ? $_GET['supply_chain_id'] : null;
            $year = isset($_GET['year']) ? $_GET['year'] : null; // Null if not provided

            // Base SQL query
            $sql_workers = "SELECT 
                                SUM(active_days * contract_salary) AS total_contract_salary,
                                SUM((labor_salary * active_days) - COALESCE(insurance2, 0)) AS total_labor_salary,
                                SUM(transport * active_days) AS transportation,
                                SUM(insurance) AS insurance,
                                SUM(COALESCE(ppe, 0)) AS ppe,
                                SUM(active_days) AS working_days";

            if ($supply_chain_id !== null) {
                // Filter for a specific supply_chain_id
                $sql_workers .= " FROM supply_chain_workers
                                  WHERE supply_chain_id = :supply_chain_id";
            } else {
                // Join with the supply_chain table for monthly aggregation
                $sql_workers .= ", a.date AS month
                                  FROM supply_chain_workers AS scw
                                  JOIN supply_chain AS a ON scw.supply_chain_id = a.id";
                $sql_workers .= " GROUP BY a.date";

                if ($year !== null) {
                    // Filter by year if provided
                    $sql_workers .= " HAVING LEFT(a.date, 4) = :year";
                }
            }

            // Prepare and execute the SQL statement
            $stmt_workers = $con->prepare($sql_workers);
            $params = [];

            if ($supply_chain_id !== null) {
                $params[':supply_chain_id'] = $supply_chain_id;
            } else if ($year !== null) {
                $params[':year'] = $year;
            }

            $stmt_workers->execute($params);
            $result = $stmt_workers->fetchAll(PDO::FETCH_ASSOC);

            // Prepare response
            if ($supply_chain_id !== null) {
                // Single supply_chain_id response
                $response = $result[0]; // Assuming single result
            } else {
                // Aggregate totals for each month response
                $response = [];
                foreach ($result as $row) {
                    $response[] = [
                        "month" => isset($row['month']) ? $row['month'] : null,
                        "total_contract_salary" => $row['total_contract_salary'] ?? 0,
                        "total_labor_salary" => $row['total_labor_salary'] ?? 0,
                        "transportation" => $row['transportation'] ?? 0,
                        "insurance" => $row['insurance'] ?? 0,
                        "ppe" => $row['ppe'] ?? 0,
                        "working_days" => $row['working_days'] ?? 0
                    ];
                }
            }

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
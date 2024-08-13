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
            $mec_id = isset($_GET['mec_id']) ? $_GET['mec_id'] : null;
            $year = isset($_GET['year']) ? $_GET['year'] : null; // Null if not provided

            // Base SQL query
            $sql_workers = "SELECT 
                                SUM(contract_days * contract_salary) AS total_contract_salary,
                                SUM((labor_salary * active_days)) AS total_labor_salary,
                                SUM(transport * active_days) AS transportation,
                                SUM(insurance) AS insurance,
                                SUM(COALESCE(ppe, 0)) AS ppe,
                                SUM(active_days) AS working_days,
                                a.fixed_invoice_cost AS fixed_invoice_cost";

            if ($mec_id !== null) {
                // Filter for a specific mec_id
                $sql_workers .= " FROM mec_workers
                                  WHERE mec_id = :mec_id";
            } else {
                // Join with the mec table for monthly aggregation
                $sql_workers .= ", a.date AS month
                                  FROM mec_workers AS mw
                                  JOIN mec AS a ON mw.mec_id = a.id";
                $sql_workers .= " GROUP BY a.date";

                if ($year !== null) {
                    // Filter by year if provided
                    $sql_workers .= " HAVING LEFT(a.date, 4) = :year";
                }
            }

            // Prepare and execute the SQL statement
            $stmt_workers = $con->prepare($sql_workers);
            $params = [];

            if ($mec_id !== null) {
                $params[':mec_id'] = $mec_id;
            } else if ($year !== null) {
                $params[':year'] = $year;
            }

            $stmt_workers->execute($params);
            $result = $stmt_workers->fetchAll(PDO::FETCH_ASSOC);

            // Prepare response
            if ($mec_id !== null) {
                // Single mec_id response
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
                        "working_days" => $row['working_days'] ?? 0,
                        "fixed_invoice_cost" => $row['fixed_invoice_cost'] ?? 0
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
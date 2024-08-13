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
            $admin_id = isset($_GET['admin_id']) ? $_GET['admin_id'] : null;
            $year = isset($_GET['year']) ? $_GET['year'] : null; // Null if not provided

            // Base SQL query with JOIN
            $sql_workers = "SELECT 
                                SUM((aw.pr_days * aw.pr_cost) + COALESCE(aw.rewards, 0)) AS total_contract_salary,
                                SUM((aw.salary * aw.active_days) - COALESCE(aw.insurance2, 0)) AS total_labor_salary,
                                SUM(aw.transport * aw.active_days) AS transportation,
                                SUM(aw.insurance) AS insurance,
                                SUM(COALESCE(aw.ppe)) AS ppe,
                                SUM(aw.active_days) AS working_days,
                                SUM(aw.pr_days) AS pr_days";

            if ($admin_id !== null) {
                // Specific admin_id
                $sql_workers .= " FROM admin_workers AS aw
                                  JOIN admin AS a ON aw.admin_id = a.id
                                  WHERE aw.admin_id = :admin_id";
            } else {
                // Aggregate totals for each month
                $sql_workers .= ", a.date AS month
                                  FROM admin_workers AS aw
                                  JOIN admin AS a ON aw.admin_id = a.id";

                if ($year !== null) {
                    $sql_workers .= " WHERE LEFT(a.date, 4) = :year";
                }

                $sql_workers .= " GROUP BY a.date";
            }

            // Prepare and execute the SQL statement
            $stmt_workers = $con->prepare($sql_workers);
            $params = [];

            if ($admin_id !== null) {
                $params[':admin_id'] = $admin_id;
            } else if ($year !== null) {
                $params[':year'] = $year;
            }

            $stmt_workers->execute($params);
            $result = $stmt_workers->fetchAll(PDO::FETCH_ASSOC);

            // Prepare response
            if ($admin_id !== null) {
                // Single admin_id response
                $response = $result[0]; // Assuming single result
            } else {
                // Aggregate totals for each month response
                $response = [];
                foreach ($result as $row) {
                    $response[] = [
                        "month" => $row['month'],
                        "total_contract_salary" => $row['total_contract_salary'],
                        "total_labor_salary" => $row['total_labor_salary'],
                        "transportation" => $row['transportation'],
                        "insurance" => $row['insurance'],
                        "ppe" => $row['ppe'],
                        "working_days" => $row['working_days'],
                        "pr_days" => $row['pr_days']
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
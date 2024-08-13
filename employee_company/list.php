<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS, GET"); // Adjust allowed methods
        exit();

    case "GET":
        $data = json_decode(file_get_contents('php://input'));
        $path = explode('/', $_SERVER['REQUEST_URI']);

        $sql = "SELECT * FROM employee_company";

        // Check for 'id' parameter in query string
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($employee_company = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($employee_company);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'Employee company not found.']);
                exit();
            }
        }

        // Check for 'date' or 'year' parameter in query string
        if (isset($_GET['date']) && !empty($_GET['date'])) {
            $sql .= " WHERE date = :date";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':date', $_GET['date']);
            $stmt->execute();
            $employee_company = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } elseif (isset($_GET['year']) && !empty($_GET['year'])) {
            $year = $_GET['year'];
            $sql .= " HAVING LEFT(date, 4) = :year";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':year', $year);
            $stmt->execute();
            $employee_company = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            // Default case: Fetch data for the current month
            $sql .= " WHERE date = DATE_FORMAT(CURDATE(), '%Y-%m')";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $employee_company = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        http_response_code(200);
        echo json_encode($employee_company);
        break;
}

<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "GET":
        $data = json_decode(file_get_contents('php://input'));
        // can get all or filtered projects
        $sql = "SELECT * FROM projects";
        $path = explode('/', $_SERVER['REQUEST_URI']);
        if (isset($_GET['id']) && is_numeric($_GET['id'])) {
            $sql .= " WHERE id = :id";
            $stmt = $con->prepare($sql);
            $stmt->bindParam(':id', $_GET['id']);
            $stmt->execute();
            if ($projects = $stmt->fetch(PDO::FETCH_ASSOC)) {
                http_response_code(200);
                echo json_encode($projects);
                exit();
            } else {
                http_response_code(404);
                echo json_encode(['status' => 404, 'message' => 'Project not found.']);
                exit();
            }
        } else {
            if (isset($_GET['date']) && !empty($_GET['date'])) {
                // Split the input date into year and month
                list($year, $month) = explode('-', $_GET['date']);

                // Create the start and end dates for the month
                $startDate = "$year-$month-01"; // First day of the month
                $endDate = date("Y-m-t", strtotime($startDate)); // Last day of the month

                $sql .= " WHERE created_at >= :startDate AND created_at <= :endDate";
                $stmt = $con->prepare($sql);
                $stmt->bindParam(':startDate', $startDate);
                $stmt->bindParam(':endDate', $endDate);
                $stmt->execute();
                $expenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

                http_response_code(200);
                echo json_encode($expenses);
                exit();
            }

            // Default to the current month
            $sql .= " WHERE created_at >= DATE_FORMAT(CURDATE(), '%Y-%m-01') AND created_at < DATE_FORMAT(CURDATE() + INTERVAL 1 MONTH, '%Y-%m-01')";
            $stmt = $con->prepare($sql);
            $stmt->execute();
            $projects = $stmt->fetchAll(PDO::FETCH_ASSOC);

        }
        http_response_code(200);
        echo json_encode($projects);
        break;
}
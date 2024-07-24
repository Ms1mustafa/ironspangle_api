<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../../../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));
        $sql = "INSERT INTO sd_workers (sd_id, name, days, day_cost, transportation, day_salary, created_at, created_by) VALUES(:sd_id, :name, :days, :day_cost, :transportation, :day_salary, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->sd_id) || empty($data->name) || !isset($data->days) || !isset($data->day_cost) || !isset($data->transportation) || !isset($data->day_salary)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        //day, night, hours, cost_day, food, transportation
        if (!is_numeric($data->days) || !is_numeric($data->day_cost) || !is_numeric($data->transportation) || !is_numeric($data->transportation) || !is_numeric($data->day_salary)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'days, day_cost, transportation, day_salary must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':sd_id', $data->sd_id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':days', $data->days);
        $stmt->bindParam(':day_cost', $data->day_cost);
        $stmt->bindParam(':transportation', $data->transportation);
        $stmt->bindParam(':day_salary', $data->day_salary);
        $stmt->bindParam(':created_at', $data->created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();

            $response = [
                'status' => 200,
                'message' => 'worker created successfully.',
                'worker' => [
                    'id' => $lastInsertId,
                    'name' => $data->name,
                    'days' => $data->days,
                    'day_cost' => $data->day_cost,
                    'transportation' => $data->transportation,
                    'day_salary' => $data->day_salary,
                    'created_at' => $data->created_at,
                    'created_by' => $data->created_by
                ]
            ];

            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
        }

        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));
        $sql = "UPDATE sd_workers SET name =:name, days =:days, day_cost =:day_cost, transportation =:transportation, day_salary =:day_salary WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (empty($data->name) || !isset($data->days) || !isset($data->day_cost) || !isset($data->transportation) || !isset($data->day_salary)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        if (!is_numeric($data->days) || !is_numeric($data->day_cost) || !is_numeric($data->transportation) || !is_numeric($data->transportation) || !is_numeric($data->day_salary)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'days, day_cost, transportation, day_salary must be numeric.']);
            exit();
        }

        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':days', $data->days);
        $stmt->bindParam(':day_cost', $data->day_cost);
        $stmt->bindParam(':transportation', $data->transportation);
        $stmt->bindParam(':day_salary', $data->day_salary);
        $stmt->bindParam(':id', $data->worker_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Worker updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
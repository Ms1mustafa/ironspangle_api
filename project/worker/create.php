<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include ('../../includes/config.php');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));
        $sql = "INSERT INTO project_workers (project_id, name, day, night, hours, cost_day, cost_hour, food, transportation, created_at, created_by) VALUES(:project_id, :name, :day, :night, :hours, :cost_day, :cost_hour, :food, :transportation, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->project_id) || empty($data->name) || !isset($data->day) || !isset($data->cost_day) || !isset($data->cost_hour) || !isset($data->food) || !isset($data->transportation)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        //day, night, hours, cost_day, food, transportation
        if (!is_numeric($data->day) || !is_numeric($data->night ?? 0) || !is_numeric($data->hours ?? 0) || !is_numeric($data->cost_day) || !is_numeric($data->food) || !is_numeric($data->transportation)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'day, night, hours, cost_day, food, transportation must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':project_id', $data->project_id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':day', $data->day);
        $stmt->bindParam(':night', $data->night);
        $stmt->bindParam(':hours', $data->hours);
        $stmt->bindParam(':cost_day', $data->cost_day);
        $stmt->bindParam(':cost_hour', $data->cost_hour);
        $stmt->bindParam(':food', $data->food);
        $stmt->bindParam(':transportation', $data->transportation);
        $stmt->bindParam(':created_at', $data->created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();

            $response = [
                'status' => 200,
                'message' => 'worker created successfully.',
                'worker' => [
                    'id' => $lastInsertId,
                    'project_id' => $data->project_id,
                    'name' => $data->name,
                    'day' => $data->day,
                    'night' => $data->night,
                    'hours' => $data->hours,
                    'cost_day' => $data->cost_day,
                    'cost_hour' => $data->cost_hour,
                    'food' => $data->food,
                    'transportation' => $data->transportation,
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
        $sql = "UPDATE project_workers SET name =:name, day =:day, night =:night, hours =:hours, cost_day =:cost_day, cost_hour =:cost_hour, food =:food, transportation =:transportation WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (empty($data->project_id) || empty($data->name) || !isset($data->day) || !isset($data->cost_day) || !isset($data->cost_hour) || !isset($data->food) || !isset($data->transportation)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        if (!is_numeric($data->day) || !is_numeric($data->night ?? 0) || !is_numeric($data->hours ?? 0) || !is_numeric($data->cost_day) || !is_numeric($data->food) || !is_numeric($data->transportation)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'day, night, hours, cost_day, food, transportation must be numeric.']);
            exit();
        }

        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':day', $data->day);
        $stmt->bindParam(':night', $data->night);
        $stmt->bindParam(':hours', $data->hours);
        $stmt->bindParam(':cost_day', $data->cost_day);
        $stmt->bindParam(':cost_hour', $data->cost_hour);
        $stmt->bindParam(':food', $data->food);
        $stmt->bindParam(':transportation', $data->transportation);
        $stmt->bindParam(':id', $data->worker_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Worker updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));
        $sql = "INSERT INTO sd(name, budget, isg, po, pr, created_at, created_by) VALUES(:name, :budget, :isg, :po, :pr, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->name) || !isset($data->budget) || !isset($data->isg) || !isset($data->po) || !isset($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->budget) || !is_numeric($data->isg) || !is_numeric($data->po) || !is_numeric($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'budget, isg, po, pr must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':budget', $data->budget);
        $stmt->bindParam(':isg', $data->isg);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'SD created successfully.',
                'sd' => [
                    'id' => $lastInsertId,
                    'name' => $data->name,
                    'budget' => $data->budget,
                    'isg' => $data->isg,
                    'po' => $data->po,
                    'pr' => $data->pr,
                    'created_at' => $created_at,
                    'created_by' => $data->created_by
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }

        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));
        $sql = "UPDATE sd SET name= :name, budget =:budget, isg =:isg, po =:po, pr =:pr WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->name) || !isset($data->budget) || !isset($data->isg) || !isset($data->po) || !isset($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->budget) || !is_numeric($data->isg) || !is_numeric($data->po) || !is_numeric($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'budget, isg, po, pr must be numeric.']);
            exit();
        }

        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':budget', $data->budget);
        $stmt->bindParam(':isg', $data->isg);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':id', $data->sd_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'SD updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
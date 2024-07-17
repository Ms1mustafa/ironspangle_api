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
        $sql = "INSERT INTO lafarge(item, cost, date, remark, created_at, created_by) VALUES(:item, :cost, :date, :remark, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->item) || empty($data->cost) || empty($data->date) || empty($data->remark)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->cost)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'cost must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');
        $stmt->bindParam(':item', $data->item);
        $stmt->bindParam(':cost', $data->cost);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':remark', $data->remark);
        $stmt->bindParam(':created_at', $created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'Expense created successfully.',
                'expense' => [
                    'id' => $lastInsertId,
                    'item' => $data->item,
                    'cost' => $data->cost,
                    'date' => $data->date,
                    'remark' => $data->remark,
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

        $sql = "UPDATE lafarge SET item = :item, cost = :cost, date = :date, remark = :remark WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->item) || empty($data->cost) || empty($data->date) || empty($data->remark)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->cost)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'cost must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $stmt->bindParam(':item', $data->item);
        $stmt->bindParam(':cost', $data->cost);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':remark', $data->remark);
        $stmt->bindParam(':id', $data->expense_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Expense updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
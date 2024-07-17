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
        $sql = "INSERT INTO ppe(item, unit, qty, unit_price, date, created_at, created_by) VALUES(:item, :unit, :qty, :unit_price, :date, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->item) || empty($data->unit) || empty($data->qty) || empty($data->unit_price) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->qty) || !is_numeric($data->unit_price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'qty, unit_price must be numeric.']);
            exit();
        }
        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');
        $stmt->bindParam(':item', $data->item);
        $stmt->bindParam(':unit', $data->unit);
        $stmt->bindParam(':qty', $data->qty);
        $stmt->bindParam(':unit_price', $data->unit_price);
        $stmt->bindParam(':date', $data->date);
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
                    'unit' => $data->unit,
                    'qty' => $data->qty,
                    'unit_price' => $data->unit_price,
                    'date' => $data->date,
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

        $sql = "UPDATE ppe SET item = :item, unit = :unit, qty = :qty, unit_price = :unit_price, date = :date WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->item) || empty($data->unit) || empty($data->qty) || empty($data->unit_price) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->qty) || !is_numeric($data->unit_price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'qty, unit_price must be numeric.']);
            exit();
        }

        $stmt->bindParam(':item', $data->item);
        $stmt->bindParam(':unit', $data->unit);
        $stmt->bindParam(':qty', $data->qty);
        $stmt->bindParam(':unit_price', $data->unit_price);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':id', $data->expense_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Expense updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
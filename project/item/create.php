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
        $sql = "INSERT INTO project_items (project_id, name, unit, qty, unit_price, trans, date, remarks, created_at, created_by) VALUES(:project_id, :name, :unit, :qty, :unit_price, :trans, :date, :remarks, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->project_id) || empty($data->name) || empty($data->unit) || !isset($data->qty) || !isset($data->unit_price) || !isset($data->trans) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        if (!is_numeric($data->qty) || !is_numeric($data->unit_price) || !is_numeric($data->trans)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'qty, unit_price, trans must be numeric.']);
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
        $stmt->bindParam(':unit', $data->unit);
        $stmt->bindParam(':qty', $data->qty);
        $stmt->bindParam(':unit_price', $data->unit_price);
        $stmt->bindParam(':trans', $data->trans);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':remarks', $data->remarks);
        $stmt->bindParam(':created_at', $data->created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();

            $response = [
                'status' => 200,
                'message' => 'Item created successfully.',
                'item' => [
                    'id' => $lastInsertId,
                    'project_id' => $data->project_id,
                    'name' => $data->name,
                    'unit' => $data->unit,
                    'qty' => $data->qty,
                    'unit_price' => $data->unit_price,
                    'trans' => $data->trans,
                    'date' => $data->date,
                    'remarks' => $data->remarks,
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
        //name, unit, qty, unit_price, trans, date, remarks
        $sql = "UPDATE project_items SET name =:name, unit =:unit, qty =:qty, unit_price =:unit_price, trans =:trans, date =:date, remarks =:remarks WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (empty($data->project_id) || empty($data->name) || empty($data->unit) || !isset($data->qty) || !isset($data->unit_price) || !isset($data->trans) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }
        //check if numeric
        if (!is_numeric($data->qty) || !is_numeric($data->unit_price) || !is_numeric($data->trans)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'qty, unit_price, trans must be numeric.']);
            exit();
        }

        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':unit', $data->unit);
        $stmt->bindParam(':qty', $data->qty);
        $stmt->bindParam(':unit_price', $data->unit_price);
        $stmt->bindParam(':trans', $data->trans);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':remarks', $data->remarks);
        $stmt->bindParam(':id', $data->item_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Item updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
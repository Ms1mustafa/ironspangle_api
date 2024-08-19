<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include('../includes/config.php');

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));
        $sql = "INSERT INTO po(description, no_pr, no_po, status, price) VALUES(:description, :no_pr, :no_po, :status, :price)";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->description) || !isset($data->status) || !isset($data->price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'price must be numeric.']);
            exit();
        }

        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':no_pr', $data->no_pr);
        $stmt->bindParam(':no_po', $data->no_po);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':price', $data->price);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'PO created successfully.',
                'po' => [
                    'id' => $lastInsertId,
                    'description' => $data->description,
                    'no_pr' => $data->no_pr,
                    'no_po' => $data->no_po,
                    'status' => $data->status,
                    'price' => $data->price,
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
        $sql = "UPDATE po SET description= :description, no_pr =:no_pr, no_po =:no_po, status =:status, price =:price WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->description) || !isset($data->status) || !isset($data->price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->price)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'price must be numeric.']);
            exit();
        }

        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':no_pr', $data->no_pr);
        $stmt->bindParam(':no_po', $data->no_po);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':price', $data->price);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'PO updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
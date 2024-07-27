<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));
        $sql = "INSERT INTO mec(fixed_invoice_cost, po, pr, date) VALUES(:fixed_invoice_cost, :po, :pr, :date)";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->fixed_invoice_cost) || empty($data->po) || empty($data->pr) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->po) || !is_numeric($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'po, pr must be numeric.']);
            exit();
        }

        $stmt->bindParam(':fixed_invoice_cost', $data->fixed_invoice_cost);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':date', $date);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'MEC created successfully.',
                'project' => [
                    'id' => $lastInsertId,
                    'fixed_invoice_cost' => $data->fixed_invoice_cost,
                    'po' => $data->po,
                    'pr' => $data->pr,
                    'date' => $date,
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
        $sql = "UPDATE mec SET fixed_invoice_cost =:fixed_invoice_cost, po =:po, pr =:pr, date =:date WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!$data || empty($data->fixed_invoice_cost) || empty($data->po) || empty($data->pr) || empty($data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (!is_numeric($data->po) || !is_numeric($data->pr)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'po, pr must be numeric.']);
            exit();
        }

        $stmt->bindParam(':fixed_invoice_cost', $data->fixed_invoice_cost);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':date', $date);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Admin updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
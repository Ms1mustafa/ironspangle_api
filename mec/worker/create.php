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
        $sql = "INSERT INTO mec_workers (mec_id, name, status, contract_days, active_days, contract_salary, labor_salary, insurance, ppe, transport, created_at, created_by) VALUES (:mec_id, :name, :status, :contract_days, :active_days, :contract_salary, :labor_salary, :insurance, :ppe, :transport, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->mec_id) || !isset($data->name) || !isset($data->status) || !isset($data->contract_days) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->labor_salary) || !isset($data->insurance) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['contract_days', 'active_days', 'contract_salary', 'labor_salary', 'insurance', 'transport'];
        $invalidFields = array_filter($fields, function ($field) use ($data) {
            return !is_numeric($data->$field);
        });

        if (!empty($invalidFields)) {
            http_response_code(400);
            $fieldList = implode(', ', $invalidFields);
            echo json_encode(['status' => 400, 'message' => "The following fields must be numeric: $fieldList."]);
            exit();
        }

        if (empty($data->created_by)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Some thing went wrong.']);
            exit();
        }

        $created_at = date('Y-m-d H:i:s');

        $stmt->bindParam(':mec_id', $data->mec_id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':contract_days', $data->contract_days);
        $stmt->bindParam(':active_days', $data->active_days);
        $stmt->bindParam(':contract_salary', $data->contract_salary);
        $stmt->bindParam(':labor_salary', $data->labor_salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':transport', $data->transport);
        $stmt->bindParam(':created_at', $data->created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();

            $response = [
                'status' => 200,
                'message' => 'worker created successfully.',
                'worker' => [
                    'id' => $lastInsertId,
                    'mec_id' => $data->mec_id,
                    'name' => $data->name,
                    'status' => $data->status,
                    'contract_days' => $data->contract_days,
                    'active_days' => $data->active_days,
                    'contract_salary' => $data->contract_salary,
                    'labor_salary' => $data->labor_salary,
                    'insurance' => $data->insurance,
                    'ppe' => $data->ppe,
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
        $sql = "UPDATE mec_workers SET name =:name, status =:status, contract_days =:contract_days, active_days =:active_days, contract_salary =:contract_salary, labor_salary =:labor_salary, insurance =:insurance, ppe =:ppe, transport =:transport WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!isset($data->name) || !isset($data->status) || !isset($data->contract_days) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->labor_salary) || !isset($data->insurance) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['contract_days', 'active_days', 'contract_salary', 'labor_salary', 'insurance', 'transport'];
        $invalidFields = array_filter($fields, function ($field) use ($data) {
            return !is_numeric($data->$field);
        });

        if (!empty($invalidFields)) {
            http_response_code(400);
            $fieldList = implode(', ', $invalidFields);
            echo json_encode(['status' => 400, 'message' => "The following fields must be numeric: $fieldList."]);
            exit();
        }

        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':contract_days', $data->contract_days);
        $stmt->bindParam(':active_days', $data->active_days);
        $stmt->bindParam(':contract_salary', $data->contract_salary);
        $stmt->bindParam(':labor_salary', $data->labor_salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':transport', $data->transport);
        $stmt->bindParam(':id', $data->worker_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Worker updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
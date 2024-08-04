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
        $sql = "INSERT INTO supply_chain_workers (supply_chain_id, name, job, active_days, contract_salary, labor_salary, insurance, ppe, insurance2, transport, created_at, created_by) VALUES (:supply_chain_id, :name, :job, :active_days, :contract_salary, :labor_salary, :insurance, :ppe, :insurance2, :transport, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->supply_chain_id) || !isset($data->name) || !isset($data->job) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->labor_salary) || !isset($data->insurance) || !isset($data->insurance2) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['active_days', 'contract_salary', 'labor_salary', 'insurance', 'insurance2', 'transport'];
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

        $stmt->bindParam(':supply_chain_id', $data->supply_chain_id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':job', $data->job);
        $stmt->bindParam(':active_days', $data->active_days);
        $stmt->bindParam(':contract_salary', $data->contract_salary);
        $stmt->bindParam(':labor_salary', $data->labor_salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':insurance2', $data->insurance2);
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
                    'supply_chain_id' => $data->supply_chain_id,
                    'name' => $data->name,
                    'job' => $data->job,
                    'active_days' => $data->active_days,
                    'contract_salary' => $data->contract_salary,
                    'labor_salary' => $data->labor_salary,
                    'insurance' => $data->insurance,
                    'ppe' => $data->ppe,
                    'insurance2' => $data->insurance2,
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
        $sql = "UPDATE supply_chain_workers SET name =:name, job =:job, active_days =:active_days, contract_salary =:contract_salary, labor_salary =:labor_salary, insurance =:insurance, ppe =:ppe, insurance2 =:insurance2, transport =:transport WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!isset($data->name) || !isset($data->job) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->labor_salary) || !isset($data->insurance) || !isset($data->insurance2) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['active_days', 'contract_salary', 'labor_salary', 'insurance', 'insurance2', 'transport'];
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
        $stmt->bindParam(':job', $data->job);
        $stmt->bindParam(':active_days', $data->active_days);
        $stmt->bindParam(':contract_salary', $data->contract_salary);
        $stmt->bindParam(':labor_salary', $data->labor_salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':insurance2', $data->insurance2);
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
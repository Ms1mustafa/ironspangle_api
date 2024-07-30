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
        $sql = "INSERT INTO admin_workers (admin_id, name, job, active_days, contract_salary, salary, insurance, ppe, rewards, insurance2, pr_days, pr_cost, transport, created_at, created_by) VALUES (:admin_id, :name, :job, :active_days, :contract_salary, :salary, :insurance, :ppe, :rewards, :insurance2, :pr_days, :pr_cost, :transport, :created_at, :created_by)";
        $stmt = $con->prepare($sql);

        if (empty($data->admin_id) || !isset($data->name) || !isset($data->job) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->salary) || !isset($data->insurance) || !isset($data->insurance2) || !isset($data->pr_days) || !isset($data->pr_cost) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['active_days', 'contract_salary', 'salary', 'insurance', 'insurance2', 'pr_days', 'pr_cost', 'transport'];
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

        $stmt->bindParam(':admin_id', $data->admin_id);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':job', $data->job);
        $stmt->bindParam(':active_days', $data->active_days);
        $stmt->bindParam(':contract_salary', $data->contract_salary);
        $stmt->bindParam(':salary', $data->salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':rewards', $data->rewards);
        $stmt->bindParam(':pr_days', $data->pr_days);
        $stmt->bindParam(':pr_cost', $data->pr_cost);
        $stmt->bindParam(':transport', $data->transport);
        $stmt->bindParam(':insurance2', $data->insurance2);
        $stmt->bindParam(':created_at', $data->created_at);
        $stmt->bindParam(':created_by', $data->created_by);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();

            $response = [
                'status' => 200,
                'message' => 'worker created successfully.',
                'worker' => [
                    'id' => $lastInsertId,
                    'admin_id' => $data->admin_id,
                    'name' => $data->name,
                    'job' => $data->job,
                    'active_days' => $data->active_days,
                    'contract_salary' => $data->contract_salary,
                    'salary' => $data->salary,
                    'insurance' => $data->insurance,
                    'ppe' => $data->ppe,
                    'rewards' => $data->rewards,
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
        $sql = "UPDATE admin_workers SET name =:name, job =:job, active_days =:active_days, contract_salary =:contract_salary, salary =:salary, insurance =:insurance, ppe =:ppe, rewards =:rewards, pr_days =:pr_days, pr_cost =:pr_cost, transport =:transport, insurance2 =:insurance2 WHERE id = :id";
        $stmt = $con->prepare($sql);

        if (!isset($data->name) || !isset($data->job) || !isset($data->active_days) || !isset($data->contract_salary) || !isset($data->salary) || !isset($data->insurance) || !isset($data->insurance2) || !isset($data->pr_days) || !isset($data->pr_cost) || !isset($data->transport)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        $fields = ['active_days', 'contract_salary', 'salary', 'insurance', 'insurance2', 'pr_days', 'pr_cost', 'transport'];
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
        $stmt->bindParam(':salary', $data->salary);
        $stmt->bindParam(':insurance', $data->insurance);
        $stmt->bindParam(':ppe', $data->ppe);
        $stmt->bindParam(':rewards', $data->rewards);
        $stmt->bindParam(':pr_days', $data->pr_days);
        $stmt->bindParam(':pr_cost', $data->pr_cost);
        $stmt->bindParam(':transport', $data->transport);
        $stmt->bindParam(':insurance2', $data->insurance2);
        $stmt->bindParam(':id', $data->worker_id);
        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Worker updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}
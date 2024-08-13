<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function validateEmployee_companyData($data)
{
    return isset($data->employee_salary, $data->date) &&
        is_numeric($data->employee_salary) &&
        !empty($data->date);
}

function checkDateExists($con, $date, $id = null)
{
    $sql = "SELECT COUNT(*) FROM employee_company WHERE date = :date" . ($id ? " AND id != :id" : "");
    $stmt = $con->prepare($sql);
    $stmt->bindParam(':date', $date);
    if ($id)
        $stmt->bindParam(':id', $id);
    $stmt->execute();
    return $stmt->fetchColumn() > 0;
}

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS");
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));

        if (!validateEmployee_companyData($data)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (checkDateExists($con, $data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'An Employee company already exists for this date.']);
            exit();
        }

        $sql = "INSERT INTO employee_company(employee_salary, date) VALUES(:employee_salary, :date)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':employee_salary', $data->employee_salary);
        $stmt->bindParam(':date', $data->date);

        if ($stmt->execute()) {
            $response = [
                'status' => 200,
                'message' => 'Employee company created successfully.',
                'project' => [
                    'id' => $con->lastInsertId(),
                    'employee_salary' => $data->employee_salary,
                    'date' => $data->date,
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error creating Employee company.']);
        }
        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));

        if (!isset($data->id) || !validateEmployee_companyData($data)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (checkDateExists($con, $data->date, $data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'An Employee company already exists for this date.']);
            exit();
        }

        $sql = "UPDATE employee_company SET employee_salary = :employee_salary, date = :date WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':employee_salary', $data->employee_salary);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Employee company updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error updating Employee company.']);
        }
        break;
}
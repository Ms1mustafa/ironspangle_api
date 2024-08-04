<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, PUT, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

function validateSHData($data)
{
    return isset($data->po, $data->pr, $data->date) &&
        is_numeric($data->po) &&
        is_numeric($data->pr) &&
        !empty($data->date);
}

function checkDateExists($con, $date, $id = null)
{
    $sql = "SELECT COUNT(*) FROM supply_chain WHERE date = :date" . ($id ? " AND id != :id" : "");
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

        if (!validateSHData($data)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (checkDateExists($con, $data->date)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Supply Chain already exists for this date.']);
            exit();
        }

        $sql = "INSERT INTO supply_chain(po, pr, date) VALUES(:po, :pr, :date)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':date', $data->date);

        if ($stmt->execute()) {
            $response = [
                'status' => 200,
                'message' => 'Supply Chain created successfully.',
                'supply_chain' => [
                    'id' => $con->lastInsertId(),
                    'po' => $data->po,
                    'pr' => $data->pr,
                    'date' => $data->date,
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error creating Supply Chain.']);
        }
        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));

        if (!isset($data->id) || !validateSHData($data)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        if (checkDateExists($con, $data->date, $data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Supply Chain already exists for this date.']);
            exit();
        }

        $sql = "UPDATE supply_chain SET po = :po, pr = :pr, date = :date WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':po', $data->po);
        $stmt->bindParam(':pr', $data->pr);
        $stmt->bindParam(':date', $data->date);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Supply Chain updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Error updating Supply Chain.']);
        }
        break;
}
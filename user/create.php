<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS"); // Adjust allowed methods
        exit();

    case "POST":
        $data = json_decode(file_get_contents('php://input'));

        if (!$data || empty($data->name) || empty($data->email) || empty($data->password) || empty($data->role)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'All fields are required.']);
            exit();
        }

        //generate token
        $token = bin2hex(random_bytes(16));

        // Prepare and execute the statement to check if the email exists
        $emailCheckSql = "SELECT COUNT(*) FROM users WHERE email = :email";
        $emailCheckStmt = $con->prepare($emailCheckSql);
        $emailCheckStmt->bindParam(':email', $data->email);
        $emailCheckStmt->execute();
        $emailCount = $emailCheckStmt->fetchColumn();

        // Prepare and execute the statement to check if the token exists
        $tokenCheckSql = "SELECT COUNT(*) FROM users WHERE token = :token"; // Adjust table and column names as needed
        $tokenCheckStmt = $con->prepare($tokenCheckSql);
        $tokenCheckStmt->bindParam(':token', $data->token);
        $tokenCheckStmt->execute();
        $tokenCount = $tokenCheckStmt->fetchColumn();

        if ($emailCount > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Email already exists.']);
            exit();
        }

        if ($tokenCount > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Something went wrong, please try again.']);
            exit();
        }

        // Continue with the rest of your code if no issues found

        // Proceed with the insertion
        $sql = "INSERT INTO users (token, name, email, password, role) VALUES (:token, :name, :email, :password, :role)";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':token', $token);
        $stmt->bindParam(':name', $data->name);
        $stmt->bindParam(':email', $data->email);
        $stmt->bindParam(':password', $data->password);
        $stmt->bindParam(':role', $data->role);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            $response = [
                'status' => 200,
                'message' => 'account created successfully.',
                'account' => [
                    'id' => $lastInsertId,
                    'token' => $token,
                    'name' => $data->name,
                    'email' => $data->email,
                    'password' => $data->password,
                    'role' => $data->role
                ]
            ];
            http_response_code(200);
            echo json_encode($response);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
        break;
}

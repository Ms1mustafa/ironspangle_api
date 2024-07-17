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

        if (!$data || empty($data->email) || empty($data->password)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Email and password are required.']);
            exit();
        }

        $sql = "SELECT * FROM users WHERE email = :email AND password = :password";
        $stmt = $con->prepare($sql);
        $stmt->execute(['email' => $data->email, 'password' => $data->password]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $token = $user['token'];
            setcookie('token', $token, time() + (86400 * 365), "/", "", false, true);

            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Login successful.', 'user' => $user]);

        } else {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'Invalid email or password.']);
        }
        break;

    case "GET":
        if (isset($_GET['token'])) {
            $token = $_GET['token'];
            $sql = "SELECT * FROM users WHERE token = :token";
            $stmt = $con->prepare($sql);
            $stmt->execute(['token' => $token]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($user) {
                http_response_code(200);
                echo json_encode(['status' => 200, 'message' => 'User logged in.', 'data' => $user]);
            } else {
                http_response_code(401);
                echo json_encode(['status' => 401, 'message' => 'User not logged in.']);
            }
        } else {
            http_response_code(401);
            echo json_encode(['status' => 401, 'message' => 'User not logged in.']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['status' => 405, 'message' => 'Method Not Allowed']);
        break;
}

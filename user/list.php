<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];


// Define allowed methods
switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS, GET"); // Adjust allowed methods
        exit();

    case "GET":
        // Initialize SQL query
        $sql = "SELECT * FROM users";
        $token = $_GET['t'] ?? null;

        if ($token) {
            $sql .= " WHERE token = :token"; // Adjust this based on your actual token field
        }

        // Prepare and execute statement
        $stmt = $con->prepare($sql);
        if ($token) {
            $stmt->bindParam(':token', $token);
        }
        $stmt->execute();
        $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Output result as JSON
        echo json_encode($users);
        break;

    default:
        http_response_code(405); // Method Not Allowed
        header('Allow: OPTIONS, GET');
        echo json_encode(['error' => 'Method not allowed']);
        break;
}
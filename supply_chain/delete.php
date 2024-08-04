<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: DELETE, OPTIONS");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: DELETE, OPTIONS"); // Adjust allowed methods
        exit();

    case "DELETE":
        // Read input data
        $data = json_decode(file_get_contents('php://input'));

        // Check if data was successfully decoded
        if (!$data || !isset($data->id)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Invalid or missing data.']);
            exit();
        }

        // Begin transaction
        try {
            $con->beginTransaction();

            // Prepare SQL statement to delete associated workers
            $sqlWorkers = "DELETE FROM supply_chain_workers WHERE supply_chain_id = :id";
            $stmtWorkers = $con->prepare($sqlWorkers);
            $stmtWorkers->bindParam(':id', $data->id);

            // Execute the statement
            if (!$stmtWorkers->execute()) {
                throw new Exception('Failed to delete associated workers.');
            }

            // Prepare SQL statement to delete the project
            $sqlProject = "DELETE FROM supply_chain WHERE id = :id";
            $stmtProject = $con->prepare($sqlProject);
            $stmtProject->bindParam(':id', $data->id);

            // Execute the statement
            if (!$stmtProject->execute()) {
                throw new Exception('Failed to delete Supply Chain.');
            }

            // Commit transaction
            $con->commit();

            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Supply Chain and associated workers deleted successfully.']);
        } catch (Exception $e) {
            // Rollback transaction if something failed
            $con->rollBack();

            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => $e->getMessage()]);
        }
        exit();
}

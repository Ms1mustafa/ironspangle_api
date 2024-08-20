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
        header("Allow: POST, OPTIONS");
        exit();

    case "POST":
        $data = json_decode(file_get_contents("php://input"));

        if (!$data || empty($data->invoice_no) || empty($data->cost)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Invoice number and cost are required.']);
            exit();
        }

        // Check if the invoice_no already exists
        $checkSql = "SELECT COUNT(*) FROM invoice WHERE invoice_no = :invoice_no";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':invoice_no', $data->invoice_no);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The invoice number already exists.']);
            exit();
        }

        // Set default status if not provided
        $status = isset($data->status) ? $data->status : 'in process';

        // Insert new invoice
        $sql = "INSERT INTO invoice (
            swift_id, description, pr_no, pr_date, po_no, po_date, invoice_no,
            invoice_date, invoice_send, invoice_store, invoice_pru, invoice_accounting,
            s_no, s_date, cost, status, p_and_lc, guarantee, tax, publish, fines
        ) VALUES (
            :swift_id, :description, :pr_no, :pr_date, :po_no, :po_date, :invoice_no,
            :invoice_date, :invoice_send, :invoice_store, :invoice_pru, :invoice_accounting,
            :s_no, :s_date, :cost, :status, :p_and_lc, :guarantee, :tax, :publish, :fines
        )";
        $stmt = $con->prepare($sql);

        // Bind parameters, set optional parameters to null if not provided
        $stmt->bindParam(':swift_id', $data->swift_id);
        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':pr_no', $data->pr_no);
        $stmt->bindParam(':pr_date', $data->pr_date);
        $stmt->bindParam(':po_no', $data->po_no);
        $stmt->bindParam(':po_date', $data->po_date);
        $stmt->bindParam(':invoice_no', $data->invoice_no);
        $stmt->bindParam(':invoice_date', $data->invoice_date);
        $stmt->bindParam(':invoice_send', $data->invoice_send);
        $stmt->bindParam(':invoice_store', $data->invoice_store);
        $stmt->bindParam(':invoice_pru', $data->invoice_pru);
        $stmt->bindParam(':invoice_accounting', $data->invoice_accounting);
        $stmt->bindParam(':s_no', $data->s_no);
        $stmt->bindParam(':s_date', $data->s_date);
        $stmt->bindParam(':cost', $data->cost);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':p_and_lc', $data->p_and_lc);
        $stmt->bindParam(':guarantee', $data->guarantee);
        $stmt->bindParam(':tax', $data->tax);
        $stmt->bindParam(':publish', $data->publish);
        $stmt->bindParam(':fines', $data->fines);

        if ($stmt->execute()) {
            $lastInsertId = $con->lastInsertId();
            http_response_code(200);
            echo json_encode([
                'status' => 200,
                'message' => 'Invoice created successfully.',
                'invoice' => ['id' => $lastInsertId, 'invoice_no' => $data->invoice_no]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));

        if (!$data || empty($data->id) || empty($data->invoice_no)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'ID and invoice number are required.']);
            exit();
        }

        // Check if the invoice_no already exists (excluding the current record)
        $checkSql = "SELECT COUNT(*) FROM invoice WHERE invoice_no = :invoice_no AND id != :id";
        $checkStmt = $con->prepare($checkSql);
        $checkStmt->bindParam(':invoice_no', $data->invoice_no);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();

        if ($count > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The invoice number already exists.']);
            exit();
        }

        // Update invoice
        $sql = "UPDATE invoice SET
                description = :description,
                pr_no = :pr_no,
                pr_date = :pr_date,
                po_no = :po_no,
                po_date = :po_date,
                invoice_no = :invoice_no,
                invoice_date = :invoice_date,
                invoice_send = :invoice_send,
                invoice_store = :invoice_store,
                invoice_pru = :invoice_pru,
                invoice_accounting = :invoice_accounting,
                s_no = :s_no,
                s_date = :s_date,
                cost = :cost,
                status = :status,
                p_and_lc = :p_and_lc,
                guarantee = :guarantee,
                tax = :tax,
                publish = :publish,
                fines = :fines
            WHERE id = :id";
        $stmt = $con->prepare($sql);

        // Bind parameters, set optional parameters to null if not provided
        $stmt->bindParam(':description', $data->description);
        $stmt->bindParam(':pr_no', $data->pr_no);
        $stmt->bindParam(':pr_date', $data->pr_date);
        $stmt->bindParam(':po_no', $data->po_no);
        $stmt->bindParam(':po_date', $data->po_date);
        $stmt->bindParam(':invoice_no', $data->invoice_no);
        $stmt->bindParam(':invoice_date', $data->invoice_date);
        $stmt->bindParam(':invoice_send', $data->invoice_send);
        $stmt->bindParam(':invoice_store', $data->invoice_store);
        $stmt->bindParam(':invoice_pru', $data->invoice_pru);
        $stmt->bindParam(':invoice_accounting', $data->invoice_accounting);
        $stmt->bindParam(':s_no', $data->s_no);
        $stmt->bindParam(':s_date', $data->s_date);
        $stmt->bindParam(':cost', $data->cost);
        $stmt->bindParam(':status', $data->status);
        $stmt->bindParam(':p_and_lc', $data->p_and_lc);
        $stmt->bindParam(':guarantee', $data->guarantee);
        $stmt->bindParam(':tax', $data->tax);
        $stmt->bindParam(':publish', $data->publish);
        $stmt->bindParam(':fines', $data->fines);
        $stmt->bindParam(':id', $data->id);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Invoice updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
}

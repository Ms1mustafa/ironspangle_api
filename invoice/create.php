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

        if (empty($data->invoice_no) || empty($data->cost)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'Invoice number and cost are required.']);
            exit();
        }

        // Check if invoice_no exists
        $checkStmt = $con->prepare("SELECT COUNT(*) FROM invoice WHERE invoice_no = :invoice_no");
        $checkStmt->bindParam(':invoice_no', $data->invoice_no);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The invoice number already exists.']);
            exit();
        }

        $status = $data->status ?? 'in process';  // Default status

        $sql = "INSERT INTO invoice (swift_id, description, pr_no, pr_date, po_no, po_date, invoice_no, 
                invoice_date, invoice_send, invoice_store, invoice_pru, invoice_accounting, s_no, 
                s_date, cost, status, p_and_lc, guarantee, tax, tax_bint, publish, fines) 
                VALUES (:swift_id, :description, :pr_no, :pr_date, :po_no, :po_date, :invoice_no, 
                :invoice_date, :invoice_send, :invoice_store, :invoice_pru, :invoice_accounting, 
                :s_no, :s_date, :cost, :status, :p_and_lc, :guarantee, :tax, :tax_bint, :publish, :fines)";

        $stmt = $con->prepare($sql);

        // Bind parameters
        $swift_id = $data->swift_id ?? null;
        $description = $data->description ?? null;
        $pr_no = $data->pr_no ?? null;
        $pr_date = $data->pr_date ?? null;
        $po_no = $data->po_no ?? null;
        $po_date = $data->po_date ?? null;
        $invoice_no = $data->invoice_no;
        $invoice_date = $data->invoice_date ?? null;
        $invoice_send = $data->invoice_send ?? null;
        $invoice_store = $data->invoice_store ?? null;
        $invoice_pru = $data->invoice_pru ?? null;
        $invoice_accounting = $data->invoice_accounting ?? null;
        $s_no = $data->s_no ?? null;
        $s_date = $data->s_date ?? null;
        $cost = $data->cost;
        $p_and_lc = $data->p_and_lc ?? null;
        $guarantee = $data->guarantee ?? null;
        $tax = $data->tax ?? null;
        $tax_bint = $data->tax_bint ?? null;
        $publish = $data->publish ?? null;
        $fines = $data->fines ?? null;

        $stmt->bindParam(':swift_id', $swift_id);
        $stmt->bindParam(':description', $description);
        $stmt->bindParam(':pr_no', $pr_no);
        $stmt->bindParam(':pr_date', $pr_date);
        $stmt->bindParam(':po_no', $po_no);
        $stmt->bindParam(':po_date', $po_date);
        $stmt->bindParam(':invoice_no', $invoice_no);
        $stmt->bindParam(':invoice_date', $invoice_date);
        $stmt->bindParam(':invoice_send', $invoice_send);
        $stmt->bindParam(':invoice_store', $invoice_store);
        $stmt->bindParam(':invoice_pru', $invoice_pru);
        $stmt->bindParam(':invoice_accounting', $invoice_accounting);
        $stmt->bindParam(':s_no', $s_no);
        $stmt->bindParam(':s_date', $s_date);
        $stmt->bindParam(':cost', $cost);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':p_and_lc', $p_and_lc);
        $stmt->bindParam(':guarantee', $guarantee);
        $stmt->bindParam(':tax', $tax);
        $stmt->bindParam(':tax_bint', $tax_bint);
        $stmt->bindParam(':publish', $publish);
        $stmt->bindParam(':fines', $fines);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode([
                'status' => 200,
                'message' => 'Invoice created successfully.',
                'invoice' => ['id' => $con->lastInsertId(), 'invoice_no' => $invoice_no]
            ]);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
        break;

    case "PUT":
        $data = json_decode(file_get_contents('php://input'));

        if (empty($data->id) || empty($data->invoice_no)) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'ID and invoice number are required.']);
            exit();
        }

        // Check if invoice_no exists (excluding current record)
        $checkStmt = $con->prepare("SELECT COUNT(*) FROM invoice WHERE invoice_no = :invoice_no AND id != :id");
        $checkStmt->bindParam(':invoice_no', $data->invoice_no);
        $checkStmt->bindParam(':id', $data->id);
        $checkStmt->execute();
        if ($checkStmt->fetchColumn() > 0) {
            http_response_code(400);
            echo json_encode(['status' => 400, 'message' => 'The invoice number already exists.']);
            exit();
        }

        $fields = "guarantee = :guarantee, tax = :tax, tax_bint = :tax_bint, publish = :publish, fines = :fines";
        if (empty($data->values_edit)) {
            $fields = "description = :description, pr_no = :pr_no, pr_date = :pr_date, po_no = :po_no, 
                       po_date = :po_date, invoice_no = :invoice_no, invoice_date = :invoice_date, 
                       invoice_send = :invoice_send, invoice_store = :invoice_store, invoice_pru = :invoice_pru, 
                       invoice_accounting = :invoice_accounting, s_no = :s_no, s_date = :s_date, 
                       cost = :cost, status = :status, p_and_lc = :p_and_lc, $fields";
        }

        $sql = "UPDATE invoice SET $fields WHERE id = :id";
        $stmt = $con->prepare($sql);
        $stmt->bindParam(':id', $data->id);

        // Prepare values
        $guarantee = $data->guarantee ?? null;
        $tax = $data->tax ?? null;
        $tax_bint = $data->tax_bint ?? null;
        $publish = $data->publish ?? null;
        $fines = $data->fines ?? null;
        $description = $data->description ?? null;
        $pr_no = $data->pr_no ?? null;
        $pr_date = $data->pr_date ?? null;
        $po_no = $data->po_no ?? null;
        $po_date = $data->po_date ?? null;
        $invoice_no = $data->invoice_no;
        $invoice_date = $data->invoice_date ?? null;
        $invoice_send = $data->invoice_send ?? null;
        $invoice_store = $data->invoice_store ?? null;
        $invoice_pru = $data->invoice_pru ?? null;
        $invoice_accounting = $data->invoice_accounting ?? null;
        $s_no = $data->s_no ?? null;
        $s_date = $data->s_date ?? null;
        $cost = $data->cost ?? null;  // Allow cost to be null if not provided
        $status = $data->status ?? null;  // Handle status as well
        $p_and_lc = $data->p_and_lc ?? null;

        // Bind parameters
        if (empty($data->values_edit)) {
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':pr_no', $pr_no);
            $stmt->bindParam(':pr_date', $pr_date);
            $stmt->bindParam(':po_no', $po_no);
            $stmt->bindParam(':po_date', $po_date);
            $stmt->bindParam(':invoice_no', $invoice_no);
            $stmt->bindParam(':invoice_date', $invoice_date);
            $stmt->bindParam(':invoice_send', $invoice_send);
            $stmt->bindParam(':invoice_store', $invoice_store);
            $stmt->bindParam(':invoice_pru', $invoice_pru);
            $stmt->bindParam(':invoice_accounting', $invoice_accounting);
            $stmt->bindParam(':s_no', $s_no);
            $stmt->bindParam(':s_date', $s_date);
            $stmt->bindParam(':cost', $cost);
            $stmt->bindParam(':status', $status);
            $stmt->bindParam(':p_and_lc', $p_and_lc);
        }

        $stmt->bindParam(':guarantee', $guarantee);
        $stmt->bindParam(':tax', $tax);
        $stmt->bindParam(':tax_bint', $tax_bint);
        $stmt->bindParam(':publish', $publish);
        $stmt->bindParam(':fines', $fines);

        if ($stmt->execute()) {
            http_response_code(200);
            echo json_encode(['status' => 200, 'message' => 'Invoice updated successfully.']);
        } else {
            http_response_code(500);
            echo json_encode(['status' => 500, 'message' => 'Something went wrong.']);
        }
        break;
}

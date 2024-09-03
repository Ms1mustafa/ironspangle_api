<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: POST, OPTIONS, GET");
header("Access-Control-Allow-Credentials: true");

include '../includes/config.php';

$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case "OPTIONS":
        http_response_code(200);
        header("Allow: POST, OPTIONS, GET"); // Adjust allowed methods
        exit();

    case "GET":
        // Retrieve the swift_id from the query string if available
        $swift_id = isset($_GET['swift_id']) ? $_GET['swift_id'] : null;

        // Base SQL query
        $sql = "SELECT 
            s.swift AS swift,
            i.invoice_no,
            i.description,
            i.s_no,
            i.cost,
            i.guarantee,
            i.tax,
            i.publish,
            i.fines
            FROM 
                invoice i
            JOIN 
                swift s ON i.swift_id = s.id
            WHERE
                (i.guarantee IS NOT NULL AND i.guarantee != 0)
                AND
                (i.tax IS NOT NULL AND i.tax != 0)";

        // Add filtering by swift_id if provided
        if ($swift_id !== null) {
            // Sanitize the swift_id to prevent SQL injection
            $swift_id = filter_var($swift_id, FILTER_SANITIZE_STRING);
            $sql .= " AND s.id = :swift_id";
        }

        $stmt = $con->prepare($sql);

        // Bind the swift_id parameter if it's provided
        if ($swift_id !== null) {
            $stmt->bindParam(':swift_id', $swift_id, PDO::PARAM_STR);
        }

        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Structure the data
        $data = [];
        foreach ($results as $row) {
            $swift = $row['swift'];
            if (!isset($data[$swift])) {
                $data[$swift] = [
                    'swift' => $swift,
                    'invoices' => []
                ];
            }
            $data[$swift]['invoices'][] = [
                'invoice_no' => $row['invoice_no'],
                'description' => $row['description'],
                's_no' => $row['s_no'],
                'cost' => $row['cost'],
                'guarantee' => $row['guarantee'],
                'tax' => $row['tax'],
                'publish' => $row['publish'],
                'fines' => $row['fines']
            ];
        }

        // Return the structured data as JSON
        http_response_code(200);
        echo json_encode(array_values($data));
        break;
}

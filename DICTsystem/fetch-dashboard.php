<?php
include_once('first-connection.php');

if (isset($_POST['clear_filter'])) {
    $start_date = null;
    $end_date = null;
} else {
    $start_date = isset($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = isset($_POST['end_date']) ? $_POST['end_date'] : null;
}

try {
    $pdo = new PDO("mysql:host=localhost;dbname=applicant-records", "root", "");
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $base_query = "SELECT COUNT(*) as count FROM applicantrecord";
    $date_condition = "";
    if ($start_date && $end_date) {
        $date_condition = " WHERE date_of_examination BETWEEN :start_date AND :end_date";
    }

    function executeQuery($pdo, $query, $params = []) {
        $stmt = $pdo->prepare($query);
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }
        $stmt->execute();
        return $stmt->fetchColumn();
    }

    $query_params = [];
    if ($start_date && $end_date) {
        $query_params = [':start_date' => $start_date, ':end_date' => $end_date];
    }

    // Total applicants
    $total_query = $base_query . $date_condition;
    $total_count = executeQuery($pdo, $total_query, $query_params);

    // Diagnostic test results
    $passed_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " status = 'passed'";
    $passed_count = executeQuery($pdo, $passed_query, $query_params);

    $failed_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " status = 'failed'";
    $failed_count = executeQuery($pdo, $failed_query, $query_params);

    $pending_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " status = 'pending'";
    $pending_count = executeQuery($pdo, $pending_query, $query_params);

    // Hands-on test results
    $passed_handson_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " handson_status = 'passed'";
    $passed_handson_count = executeQuery($pdo, $passed_handson_query, $query_params);

    $failed_handson_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " handson_status = 'failed'";
    $failed_handson_count = executeQuery($pdo, $failed_handson_query, $query_params);

    $pending_handson_query = $base_query . $date_condition . ($date_condition ? " AND" : " WHERE") . " handson_status = 'pending'";
    $pending_handson_count = executeQuery($pdo, $pending_handson_query, $query_params);


    // Prepare the response data
    $response_data = [
        'total_count' => $total_count,
        'passed_count' => $passed_count,
        'failed_count' => $failed_count,
        'pending_count' => $pending_count,
        'passed_handson_count' => $passed_handson_count,
        'failed_handson_count' => $failed_handson_count,
        'pending_handson_count' => $pending_handson_count
    ];

    // Check if it's an AJAX request
    if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
        // Set JSON header
        header('Content-Type: application/json');
        // Send JSON response
        echo json_encode($response_data);
        exit();
    } else {
        // If it's not an AJAX request, just make the data available for PHP
        $json_data = json_encode($response_data);
        // You can use $json_data later in your PHP code if needed
    }

    } catch(PDOException $e) {
        if(!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
            header('HTTP/1.1 500 Internal Server Error');
            header('Content-Type: application/json');
            echo json_encode(['error' => $e->getMessage()]);
            exit();
        } else {
            // Handle error for non-AJAX requests
            // You might want to log the error or display a user-friendly message
            error_log("Database error: " . $e->getMessage());
            // Optionally set an error variable that you can use in your PHP code
            $db_error = "An error occurred while fetching data.";
        }
    }
<?php
// Set the content type to application/json so the browser knows how to read it
header('Content-Type: application/json');

$host = "localhost";
$user = "root";
$pass = "";
$db = "car_members_db";
$port = 3306;

// Create an array to hold our response
$response = [];

try {
    $conn = new mysqli($host, $user, $pass, $db, $port);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }

    // Get form data
    $plate_number = $_POST['plate_number'] ?? '';

    // Check for duplicates
    $sql_check = "SELECT plate_number FROM members WHERE plate_number = ?";
    $stmt_check = $conn->prepare($sql_check);
    $stmt_check->bind_param("s", $plate_number);
    $stmt_check->execute();
    $stmt_check->store_result();

    if ($stmt_check->num_rows > 0) {
        // If the plate exists, set an error response
        $response['status'] = 'error';
        $response['message'] = "The license plate '$plate_number' is already registered.";
    } else {
        // If it's unique, proceed with insertion
        $full_name = $_POST['full_name'] ?? '';
        $phone_number = $_POST['phone_number'] ?? '';
        $id_card_number = $_POST['id_card_number'] ?? '';
        $plate_image_path = $_POST['plate_image_path'] ?? '';

        $sql_insert = "INSERT INTO members (full_name, phone_number, id_card_number, plate_number, plate_image_path) VALUES (?, ?, ?, ?, ?)";
        $stmt_insert = $conn->prepare($sql_insert);
        $stmt_insert->bind_param("sssss", $full_name, $phone_number, $id_card_number, $plate_number, $plate_image_path);

        if ($stmt_insert->execute()) {
            // Set a success response
            $response['status'] = 'success';
            $response['message'] = 'Registration complete!';
        } else {
            throw new Exception("Error during insertion: " . $stmt_insert->error);
        }
        $stmt_insert->close();
    }
    $stmt_check->close();
    $conn->close();

} catch (Exception $e) {
    // Catch any general errors and set an error response
    $response['status'] = 'error';
    $response['message'] = $e->getMessage();
}

// Encode the response array into a JSON string and output it
echo json_encode($response);
?>
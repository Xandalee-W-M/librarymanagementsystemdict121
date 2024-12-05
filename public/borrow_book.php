<?php
// Include authentication configuration file
require_once '../config/auth.php';
// Start session if not already started
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
// Redirect to login page if not authenticated
redirectIfNotAuthenticated('login.php');
// Include database configuration file
require_once '../config/db.php';

// Check if the request method is POST
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Get form data and set due date to 2 weeks from now
    $book_id = $_POST['book_id'];
    $user_id = $_SESSION['user_id'];
    $due_date = date('Y-m-d', strtotime('+2 weeks'));

    // Check database connection
    if (!$conn) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    // Prepare SQL statement to check if the book is available
    $check_stmt = $conn->prepare("SELECT available FROM books WHERE id = ?");
    if (!$check_stmt) {
        die("SQL error: " . $conn->error);
    }
    $check_stmt->bind_param("i", $book_id);
    $check_stmt->execute();
    $check_stmt->bind_result($available);
    $check_stmt->fetch();
    $check_stmt->close();

    // Check if there are available copies
    if ($available > 0) {
        // Update the available copies
        $update_stmt = $conn->prepare("UPDATE books SET available = available - 1 WHERE id = ?");
        if (!$update_stmt) {
            die("SQL error: " . $conn->error);
        }
        $update_stmt->bind_param("i", $book_id);
        $update_stmt->execute();
        $update_stmt->close();

        // Insert into borrowed_books table
        $borrow_stmt = $conn->prepare("INSERT INTO borrowed_books (user_id, book_id, due_date) VALUES (?, ?, ?)");
        if (!$borrow_stmt) {
            die("SQL error: " . $conn->error);
        }
        $borrow_stmt->bind_param("iis", $user_id, $book_id, $due_date);
        try {
            $borrow_stmt->execute();
            $_SESSION['message'] = "Book borrowed successfully!";
        } catch (mysqli_sql_exception $e) {
            $_SESSION['message'] = "Error: " . $e->getMessage();
        }
        $borrow_stmt->close();
    } else {
        $_SESSION['message'] = "Sorry, this book is not available.";
    }

    // Redirect to the dashboard page to show message
    header("Location: dashboard.php");
    exit();
}
?>

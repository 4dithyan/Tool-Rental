<?php
require_once 'includes/db_connect.php';

// Check if user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: login.php');
    exit();
}

echo "<h2>Database Update Script</h2>";

// Add payment_method column to rentals table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'payment_method'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE rentals ADD COLUMN payment_method ENUM('full', 'cod') DEFAULT 'full'";
    if ($conn->query($alter_query)) {
        echo "<p>Added payment_method column to rentals table</p>";
    } else {
        echo "<p>Error adding payment_method column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>payment_method column already exists in rentals table</p>";
}

// Add deposit_amount column to rentals table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'deposit_amount'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE rentals ADD COLUMN deposit_amount DECIMAL(10,2) DEFAULT 0.00";
    if ($conn->query($alter_query)) {
        echo "<p>Added deposit_amount column to rentals table</p>";
    } else {
        echo "<p>Error adding deposit_amount column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>deposit_amount column already exists in rentals table</p>";
}

// Add address_updated column to rentals table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'address_updated'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE rentals ADD COLUMN address_updated TINYINT(1) DEFAULT 0";
    if ($conn->query($alter_query)) {
        echo "<p>Added address_updated column to rentals table</p>";
    } else {
        echo "<p>Error adding address_updated column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>address_updated column already exists in rentals table</p>";
}

// Add full_payment_received column to rentals table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'full_payment_received'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE rentals ADD COLUMN full_payment_received TINYINT(1) DEFAULT 0";
    if ($conn->query($alter_query)) {
        echo "<p>Added full_payment_received column to rentals table</p>";
    } else {
        echo "<p>Error adding full_payment_received column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>full_payment_received column already exists in rentals table</p>";
}

// Add id_proof_image column to rentals table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM rentals LIKE 'id_proof_image'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE rentals ADD COLUMN id_proof_image VARCHAR(255) DEFAULT NULL";
    if ($conn->query($alter_query)) {
        echo "<p>Added id_proof_image column to rentals table</p>";
    } else {
        echo "<p>Error adding id_proof_image column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>id_proof_image column already exists in rentals table</p>";
}

// Add is_featured column to tools table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM tools LIKE 'is_featured'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE tools ADD COLUMN is_featured TINYINT(1) DEFAULT 0";
    if ($conn->query($alter_query)) {
        echo "<p>Added is_featured column to tools table</p>";
    } else {
        echo "<p>Error adding is_featured column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>is_featured column already exists in tools table</p>";
}

// Add is_common column to tools table if it doesn't exist
$columns_query = "SHOW COLUMNS FROM tools LIKE 'is_common'";
$columns_result = $conn->query($columns_query);

if ($columns_result->num_rows == 0) {
    $alter_query = "ALTER TABLE tools ADD COLUMN is_common TINYINT(1) DEFAULT 0";
    if ($conn->query($alter_query)) {
        echo "<p>Added is_common column to tools table</p>";
    } else {
        echo "<p>Error adding is_common column: " . $conn->error . "</p>";
    }
} else {
    echo "<p>is_common column already exists in tools table</p>";
}

echo "<p>Database update completed!</p>";
echo "<a href='admin/admin_dashboard.php'>Return to Admin Dashboard</a>";
?>
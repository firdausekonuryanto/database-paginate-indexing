<?php
// Require your existing database connection
require_once 'db.php';

// Increase script execution time limit for bulk operations
set_time_limit(120);

// Sample data arrays to generate realistic fake product details
$adjectives = ['Smart', 'Wireless', 'Ergonomic', 'Ultra', 'Portable', 'Gaming', 'Professional', 'Mini', 'Compact', 'Advanced'];
$nouns = ['Mouse', 'Keyboard', 'Monitor', 'Headset', 'Office Chair', 'Desk Organizer', 'Laptop Stand', 'USB-C Hub', 'Bluetooth Speaker', 'HD Webcam'];
$categories = ['Electronics', 'Office Supplies', 'Gaming Gear', 'Computer Accessories', 'Furniture'];

$totalRecords = 5000;
$successCount = 0;

try {
    // Start a transaction for massive performance boost
    $pdo->beginTransaction();

    // Prepare the SQL statement once
    $stmt = $pdo->prepare("INSERT INTO products (name, category, price, stock) VALUES (:name, :category, :price, :stock)");

    for ($i = 1; $i <= $totalRecords; $i++) {
        // Generate random mock data
        $name = $adjectives[array_rand($adjectives)] . ' ' . $nouns[array_rand($nouns)] . ' ' . rand(10, 99);
        $category = $categories[array_rand($categories)];
        $price = rand(25, 500) * 10000; // Random price between Rp 250.000 and Rp 5.000.000
        $stock = rand(0, 150);          // Random stock between 0 and 150

        // Execute the statement
        $stmt->execute([
            ':name' => $name,
            ':category' => $category,
            ':price' => $price,
            ':stock' => $stock
        ]);

        $successCount++;
    }

    // Commit the transaction to save all records at once
    $pdo->commit();

    $outputMessage = "Successfully generated and inserted $successCount fake products into the database!";
} catch (\Exception $e) {
    // Rollback changes if any error occurs
    $pdo->rollBack();
    $outputMessage = "Error seeding data: " . $e->getMessage();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Seeder</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container mt-5">
        <div class="card shadow-sm p-4 text-center">
            <h3 class="mb-3">Database Seeder Status</h3>
            <p class="lead <?= str_contains($outputMessage, 'Successfully') ? 'text-success' : 'text-danger' ?>">
                <?= htmlspecialchars($outputMessage) ?>
            </p>
            <div class="mt-3">
                <a href="index.php" class="btn btn-primary">View Product Table</a>
            </div>
        </div>
    </div>
</body>
</html>
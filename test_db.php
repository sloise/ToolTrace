<?php
require_once __DIR__ . '/includes/config.php';

try {
    $pdo = db();
    echo "<h2 style='color:green'>✅ Database connected successfully!</h2>";

    // Quick sanity checks
    $orgs  = $pdo->query("SELECT COUNT(*) AS total FROM organizations")->fetch();
    $equip = $pdo->query("SELECT COUNT(*) AS total FROM equipment")->fetch();
    $units = $pdo->query("SELECT COUNT(*) AS total FROM equipment_units")->fetch();
    $trans = $pdo->query("SELECT COUNT(*) AS total FROM borrow_transactions")->fetch();
    $staff = $pdo->query("SELECT COUNT(*) AS total FROM staff")->fetch();

    echo "<table border='1' cellpadding='8'>";
    echo "<tr><th>Table</th><th>Row Count</th></tr>";
    echo "<tr><td>organizations</td><td>{$orgs['total']}</td></tr>";
    echo "<tr><td>equipment</td><td>{$equip['total']}</td></tr>";
    echo "<tr><td>equipment_units</td><td>{$units['total']}</td></tr>";
    echo "<tr><td>borrow_transactions</td><td>{$trans['total']}</td></tr>";
    echo "<tr><td>staff</td><td>{$staff['total']}</td></tr>";
    echo "</table>";

} catch (PDOException $e) {
    echo "<h2 style='color:red'>❌ Connection failed: " . $e->getMessage() . "</h2>";
}
?>
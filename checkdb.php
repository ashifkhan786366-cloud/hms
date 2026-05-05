<?php
require 'config/db.php';
echo "BILLS TABLE:\n";
print_r($pdo->query('DESCRIBE bills')->fetchAll(PDO::FETCH_ASSOC));
echo "\nBILL_ITEMS TABLE:\n";
print_r($pdo->query('DESCRIBE bill_items')->fetchAll(PDO::FETCH_ASSOC));
echo "\nDOCTORS LIST:\n";
print_r($pdo->query('SELECT id, full_name, role FROM users WHERE role="Doctor"')->fetchAll(PDO::FETCH_ASSOC));

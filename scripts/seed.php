<?php
declare(strict_types=1);
require_once __DIR__ . '/../src/Database.php';
$db=Database::connection();
echo "Seeded websites: ".$db->query('SELECT COUNT(*) FROM websites')->fetchColumn().PHP_EOL;

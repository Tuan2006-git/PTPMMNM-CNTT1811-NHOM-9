<?php
/**
 * Chuyển dữ liệu từ SQLite cũ sang MySQL.
 * Chạy sau khi đã cấu hình MySQL và tạo database:
 * php scripts/migrate_sqlite_to_mysql.php
 */
if (PHP_SAPI !== 'cli') { exit("Chi chay tu dong lenh.\n"); }
require dirname(__DIR__) . '/src/bootstrap.php';

$sqliteFile = WI_ROOT . '/data/webinsight.sqlite';
if (!is_file($sqliteFile)) {
    fwrite(STDERR, "[LOI] Khong tim thay $sqliteFile\n");
    exit(1);
}
if (!extension_loaded('pdo_sqlite')) {
    fwrite(STDERR, "[LOI] Can extension pdo_sqlite de doc CSDL cu. Cai: sudo apt install php-sqlite3\n");
    exit(1);
}

$source = new PDO('sqlite:' . $sqliteFile, null, null, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
]);
$target = wi_db();
wi_init_schema($target);

echo "Canh bao: du lieu trong cac bang MySQL se bi thay the boi du lieu SQLite.\n";
echo "Nhap MIGRATE de tiep tuc: ";
$confirm = trim((string) fgets(STDIN));
if ($confirm !== 'MIGRATE') {
    echo "Da huy, khong co du lieu nao bi thay doi.\n";
    exit(0);
}

$tables = ['admins', 'websites', 'rules', 'events', 'funnels', 'settings', 'login_attempts'];
$target->beginTransaction();
try {
    // Xóa dữ liệu cũ ở MySQL theo thứ tự ngược để không vi phạm khóa ngoại.
    foreach (array_reverse($tables) as $table) {
        $target->exec("DELETE FROM `$table`");
    }

    $summary = [];
    foreach ($tables as $table) {
        $columns = $source->query("PRAGMA table_info(`$table`)")->fetchAll();
        if (!$columns) continue;
        $names = array_column($columns, 'name');
        $quoted = array_map(static fn($name) => '`' . str_replace('`', '``', $name) . '`', $names);
        $rows = $source->query("SELECT * FROM `$table`")->fetchAll();
        if ($rows) {
            $sql = "INSERT INTO `$table` (" . implode(',', $quoted) . ") VALUES (" .
                implode(',', array_fill(0, count($names), '?')) . ")";
            $insert = $target->prepare($sql);
            foreach ($rows as $row) {
                $insert->execute(array_values($row));
            }
        }
        $summary[$table] = count($rows);
    }
    $target->commit();
    foreach ($summary as $table => $count) {
        echo sprintf("[OK] %-16s %d dong\n", $table, $count);
    }
    echo "Da chuyen du lieu SQLite sang MySQL. File SQLite cu van duoc giu nguyen lam ban sao.\n";
} catch (Throwable $e) {
    if ($target->inTransaction()) $target->rollBack();
    fwrite(STDERR, "[LOI] Chuyen du lieu that bai: " . $e->getMessage() . "\n");
    exit(1);
}

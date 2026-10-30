
<?php
require __DIR__ . '/../src/bootstrap.php';

wi_require_login();

$pdo = wi_db();
$f = wi_filters();
$sites = wi_sites($pdo);
$prods = wi_by_product($pdo, $f);

// Biểu tượng sản phẩm
$emoji = [
    'quan' => '👖',
    'ao' => '👕',
    'kinh' => '🕶️',
    'giay' => '👟'
];

wi_header('Thống kê sản phẩm', 'products');
wi_filter_form($f, $sites);
?>

<!-- Thống kê lượt nhấp -->
<div class="grid grid-4">
    <?php foreach ($prods as $key => $p): ?>
        <div class="card stat">
            <div class="stat-icon" style="font-size:28px">
                <?= $emoji[$key] ?? '🛍️' ?>
            </div>

            <div>
                <div class="stat-label">
                    <?= e($p['label']) ?>
                </div>

                <div class="stat-value">
                    <?= (int) $p['clicks'] ?>
                </div>

                <span class="stat-label">lượt nhấp</span>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<!-- Biểu đồ thống kê lượt nhấp -->
<div class="grid grid-2" style="margin-top:16px">
    <div class="card">
        <h2>So sánh lượt nhấp theo sản phẩm</h2>

        <?php
        $rows = [];

        foreach ($prods as $p) {
            $rows[] = [
                $p['label'],
                (int) $p['clicks']
            ];
        }

        echo wi_hbars($rows);
        ?>
    </div>

    <!-- Bảng chi tiết chỉ có lượt nhấp -->
    <div class="card">
        <h2>Bảng chi tiết</h2>

        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Sản phẩm</th>
                        <th class="num">Lượt xem</th>
                    </tr>
                </thead>

                <tbody>
                    <?php foreach ($prods as $p): ?>
                        <tr>
                            <td><?= e($p['label']) ?></td>
                            <td class="num">
                                <?= (int) $p['clicks'] ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>

                    <?php if (!$prods): ?>
                        <tr>
                            <td colspan="2">
                                Chưa có dữ liệu thống kê.
                            </td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<p class="hint">
    Số lượt nhấp của từng sản phẩm được xác định bởi
    trường "Sản phẩm" trong
    <a href="rules.php">quy tắc sự kiện</a>.
</p>

<?php wi_footer(); ?>

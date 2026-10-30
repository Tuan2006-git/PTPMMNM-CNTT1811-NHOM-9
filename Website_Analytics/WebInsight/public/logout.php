<?php
require __DIR__ . '/../src/bootstrap.php';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    wi_logout();
}
wi_redirect('login.php');

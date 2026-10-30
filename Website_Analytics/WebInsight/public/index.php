<?php
require __DIR__ . '/../src/bootstrap.php';
wi_redirect(wi_current_admin() ? 'dashboard.php' : 'login.php');

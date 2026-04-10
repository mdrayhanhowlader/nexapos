<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::requireAuth();
Response::redirect(app_url('public/dashboard.php'));

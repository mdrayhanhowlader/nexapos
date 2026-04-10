<?php
require_once dirname(__DIR__) . '/bootstrap.php';
Auth::logout();
Response::redirect(app_url('public/login.php'));

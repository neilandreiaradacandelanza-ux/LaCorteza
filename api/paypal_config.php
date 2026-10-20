<?php
require_once __DIR__ . '/../functions/env.php';

define('PAYPAL_CLIENT_ID', env('PAYPAL_CLIENT_ID'));
define('PAYPAL_CLIENT_SECRET', env('PAYPAL_CLIENT_SECRET'));
define('PAYPAL_BASE_URL', env('PAYPAL_BASE_URL', 'https://api-m.sandbox.paypal.com'));
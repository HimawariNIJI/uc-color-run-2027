<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();
try {
    echo view('registration.simple')->render();
} catch (Exception $e) {
    echo 'ERROR: '.$e->getMessage();
}
?>

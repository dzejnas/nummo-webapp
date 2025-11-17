<?php
Flight::route('GET /', function() {
    include __DIR__ . '/../views/health.php';
});

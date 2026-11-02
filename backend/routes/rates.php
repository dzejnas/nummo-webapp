<?php
require_once __DIR__ . '/../services/ExchangeRateService.php';

Flight::register('exchangeRateService', 'ExchangeRateService');

// External API (Frankfurter) — exchange rates for the dashboard currency converter

Flight::route('GET /api/rates', function () {
    Flight::json(Flight::exchangeRateService()->getRates(Flight::request()->query->getData()));
});

Flight::route('GET /api/rates/convert', function () {
    Flight::json(Flight::exchangeRateService()->convert(Flight::request()->query->getData()));
});

Flight::route('GET /api/rates/currencies', function () {
    Flight::json(Flight::exchangeRateService()->getCurrencies());
});

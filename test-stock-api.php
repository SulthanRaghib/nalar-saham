#!/usr/bin/env php
<?php

/**
 * Stock API Test - Alpha Vantage
 */

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

echo "\n╔═══════════════════════════════════════════════════════════╗\n";
echo "║  Stock Fundamental Data API Test                          ║\n";
echo "║  Powered by Alpha Vantage                                 ║\n";
echo "╚═══════════════════════════════════════════════════════════╝\n\n";

echo "✅ Configuration:\n";
$key = config('services.alpha_vantage.key');
echo "   API Key: " . substr($key, 0, 15) . "...\n";
echo "   Type: " . ($key === 'demo' ? 'DEMO (Limited)' : 'REAL') . "\n\n";

$service = app(\App\Services\StockApiService::class);
$tickers = ['AAPL', 'MSFT', 'JPM', 'JNJ'];
$success = 0;

foreach ($tickers as $ticker) {
    echo "Testing {$ticker}...\n";
    $data = $service->fetchFundamentalData($ticker);

    if ($data) {
        echo "  ✅ EPS: {$data['eps']}, BVPS: {$data['bvps']}, ROE: {$data['roe']}%\n";
        $success++;
    } else {
        echo "  ❌ No data\n";
    }
    usleep(500000);
}

echo "\n✅ Result: {$success}/" . count($tickers) . " successful\n\n";

if ($success > 0) {
    echo "System working! Register real key at https://www.alphavantage.co/\n";
    echo "Manual input mode always available as fallback.\n";
}
echo "\n";

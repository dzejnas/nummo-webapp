<?php
require_once __DIR__ . '/../config/Settings.php';
require_once __DIR__ . '/../utils/ApiException.php';
require_once __DIR__ . '/../utils/Logger.php';
require_once __DIR__ . '/../utils/Validator.php';

// Talks to the external Frankfurter API (https://frankfurter.dev) for exchange rates.
// If the API is slow or down, the failure is written to the error log and the client gets
// a friendly 503 message; the rest of Nummo keeps working.
class ExchangeRateService
{
    private string $baseUrl;
    private int $timeout;

    public function __construct()
    {
        $this->baseUrl = rtrim(Settings::get('rates_api_url'), '/');
        $this->timeout = (int) Settings::get('rates_api_timeout');
    }

    // Supported currencies, e.g. ["EUR" => "Euro", "USD" => "United States Dollar", ...]
    public function getCurrencies(): array
    {
        return $this->request('/currencies');
    }

    // Latest rates for one base currency, e.g. base EUR -> ["USD" => 1.12, "GBP" => 0.84]
    public function getRates(array $query): array
    {
        $base = isset($query['base']) ? Validator::currency($query, 'base') : 'EUR';
        $params = ['base' => $base];

        if (!empty($query['symbols'])) {
            $params['symbols'] = strtoupper(preg_replace('/[^A-Za-z,]/', '', $query['symbols']));
        }

        $result = $this->request('/latest?' . http_build_query($params));
        return [
            'base'  => $result['base'],
            'date'  => $result['date'],
            'rates' => $result['rates'],
        ];
    }

    // Converts an amount, e.g. 100 EUR -> USD
    public function convert(array $query): array
    {
        $amount = (float) Validator::amount($query); // display conversion only, never stored
        $from   = Validator::currency($query, 'from');
        $to     = Validator::currency($query, 'to');

        if ($from === $to) {
            return ['amount' => $amount, 'from' => $from, 'to' => $to, 'rate' => 1.0,
                    'result' => $amount, 'date' => date('Y-m-d')];
        }

        $result = $this->request('/latest?' . http_build_query(['base' => $from, 'symbols' => $to]));
        $rate = (float) $result['rates'][$to];

        return [
            'amount' => $amount,
            'from'   => $from,
            'to'     => $to,
            'rate'   => $rate,
            'result' => round($amount * $rate, 2),
            'date'   => $result['date'],
        ];
    }

    // Sends a GET request to the Frankfurter API and returns the decoded JSON
    private function request(string $path): array
    {
        $url = $this->baseUrl . $path;

        $curl = curl_init($url);
        curl_setopt_array($curl, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CONNECTTIMEOUT => $this->timeout,
            CURLOPT_TIMEOUT        => $this->timeout,
            CURLOPT_HTTPHEADER     => ['Accept: application/json'],
        ]);
        $body   = curl_exec($curl);
        $status = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error  = curl_error($curl);
        curl_close($curl);

        if ($body === false) {
            Logger::error("Exchange rate API unreachable: GET $url - $error");
            throw new ApiException('Exchange rates are not available right now. Please try again later.', 503);
        }

        $data = json_decode($body, true);

        // Frankfurter answers 404 for currencies it does not know
        if ($status === 404 || $status === 422) {
            Logger::error("Exchange rate API rejected request: GET $url - HTTP $status");
            throw new ApiException('Unknown or unsupported currency', 400);
        }
        if ($status !== 200 || !is_array($data)) {
            Logger::error("Exchange rate API error: GET $url - HTTP $status");
            throw new ApiException('Exchange rates are not available right now. Please try again later.', 503);
        }

        Logger::info("Exchange rate API call: GET $url - HTTP $status");
        return $data;
    }
}

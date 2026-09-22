<?php
declare(strict_types=1);

class PlategaService
{
    private string $baseUrl;
    private string $merchantId;
    private string $secret;

    public function __construct(array $config)
    {
        $this->baseUrl    = rtrim((string)($config['platega']['base_url'] ?? 'https://app.platega.io'), '/');
        $this->merchantId = trim((string)($config['platega']['merchant_id'] ?? ''));
        $this->secret     = trim((string)($config['platega']['secret'] ?? ''));
    }

    public function createTransaction(
        int $paymentMethod,
        int $totalAmount,
        string $currency,
        string $description,
        string $returnUrl,
        string $failedUrl,
        string $payload = '',
        array $metadata = []
    ): array {
        $endpoint = $this->baseUrl . '/transaction/process';

        $cleanMetadata = ['service' => 'GRAMPAY'];
        if (!empty($metadata) && is_array($metadata)) {
            foreach ($metadata as $key => $value) {
                if ($value !== null && $value !== '') {
                    $cleanMetadata[(string)$key] = (string)$value;
                }
            }
        }

        $body = [
            'paymentMethod'  => (int)$paymentMethod,
            'paymentDetails' => [
                'amount'   => (int)$totalAmount,
                'currency' => strtoupper(trim($currency))
            ],
            'description'    => $description,
            'return'         => $returnUrl,
            'failedUrl'      => $failedUrl,
            'payload'        => $payload,
            'metadata'       => $cleanMetadata
        ];

        return $this->sendRequest('POST', $endpoint, $body);
    }

    public function getTransactionStatus(string $transactionId): array
    {
        $endpoint = $this->baseUrl . '/transaction/' . urlencode($transactionId);
        return $this->sendRequest('GET', $endpoint);
    }

    private function sendRequest(string $method, string $url, ?array $data = null): array
    {
        $curl = curl_init();

        $headers = [
            'X-MerchantId: ' . $this->merchantId,
            'X-Secret: ' . $this->secret,
            'Content-Type: application/json',
            'Accept: application/json'
        ];

        $opts = [
            CURLOPT_URL            => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING       => '',
            CURLOPT_MAXREDIRS      => 10,
            CURLOPT_TIMEOUT        => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_SSL_VERIFYPEER => true
        ];

        if ($method === 'POST' && $data !== null) {
            $opts[CURLOPT_POSTFIELDS] = json_encode($data, JSON_UNESCAPED_UNICODE);
        }

        curl_setopt_array($curl, $opts);
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if (curl_errno($curl)) {
            $err = curl_error($curl);
            curl_close($curl);
            throw new RuntimeException("cURL Network Error: " . $err);
        }

        curl_close($curl);

        $decoded = json_decode((string)$response, true);

        if ($httpCode >= 400 || !is_array($decoded) || isset($decoded['code']) || isset($decoded['error'])) {
            $rawResponse = is_string($response) ? $response : json_encode($decoded, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            throw new RuntimeException("HTTP {$httpCode} Response: " . $rawResponse);
        }

        return $decoded;
    }
}
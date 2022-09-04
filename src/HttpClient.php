<?php

class HttpClient
{
    public function postFormUrlEncodedAcceptJson(string $uri, array $data): ?array
    {
        $ch = curl_init($uri);

        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POSTFIELDS => http_build_query($data),
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ]
        ]);

        $response = curl_exec($ch);
        curl_close($ch);

        if (!$response) {
            return null;
        }

        $response = json_decode($response, true);

        if (!$response) {
            return null;
        }

        return $response;
    }
}
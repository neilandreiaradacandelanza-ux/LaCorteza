<?php 
require_once __DIR__ . '/paypal_config.php';

function paypalToken(): ?string { 
    $ch = curl_init(PAYPAL_BASE_URL . '/v1/oauth2/token'); 
    curl_setopt_array($ch, [ 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_TIMEOUT => 10, 
        CURLOPT_USERPWD => PAYPAL_CLIENT_ID . ':' . PAYPAL_CLIENT_SECRET,  
        CURLOPT_POSTFIELDS => 'grant_type=client_credentials',  
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]); 
    $raw = curl_exec($ch); 
    if ($raw === false) { 
        return null; 
    } 
    $res = json_decode($raw, true); 
    return $res['access_token'] ?? null; 
}

function paypalRequest(string $method, string $path, ?array $body = null): array { 
    $token = paypalToken(); 
    if ($token === null) { 
        return ['status' => 0, 'data' => ['error' => 'Failed to retrieve access token. Check Client ID and Secret in paypal_config.php']]; 
    } 
    $ch = curl_init(PAYPAL_BASE_URL . $path); 
    curl_setopt_array($ch, [ 
        CURLOPT_RETURNTRANSFER => true, 
        CURLOPT_TIMEOUT => 15, 
        CURLOPT_CUSTOMREQUEST => $method, 
        CURLOPT_HTTPHEADER => [ 
            'Content-Type: application/json', 
            'Authorization: Bearer ' . $token, 
        ], 
        CURLOPT_POSTFIELDS => $body === null ? '' : json_encode($body),  
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
    ]); 
    $raw = curl_exec($ch); 
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE); 
    return ['status' => $status, 'data' => $raw === false ? null : json_decode($raw, true)]; 
}
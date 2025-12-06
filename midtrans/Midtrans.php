<?php

class Midtrans {
    
    public static $serverKey;
    public static $clientKey;
    public static $isProduction = false;
    public static $isSanitized = true;
    public static $is3ds = true;
    
    const SNAP_SANDBOX_BASE_URL = 'https://app.sandbox.midtrans.com/snap/v1';
    const SNAP_PRODUCTION_BASE_URL = 'https://app.midtrans.com/snap/v1';
    
    const API_SANDBOX_BASE_URL = 'https://api.sandbox.midtrans.com/v2';
    const API_PRODUCTION_BASE_URL = 'https://api.midtrans.com/v2';
    
    /**
     * Get Snap API URL
     */
    public static function getSnapBaseUrl() {
        return self::$isProduction ? self::SNAP_PRODUCTION_BASE_URL : self::SNAP_SANDBOX_BASE_URL;
    }
    
    /**
     * Get Core API URL
     */
    public static function getApiBaseUrl() {
        return self::$isProduction ? self::API_PRODUCTION_BASE_URL : self::API_SANDBOX_BASE_URL;
    }
    
    /**
     * Create Snap Transaction
     */
    public static function createTransaction($params) {
        $url = self::getSnapBaseUrl() . '/transactions';
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => json_encode($params),
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Content-Type: application/json',
                'Authorization: Basic ' . base64_encode(self::$serverKey . ':')
            ),
        ));
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Midtrans Error: ' . ($result['error_messages'][0] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    /**
     * Get Transaction Status
     */
    public static function status($orderId) {
        $url = self::getApiBaseUrl() . '/' . $orderId . '/status';
        
        $curl = curl_init();
        
        curl_setopt_array($curl, array(
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'GET',
            CURLOPT_HTTPHEADER => array(
                'Accept: application/json',
                'Authorization: Basic ' . base64_encode(self::$serverKey . ':')
            ),
        ));
        
        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        
        curl_close($curl);
        
        if ($error) {
            throw new Exception('cURL Error: ' . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            throw new Exception('Midtrans Error: ' . ($result['error_messages'][0] ?? 'Unknown error'));
        }
        
        return $result;
    }
    
    /**
     * Verify notification signature
     */
    public static function verifySignature($orderId, $statusCode, $grossAmount, $signatureKey) {
        $mySignature = hash('sha512', $orderId . $statusCode . $grossAmount . self::$serverKey);
        return $mySignature === $signatureKey;
    }
    
    /**
     * Handle notification from Midtrans
     */
    public static function handleNotification($postData) {
        $notification = json_decode($postData, true);
        
        if (empty($notification)) {
            throw new Exception('Invalid notification data');
        }
        
        // Verify signature
        $orderId = $notification['order_id'] ?? '';
        $statusCode = $notification['status_code'] ?? '';
        $grossAmount = $notification['gross_amount'] ?? '';
        $signatureKey = $notification['signature_key'] ?? '';
        
        if (!self::verifySignature($orderId, $statusCode, $grossAmount, $signatureKey)) {
            throw new Exception('Invalid signature');
        }
        
        return $notification;
    }
}

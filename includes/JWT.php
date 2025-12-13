<?php
require_once __DIR__ . '/Database.php';

/**
 * فئة JWT للتوثيق
 * JWT Authentication Class
 */

class JWT {
    
    /**
     * توليد توكن JWT
     */
    public static function generate(array $payload): string {
        $header = self::base64UrlEncode(json_encode([
            'typ' => 'JWT',
            'alg' => 'HS256'
        ]));
        
        $payload['iat'] = time();
        $payload['exp'] = time() + JWT_EXPIRY;
        
        $payloadEncoded = self::base64UrlEncode(json_encode($payload));
        
        $signature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payloadEncoded", JWT_SECRET, true)
        );
        
        return "$header.$payloadEncoded.$signature";
    }
    
    /**
     * التحقق من صحة التوكن
     */
    public static function verify(string $token): ?array {
        $parts = explode('.', $token);
        
        if (count($parts) !== 3) {
            return null;
        }
        
        [$header, $payload, $signature] = $parts;
        
        // التحقق من التوقيع
        $expectedSignature = self::base64UrlEncode(
            hash_hmac('sha256', "$header.$payload", JWT_SECRET, true)
        );
        
        if (!hash_equals($expectedSignature, $signature)) {
            return null;
        }
        
        $payloadData = json_decode(self::base64UrlDecode($payload), true);
        
        if (!$payloadData) {
            return null;
        }
        
        // التحقق من انتهاء الصلاحية
        if (isset($payloadData['exp']) && $payloadData['exp'] < time()) {
            return null;
        }
        
        return $payloadData;
    }
    
    /**
     * استخراج التوكن من الهيدر
     */
    public static function getTokenFromHeader(): ?string {
        $headers = getallheaders();
        $authHeader = $headers['Authorization'] ?? $headers['authorization'] ?? '';
        
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * الحصول على المستخدم الحالي من التوكن
     */
    public static function getCurrentUser(): ?array {
        $token = self::getTokenFromHeader();
        
        if (!$token) {
            return null;
        }
        
        return self::verify($token);
    }
    
    /**
     * توليد Refresh Token
     */
    public static function generateRefreshToken(int $userId): string {
        $token = bin2hex(random_bytes(32));
        
        $db = Database::getInstance();
        $db->insert('api_tokens', [
            'user_id' => $userId,
            'token' => hash('sha256', $token),
            'expires_at' => date('Y-m-d H:i:s', time() + JWT_REFRESH_EXPIRY)
        ]);
        
        return $token;
    }
    
    /**
     * تشفير Base64 URL Safe
     */
    private static function base64UrlEncode(string $data): string {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
    
    /**
     * فك تشفير Base64 URL Safe
     */
    private static function base64UrlDecode(string $data): string {
        return base64_decode(strtr($data, '-_', '+/'));
    }
}

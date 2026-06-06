<?php
/**
 * HTTP GET — ใช้ curl ก่อน (shared hosting มักปิด allow_url_fopen)
 */
declare(strict_types=1);

namespace App\Helpers;

final class HttpClient
{
    private static string $lastError = '';

    public static function lastError(): string
    {
        return self::$lastError;
    }

    public static function get(string $url, int $timeout = 20): ?string
    {
        self::$lastError = '';

        if (function_exists('curl_init')) {
            $ch = curl_init($url);
            if ($ch === false) {
                self::$lastError = 'curl_init failed';

                return null;
            }
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => $timeout,
                CURLOPT_CONNECTTIMEOUT => min(10, $timeout),
                CURLOPT_SSL_VERIFYPEER => true,
                CURLOPT_SSL_VERIFYHOST => 2,
                CURLOPT_HTTPHEADER => ['Accept: application/json'],
                CURLOPT_USERAGENT => 'Omnichannel/1.0',
            ]);
            $resp = curl_exec($ch);
            if ($resp === false) {
                self::$lastError = (string) curl_error($ch);
                curl_close($ch);

                return null;
            }
            $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            if ($code >= 400) {
                self::$lastError = 'HTTP ' . $code;

                return is_string($resp) ? $resp : null;
            }

            return is_string($resp) ? $resp : null;
        }

        if (filter_var(ini_get('allow_url_fopen'), FILTER_VALIDATE_BOOLEAN)) {
            $ctx = stream_context_create([
                'http' => [
                    'method' => 'GET',
                    'timeout' => $timeout,
                    'ignore_errors' => true,
                    'header' => "Accept: application/json\r\nUser-Agent: Omnichannel/1.0\r\n",
                ],
                'ssl' => [
                    'verify_peer' => true,
                    'verify_peer_name' => true,
                ],
            ]);
            $resp = @file_get_contents($url, false, $ctx);
            if ($resp === false) {
                self::$lastError = 'file_get_contents failed (allow_url_fopen)';

                return null;
            }

            return $resp;
        }

        self::$lastError = 'curl และ allow_url_fopen ใช้ไม่ได้บนเซิร์ฟเวอร์';

        return null;
    }
}

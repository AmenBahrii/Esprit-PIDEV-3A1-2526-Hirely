<?php

namespace App\Service\Forum\AI;

final class CliCurlFallback
{
    /**
     * @param array<string, mixed> $payload
     * @return array{status_code:int, body:string}
     */
    public static function postJson(string $url, array $payload, int $timeoutSeconds = 15): array
    {
        if (PHP_OS_FAMILY !== 'Windows' && function_exists('curl_init')) {
            return self::postJsonWithCurlExtension($url, $payload, $timeoutSeconds);
        }

        $payloadFile = tempnam(sys_get_temp_dir(), 'forum-api-payload-');
        $responseFile = tempnam(sys_get_temp_dir(), 'forum-api-response-');
        $headersFile = tempnam(sys_get_temp_dir(), 'forum-api-headers-');

        if ($payloadFile === false || $responseFile === false || $headersFile === false) {
            throw new \RuntimeException('Unable to create temporary files for curl fallback.');
        }

        try {
            file_put_contents($payloadFile, json_encode($payload, JSON_THROW_ON_ERROR));

            $sslFlags = PHP_OS_FAMILY === 'Windows' ? ' --ssl-no-revoke' : '';

            $command = sprintf(
                'curl.exe -sS%s -X POST -H %s --data-binary %s -D %s -o %s --connect-timeout %d --max-time %d %s',
                $sslFlags,
                self::quoteArg('Content-Type: application/json'),
                self::quoteArg('@' . $payloadFile),
                self::quoteArg($headersFile),
                self::quoteArg($responseFile),
                max(1, min($timeoutSeconds, 30)),
                max(1, $timeoutSeconds),
                self::quoteArg($url)
            );

            $descriptorSpec = [
                0 => ['pipe', 'r'],
                1 => ['pipe', 'w'],
                2 => ['pipe', 'w'],
            ];

            $process = proc_open($command, $descriptorSpec, $pipes);
            if (!is_resource($process)) {
                throw new \RuntimeException('Unable to start curl fallback process.');
            }

            fclose($pipes[0]);
            $stdout = stream_get_contents($pipes[1]) ?: '';
            $stderr = stream_get_contents($pipes[2]) ?: '';
            fclose($pipes[1]);
            fclose($pipes[2]);
            $exitCode = proc_close($process);

            if ($exitCode !== 0) {
                throw new \RuntimeException(trim($stderr) !== '' ? trim($stderr) : 'curl.exe request failed.');
            }

            $statusCode = self::parseStatusCode((string) file_get_contents($headersFile));
            $body = file_get_contents($responseFile);
            if ($body === false) {
                $body = '';
            }

            return [
                'status_code' => $statusCode,
                'body' => $body,
            ];
        } finally {
            if (is_file($payloadFile)) {
                @unlink($payloadFile);
            }
            if (is_file($responseFile)) {
                @unlink($responseFile);
            }
            if (is_file($headersFile)) {
                @unlink($headersFile);
            }
        }
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status_code:int, body:string}
     */
    private static function postJsonWithCurlExtension(string $url, array $payload, int $timeoutSeconds): array
    {
        $body = json_encode($payload, JSON_THROW_ON_ERROR);
        $handle = curl_init($url);
        if ($handle === false) {
            throw new \RuntimeException('Unable to initialize cURL fallback.');
        }

        try {
            curl_setopt_array($handle, [
                CURLOPT_POST => true,
                CURLOPT_HTTPHEADER => ['Content-Type: application/json'],
                CURLOPT_POSTFIELDS => $body,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_CONNECTTIMEOUT => max(1, min($timeoutSeconds, 30)),
                CURLOPT_TIMEOUT => max(1, $timeoutSeconds),
            ]);

            if (PHP_OS_FAMILY === 'Windows' && defined('CURLSSLOPT_NO_REVOKE')) {
                curl_setopt($handle, CURLOPT_SSL_OPTIONS, CURLSSLOPT_NO_REVOKE);
            }

            $responseBody = curl_exec($handle);
            if ($responseBody === false) {
                throw new \RuntimeException(curl_error($handle) ?: 'cURL request failed.');
            }

            return [
                'status_code' => (int) curl_getinfo($handle, CURLINFO_RESPONSE_CODE),
                'body' => (string) $responseBody,
            ];
        } finally {
            curl_close($handle);
        }
    }

    private static function quoteArg(string $value): string
    {
        if (PHP_OS_FAMILY !== 'Windows') {
            return escapeshellarg($value);
        }

        return '"' . str_replace('"', '\"', $value) . '"';
    }

    private static function parseStatusCode(string $headers): int
    {
        if (preg_match_all('/^HTTP\/\S+\s+(\d{3})/mi', $headers, $matches) === false) {
            return 0;
        }

        $codes = $matches[1];
        if ($codes === []) {
            return 0;
        }

        return (int) end($codes);
    }
}

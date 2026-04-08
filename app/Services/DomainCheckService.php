<?php

namespace App\Services;

use App\Models\CheckLog;
use App\Models\DomainCheck;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Throwable;

class DomainCheckService
{
    public function run(DomainCheck $check): CheckLog
    {
        $url = $check->buildUrl();
        $method = strtoupper($check->method);
        $timeout = max(1, min(120, (int) $check->timeout_seconds));

        $meta = [];
        $started = microtime(true);
        $httpStatus = null;
        $errorMessage = null;
        $ok = false;

        try {
            $pending = Http::timeout($timeout)
                ->withOptions([
                    'allow_redirects' => false,
                    'verify' => true,
                ]);

            $response = $method === 'HEAD'
                ? $pending->head($url)
                : $pending->get($url);

            $httpStatus = $response->status();
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);

            if ($httpStatus >= 300 && $httpStatus < 400) {
                $meta['type'] = 'redirect';
                $meta['location'] = $response->header('Location');
                $errorMessage = __('checks.redirect_not_success');
            } elseif ($httpStatus >= 200 && $httpStatus < 300) {
                $ok = true;
            } else {
                $meta['type'] = 'http_error';
                $errorMessage = __('checks.http_status_not_success', ['code' => $httpStatus]);
            }

            return $this->persistLog($check, $ok, $httpStatus, $elapsedMs, $errorMessage, $meta);
        } catch (ConnectionException $e) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $msg = $e->getMessage();
            $meta['type'] = 'connection';
            if ($this->looksLikeSslProblem($msg)) {
                $meta['ssl'] = true;
                $errorMessage = __('checks.ssl_or_tls_error');
            } else {
                $errorMessage = $msg;
            }

            return $this->persistLog($check, false, $httpStatus, $elapsedMs, $errorMessage, $meta);
        } catch (RequestException $e) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $response = $e->response;
            $httpStatus = $response ? $response->status() : null;
            $errorMessage = $e->getMessage();

            return $this->persistLog($check, false, $httpStatus, $elapsedMs, $errorMessage, $meta);
        } catch (Throwable $e) {
            $elapsedMs = (int) round((microtime(true) - $started) * 1000);
            $errorMessage = $e->getMessage();

            return $this->persistLog($check, false, $httpStatus, $elapsedMs, $errorMessage, $meta);
        }
    }

    private function persistLog(
        DomainCheck $check,
        bool $ok,
        ?int $httpStatus,
        int $responseTimeMs,
        ?string $errorMessage,
        array $meta
    ): CheckLog {
        return CheckLog::query()->create([
            'domain_check_id' => $check->id,
            'ok' => $ok,
            'http_status' => $httpStatus,
            'response_time_ms' => $responseTimeMs,
            'error_message' => $errorMessage,
            'meta' => $meta ?: null,
        ]);
    }

    private function looksLikeSslProblem(string $message): bool
    {
        $m = strtolower($message);

        return str_contains($m, 'ssl')
            || str_contains($m, 'tls')
            || str_contains($m, 'certificate')
            || str_contains($m, 'cert ')
            || str_contains($m, 'handshake');
    }
}

<?php

namespace App\Services\Sms;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsSender
{
    public function sendPhoneCode(string $phone, string $code, ?string $ip = null): SmsSendResult
    {
        $driver = (string) config('sms.driver', 'log');
        $message = $this->buildCodeMessage($code);

        if ($driver === 'smsru') {
            return $this->sendViaSmsRu($phone, $message, $ip);
        }

        Log::info('SMS confirmation code', [
            'phone' => $phone,
            'code' => $code,
            'message' => $message,
        ]);

        return SmsSendResult::success('Код записан в лог приложения');
    }

    private function sendViaSmsRu(string $phone, string $message, ?string $ip = null): SmsSendResult
    {
        $apiId = (string) config('sms.smsru.api_id');

        if (blank($apiId)) {
            return SmsSendResult::fail('Не указан SMSRU_API_ID в .env');
        }

        $payload = [
            'api_id' => $apiId,
            'to' => $phone,
            'msg' => $message,
            'json' => 1,
        ];

        $from = config('sms.smsru.from');
        if (!blank($from)) {
            $payload['from'] = $from;
        }

        if (!blank($ip)) {
            $payload['ip'] = $ip;
        }

        if ((bool) config('sms.smsru.test')) {
            $payload['test'] = 1;
        }

        try {
            $response = Http::asForm()
                ->timeout((int) config('sms.smsru.timeout', 12))
                ->post('https://sms.ru/sms/send', $payload);
        } catch (Throwable $e) {
            Log::warning('SMS.RU request failed', [
                'phone' => $phone,
                'error' => $e->getMessage(),
            ]);

            return SmsSendResult::fail('SMS-сервис недоступен. Попробуйте позже.');
        }

        $json = $response->json();

        if (!$response->ok() || !is_array($json)) {
            Log::warning('SMS.RU invalid response', [
                'phone' => $phone,
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return SmsSendResult::fail('SMS-сервис вернул некорректный ответ.');
        }

        if (($json['status'] ?? null) !== 'OK') {
            return SmsSendResult::fail($json['status_text'] ?? 'SMS не отправлено.', $json);
        }

        $smsItem = $json['sms'][$phone] ?? null;

        if (!is_array($smsItem) || ($smsItem['status'] ?? null) !== 'OK') {
            return SmsSendResult::fail($smsItem['status_text'] ?? 'SMS не отправлено на указанный номер.', $json);
        }

        return SmsSendResult::success('SMS отправлено', $smsItem['sms_id'] ?? null, $json);
    }

    private function buildCodeMessage(string $code): string
    {
        return 'Код подтверждения Чистый город: ' . $code . '. Никому не сообщайте этот код.';
    }
}

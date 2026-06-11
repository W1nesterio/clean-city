<?php

namespace App\Services\Sms;

class SmsSendResult
{
    public function __construct(
        public readonly bool $ok,
        public readonly ?string $message = null,
        public readonly ?string $providerMessageId = null,
        public readonly ?array $raw = null,
    ) {
    }

    public static function success(?string $message = null, ?string $providerMessageId = null, ?array $raw = null): self
    {
        return new self(true, $message, $providerMessageId, $raw);
    }

    public static function fail(?string $message = null, ?array $raw = null): self
    {
        return new self(false, $message, null, $raw);
    }
}

<?php

namespace IranKish\Support;

/**
 * این کلاس پاسخ‌های درگاه ایران‌کیش را در قالب یک آبجکت مدیریت می‌کند.
 * هدف این است که توسعه‌دهنده بتواند با متدهای خوانا و قابل پیش‌بینی
 * به وضعیت و داده‌های بازگشتی دسترسی داشته باشد.
 *
 * معمولاً در پاسخ API ایران‌کیش ساختار زیر وجود دارد:
 * {
 *   "responseCode": "00",
 *   "description": "Successful",
 *   "token": "...",
 *   "retrievalReferenceNumber": "...",
 *   "systemTraceAuditNumber": "...",
 *   "amount": 10000,
 *   "maskedPan": "6037********1234",
 *   "sha256OfPan": "..."
 * }
 */
class IkcResponse
{
    /**
     * پاسخ خام دریافتی از سرور ایران‌کیش
     */
    protected array $raw;

    /**
     * نمونه جدید با آرایه پاسخ دریافتی ساخته می‌شود.
     */
    public function __construct(array $response)
    {
        $this->raw = $response;
    }

    /**
     * بررسی موفق بودن تراکنش
     * اگر کد پاسخ برابر "00" باشد یعنی عملیات موفق بوده.
     */
    public function isSuccessful(): bool
    {
        return $this->get('responseCode') === '00';
    }

    /**
     * برگرداندن توضیح متنی از پاسخ درگاه (description)
     */
    public function message(): ?string
    {
        return $this->get('description');
    }

    /**
     * دریافت مقدار توکن پرداخت در پاسخ makeToken
     */
    public function token(): ?string
    {
        return $this->get('token');
    }

    /**
     * شماره ارجاع بانکی (RRN)
     */
    public function rrn(): ?string
    {
        return $this->get('retrievalReferenceNumber');
    }

    /**
     * شماره پیگیری (STAN)
     */
    public function stan(): ?string
    {
        return $this->get('systemTraceAuditNumber');
    }

    /**
     * مبلغ تراکنش (amount)
     */
    public function amount(): ?int
    {
        $amount = $this->get('amount');
        return $amount !== null ? (int) $amount : null;
    }

    /**
     * شماره کارت ماسک‌شده (maskedPan)
     */
    public function cardMasked(): ?string
    {
        return $this->get('maskedPan');
    }

    /**
     * هش کارت (sha256OfPan)
     */
    public function cardHash(): ?string
    {
        return $this->get('sha256OfPan');
    }

    /**
     * دسترسی مستقیم به فیلد خاص از پاسخ
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->raw[$key] ?? $default;
    }

    /**
     * تمام داده‌های خام پاسخ را برمی‌گرداند.
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * تبدیل به JSON برای استفاده در لاگ یا دیباگ
     */
    public function toJson(int $options = JSON_PRETTY_PRINT): string
    {
        return json_encode($this->raw, $options | JSON_UNESCAPED_UNICODE);
    }

    /**
     * در صورت نیاز برای log یا dd()
     */
    public function __toString(): string
    {
        return $this->toJson();
    }
}

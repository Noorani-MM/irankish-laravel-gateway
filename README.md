# IranKish Laravel Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![Total Downloads](https://img.shields.io/packagist/dt/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

درگاه پرداخت ایران‌کیش برای لاراول، با پیاده‌سازی کامل طبق مستندات فنی **نسخه 9 (V9)**.  
طراحی‌شده برای توسعه‌دهندگان مدرن لاراول با تمرکز بر خوانایی، امنیت و قابلیت تست.

---

## 🚀 نصب پکیج

```bash
composer require noorani-mm/irankish-laravel-gateway
````

---

## ⚙️ تنظیمات اولیه

پس از نصب، فایل تنظیمات را منتشر کنید:

```bash
php artisan vendor:publish --provider="IranKish\IranKishServiceProvider" --tag="config"
```

فایل `config/irankish.php` ایجاد می‌شود.
مقدارهای زیر را با اطلاعات درگاه خود جایگزین کنید:

```php
return [
    'terminal_id'    => env('IRANKISH_TERMINAL_ID'),
    'acceptor_id'    => env('IRANKISH_ACCEPTOR_ID'),
    'pass_phrase'    => env('IRANKISH_PASS_PHRASE'),
    'callback_url'   => env('IRANKISH_CALLBACK_URL'),
    'public_key'     => env('IRANKISH_PUBLIC_KEY'),
    'rsa_padding'    => OPENSSL_PKCS1_PADDING, // یا OPENSSL_PKCS1_OAEP_PADDING
    'sandbox'        => env('IRANKISH_SANDBOX', false),
];
```

در `.env` اضافه کنید:

```bash
IRANKISH_TERMINAL_ID=12345678
IRANKISH_ACCEPTOR_ID=87654321
IRANKISH_PASS_PHRASE=YourSecretPass
IRANKISH_CALLBACK_URL=https://example.com/payment/callback
IRANKISH_PUBLIC_KEY="-----BEGIN PUBLIC KEY----- ... -----END PUBLIC KEY-----"
```

---

## 💳 مثال استفاده

در Controller خود:

```php
use IranKish\Facades\IranKish;

class PaymentController extends Controller
{
    public function pay()
    {
        $amount = 150000; // مبلغ به ریال
        $orderId = 'ORDER-1234';

        $response = IranKish::makeToken($amount, $orderId);

        if ($response->isSuccessful()) {
            $token = $response->token();
            return redirect()->away("https://ikc.shaparak.ir/ikcstartpay/{$token}");
        }

        return back()->withErrors($response->message());
    }

    public function callback(Request $request)
    {
        $response = IranKish::confirm(
            $request->input('tokenIdentity'),
            $request->input('RRN'),
            $request->input('STAN')
        );

        if ($response->isSuccessful()) {
            return 'پرداخت با موفقیت انجام شد ✅';
        }

        return 'پرداخت ناموفق بود ❌';
    }
}
```

---

## 🧠 متدهای اصلی

| متد                                                         | توضیح                |
| ----------------------------------------------------------- | -------------------- |
| `makeToken(int $amount, ?string $paymentId)`                | ایجاد توکن پرداخت    |
| `confirm(string $tokenIdentity, string $rrn, string $stan)` | تأیید نهایی تراکنش   |
| `reverse(string $tokenIdentity, string $rrn, string $stan)` | بازگشت مبلغ (Refund) |
| `inquiry(string $rrn)`                                      | استعلام وضعیت تراکنش |

---

## 🧪 اجرای تست‌ها

پکیج شامل تست‌های واحد کامل با **Orchestra Testbench** است.

برای اجرای تست‌ها:

```bash
composer install
composer test
```

اگر می‌خواهید همه‌چیز تمیز باشد:

```bash
rm -rf vendor
composer install
composer test
```

📁 فایل تست‌ها در مسیر `tests/` قرار دارد.
نمونه‌ی کلید عمومی تستی (`tests/stub_public.pem`) در پکیج وجود دارد.

---

## 💡 نکات توسعه

* پکیج از نسخه‌های **Laravel 10 تا 14** پشتیبانی می‌کند.
* رمزگذاری RSA طبق مستندات رسمی ایران‌کیش V9 انجام می‌شود.
* ساختار و کدها بر پایه‌ی استانداردهای PSR و Composer طراحی شده‌اند.
* متدهای اصلی از طریق Facade `IranKish` در دسترس هستند.

---

## 🤝 مشارکت

پیشنهادها و Pull Requestها همیشه خوش‌آمد هستند 🙌
برای گزارش باگ‌ها یا پیشنهاد ویژگی جدید، از بخش [Issues](https://github.com/noorani-mm/irankish-laravel-gateway/issues) استفاده کنید.

---

## 📄 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

Copyright (c) 2025 [Mohammad Mahdi Noorani](https://github.com/noorani-mm)

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

# درگاه پرداخت ایران‌کیش برای لاراول

[![Latest Version on Packagist](https://img.shields.io/packagist/v/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![Total Downloads](https://img.shields.io/packagist/dt/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://github.com/noorani-mm/irankish-laravel-gateway/actions/workflows/tests.yml/badge.svg)](https://github.com/noorani-mm/irankish-laravel-gateway/actions)

پکیج رسمی و توسعه‌پذیر برای اتصال پروژه‌های **Laravel** به درگاه پرداخت اینترنتی **ایران‌کیش (IKC)**  
بر اساس مستند فنی رسمی [IranKish Technical Guide V9](https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf) طراحی و پیاده‌سازی شده است.

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

### تنظیمات `.env`

در فایل `.env` مقادیر زیر را اضافه کنید:

```bash
IRANKISH_TERMINAL_ID=12345678
IRANKISH_ACCEPTOR_ID=87654321
IRANKISH_PASSWORD=YourSecurePassword
IRANKISH_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkq...\n-----END PUBLIC KEY-----"
IRANKISH_REVERT_URL=https://example.com/payment/callback
```

### تنظیمات `config/irankish.php`

```php
return [
    'terminal_id' => env('IRANKISH_TERMINAL_ID'),
    'acceptor_id' => env('IRANKISH_ACCEPTOR_ID'),
    'password'    => env('IRANKISH_PASSWORD'),
    'public_key'  => env('IRANKISH_PUBLIC_KEY'),
    'revert_url'  => env('IRANKISH_REVERT_URL'),
];
```

---

## 💳 مثال استفاده

```php
use IranKish\Facades\IranKish;
use IranKish\Enums\TransactionType;
use IranKish\Exceptions\IranKishException;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function pay()
    {
        try {
            // مرحله ۱: دریافت توکن پرداخت
            $response = IranKish::requestToken(150000, TransactionType::PURCHASE, [
                'paymentId' => 'ORDER-1234',
            ]);

            // مرحله ۲: ریدایرکت خودکار به درگاه
            return IranKish::redirect($response['token']);

            // یا (در صورت نیاز به بازگشت داده برای SPA)
            // $redirect = IranKish::redirectData($response['token']);
            // return view('payments.redirect', compact('redirect'));
        }
        catch (IranKishException $e) {
            return back()->withErrors($e->getMessage());
        }
    }

    public function callback(Request $request)
    {
        try {
            // مرحله ۳: تأیید تراکنش پس از بازگشت از درگاه
            $confirmation = IranKish::confirm(
                $request->input('tokenIdentity'),
                $request->input('retrievalReferenceNumber'),
                $request->input('systemTraceAuditNumber')
            );

            return response()->json(['status' => 'success', 'data' => $confirmation]);
        }
        catch (IranKishException $e) {
            return response()->json(['status' => 'failed', 'message' => $e->getMessage()]);
        }
    }
}
```

---

## 📚 متدهای اصلی

| متد                                                                                 | توضیح                                                        |
| ----------------------------------------------------------------------------------- | ------------------------------------------------------------ |
| **`requestToken(int $amount, ?TransactionType $type = null, array $options = [])`** | ایجاد توکن پرداخت با استفاده از API `/tokenization/make`     |
| **`requestSpecialToken(...)`**                                                      | ایجاد توکن برای پذیرندگان خاص (مانند AsanShpWPP یا IsacoWPP) |
| **`redirect(string $token)`**                                                       | ریدایرکت خودکار کاربر به صفحه پرداخت ایران‌کیش               |
| **`redirectData(string $token)`**                                                   | بازگرداندن مشخصات ریدایرکت به صورت آرایه برای مدیریت دستی    |
| **`confirm(string $token, string $rrn, string $stan)`**                             | تأیید نهایی تراکنش پس از بازگشت از درگاه                     |
| **`reverse(string $token, string $rrn, string $stan)`**                             | لغو (برگشت) تراکنش موفق پیش از تسویه روزانه                  |
| **`inquiry(array $criteria)`**                                                      | استعلام وضعیت تراکنش با استفاده از RRN، Token یا RequestId   |

---

## 🧠 انواع تراکنش‌ها

| مقدار Enum       | توضیح                       |
| ---------------- | --------------------------- |
| `Purchase`       | خرید معمولی                 |
| `Bill`           | پرداخت قبض                  |
| `AsanShpWPP`     | کیف پول آسان‌پرداخت         |
| `SpecialBill`    | قبض ویژه                    |
| `AsanShpWPPDrug` | کیف پول دارویی آسان‌پرداخت  |
| `IsacoWPP`       | پرداخت از طریق ISACO Wallet |

---

## 🧪 اجرای تست‌ها

پکیج شامل تست‌های کامل با **Orchestra Testbench** و `Http::fake()` است.

```bash
composer install
composer test
```

برای اجرای تمیز:

```bash
rm -rf vendor
composer install && composer test
```

📁 مسیر تست‌ها: `tests/`
یک کلید عمومی تستی (`tests/stubs/pubkey.pem`) نیز برای رمزنگاری تستی در پکیج وجود دارد.

---

## 💡 نکات توسعه

* پشتیبانی از نسخه‌های **Laravel 10 تا 14**
* طراحی مطابق استانداردهای PSR-4 و PSR-12
* رمزنگاری AES-128 و RSA طبق مستند رسمی [IranKish V9](https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf)
* مناسب برای Unit Test و Integration Test
* دارای ساختار ServiceProvider و Facade استاندارد لاراول

---

## 🤝 مشارکت در توسعه

از Pull Request و پیشنهاد ویژگی‌های جدید استقبال می‌شود 🙌
برای گزارش باگ یا ارسال ایده جدید:

👉 [صفحه Issues در GitHub](https://github.com/noorani-mm/irankish-laravel-gateway/issues)

برای مشارکت:

```bash
git clone https://github.com/noorani-mm/irankish-laravel-gateway.git
composer install
vendor/bin/phpunit
```

لطفاً استاندارد PSR-12 را رعایت کرده و قبل از ارسال، تست‌ها را اجرا کنید ✅

---

## 📄 مجوز (License)

این پکیج تحت مجوز [MIT](LICENSE) منتشر شده است.
استفاده، ویرایش و توسعه برای عموم آزاد است.

© 2025 [محمدمهدی نورانی](https://github.com/noorani-mm)

[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)

---

## 👥 مشارکت‌کنندگان

<a href="https://github.com/noorani-mm/irankish-laravel-gateway/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=noorani-mm/irankish-laravel-gateway" />
</a>

---

> 🌍 [English README](README.md)

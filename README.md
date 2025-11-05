# 💳 IranKish Laravel Gateway

A clean, secure, and developer-friendly **Laravel** integration for **IranKish (IKC)** payment gateway.
This package handles **crypto envelope (AES + RSA)**, token request, redirect URL generation, and payment confirmation — so you can integrate payments in a few lines of code.

---

## 🚀 Installation

Install via Composer:

```bash
composer require noorani-mm/irankish-laravel-gateway
```

Laravel auto-discovers the service provider automatically.
If you use an older Laravel version or auto-discovery is disabled, register it manually in `config/app.php`:

```php
'providers' => [
    // ...
    IranKish\IranKishServiceProvider::class,
],
```

---

## ⚙️ Configuration

Add the following keys to your `.env`:

```dotenv
IRANKISH_TERMINAL_ID=12345678
IRANKISH_PASSWORD=abcd1234
IRANKISH_ACCEPTOR_ID=987654
IRANKISH_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----...-----END PUBLIC KEY-----"
IRANKISH_CALLBACK_URL=https://example.com/irankish/callback

# Optional overrides (when IKC updates their routes)
IRANKISH_MAKE_TOKEN_URL=https://ikc.shaparak.ir/api/v3/tokenization/make
IRANKISH_CONFIRM_URL=https://ikc.shaparak.ir/api/v3/confirmation/purchase
IRANKISH_PAYMENT_URL=https://ikc.shaparak.ir/TPayment/Payment/Index
```

Then publish the config file (optional but recommended):

```bash
php artisan vendor:publish --provider="IranKish\IranKishServiceProvider" --tag=config
```

### What each field means (Persian)

| کلید                    | توضیح                                                            |
| ----------------------- | ---------------------------------------------------------------- |
| `IRANKISH_TERMINAL_ID`  | شناسه‌ی ترمینال که توسط ایران‌کیش ارائه می‌شود.                  |
| `IRANKISH_PASSWORD`     | رمز عبور محرمانه مرتبط با ترمینال (برای پاکِت احراز هویت).       |
| `IRANKISH_ACCEPTOR_ID`  | کد پذیرنده ثبت شده نزد ایران‌کیش.                                |
| `IRANKISH_PUBLIC_KEY`   | کلید عمومی RSA (متن PEM یا مسیر فایل) برای رمزنگاری.             |
| `IRANKISH_CALLBACK_URL` | آدرس بازگشت پس از پرداخت (موفق/ناموفق).                          |
| `IRANKISH_*_URL`        | در صورت تغییر مسیرهای IKC، می‌توانید این URLها را override کنید. |

---

## 🧠 Response Model (Normalized)

This package returns **normalized arrays** so your controllers can make decisions easily.

### `requestPayment(...)` result

```json
{
  "url": "https://ikc.shaparak.ir/TPayment/Payment/Index?token=...",
  "ok": true,
  "message": "Approved",
  "status": "00"
}
```

On failure:

```json
{
  "url": null,
  "ok": false,
  "message": "Token missing in IKC response",
  "status": "NO_TOKEN"
}
```

### `confirm(...)` result

```json
{
  "ok": true,
  "message": "Approved",
  "status": "00",
  "data": { "responseCode": "00", "description": "Approved", "...": "..." }
}
```

---

## 💰 Start a Payment (Controller Example)

```php
use IranKish\IranKish;
use Illuminate\Http\Request;

class PaymentController
{
    public function start(IranKish $gateway)
    {
        // Amount in Rials
        $res = $gateway->requestPayment(150000, $billInfo = null, $paymentId = 'ORDER-12345');

        if (!$res['ok']) {
            return back()->withErrors([
                'gateway' => "Payment init failed [{$res['status']}] " . ($res['message'] ?? '')
            ]);
        }

        // You decide how to proceed (redirect or return JSON to FE/mobile)
        return redirect()->away($res['url']);
        // return response()->json($res);
    }

    public function callback(Request $request, IranKish $gateway)
    {
        if ($request->input('responseCode') !== '00') {
            return back()->withErrors(['gateway' => 'Payment canceled or failed.']);
        }

        $confirm = $gateway->confirm(
            $request->input('token'),
            $request->input('retrievalReferenceNumber'),
            $request->input('systemTraceAuditNumber')
        );

        if (!$confirm['ok']) {
            return back()->withErrors([
                'gateway' => "Confirm failed [{$confirm['status']}] " . ($confirm['message'] ?? '')
            ]);
        }

        // Success — fulfill the order, log data, etc.
        return view('payment.success', ['receipt' => $confirm['data']]);
    }
}
```

> Note: You can also call `app('irankish')->requestPayment(...)` if you prefer the container alias.

---

## 🧩 Methods (English)

### `requestPayment(int $amount, $billInfo = null, $paymentId = null): array`

Requests a payment token from IKC, builds a **ready-to-use** gateway URL, and returns a normalized response.

### `confirm(string $token, string $retrievalReferenceNumber, string $systemTraceAuditNumber): array`

Confirms a payment with IKC and returns a normalized structure.

### `redirectToGateway(string $token)`

Redirects directly to the gateway using the configured `IRANKISH_PAYMENT_URL`.

---

## 🔒 Security Notes

* Keep your **terminal credentials** and **public key** secret.
* Use HTTPS for your callback URL.
* Always validate and log callbacks from IKC.

---

## 🐞 Troubleshooting

* **`NETWORK_ERROR`** → network, SSL, or timeout problem.
* **`NO_TOKEN`** → IKC responded `00` but token field missing.
* Non-`00` responseCode → check `message`/`description`.

---

## ✅ Version Compatibility

* PHP `>= 8.1`
* Laravel `^10.0` or `^11.0`

---

## 📄 License

MIT © Mohammad Mahdi Noorani

---

## 🌟 Support

If this package helped you, please star the repository on GitHub. It helps others discover it faster. 🙌

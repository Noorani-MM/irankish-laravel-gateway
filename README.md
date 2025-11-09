# IranKish Laravel Gateway

A modern and fully-documented Laravel package for integrating **IranKish Payment Gateway** (درگاه پرداخت ایران‌کیش).  
Supports Laravel **v10 → v14** and based on **IranKish IPG Technical Guide v9**.

---

## 🚀 Features

- 🧩 Clean, PSR-4 & Laravel-native structure  
- 🔐 Full Digital Envelope (AES + RSA) implementation  
- 💳 Tokenization, Confirm, Reverse, Inquiry endpoints  
- 🧾 Split (Multiplex) payment support ready  
- ⚙️ Configurable RSA padding (PKCS1 / OAEP)  
- ✅ Tested with Laravel 10–14 and Orchestra Testbench  

---

## 📦 Installation

Install via Composer:

```bash
composer require noorani-mm/irankish-laravel-gateway
````

Laravel will auto-discover the service provider and facade.

If you're using **Lumen**, you can manually register it:

```php
$app->register(IranKish\IranKishServiceProvider::class);
```

---

## ⚙️ Configuration

Publish the configuration file:

```bash
php artisan vendor:publish --tag=config --provider="IranKish\IranKishServiceProvider"
```

Then set up your environment variables in `.env`:

```bash
IRANKISH_TERMINAL_ID=12345678
IRANKISH_ACCEPTOR_ID=87654321
IRANKISH_PASS_PHRASE=1234567890123456
IRANKISH_CALLBACK_URL=https://yourdomain.com/payment/irankish/callback
IRANKISH_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----YOUR_KEY-----END PUBLIC KEY-----"
IRANKISH_RSA_PADDING=OPENSSL_PKCS1_PADDING
IRANKISH_LOGGING=false
```

Optional (for sandbox mode or test servers):

```bash
IRANKISH_BASE_URL=https://ikc.shaparak.ir
```

---

## 🔧 Config Reference (`config/irankish.php`)

| Key            | Description                      |
| -------------- | -------------------------------- |
| `base_url`     | IranKish gateway base URL        |
| `terminal_id`  | Your terminal ID                 |
| `acceptor_id`  | Your acceptor ID                 |
| `pass_phrase`  | Your pass phrase (16 chars)      |
| `callback_url` | Redirect URL after payment       |
| `public_key`   | IranKish public key (PEM format) |
| `rsa_padding`  | RSA padding mode (PKCS1 or OAEP) |
| `logging`      | Enable/disable request logging   |

---

## 💡 Usage

### 1️⃣ Create Payment Token

```php
use IranKish\Facades\IranKish;

$response = IranKish::makeToken(50000, 'ORDER-123');

if ($response->isSuccessful()) {
    $token = $response->token();

    // Redirect user to IranKish payment page:
    return redirect()->away('https://ikc.shaparak.ir/iuiv3/IPG/Index?tokenIdentity=' . $token);
}

return back()->with('error', $response->message());
```

---

### 2️⃣ Handle Callback (Confirm Payment)

In your controller handling `/payment/irankish/callback`:

```php
use IranKish\Facades\IranKish;

public function callback(Request $request)
{
    $token = $request->input('tokenIdentity');
    $rrn   = $request->input('retrievalReferenceNumber');
    $stan  = $request->input('systemTraceAuditNumber');

    $confirm = IranKish::confirm($token, $rrn, $stan);

    if ($confirm->isSuccessful()) {
        // ✅ Payment confirmed
        return view('payment.success', ['response' => $confirm]);
    }

    // ❌ Payment failed or canceled
    return view('payment.failed', ['message' => $confirm->message()]);
}
```

---

### 3️⃣ Reverse Transaction (Optional)

```php
$reverse = IranKish::reverse($token, $rrn, $stan);
```

---

### 4️⃣ Inquiry Transaction

```php
$inquiry = IranKish::inquiry($rrn);

if ($inquiry->isSuccessful()) {
    // Payment is valid
}
```

---

## 🧱 Folder Structure

```
src/
 ├── IranKish.php
 ├── IranKishServiceProvider.php
 ├── Facades/
 │    └── IranKish.php
 └── Support/
      ├── EncryptionHelper.php
      └── IkcResponse.php
config/
 └── irankish.php
```

---

## 🧩 Helper Methods (from IkcResponse)

| Method           | Description                               |
| ---------------- | ----------------------------------------- |
| `isSuccessful()` | Returns `true` if `responseCode === "00"` |
| `message()`      | Text description from gateway             |
| `token()`        | Payment token (from makeToken)            |
| `rrn()`          | Retrieval Reference Number                |
| `stan()`         | System Trace Audit Number                 |
| `amount()`       | Transaction amount                        |
| `cardMasked()`   | Masked PAN                                |
| `cardHash()`     | SHA256 hash of PAN                        |

---

## 🧠 Example Blade Integration

```blade
<form method="POST" action="https://ikc.shaparak.ir/iuiv3/IPG/Index">
    <input type="hidden" name="tokenIdentity" value="{{ $token }}">
    <button type="submit" class="btn btn-primary">Pay with IranKish</button>
</form>
```

---

## 🧰 Testing (optional)

To run unit tests:

```bash
composer test
```

This package uses `orchestra/testbench` for Laravel integration testing.

---

## 🪪 License

This package is open-sourced software licensed under the [MIT license](LICENSE).

© 2025 [Mohammad Mahdi Noorani](https://github.com/noorani-mm)

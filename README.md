# IranKish Laravel Gateway

[![Latest Version on Packagist](https://img.shields.io/packagist/v/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![Total Downloads](https://img.shields.io/packagist/dt/noorani-mm/irankish-laravel-gateway.svg?style=flat-square)](https://packagist.org/packages/noorani-mm/irankish-laravel-gateway)
[![License: MIT](https://img.shields.io/badge/License-MIT-green.svg)](LICENSE)
[![Tests](https://github.com/noorani-mm/irankish-laravel-gateway/actions/workflows/tests.yml/badge.svg)](https://github.com/noorani-mm/irankish-laravel-gateway/actions)

> A modern and developer-friendly **Laravel integration for the IranKish Payment Gateway (IPG)**,  
> implemented fully according to the [official technical documentation V9](https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf).

---

## 🚀 Installation

```bash
composer require noorani-mm/irankish-laravel-gateway
````

---

## ⚙️ Configuration

After installation, publish the configuration file:

```bash
php artisan vendor:publish --provider="IranKish\IranKishServiceProvider" --tag="config"
```

This creates the file:
`config/irankish.php`

### `.env` variables

```bash
IRANKISH_TERMINAL_ID=12345678
IRANKISH_ACCEPTOR_ID=87654321
IRANKISH_PASSWORD=YourSecurePassword
IRANKISH_PUBLIC_KEY="-----BEGIN PUBLIC KEY-----\nMIIBIjANBgkq...\n-----END PUBLIC KEY-----"
IRANKISH_REVERT_URL=https://example.com/payment/callback
```

### `config/irankish.php`

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

## 💳 Example Usage

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
            // Step 1: Request token
            $response = IranKish::requestToken(150000, TransactionType::PURCHASE, [
                'paymentId' => 'ORDER-1234',
            ]);

            // Step 2: Redirect user to payment page (auto-submit form)
            return IranKish::redirect($response['token']);

            // OR (manual redirect data)
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
            // Step 3: Confirm transaction
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

## 📘 Methods Reference

| Method                                                                                     | Description                                                                         |
| ------------------------------------------------------------------------------------------ | ----------------------------------------------------------------------------------- |
| **`requestToken(int $amount, ?TransactionType $type = null, array $options = [])`**        | Create a payment token using the `/tokenization/make` endpoint.                     |
| **`requestSpecialToken(int $amount, ?TransactionType $type = null, array $options = [])`** | Same as `requestToken` but for special merchants (e.g., AsanShpWPP, IsacoWPP).      |
| **`redirect(string $token)`**                                                              | Automatically renders a POST form and redirects the payer to IranKish payment page. |
| **`redirectData(string $token)`**                                                          | Returns redirection data array for manual handling or SPA redirects.                |
| **`confirm(string $token, string $rrn, string $stan)`**                                    | Confirms the transaction after return from payment page.                            |
| **`reverse(string $token, string $rrn, string $stan)`**                                    | Reverses a previously successful transaction (before settlement).                   |
| **`inquiry(array $criteria)`**                                                             | Checks transaction status by RRN, tokenIdentity, or requestId.                      |

---

## 🧠 Transaction Types

| Enum             | Meaning               |
| ---------------- | --------------------- |
| `Purchase`       | Standard purchase     |
| `Bill`           | Bill payment          |
| `AsanShpWPP`     | AsanShp wallet        |
| `SpecialBill`    | Special bill payments |
| `AsanShpWPPDrug` | AsanShp Drug Wallet   |
| `IsacoWPP`       | ISACO Wallet Payment  |

---

## 🧪 Running Tests

The package includes a complete test suite using **Orchestra Testbench** and `Http::fake()`.

```bash
composer install
composer test
```

To re-run cleanly:

```bash
rm -rf vendor
composer install && composer test
```

📁 All tests are located in `/tests`.
A fake public key (`tests/stubs/pubkey.pem`) is included for encryption testing.

---

## 💡 Development Notes

* Compatible with **Laravel 10.x – 14.x**
* Built following PSR-4, PSR-12, and Laravel package conventions
* Fully supports `Http::fake()` for integration testing
* AES-128 + RSA encryption exactly as per [IranKish API Guide V9](https://www.irankish.com/App_Data_Public/IPG/IPG_TechnicalGuide.V9.pdf)

---

## 🤝 Contributing

Contributions are welcome!
If you encounter a bug or have a feature request, please open an issue at:

👉 [GitHub Issues](https://github.com/noorani-mm/irankish-laravel-gateway/issues)

To contribute code:

```bash
git clone https://github.com/noorani-mm/irankish-laravel-gateway.git
composer install
phpunit
```

Follow PSR-12 and submit a Pull Request 💪

---

## 🧾 License

This package is open-source software licensed under the [MIT License](LICENSE).

> MIT License © [Mohammad Mahdi Noorani](https://github.com/noorani-mm)


---

## 👥 Contributors

<a href="https://github.com/noorani-mm/irankish-laravel-gateway/graphs/contributors">
  <img src="https://contrib.rocks/image?repo=noorani-mm/irankish-laravel-gateway" />
</a>

---

> 🌐 Also available in Persian: [README-Fa.md](README-Fa.md)

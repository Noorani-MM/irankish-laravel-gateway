<?php

namespace IranKish;

use Illuminate\Support\ServiceProvider;

/**
 * Service Provider اصلی پکیج IranKish Gateway.
 * وظیفه‌اش ثبت تنظیمات، انتشار فایل config و
 * بایند کردن کلاس IranKish در Service Container لاراول است.
 */
class IranKishServiceProvider extends ServiceProvider
{
    /**
     * در زمان boot تنظیمات پکیج بارگذاری و قابلیت انتشار فایل config فعال می‌شود.
     */
    public function boot(): void
    {
        // مسیر فایل تنظیمات پیش‌فرض
        $this->publishes([
            __DIR__ . '/../config/irankish.php' => config_path('irankish.php'),
        ], 'config');
    }

    /**
     * ثبت سرویس‌ها و ادغام تنظیمات پکیج با تنظیمات پروژه.
     */
    public function register(): void
    {
        // ادغام تنظیمات پکیج با config اصلی پروژه
        $this->mergeConfigFrom(
            __DIR__ . '/../config/irankish.php',
            'irankish'
        );

        // بایند کردن کلاس IranKish در Service Container
        $this->app->singleton(IranKish::class, function () {
            return new IranKish();
        });

        // امکان resolve با نام کوتاه 'irankish'
        $this->app->alias(IranKish::class, 'irankish');
    }
}

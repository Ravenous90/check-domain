<?php

namespace App\Providers;

use App\Models\Domain;
use App\Models\DomainCheck;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if (str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }

        Route::bind('domain', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);
            $query = Domain::query()->whereKey($value);
            if (! $user->isSuperuser()) {
                $query->where('user_id', $user->id);
            }

            return $query->firstOrFail();
        });

        Route::bind('check', function (string $value) {
            $user = auth()->user();
            abort_unless($user, 403);
            $check = DomainCheck::query()->with('domain')->findOrFail($value);
            if (! $user->isSuperuser() && $check->domain->user_id !== $user->id) {
                abort(404);
            }

            return $check;
        });
    }
}

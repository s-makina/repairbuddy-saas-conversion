<?php

use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/email/verify/{id}/{hash}', function (Request $request, string $id, string $hash) {
    $user = User::query()->findOrFail($id);

    if (! hash_equals(sha1($user->getEmailForVerification()), (string) $hash)) {
        abort(403);
    }

    if (! $user->hasVerifiedEmail()) {
        $user->markEmailAsVerified();
        event(new Verified($user));
    }

    $frontendUrl = rtrim((string) env('FRONTEND_URL', 'http://localhost:3000'), '/');

    return redirect()->away($frontendUrl.'/verify-email?verified=1');
})->middleware(['signed'])->name('verification.verify');

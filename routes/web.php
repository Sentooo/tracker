<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Session;
use Illuminate\Http\Request;
use App\Http\Controllers\FormController;
use App\Http\Controllers\ResponseController;

// Admin login route
Route::get('/forms/admin-login', function () {
    return view('forms.authorize');
})->name('forms.login');

Route::post('/forms/admin-login', function (Request $request) {
    if ($request->access_code === env('FORM_ACCESS_CODE')) {
        session(['form_builder_unlocked' => true]);
        return redirect()->route('forms.create');
    }
    return back()->with('error', 'Incorrect access code.');
})->name('forms.authorize');

// Protect form builder routes
Route::middleware('form.builder')->group(function () {
    Route::get('/forms/create', [FormController::class, 'create'])->name('forms.create');
    Route::post('/forms', [FormController::class, 'store'])->name('forms.store');
    Route::get('/forms/{form}/edit', [FormController::class, 'edit'])->name('forms.edit');
    Route::put('/forms/{form}', [FormController::class, 'update'])->name('forms.update');
});

// Public form view and response
Route::get('/forms/{form}', [FormController::class, 'show'])->name('forms.show');
// Add this only if you have response handling


// Store form responses
Route::post('/forms/{form}/response', [ResponseController::class, 'store'])->name('forms.submit');
// Thank you page after submission
Route::get('/forms/{form}/thankyou', [ResponseController::class, 'thankyou'])->name('forms.thankyou');


// Main entry route
Route::get('/forms', function () {
    $form = \App\Models\Form::with('questions')->first();

    if (!$form || $form->questions->isEmpty()) {
        return redirect()->route('forms.create');
    }

    return redirect()->route('forms.show', $form->id);
})->name('forms.index');

// Optional homepage redirect to /forms
Route::get('/', function () {
    return redirect()->route('forms.index');
});

// Logout
Route::post('/forms/logout', function () {
    Session::forget('form_builder_unlocked');
    $form = \App\Models\Form::first();
    return $form ? redirect()->route('forms.show', $form->id) : redirect()->route('forms.index');
})->name('forms.logout');

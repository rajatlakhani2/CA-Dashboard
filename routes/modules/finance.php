<?php

use Illuminate\Support\Facades\Route;

Route::middleware(['role:partner,manager', 'module:invoices'])->group(function () {
    Route::get('invoices/create', [\App\Http\Controllers\InvoiceController::class, 'create'])->name('invoices.create');
    Route::post('invoices', [\App\Http\Controllers\InvoiceController::class, 'store'])->name('invoices.store');
    Route::get('invoices/{invoice}/edit', [\App\Http\Controllers\InvoiceController::class, 'edit'])->name('invoices.edit');
    Route::put('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update'])->name('invoices.update');
    Route::patch('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'update']);
    Route::delete('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'destroy'])->name('invoices.destroy');
    Route::post('/invoices/{invoice}/send-email', [\App\Http\Controllers\InvoiceController::class, 'sendEmail'])->name('invoices.send-email');
    Route::post('/invoices/{invoice}/whatsapp', [\App\Http\Controllers\InvoiceController::class, 'sendWhatsApp'])->name('invoices.whatsapp');
});

Route::middleware(['role:partner,manager', 'module:billing'])->group(function () {
    Route::get('/billing', [\App\Http\Controllers\BillingController::class, 'index'])->name('billing.index');
    Route::post('/billing/process', [\App\Http\Controllers\BillingController::class, 'process'])->name('billing.process');
    Route::post('/billing/draft-invoice', [\App\Http\Controllers\BillingController::class, 'createDraft'])->name('billing.create-draft');
    Route::post('/billing/apply-rules', [\App\Http\Controllers\BillingController::class, 'applyRules'])->name('billing.apply-rules');
    Route::get('/billing-rules', [\App\Http\Controllers\BillingRuleController::class, 'index'])->name('billing-rules.index');
    Route::post('/billing-rules', [\App\Http\Controllers\BillingRuleController::class, 'store'])->name('billing-rules.store');
    Route::delete('/billing-rules/{billingRule}', [\App\Http\Controllers\BillingRuleController::class, 'destroy'])->name('billing-rules.destroy');

});

Route::middleware(['role:partner,manager', 'module:payments'])->group(function () {
    Route::get('/collections', [\App\Http\Controllers\CollectionsController::class, 'index'])->name('collections.index');
    Route::post('/clients/{client}/collection-follow-up', [\App\Http\Controllers\CollectionsController::class, 'storeFollowUp'])->name('collections.follow-up');
    Route::get('/payments/{payment}/receipt', [\App\Http\Controllers\PaymentController::class, 'downloadReceipt'])->name('payments.receipt');
    Route::resource('payments', \App\Http\Controllers\PaymentController::class)->except(['edit', 'update']);
    Route::get('/ledger/{client}', [\App\Http\Controllers\LedgerController::class, 'show'])->name('ledger.show');
    Route::get('/ledger/{client}/soa', [\App\Http\Controllers\LedgerController::class, 'downloadSoa'])->name('ledger.soa');
});

Route::middleware(['role:partner,manager', 'module:expenses'])->group(function () {
    Route::resource('expenses', \App\Http\Controllers\ExpenseController::class)->except(['show']);
});

Route::middleware(['role:partner,manager', 'module:subscriptions'])->group(function () {
    Route::post('/subscriptions/{subscription}/toggle', [\App\Http\Controllers\SubscriptionController::class, 'toggle'])->name('subscriptions.toggle');
    Route::resource('subscriptions', \App\Http\Controllers\SubscriptionController::class)
        ->only(['index', 'create', 'store', 'destroy']);
});

Route::middleware('role:partner,manager,associate')->group(function () {
    Route::get('invoices', [\App\Http\Controllers\InvoiceController::class, 'index'])->name('invoices.index');
    Route::get('invoices/{invoice}', [\App\Http\Controllers\InvoiceController::class, 'show'])
        ->whereNumber('invoice')
        ->name('invoices.show');
    Route::get('invoices/{invoice}/download-pdf', [\App\Http\Controllers\InvoiceController::class, 'downloadPdf'])
        ->whereNumber('invoice')
        ->name('invoices.download-pdf');
});

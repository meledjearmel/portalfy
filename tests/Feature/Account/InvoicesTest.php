<?php

use App\Models\Customer;
use App\Models\Invoice;
use App\Models\Order;
use Illuminate\Support\Facades\Storage;

test('guests are redirected to the login screen', function () {
    $response = $this->get(route('account.invoices'));

    $response->assertRedirect(route('login'));
});

test('a customer only sees their own invoices', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();

    Invoice::factory()->create([
        'order_id' => Order::factory()->paid()->create(['customer_id' => $customer->id]),
        'number' => 1001,
    ]);
    Invoice::factory()->create([
        'order_id' => Order::factory()->paid()->create(['customer_id' => $otherCustomer->id]),
        'number' => 2002,
    ]);

    $response = $this->actingAs($customer, 'customer')->get(route('account.invoices'));

    $response->assertOk();
    $response->assertSee('Facture #1001');
    $response->assertDontSee('Facture #2002');
});

test('a customer can download their own invoice as a generated pdf', function () {
    Storage::fake('local');

    $customer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $customer->id]);
    $invoice = Invoice::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')
        ->get(route('account.invoices.download', $invoice));

    $response->assertOk();
    $response->assertHeader('content-type', 'application/pdf');
    Storage::disk('local')->assertExists("invoices/{$invoice->number}.pdf");
});

test('a customer can not download another customer\'s invoice', function () {
    $customer = Customer::factory()->create();
    $otherCustomer = Customer::factory()->create();
    $order = Order::factory()->paid()->create(['customer_id' => $otherCustomer->id]);
    $invoice = Invoice::factory()->create(['order_id' => $order->id]);

    $response = $this->actingAs($customer, 'customer')
        ->get(route('account.invoices.download', $invoice));

    $response->assertForbidden();
});

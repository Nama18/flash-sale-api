<?php

namespace App\Filament\Resources\Orders\Pages;

use App\Filament\Resources\Orders\OrderResource;
use App\Services\OrderService;
use Filament\Resources\Pages\CreateRecord;
use Filament\Notifications\Notification;
use Illuminate\Validation\ValidationException;

class CreateOrder extends CreateRecord
{
    protected static string $resource = OrderResource::class;

    public function create(bool $another = false): void
{
    $data  = $this->form->getRawState();

    // items punya key UUID, kita ambil values-nya aja
    $items = array_values(array_map(fn($item) => [
        'product_id' => (int) $item['product_id'],
        'quantity'   => (int) $item['quantity'],
    ], $data['items'] ?? []));

    if (empty($items)) {
        Notification::make()
            ->title('Tambahkan minimal 1 item!')
            ->danger()
            ->send();
        return;
    }

    try {
        $order = app(OrderService::class)->createOrder(
            $data['customer_name'],
            $items
        );

        $this->record = $order;

        Notification::make()
            ->title('Order berhasil dibuat!')
            ->success()
            ->send();

        $this->redirect(OrderResource::getUrl('index'));

    } catch (ValidationException $e) {
        Notification::make()
            ->title(collect($e->errors())->flatten()->first())
            ->danger()
            ->send();

    } catch (\Throwable $e) {
        Notification::make()
            ->title('Error: ' . $e->getMessage())
            ->danger()
            ->send();
    }
}
}

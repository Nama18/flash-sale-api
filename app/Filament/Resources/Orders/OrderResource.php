<?php

namespace App\Filament\Resources\Orders;

use App\Filament\Resources\Orders\Pages\CreateOrder;
use App\Filament\Resources\Orders\Pages\EditOrder;
use App\Filament\Resources\Orders\Pages\ListOrders;
use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Repeater;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;
    protected static ?string $navigationLabel = 'Orders';
    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Order Information')
            ->schema([
                TextInput::make('customer_name')
                    ->required()
                    ->maxLength(255),

                TextInput::make('total_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->disabled(),

                Select::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'failed'    => 'Failed',
                    ])
                    ->required(),
            ])->columns(3),

        Section::make('Order Items')
            ->schema([
                Repeater::make('items')
                ->relationship()
                ->schema([
                    Select::make('product_id')
                        ->relationship('product', 'name')
                        ->required()
                        ->columnSpan(2)
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if ($state) {
                                $product = \App\Models\Product::find($state);
                                if ($product) {
                                    $set('unit_price', $product->effective_price);
                                }
                            }
                        }),

                    TextInput::make('quantity')
                        ->numeric()
                        ->required()
                        ->minValue(1),

                    TextInput::make('unit_price')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->minValue(0),
                ])->columns(4),
            ]),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('customer_name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('total_price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('items_count')
                    ->counts('items')
                    ->label('Items')
                    ->badge(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state) => match($state) {
                        'pending'   => 'warning',
                        'confirmed' => 'success',
                        'failed'    => 'danger',
                        default     => 'gray',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending'   => 'Pending',
                        'confirmed' => 'Confirmed',
                        'failed'    => 'Failed',
                    ]),
            ])
            ->actions([
                \Filament\Actions\EditAction::make(),

                \Filament\Actions\DeleteAction::make()
                    ->using(fn(\App\Models\Order $record) =>
                        app(\App\Services\OrderService::class)->deleteOrder($record)
                    )
                    ->successNotificationTitle('Order berhasil dihapus & stok dikembalikan!'),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index'  => ListOrders::route('/'),
            'create' => CreateOrder::route('/create'),
            'edit'   => EditOrder::route('/{record}/edit'),
        ];
    }
}

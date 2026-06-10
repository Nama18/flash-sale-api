<?php

namespace App\Filament\Resources\Products;

use App\Filament\Resources\Products\Pages\ManageProducts;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\ProductService;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;
    protected static ?string $navigationLabel = 'Products';
    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
{
    return $schema->components([
        Section::make('Product Information')
            ->schema([
                TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),

                TextInput::make('price')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0),

                TextInput::make('flash_sale_price')
                    ->numeric()
                    ->prefix('Rp')
                    ->minValue(0)
                    ->nullable()
                    ->helperText('Kosongkan jika bukan flash sale'),

                TextInput::make('stock')
                    ->required()
                    ->numeric()
                    ->minValue(0),
            ])->columns(3),
    ]);
}

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->sortable(),

                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('price')
                    ->money('IDR')
                    ->sortable(),

                Tables\Columns\TextColumn::make('flash_sale_price')
                    ->money('IDR')
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('stock')
                    ->sortable()
                    ->badge()
                    ->color(fn ($state) => match(true) {
                        $state === 0 => 'danger',
                        $state <= 10 => 'warning',
                        default      => 'success',
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('flash_sale')
                    ->label('Flash Sale Only')
                    ->query(fn ($query) => $query->whereNotNull('flash_sale_price')),

                Tables\Filters\Filter::make('out_of_stock')
                    ->label('Out of Stock')
                    ->query(fn ($query) => $query->where('stock', 0)),
            ])
            ->actions([
                \Filament\Actions\EditAction::make()
                    ->using(fn(Product $record, array $data) =>
                        app(ProductService::class)->updateProduct($record, $data)
                    ),

                \Filament\Actions\DeleteAction::make()
                    ->using(fn(Product $record) => app(ProductService::class)->deleteProduct($record))
                    ->successNotificationTitle('Produk berhasil dihapus!')
                    ->failureNotificationTitle('Gagal menghapus produk!'),
                ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProducts::route('/'),
        ];
    }
}

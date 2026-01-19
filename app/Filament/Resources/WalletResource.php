<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WalletResource\Pages;
use App\Models\Wallet;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Validation\Rules\Unique; // Penting untuk validasi custom

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';
    protected static ?string $navigationGroup = 'Keuangan';
    protected static ?string $navigationLabel = 'Dompet';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'name')
                    ->required()
                    ->label('Pemilik Dompet')
                    ->searchable()
                    ->live(), // Agar validasi nama merespon jika user diganti
                
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->maxLength(255)
                    ->label('Nama Dompet')
                    ->placeholder('Contoh: BCA, Dompet Saku, Gopay')
                    // Validasi Unik per User
                    ->unique(
                        ignoreRecord: true, 
                        modifyRuleUsing: function (Unique $rule, callable $get) {
                            // Cek unik hanya untuk user_id yang sedang dipilih
                            return $rule->where('user_id', $get('user_id'));
                        }
                    )
                    ->validationMessages([
                        'unique' => 'Nama dompet ini sudah dipakai oleh user tersebut. Coba nama lain (misal: BCA-2).',
                    ]),

                Forms\Components\Select::make('type')
                    ->options([
                        'cash' => 'Tunai (Cash)',
                        'bank' => 'Bank / E-Wallet',
                    ])
                    ->required()
                    ->label('Tipe'),

                Forms\Components\TextInput::make('balance')
                    ->required()
                    ->numeric()
                    ->prefix('Rp')
                    ->default(0)
                    ->label('Saldo Awal'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Pemilik')
                    ->sortable()
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('name')
                    ->label('Nama Dompet')
                    ->searchable()
                    ->weight('bold')
                    ->sortable(),

                TextColumn::make('type')
                    ->badge()
                    ->colors([
                        'success' => 'cash',
                        'primary' => 'bank',
                    ])
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'cash' => 'Tunai',
                        'bank' => 'Bank',
                        default => $state,
                    })
                    ->label('Tipe'),

                TextColumn::make('balance')
                    ->money('IDR', locale: 'id')
                    ->label('Saldo')
                    ->sortable()
                    ->alignment('end'),

                TextColumn::make('updated_at')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->label('Terakhir Update'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->options([
                        'cash' => 'Tunai',
                        'bank' => 'Bank',
                    ])
                    ->label('Filter Tipe'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWallets::route('/'),
            'create' => Pages\CreateWallet::route('/create'),
            'edit' => Pages\EditWallet::route('/{record}/edit'),
        ];
    }
}
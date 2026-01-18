<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                // Select Type: Pemasukan / Pengeluaran
                Forms\Components\Select::make('type')
                    ->options([
                        'income' => 'Pemasukan',
                        'expense' => 'Pengeluaran',
                    ])
                    ->required()
                    ->native(false),
                
                Forms\Components\TextInput::make('amount')
                    ->numeric()
                    ->prefix('Rp')
                    ->required(),

                Forms\Components\DatePicker::make('transaction_date')
                    ->default(now())
                    ->required(),

                // Trik: Hanya tampilkan Wallet milik user yang sedang login!
                Forms\Components\Select::make('wallet_id')
                    ->relationship('wallet', 'name', function ($query) {
                        return $query->where('user_id', auth()->id());
                    })
                    ->searchable()
                    ->preload()
                    ->label('Source Wallet')
                    ->required(),

                Forms\Components\TextInput::make('category')
                    ->placeholder('Makan, Bensin, Gaji...'),
                
                Forms\Components\Textarea::make('description')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('transaction_date')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('description')
                    ->searchable()
                    ->limit(30),

                Tables\Columns\TextColumn::make('type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'income' => 'success',
                        'expense' => 'danger',
                    }),

                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('wallet.name')
                    ->label('Wallet'),
            ])
            ->defaultSort('transaction_date', 'desc');
    }

    public static function getEloquentQuery(): Builder
    {
        // Filter global: Admin hanya melihat data miliknya sendiri
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    // --- BAGIAN INI YANG TADI HILANG ---
    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}
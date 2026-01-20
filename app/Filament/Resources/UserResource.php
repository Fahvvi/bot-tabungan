<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'Settings';

    // 👇 TAMBAHKAN INI: Mematikan Fitur Create (Tombol "New User" Hilang)
    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required(),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required(),
                Forms\Components\TextInput::make('telegram_chat_id')
                    ->numeric()
                    ->label('Telegram ID'),
                Forms\Components\Toggle::make('is_verified')
                    ->label('Sudah Verifikasi?')
                    ->inline(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->searchable(),
                
                // Status Verifikasi
                IconColumn::make('is_verified')
                    ->label('Verified')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-circle')
                    ->falseIcon('heroicon-o-x-circle')
                    ->trueColor('success')
                    ->falseColor('danger'),

                TextColumn::make('telegram_chat_id')
                    ->label('Tele ID')
                    ->copyable(),
                
                TextColumn::make('default_wallet.name')
                    ->label('Dompet Utama')
                    ->placeholder('-'),
                    
                TextColumn::make('created_at')->dateTime()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('is_verified')
                    ->query(fn ($query) => $query->where('is_verified', true))
                    ->label('Hanya User Verified'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // Tombol Delete Opsional (Jika ingin proteksi penuh, hapus baris di bawah ini)
                // Tables\Actions\DeleteAction::make(), 
            ])
            ->bulkActions([]); // Kosongkan bulk actions agar tidak bisa delete massal
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            // 'create' => Pages\CreateUser::route('/create'), // Hapus atau Komen baris ini
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}
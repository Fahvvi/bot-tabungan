<?php

namespace App\Filament\Resources;

use App\Filament\Resources\GoalResource\Pages;
use App\Models\Goal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class GoalResource extends Resource
{
    protected static ?string $model = Goal::class;
    protected static ?string $navigationIcon = 'heroicon-o-trophy';
    protected static ?string $navigationGroup = 'Keuangan';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('owner_id')
                    ->relationship('owner', 'name')
                    ->required()
                    ->label('Pemilik'),
                Forms\Components\TextInput::make('name')->required()->label('Nama Goal'),
                Forms\Components\TextInput::make('target_amount')->numeric()->prefix('Rp')->required(),
                Forms\Components\TextInput::make('code')
                    ->label('Kode Invite')
                    ->disabled() // Kode otomatis, jangan diedit manual
                    ->dehydrated(false),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->weight('bold')->searchable(),
                
                // Kode Invite (Bisa dicopy)
                TextColumn::make('code')
                    ->label('Kode Invite')
                    ->badge()
                    ->color('info')
                    ->copyable()
                    ->copyMessage('Kode disalin!'),

                TextColumn::make('current_amount')->money('IDR')->label('Terkumpul'),
                TextColumn::make('target_amount')->money('IDR')->label('Target'),
                
                // Progress Bar (Persentase)
                TextColumn::make('progress')
                    ->label('%')
                    ->state(function (Goal $record) {
                        return $record->target_amount > 0 
                            ? round(($record->current_amount / $record->target_amount) * 100) . '%' 
                            : '0%';
                    })
                    ->badge()
                    ->color(fn ($state) => intval($state) >= 100 ? 'success' : 'warning'),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListGoals::route('/'),
            'create' => Pages\CreateGoal::route('/create'),
            'edit' => Pages\EditGoal::route('/{record}/edit'),
        ];
    }
}
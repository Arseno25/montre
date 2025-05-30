<?php

namespace App\Filament\Resources;

use App\Enums\BudgetPeriod;
use App\Filament\Resources\BudgetResource\Pages;
use App\Filament\Resources\BudgetResource\RelationManagers;
use App\Models\Budget;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\View\Components\Modal;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BudgetResource extends Resource
{
    protected static ?string $model = Budget::class;

    protected static ?string $navigationGroup = 'Money Tracking';

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\select::make('user_id')
                    ->label('User')
                    ->searchable()
                    ->preload()
                    ->default(auth()->user()->name)
                    ->relationship('user', 'name'),
                Forms\Components\Select::make('category_id')
                    ->label('Category')
                    ->required()
                    ->relationship('category', 'name')
                    ->preload()
                    ->searchable(),
                Forms\Components\Select::make('period')
                    ->label('Period')
                    ->required()
                    ->options([
                        BudgetPeriod::MONTHLY->value => BudgetPeriod::MONTHLY->label(),
                        BudgetPeriod::YEARLY->value => BudgetPeriod::YEARLY->label()
                    ]),
                Forms\Components\DateTimePicker::make('start_date')
                    ->label('Start Date')
                    ->default(now())
                    ->required(),
                Forms\Components\DateTimePicker::make('end_date')
                    ->label('End Date')
                    ->default(fn() => now()->addMonth())
                    ->required(),
                Forms\Components\TextInput::make('amount')
                    ->label('Amount')
                    ->required()
                    ->numeric()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('period')
                    ->searchable()
                    ->getStateUsing(function (Budget $record) {
                        return $record->period == BudgetPeriod::MONTHLY->value ? BudgetPeriod::MONTHLY->label() : BudgetPeriod::YEARLY->label();
                    }),
                Tables\Columns\TextColumn::make('amount')
                    ->searchable(),
                Tables\Columns\TextColumn::make('start_date')
                    ->date('d M Y')
                    ->searchable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->date('d M Y')
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListBudgets::route('/'),
            'create' => Pages\CreateBudget::route('/create'),
            'edit' => Pages\EditBudget::route('/{record}/edit'),
        ];
    }
}

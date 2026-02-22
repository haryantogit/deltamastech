<?php

namespace App\Filament\Resources;

use App\Filament\Resources\RoleResource\Pages;
use App\Models\Role;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\CheckboxList;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Actions\EditAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Database\Eloquent\Builder;

class RoleResource extends Resource
{
    protected static ?string $model = Role::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';
    protected static bool $shouldRegisterNavigation = false;
    protected static ?int $navigationSort = 2;
    protected static ?string $modelLabel = 'Peran';
    protected static ?string $pluralModelLabel = 'Peran';
    protected static ?string $navigationLabel = 'Peran';
    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-shield-check';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Detail Peran')
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama Peran')
                            ->required()
                            ->maxLength(255)
                            ->unique(ignoreRecord: true),
                        Hidden::make('guard_name')
                            ->default('web'),
                    ]),

                Section::make('Hak Akses')
                    ->description('Pilih hak akses untuk peran ini.')
                    ->schema(function () {
                        $features = ['product', 'sales', 'purchase', 'inventory', 'contact', 'user', 'role', 'report'];
                        $schema = [];

                        foreach ($features as $feature) {
                            $schema[] = Section::make(ucfirst($feature))
                                ->schema([
                                    CheckboxList::make('permissions_' . $feature)
                                        ->label('')
                                        ->options(function () use ($feature) {
                                            return \App\Models\Permission::where('name', 'LIKE', "%{$feature}%")
                                                ->pluck('name', 'id')
                                                ->map(fn($name) => ucwords(str_replace(['_', $feature], [' ', ''], $name)));
                                        })
                                        ->afterStateHydrated(function ($component, $record) use ($feature) {
                                            if (!$record)
                                                return;
                                            $ids = $record->permissions()
                                                ->where('name', 'LIKE', "%{$feature}%")
                                                ->pluck('permissions.id')
                                                ->toArray();
                                            $component->state($ids);
                                        })
                                        ->dehydrated(false) // Handled manually in page classes
                                        ->columns(2)
                                        ->bulkToggleable()
                                        ->gridDirection('row'),
                                ])
                                ->collapsible()
                                ->collapsed(false)
                                ->compact();
                        }

                        $schema[] = Section::make('Lainnya')
                            ->schema([
                                CheckboxList::make('permissions_other')
                                    ->label('')
                                    ->options(function () use ($features) {
                                        return \App\Models\Permission::where(function ($query) use ($features) {
                                            foreach ($features as $feature) {
                                                $query->where('name', 'NOT LIKE', "%{$feature}%");
                                            }
                                        })
                                            ->pluck('name', 'id')
                                            ->map(fn($name) => ucwords(str_replace('_', ' ', $name)));
                                    })
                                    ->afterStateHydrated(function ($component, $record) use ($features) {
                                        if (!$record)
                                            return;
                                        $ids = $record->permissions()
                                            ->where(function ($query) use ($features) {
                                                foreach ($features as $feature) {
                                                    $query->where('name', 'NOT LIKE', "%{$feature}%");
                                                }
                                            })
                                            ->pluck('permissions.id')
                                            ->toArray();
                                        $component->state($ids);
                                    })
                                    ->dehydrated(false)
                                    ->columns(2)
                                    ->bulkToggleable()
                                    ->gridDirection('row'),
                            ])
                            ->collapsible()
                            ->collapsed(false)
                            ->compact();

                        return $schema;
                    }),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Peran')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('users_count')
                    ->counts('users')
                    ->label('Jumlah Pengguna'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    DeleteAction::make(),
                ])
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRoles::route('/'),
            'create' => Pages\CreateRole::route('/create'),
            'edit' => Pages\EditRole::route('/{record}/edit'),
        ];
    }
}

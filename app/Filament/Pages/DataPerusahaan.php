<?php

namespace App\Filament\Pages;

use App\Models\Company;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use App\Filament\Pages\Pengaturan;

class DataPerusahaan extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected string $view = 'filament.pages.data-perusahaan';

    public ?array $data = [];

    public function mount(): void
    {
        $company = Company::first();

        if ($company) {
            $this->form->fill($company->toArray());
        }
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Section::make('Logo Perusahaan')
                    ->extraAttributes(['class' => 'mb-8'])
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->image()
                            ->directory('company-logos')
                            ->visibility('public'),
                    ]),
                Section::make('Data Perusahaan')
                    ->extraAttributes(['class' => 'mt-4'])
                    ->schema([
                        TextInput::make('name')
                            ->label('Nama')
                            ->required(),
                        TextInput::make('email')
                            ->label('Alamat email')
                            ->email(),
                        TextInput::make('phone')
                            ->label('Nomor telepon'),
                        Textarea::make('address')
                            ->label('Alamat')
                            ->rows(3),
                        TextInput::make('npwp')
                            ->label('Nomor NPWP Perusahaan'),
                        Actions::make([
                            Action::make('save')
                                ->label('Simpan')
                                ->color('primary')
                                ->action('save'),
                        ])->columnSpanFull()->alignEnd(),
                    ]),
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label('Kembali')
                ->color('gray')
                ->url(fn() => Pengaturan::getUrl()),
        ];
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $company = Company::first();

        if ($company) {
            $company->update($data);
        } else {
            Company::create($data);
        }

        Notification::make()
            ->title('Berhasil disimpan')
            ->success()
            ->send();
    }

    public function getMaxContentWidth(): string
    {
        return 'full';
    }

    protected static string|\UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static bool $shouldRegisterNavigation = false;

    protected static ?int $navigationSort = 3;

    protected static string|null $navigationLabel = 'Data Perusahaan';

    public function getHeading(): string
    {
        return 'Perusahaan';
    }
    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Perusahaan',
        ];
    }
}

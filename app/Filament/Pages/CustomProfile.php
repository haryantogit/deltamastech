<?php

namespace App\Filament\Pages;

use App\Models\User;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Schemas\Schema;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Filament\Pages\Pengaturan;

class CustomProfile extends Page implements HasForms
{
    use InteractsWithForms;

    protected string $view = 'filament.pages.auth.custom-profile';

    protected static ?string $slug = 'profile';

    protected static bool $shouldRegisterNavigation = false;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill(auth()->user()->toArray());
    }

    public function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(2)
                    ->schema([
                        Section::make('Profil')
                            ->description('Atur data diri Anda.')
                            ->schema([
                                FileUpload::make('avatar_url')
                                    ->label('Avatar')
                                    ->image()
                                    ->avatar()
                                    ->imageEditor()
                                    ->directory('avatars')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                                TextInput::make('email')
                                    ->label('Email')
                                    ->email()
                                    ->required()
                                    ->unique(ignoreRecord: true),
                                TextInput::make('name')
                                    ->label('Nama lengkap')
                                    ->required(),
                                TextInput::make('phone')
                                    ->label('Nomor telepon'),
                            ])->columnSpan(1),

                        Section::make('Ganti Password')
                            ->description('Perbarui password Anda saat ini.')
                            ->schema([
                                TextInput::make('current_password')
                                    ->label('Password lama')
                                    ->password()
                                    ->revealable()
                                    ->requiredWith('new_password')
                                    ->currentPassword(),
                                TextInput::make('new_password')
                                    ->label('Password baru')
                                    ->password()
                                    ->revealable()
                                    ->rule(Password::default()),
                                TextInput::make('new_password_confirmation')
                                    ->label('Ulangi password baru')
                                    ->password()
                                    ->revealable()
                                    ->same('new_password')
                                    ->requiredWith('new_password'),
                                Actions::make([
                                    Action::make('save')
                                        ->label('Simpan')
                                        ->color('primary')
                                        ->action('save'),
                                ])->alignEnd(),
                            ])->columnSpan(1),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $user = auth()->user();

        $user->update([
            'email' => $data['email'],
            'name' => $data['name'],
            'phone' => $data['phone'],
            'avatar_url' => $data['avatar_url'],
        ]);

        if (!empty($data['new_password'])) {
            $user->update([
                'password' => Hash::make($data['new_password']),
            ]);
        }

        Notification::make()
            ->title('Profil berhasil diperbarui')
            ->success()
            ->send()
            ->sendToDatabase($user);

        $this->form->fill($user->fresh()->toArray());
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

    protected static ?string $title = 'Profilku';
    protected static ?string $navigationLabel = 'Profilku';

    public function getHeading(): string
    {
        return 'Profilku';
    }

    public function getBreadcrumbs(): array
    {
        return [
            url('/admin') => 'Beranda',
            \App\Filament\Pages\Pengaturan::getUrl() => 'Pengaturan',
            'Profilku',
        ];
    }
}

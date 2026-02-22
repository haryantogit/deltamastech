<x-filament-widgets::widget>
    <style>
        .contact-info-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }

        @media (min-width: 768px) {
            .contact-info-grid {
                grid-template-columns: 1fr 1fr;
            }
        }

        .account-link:hover {
            text-decoration: underline !important;
            color: #1d4ed8 !important;
        }
    </style>
    <div class="contact-info-grid">

        {{-- LEFT CARD: Contact Identity --}}
        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 1rem;">
                {{-- Contact Name & Photo --}}
                <div
                    style="padding-bottom: 1rem; border-bottom: 1px solid #e5e7eb; display: flex; flex-wrap: wrap; gap: 1rem; align-items: center;">
                    @if($record->photo)
                        <div
                            style="width: 64px; height: 64px; border-radius: 9999px; overflow: hidden; flex-shrink: 0; border: 2px solid #e5e7eb;">
                            <img src="{{ asset('storage/' . $record->photo) }}" alt="Photo"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    @else
                        <div
                            style="width: 64px; height: 64px; border-radius: 9999px; overflow: hidden; flex-shrink: 0; border: 2px solid #e5e7eb;">
                            <img src="{{ asset('images/default-avatar.png') }}" alt="Default Avatar"
                                style="width: 100%; height: 100%; object-fit: cover;">
                        </div>
                    @endif
                    <div>
                        <h1 style="font-size: 1.5rem; font-weight: 700; color: #111827; margin-bottom: 0.25rem;">
                            {{ $record->name }}
                        </h1>
                        <div style="font-size: 0.875rem; color: #6b7280;">
                            {{ $record->company ?? 'Perorangan' }}
                        </div>
                    </div>
                </div>

                {{-- Type Badge --}}
                <div class="flex flex-col sm:flex-row sm:items-start md:items-center gap-1 sm:gap-3">
                    <span style="font-size: 0.875rem; font-weight: 500; color: #374151; min-width: 7.5rem;">Tipe
                        Kontak</span>
                    <div class="flex items-center gap-2">
                        <span class="hidden sm:inline" style="font-size: 0.875rem; color: #9ca3af;">:</span>
                        @php
                            $typeConfig = [
                                'customer' => ['bg' => '#d1fae5', 'color' => '#065f46', 'label' => 'Pelanggan'],
                                'vendor' => ['bg' => '#fef3c7', 'color' => '#92400e', 'label' => 'Vendor'],
                                'employee' => ['bg' => '#f3f4f6', 'color' => '#374151', 'label' => 'Karyawan'],
                                'others' => ['bg' => '#dbeafe', 'color' => '#1e40af', 'label' => 'Lainnya'],
                            ];
                            $type = $typeConfig[$record->type] ?? $typeConfig['others'];
                        @endphp
                        <span
                            style="padding: 0.25rem 0.75rem; font-size: 0.75rem; font-weight: 600; border-radius: 9999px; background: {{ $type['bg'] }}; color: {{ $type['color'] }};">
                            {{ $type['label'] }}
                        </span>
                    </div>
                </div>

                {{-- Contact Details Section --}}
                <div style="background: #f9fafb; border-radius: 0.5rem; padding: 1rem;">
                    <h3
                        style="font-size: 0.75rem; font-weight: 700; color: #374151; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                        Informasi Kontak
                    </h3>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-3">
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                            <span
                                style="font-size: 0.875rem; font-weight: 500; color: #6b7280; min-width: 5rem;">NIK</span>
                            <span class="hidden sm:inline" style="font-size: 0.875rem; color: #9ca3af;">:</span>
                            <span
                                style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $record->nik ?? '-' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                            <span
                                style="font-size: 0.875rem; font-weight: 500; color: #6b7280; min-width: 5rem;">Telepon</span>
                            <span class="hidden sm:inline" style="font-size: 0.875rem; color: #9ca3af;">:</span>
                            <span
                                style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $record->phone ?? '-' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-center gap-1 sm:gap-3">
                            <span
                                style="font-size: 0.875rem; font-weight: 500; color: #6b7280; min-width: 5rem;">Email</span>
                            <span class="hidden sm:inline" style="font-size: 0.875rem; color: #9ca3af;">:</span>
                            <span
                                style="font-size: 0.875rem; font-weight: 600; color: #111827;">{{ $record->email ?? '-' }}</span>
                        </div>
                        <div class="flex flex-col sm:flex-row sm:items-start gap-1 sm:gap-3">
                            <span
                                style="font-size: 0.875rem; font-weight: 500; color: #6b7280; min-width: 5rem;">Alamat</span>
                            <span class="hidden sm:inline" style="font-size: 0.875rem; color: #9ca3af;">:</span>
                            <span
                                style="font-size: 0.875rem; font-weight: 600; color: #111827; flex: 1;">{{ $record->address ?? '-' }}</span>
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

        <x-filament::section>
            <div style="display: flex; flex-direction: column; gap: 1.5rem;">

                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    {{-- Akun Piutang Section --}}
                    <div
                        style="background: linear-gradient(135deg, #dbeafe 0%, #eff6ff 100%); border-radius: 0.5rem; padding: 1rem; border: 1px solid #bfdbfe;">
                        <h3
                            style="font-size: 0.75rem; font-weight: 700; color: #1d4ed8; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                            Akun Piutang
                        </h3>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; flex: 1;">Akun untuk
                                mencatat piutang dari kontak ini</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                            @if($record->receivableAccount)
                                <a href="{{ \App\Filament\Resources\AccountResource::getUrl('view', ['record' => $record->receivableAccount->id]) }}"
                                    class="account-link"
                                    style="font-size: 1rem; font-weight: 700; color: #111827; text-decoration: none; transition: color 0.2s;">
                                    {{ $record->receivableAccount->name ?? '-' }}
                                    <span
                                        style="color: #6b7280; font-weight: 400; font-size: 0.875rem;">({{ $record->receivableAccount->code ?? '-' }})</span>
                                </a>
                            @else
                                <span style="font-size: 1rem; font-weight: 700; color: #111827;">-</span>
                            @endif
                        </div>
                    </div>

                    {{-- Akun Hutang Section --}}
                    <div
                        style="background: linear-gradient(135deg, #fee2e2 0%, #fef2f2 100%); border-radius: 0.5rem; padding: 1rem; border: 1px solid #fecaca;">
                        <h3
                            style="font-size: 0.75rem; font-weight: 700; color: #b91c1c; text-transform: uppercase; letter-spacing: 0.05em; margin-bottom: 0.75rem;">
                            Akun Hutang
                        </h3>
                        <div style="display: flex; align-items: center; gap: 0.75rem;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #6b7280; flex: 1;">Akun untuk
                                mencatat hutang kepada kontak ini</span>
                        </div>
                        <div style="display: flex; align-items: center; gap: 0.75rem; margin-top: 0.5rem;">
                            @if($record->payableAccount)
                                <a href="{{ \App\Filament\Resources\AccountResource::getUrl('view', ['record' => $record->payableAccount->id]) }}"
                                    class="account-link"
                                    style="font-size: 1rem; font-weight: 700; color: #111827; text-decoration: none; transition: color 0.2s;">
                                    {{ $record->payableAccount->name ?? '-' }}
                                    <span
                                        style="color: #6b7280; font-weight: 400; font-size: 0.875rem;">({{ $record->payableAccount->code ?? '-' }})</span>
                                </a>
                            @else
                                <span style="font-size: 1rem; font-weight: 700; color: #111827;">-</span>
                            @endif
                        </div>
                    </div>

                    {{-- Tax Status --}}
                    <div style="background: #f9fafb; border-radius: 0.5rem; padding: 1rem;">
                        <div style="display: flex; align-items: center; justify-content: space-between;">
                            <span style="font-size: 0.875rem; font-weight: 500; color: #374151;">Status Pajak</span>
                            @if($record->tax_id)
                                <span
                                    style="padding: 0.25rem 0.75rem; background-color: #d1fae5; color: #065f46; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                                    Kena Pajak
                                </span>
                            @else
                                <span
                                    style="padding: 0.25rem 0.75rem; background-color: #f3f4f6; color: #374151; border-radius: 9999px; font-size: 0.75rem; font-weight: 600;">
                                    Tidak Kena Pajak
                                </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </x-filament::section>

    </div>
</x-filament-widgets::widget>
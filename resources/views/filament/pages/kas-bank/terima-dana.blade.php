<x-filament-panels::page>
    <style>
        .kb-container {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            width: 100%;
        }

        .kb-breadcrumbs {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.8125rem;
            color: #6b7280;
            margin-bottom: -0.5rem;
        }

        .kb-breadcrumbs span.separator {
            color: #d1d5db;
        }

        .kb-breadcrumbs .active {
            color: #111827;
            font-weight: 700;
        }

        .kb-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .kb-title-wrapper {
            display: flex;
            align-items: baseline;
            gap: 0.75rem;
        }

        .kb-title {
            font-size: 1.875rem;
            font-weight: 700;
            color: #111827;
            margin: 0;
        }

        .kb-subtitle {
            font-size: 1.125rem;
            color: #9ca3af;
            font-weight: 500;
        }

        .kb-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 0.875rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 600;
            cursor: pointer;
            border: 1px solid transparent;
            text-decoration: none;
            line-height: 1.25rem;
        }

        .kb-btn-white {
            background: white;
            border-color: #e5e7eb;
            color: #374151;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .kb-btn-orange {
            background: #f97316;
            color: white;
        }

        .kb-form-container {
            background: white;
            padding: 1.5rem;
            border-radius: 0.75rem;
            border: 1px solid #f3f4f6;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.05);
        }

        .kb-form-footer {
            margin-top: 1.5rem;
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
            align-items: center;
        }

        .kb-input-label {
            font-size: 0.75rem;
            font-weight: 700;
            color: #ef4444;
            /* Required asterisk color mostly */
        }
    </style>

    <div class="kb-container">
        {{-- Breadcrumbs Removed --}}

        {{-- Header --}}
        <div class="kb-header">
            <div class="kb-title-wrapper">
                <h1 class="kb-title">{{ $account->name }}</h1>
                <span class="kb-subtitle">{{ $account->code }}</span>
            </div>

            <div class="kb-actions">
                <button class="kb-btn kb-btn-white">
                    <x-filament::icon icon="heroicon-o-book-open" style="width: 16px; height: 16px; color: #9ca3af;" />
                    Panduan
                    <x-filament::icon icon="heroicon-m-chevron-down"
                        style="width: 14px; height: 14px; color: #9ca3af;" />
                </button>
                <a href="/admin/kas-bank/detail/{{ $account->id }}" class="kb-btn kb-btn-orange">
                    <x-filament::icon icon="heroicon-m-arrow-left" style="width: 16px; height: 16px;" />
                    Kembali
                </a>
            </div>
        </div>

        {{-- Title Removed --}}

        <div class="kb-form-container">
            {{ $this->form }}

            <div class="kb-form-footer">
                <x-filament::button wire:click="create" color="primary">
                    <x-filament::icon icon="heroicon-m-check" class="w-4 h-4 mr-1" />
                    Simpan
                </x-filament::button>
            </div>
        </div>
    </div>
</x-filament-panels::page>
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

        .dark .kb-title {
            color: #f9fafb;
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

        .dark .kb-btn-white {
            background: rgba(255, 255, 255, 0.05);
            border-color: rgba(255, 255, 255, 0.1);
            color: #e5e7eb;
        }

        .dark .kb-btn-white:hover {
            background: rgba(255, 255, 255, 0.1);
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

        .dark .kb-form-container {
            background: rgba(255, 255, 255, 0.03);
            /* Filament dark form background */
            border-color: rgba(255, 255, 255, 0.1);
            box-shadow: none;
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
            </div>
        </div>

        <form wire:submit.prevent="create" class="flex flex-col">
            {{ $this->form }}

            <div style="margin-top: 2rem;">
                <div class="fi-form-actions flex flex-wrap items-center gap-3">
                    <x-filament::button type="submit" color="primary">
                        Buat
                    </x-filament::button>

                    <x-filament::button type="button" wire:click="createAnother" color="gray">
                        Buat & buat lainnya
                    </x-filament::button>

                    <x-filament::button tag="a" href="/admin/kas-bank/detail/{{ $account->id }}" color="gray">
                        Batal
                    </x-filament::button>
                </div>
            </div>
        </form>
    </div>
</x-filament-panels::page>
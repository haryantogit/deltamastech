<x-filament-panels::page>
    <style>
        .fi-header {
            display: none !important;
        }

        /* Hide default Filament header */

        .biaya-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding-top: 0.5rem;
        }

        .biaya-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #111827;
        }


        .biaya-toolbar {
            background: #f9fafb;
            padding: 0.75rem 1rem;
            border-radius: 0.75rem 0.75rem 0 0;
            border: 1px solid #e5e7eb;
            border-bottom: none;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .biaya-table-container {
            background: white;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 0.75rem 0.75rem;
            overflow: hidden;
        }

        .biaya-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .biaya-table th {
            background: #fdfdfd;
            padding: 0.75rem 1rem;
            font-size: 0.7rem;
            font-weight: 600;
            color: #6b7280;
            text-transform: uppercase;
            border-bottom: 1px solid #e5e7eb;
        }

        .biaya-table td {
            padding: 0.875rem 1rem;
            font-size: 0.8125rem;
            color: #374151;
            border-bottom: 1px solid #f3f4f6;
        }

        .status-badge {
            padding: 0.125rem 0.625rem;
            border-radius: 9999px;
            font-size: 0.7rem;
            font-weight: 600;
        }

        .status-lunas {
            background: #dcfce7;
            color: #166534;
        }

        .status-pending {
            background: #fef3c7;
            color: #92400e;
        }

        .btn-action {
            padding: 0.5rem 1rem;
            border-radius: 0.5rem;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .btn-white {
            background: white;
            color: #374151;
            border: 1px solid #d1d5db;
        }

        .btn-white:hover {
            background: #f9fafb;
        }

        .btn-primary {
            background: #2563eb;
            color: white;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #1d4ed8;
        }

        /* Fix Search Input */
        .search-container {
            position: relative;
            width: 18rem;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            font-size: 0.875rem;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            transition: border-color 0.2s;
        }

        .search-input:focus {
            outline: none;
            border-color: #2563eb;
            ring: 2px #bfdbfe;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #9ca3af;
        }
    </style>

    <div class="biaya-header">
        <div class="biaya-title">Biaya</div>
        @can('biaya.list.add')
            <x-filament::button icon="heroicon-m-plus" tag="a"
                href="{{ \App\Filament\Resources\ExpenseResource::getUrl('create') }}" size="sm">
                Tambah Biaya
            </x-filament::button>
        @endcan
    </div>

    {{-- Widgets --}}
    <x-filament-widgets::widgets :columns="$this->getHeaderWidgetsColumns()" :widgets="$this->getHeaderWidgets()" />

    <div class="biaya-toolbar">
        <div class="flex items-center gap-1 bg-gray-200 p-1 rounded-lg">
            <button wire:click="$set('filterStatus', 'semua')"
                class="px-4 py-1 text-xs font-semibold rounded-md transition-all {{ $filterStatus === 'semua' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">SEMUA</button>
            <button wire:click="$set('filterStatus', 'belum_dibayar')"
                class="px-4 py-1 text-xs font-semibold rounded-md transition-all {{ $filterStatus === 'belum_dibayar' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">BELUM
                DIBAYAR</button>
            <button wire:click="$set('filterStatus', 'lunas')"
                class="px-4 py-1 text-xs font-semibold rounded-md transition-all {{ $filterStatus === 'lunas' ? 'bg-white shadow-sm text-blue-600' : 'text-gray-500 hover:text-gray-700' }}">LUNAS</button>
        </div>
        <div class="search-container">
            <svg class="search-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
            </svg>
            <input type="text" wire:model.live.debounce.300ms="search" placeholder="Cari..." class="search-input">
        </div>
    </div>

    <div class="biaya-table-container">
        <table class="biaya-table">
            <thead>
                <tr>
                    <th>Tanggal</th>
                    <th>Nomor</th>
                    <th>Referensi</th>
                    <th>Penerima</th>
                    <th>Status</th>
                    <th style="text-align: right;">Total</th>
                </tr>
            </thead>
            <tbody>
                @forelse($this->getExpenses() as $expense)
                    <tr>
                        <td>{{ $expense->transaction_date->format('d/m/Y') }}</td>
                        <td>
                            <a href="{{ \App\Filament\Resources\ExpenseResource::getUrl('edit', ['record' => $expense]) }}"
                                class="text-blue-600 font-medium hover:underline">
                                {{ $expense->reference_number }}
                            </a>
                        </td>
                        <td>{{ $expense->memo ?: '-' }}</td>
                        <td>
                            @if($expense->contact)
                                <a href="{{ \App\Filament\Resources\ContactResource::getUrl('view', ['record' => $expense->contact]) }}"
                                    class="text-blue-600 hover:underline">
                                    {{ $expense->contact->name }}
                                </a>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            <span class="status-badge {{ $expense->is_pay_later ? 'status-pending' : 'status-lunas' }}">
                                {{ $expense->is_pay_later ? 'Belum Dibayar' : 'Lunas' }}
                            </span>
                        </td>
                        <td style="text-align: right;" class="font-bold">Rp
                            {{ number_format($expense->total_amount, 0, ',', '.') }}
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="text-center py-12 text-gray-400 font-medium">Tidak ada data biaya ditemukan.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
        @if($this->getExpenses()->hasPages())
            <div class="px-4 py-3 border-t border-gray-100">
                {{ $this->getExpenses()->links() }}
            </div>
        @endif
    </div>
</x-filament-panels::page>
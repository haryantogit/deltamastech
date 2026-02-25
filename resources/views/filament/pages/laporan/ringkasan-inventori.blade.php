<x-filament-panels::page>
    <style>
        .report-content {
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
        }

        .report-toolbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
            flex-wrap: wrap;
        }

        .search-container {
            position: relative;
            flex: 1;
            min-width: 250px;
            max-width: 400px;
        }

        .search-input {
            width: 100%;
            padding: 0.5rem 1rem 0.5rem 2.5rem;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            background: white;
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .search-input {
            background: #1e293b;
            border-color: #334155;
            color: #f1f5f9;
        }

        .search-input:focus {
            border-color: #3b82f6;
        }

        .search-icon {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            color: #94a3b8;
        }

        .date-display {
            display: flex;
            align-items: center;
            padding: 0.5rem 1rem;
            background: white;
            border-radius: 8px;
            border: 1px solid #e2e8f0;
            font-size: 0.875rem;
            color: #64748b;
            gap: 0.5rem;
        }

        .dark .date-display {
            background: #1e293b;
            border-color: #334155;
            color: #94a3b8;
        }

        .report-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .report-section {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table th {
            padding: 1rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #94a3b8;
            text-transform: uppercase;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: left;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
        }

        .report-table td {
            padding: 1rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .product-name {
            color: #3b82f6;
            font-weight: 500;
            text-decoration: none;
        }

        .product-name:hover {
            text-decoration: underline;
        }

        .number-col {
            text-align: right !important;
        }

        .report-table th.number-col,
        .report-table td.number-col {
            text-align: right;
            padding-right: 2rem;
        }

        .total-row {
            background: #f8fafc;
            font-weight: 700;
        }

        .dark .total-row {
            background: rgba(255, 255, 255, 0.02);
        }

        .sub-total-label {
            color: #64748b;
            font-weight: 500;
        }

        @media print {

            .report-toolbar,
            .fi-header-actions {
                display: none !important;
            }

            .report-section {
                border: none !important;
                box-shadow: none !important;
            }
        }
    </style>

    @php
        $data = $this->getViewData();
        $products = $data['products'];
        $totalQty = $data['totalQty'];
        $totalValue = $data['totalValue'];
    @endphp

    <div class="report-content">
        <div class="report-toolbar">
            <div class="search-container">
                <x-filament::icon icon="heroicon-m-magnifying-glass" class="w-5 h-5 search-icon" />
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari produk..."
                    class="search-input">
            </div>
            <div class="date-display">
                <x-filament::icon icon="heroicon-m-calendar" class="w-4 h-4" />
                {{ \Carbon\Carbon::parse($date)->format('d/m/Y') }}
            </div>
        </div>

        <div class="report-section">
            <table class="report-table">
                <thead>
                    <tr>
                        <th>Nama Produk</th>
                        <th>Kode</th>
                        <th class="number-col">Kuantitas</th>
                        <th class="number-col">Harga Rata-rata</th>
                        <th class="number-col">Nilai Produk</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($products as $product)
                        <tr>
                            <td>
                                <a href="{{ \App\Filament\Resources\ProductResource::getUrl('view', ['record' => $product->id]) }}"
                                    target="_blank" class="product-name">
                                    {{ $product->name }}
                                </a>
                            </td>
                            <td style="color: #64748b;">{{ $product->sku }}</td>
                            <td class="number-col">{{ number_format($product->stock, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($product->hpp, 0, ',', '.') }}</td>
                            <td class="number-col">{{ number_format($product->value, 0, ',', '.') }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" style="text-align: center; color: #94a3b8; padding: 3rem;">
                                Tidak ada data produk.
                            </td>
                        </tr>
                    @endforelse

                    @if($products->count() > 0)
                        <tr class="total-row">
                            <td colspan="2" class="sub-total-label">Subtotal</td>
                            <td class="number-col">{{ number_format($totalQty, 0, ',', '.') }}</td>
                            <td class="number-col"></td>
                            <td class="number-col">{{ number_format($totalValue, 0, ',', '.') }}</td>
                        </tr>
                        <tr class="total-row" style="border-top: 2px solid #f1f5f9;">
                            <td colspan="2">Total</td>
                            <td class="number-col">{{ number_format($totalQty, 0, ',', '.') }}</td>
                            <td class="number-col"></td>
                            <td class="number-col">{{ number_format($totalValue, 0, ',', '.') }}</td>
                        </tr>
                    @endif
                </tbody>
            </table>
        </div>

        @if($products->count() > 0)
            <div style="text-align: right; color: #94a3b8; font-size: 0.75rem; margin-top: 0.5rem;">
                Total {{ $products->count() }} data
            </div>
        @endif
    </div>
</x-filament-panels::page>
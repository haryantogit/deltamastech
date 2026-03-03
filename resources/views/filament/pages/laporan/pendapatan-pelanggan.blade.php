@php
    $viewData = $this->getViewData();
    $contacts = $viewData['results'];
    $paginator = $viewData['paginator'];
    $totalCount = $viewData['totalCount'];
    $globalPendapatan = $viewData['globalPendapatan'];
    $expandedContacts = $this->expandedContacts;

    $fmt = function ($num) {
        if ($num == 0)
            return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        .delivery-report-container {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.02);
        }

        .dark .delivery-report-container {
            background: #111827;
            border-color: #374151;
        }

        .report-table {
            width: 100%;
            border-collapse: collapse;
            min-width: 800px;
        }

        .report-table th {
            padding: 0.875rem 1.25rem;
            font-size: 0.75rem;
            font-weight: 700;
            color: #64748b;
            text-transform: capitalize;
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
        }

        .dark .report-table th {
            background: #1f2937;
            border-bottom-color: #374151;
            color: #94a3b8;
        }

        .report-table td {
            padding: 0.75rem 1.25rem;
            font-size: 0.8125rem;
            border-bottom: 1px solid #f1f5f9;
            color: #1e293b;
        }

        .dark .report-table td {
            border-bottom-color: #374151;
            color: #e2e8f0;
        }

        .nested-table {
            width: 100%;
            border-collapse: collapse;
            background: #fcfdfe;
        }

        .dark .nested-table {
            background: rgba(255, 255, 255, 0.01) !important;
        }

        .nested-table-header {
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .nested-table-header {
            border-bottom-color: #1f2937;
        }

        .nested-table-row {
            border-bottom: 1px solid #f8fafc;
        }

        .dark .nested-table-row {
            border-bottom-color: rgba(255, 255, 255, 0.05);
        }

        .toggle-btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.5rem;
            height: 1.5rem;
            border: 1px solid rgba(59, 130, 246, 0.2);
            border-radius: 6px;
            margin-right: 0.5rem;
            font-size: 1rem;
            color: #3b82f6;
            background: rgba(59, 130, 246, 0.1);
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 800;
            line-height: 0;
            user-select: none;
        }

        .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }

        .dark .toggle-btn {
            background: rgba(59, 130, 246, 0.2);
            border-color: rgba(59, 130, 246, 0.3);
            color: #60a5fa;
        }

        .dark .toggle-btn:hover {
            background: #3b82f6;
            color: white;
            border-color: #3b82f6;
        }

        .invoice-link {
            color: #3b82f6;
            font-weight: 600;
            text-decoration: none;
        }

        .filter-search-row {
            display: flex;
            justify-content: flex-end;
            align-items: center;
            padding: 1rem 1.25rem;
            border-bottom: 1px solid #f1f5f9;
        }

        .dark .filter-search-row {
            border-color: #374151;
        }

        .custom-search-container {
            position: relative;
            width: 280px;
        }

        .search-icon-abs {
            position: absolute;
            left: 0.75rem;
            top: 50%;
            transform: translateY(-50%);
            width: 1rem;
            height: 1rem;
            color: #94a3b8;
        }

        .custom-search-input {
            width: 100%;
            padding: 0.5rem 0.75rem 0.5rem 2.25rem;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 0.8125rem;
            background: white;
            color: #1e293b;
            outline: none;
            transition: border-color 0.2s;
        }

        .dark .custom-search-input {
            background: #1f2937;
            border-color: #374151;
            color: #f1f5f9;
        }

        .custom-search-input:focus {
            border-color: #3b82f6;
        }

        @media print {

            .fi-header-actions,
            .filter-search-row {
                display: none !important;
            }
        }
    </style>

    <div class="delivery-report-container">
        {{-- Search Row --}}
        <div class="filter-search-row">
            <div class="custom-search-container">
                <svg class="search-icon-abs" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                </svg>
                <input type="text" wire:model.live.debounce.500ms="search" placeholder="Cari"
                    class="custom-search-input">
            </div>
        </div>

        {{-- Table --}}
        <div style="overflow-x: auto;">
            <table class="report-table">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th style="text-align: left;">Pelanggan</th>
                        <th style="text-align: left;">Perusahaan</th>
                        <th style="text-align: left;">Telepon</th>
                        <th style="text-align: right;">Total Transaksi</th>
                        <th style="text-align: right;">Total Qty</th>
                        <th style="text-align: right;">Pendapatan</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($contacts as $contact)
                        @php $isExpanded = in_array($contact['id'], $expandedContacts); @endphp
                        <tr wire:key="contact-{{ $contact['id'] }}" wire:click="toggleContact({{ $contact['id'] }})"
                            style="cursor: pointer;">
                            <td>
                                <span class="toggle-btn">{{ $isExpanded ? '−' : '+' }}</span>
                            </td>
                            <td style="font-weight: 600;">{{ $contact['name'] }}</td>
                            <td style="color: #64748b;">{{ $contact['company'] }}</td>
                            <td style="color: #64748b;">{{ $contact['phone'] }}</td>
                            <td style="text-align: right; font-weight: 500;">{{ $fmt($contact['total_transaksi']) }}</td>
                            <td style="text-align: right;">{{ $fmt($contact['total_qty']) }}</td>
                            <td style="text-align: right; font-weight: 700; color: #3b82f6;">
                                {{ $fmt($contact['pendapatan']) }}</td>
                        </tr>

                        @if($isExpanded)
                            <tr wire:key="details-{{ $contact['id'] }}">
                                <td colspan="7" style="padding: 0;">
                                    <table class="nested-table">
                                        <thead>
                                            <tr class="nested-table-header">
                                                <th
                                                    style="padding: 0.5rem 1.25rem 0.5rem 4rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Nomor</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Tanggal</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Produk</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kode/SKU</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: left; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kategori</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Kuantitas</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Harga</th>
                                                <th
                                                    style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.70rem; color: #94a3b8; font-weight: 600;">
                                                    Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($contact['items'] as $item)
                                                <tr class="nested-table-row">
                                                    <td style="padding: 0.5rem 1.25rem 0.5rem 4rem; font-size: 0.75rem;">
                                                        <span class="invoice-link">{{ $item['invoice_number'] }}</span>
                                                    </td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['date'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; font-weight: 500;">
                                                        {{ $item['product_name'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['product_sku'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; font-size: 0.75rem; color: #64748b;">
                                                        {{ $item['category_name'] }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['qty']) }}</td>
                                                    <td style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem;">
                                                        {{ $fmt($item['price']) }}</td>
                                                    <td
                                                        style="padding: 0.5rem 1.25rem; text-align: right; font-size: 0.75rem; font-weight: 600;">
                                                        {{ $fmt($item['total']) }}</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td colspan="8"
                                                        style="text-align: center; padding: 1rem; color: #94a3b8; font-size: 0.75rem;">
                                                        Tidak ada detail barang.
                                                    </td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                </td>
                            </tr>
                        @endif
                    @empty
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 3rem; color: #94a3b8;">
                                Tidak ada data pelanggan untuk rentang ini.
                            </td>
                        </tr>
                    @endforelse

                    {{-- Grand Total row --}}
                    <tr style="border-top: 2px solid rgba(128,128,128,0.2);">
                        <td style="padding:16px 14px;" colspan="6"></td>
                        <td style="padding:16px 14px; text-align: right; font-size: 13px; font-weight: 700;"
                            class="text-gray-900 dark:text-gray-100">
                            {{ $fmt($globalPendapatan) }}
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>

    @if ($paginator->hasPages() || count([5, 10, 20, 50, 100, 'all']) > 1)
        <div style="margin-top: 2rem; margin-bottom: 1rem;">
            <x-filament::pagination :paginator="$paginator" :page-options="[5, 10, 20, 50, 100, 'all']"
                current-page-option-property="perPage" />
        </div>
    @endif

    <x-filament-actions::modals />
</x-filament-panels::page>

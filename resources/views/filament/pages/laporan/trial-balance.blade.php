@php
    $data = $this->getViewData();
    $rows = $data['rows'];
    $grandTotals = $data['grandTotals'];
    $stats = $data['stats'];
    $expandedCategories = $this->expandedCategories;

    $fmt = function ($num) {
        if ($num == 0) return '0';
        return number_format($num, 0, ',', '.');
    };
@endphp

<x-filament-panels::page>
    <style>
        /* Premium Stats Cards Sync */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1.25rem;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 120px;
        }
        .dark .stat-card { background: #111827; border-color: #374151; }
        .stat-label { font-size: 0.75rem; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.025em; margin-bottom: 0.5rem; }
        .dark .stat-label { color: #94a3b8; }
        .stat-value { font-size: 1.5rem; font-weight: 800; color: #1e293b; line-height: 1; }
        .dark .stat-value { color: white; }
        .stat-footer { margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem; font-size: 0.75rem; font-weight: 600; }
        .trend-badge { display: flex; align-items: center; gap: 0.25rem; }
        .trend-success { color: #10b981; }
        .trend-danger { color: #ef4444; }

        /* Trial Balance Table Styling */
        .trial-balance-section {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow-x: auto;
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }
        .dark .trial-balance-section { background: #111827; border-color: #374151; }

        .tb-table { width: 100%; border-collapse: collapse; min-width: 900px; }
        .tb-table th { 
            padding: 1rem; 
            font-size: 0.75rem; 
            font-weight: 700; 
            color: #94a3b8; 
            text-transform: uppercase; 
            background: #f8fafc;
            border-bottom: 2px solid #f1f5f9;
            text-align: center;
        }
        .dark .tb-table th { background: #1f2937; border-bottom-color: #374151; }
        
        .tb-table td { padding: 0.875rem 1rem; font-size: 0.8125rem; border-bottom: 1px solid #f1f5f9; }
        .dark .tb-table td { border-bottom-color: #374151; }

        .col-account { text-align: left; width: 30%; }
        .col-amount { text-align: right; font-weight: 600; width: 11.66%; font-variant-numeric: tabular-nums; }
        .amount-zero { color: #cbd5e1; font-weight: 400; }
        .dark .amount-zero { color: #4b5563; }

        /* Row Types */
        .row-category { 
            background: rgba(59, 130, 246, 0.03); 
            cursor: pointer; 
            transition: background 0.2s;
        }
        .row-category:hover { background: rgba(59, 130, 246, 0.08); }
        .row-category td { font-weight: 700; color: #1e293b; font-size: 0.8125rem; position: relative; }
        .dark .row-category td { color: #f1f5f9; }
        
        .expand-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 1.25rem;
            height: 1.25rem;
            margin-right: 0.75rem;
            color: #3b82f6;
            font-weight: 800;
            font-size: 1rem;
        }

        .row-parent { font-weight: 700; color: #1e293b; }
        .dark .row-parent { color: #f1f5f9; }
        .row-child { color: #64748b; }
        .dark .row-child { color: #94a3b8; }
        .child-indent { padding-left: 2.5rem !important; }

        /* Grand Total Row */
        .row-grand-total { background: #1e293b; border-top: 3px solid #3b82f6; }
        .row-grand-total td { color: white; font-weight: 800; font-size: 0.875rem; }
    </style>

    <!-- Statistics Grid -->
    <div class="stats-grid">
        @foreach($stats as $key => $stat)
            <div class="stat-card">
                <div>
                    <div class="stat-label">{{ $stat['label'] }}</div>
                    <div class="stat-value">Rp {{ $fmt($stat['value']) }}</div>
                </div>
                <div class="stat-footer">
                    <div class="trend-badge {{ $stat['trend']['color'] == 'success' ? 'trend-success' : 'trend-danger' }}">
                        <x-dynamic-component :component="$stat['trend']['icon']" style="width: 0.875rem; height: 0.875rem;" />
                        {{ abs($stat['trend']['pct']) }}%
                    </div>
                    <span style="color: #94a3b8">vs bulan lalu</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Trial Balance Table Container -->
    <div class="trial-balance-section">
        <table class="tb-table">
            <thead>
                <tr>
                    <th rowspan="2" style="text-align: left;">Daftar Akun</th>
                    <th colspan="2">Saldo Awal</th>
                    <th colspan="2">Pergerakan</th>
                    <th colspan="2">Saldo Akhir</th>
                </tr>
                <tr>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Debit</th>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Kredit</th>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Debit</th>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Kredit</th>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Debit</th>
                    <th style="border-top: 1px solid rgba(0,0,0,0.05);">Kredit</th>
                </tr>
            </thead>
            <tbody>
                @php $currentCategory = null; @endphp
                @foreach ($rows as $row)
                    @php 
                        $isExpanded = in_array($row['category'], $expandedCategories);
                    @endphp

                    @if ($currentCategory !== $row['category'])
                        <tr class="row-category" wire:click="toggleCategory('{{ $row['category'] }}')">
                            <td colspan="7">
                                <span class="expand-icon">{{ $isExpanded ? '−' : '+' }}</span>
                                {{ $row['category'] }}
                            </td>
                        </tr>
                        @php $currentCategory = $row['category']; @endphp
                    @endif

                    @if ($isExpanded)
                        <tr class="{{ $row['is_parent'] ? 'row-parent' : 'row-child' }}">
                            <td class="col-account {{ !$row['is_parent'] ? 'child-indent' : '' }}" style="padding-left: 2rem;">
                                <span style="color: #94a3b8; font-weight: 500;">{{ $row['code'] }}</span>
                                <span style="margin-left: 0.5rem;">{{ $row['name'] }}</span>
                            </td>
                            
                            {{-- Opening --}}
                            <td class="col-amount {{ $row['opening_debit'] == 0 ? 'amount-zero' : '' }}">
                                {{ $row['opening_debit'] > 0 ? $fmt($row['opening_debit']) : '0' }}
                            </td>
                            <td class="col-amount {{ $row['opening_credit'] == 0 ? 'amount-zero' : '' }}">
                                {{ $row['opening_credit'] > 0 ? $fmt($row['opening_credit']) : '0' }}
                            </td>

                            {{-- Movement --}}
                            <td class="col-amount {{ $row['movement_debit'] == 0 ? 'amount-zero' : '' }}">
                                {{ $row['movement_debit'] > 0 ? $fmt($row['movement_debit']) : '0' }}
                            </td>
                            <td class="col-amount {{ $row['movement_credit'] == 0 ? 'amount-zero' : '' }}">
                                {{ $row['movement_credit'] > 0 ? $fmt($row['movement_credit']) : '0' }}
                            </td>

                            {{-- Ending --}}
                            <td class="col-amount {{ $row['ending_debit'] == 0 ? 'amount-zero' : '' }}" style="color: #3b82f6;">
                                {{ $row['ending_debit'] > 0 ? $fmt($row['ending_debit']) : '0' }}
                            </td>
                            <td class="col-amount {{ $row['ending_credit'] == 0 ? 'amount-zero' : '' }}" style="color: #3b82f6;">
                                {{ $row['ending_credit'] > 0 ? $fmt($row['ending_credit']) : '0' }}
                            </td>
                        </tr>
                    @endif
                @endforeach
            </tbody>
            <tfoot>
                <tr class="row-grand-total">
                    <td style="text-align: left;">TOTAL SEMUA AKUN</td>
                    <td class="col-amount">{{ $fmt($grandTotals['opening_debit']) }}</td>
                    <td class="col-amount">{{ $fmt($grandTotals['opening_credit']) }}</td>
                    <td class="col-amount">{{ $fmt($grandTotals['movement_debit']) }}</td>
                    <td class="col-amount">{{ $fmt($grandTotals['movement_credit']) }}</td>
                    <td class="col-amount">{{ $fmt($grandTotals['ending_debit']) }}</td>
                    <td class="col-amount">{{ $fmt($grandTotals['ending_credit']) }}</td>
                </tr>
            </tfoot>
        </table>
    </div>
</x-filament-panels::page>
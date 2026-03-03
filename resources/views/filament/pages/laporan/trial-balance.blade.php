<x-filament-panels::page>
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

    <style>
        /* Premium Stats Cards Sync */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 1.25rem;
            margin-bottom: 0.75rem;
        }
        .stat-card {
            background: white;
            border-radius: 12px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            min-height: 110px;
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

        /* Tab Buttons Styling */
        .custom-tab-button {
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            padding: 0.4rem 1.25rem;
            border-radius: 6px;
            font-size: 0.8125rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
        }
        .custom-tab-button.active {
            background-color: #3b82f6;
            color: white;
            box-shadow: 0 4px 6px -1px rgba(59, 130, 246, 0.2), 0 2px 4px -1px rgba(59, 130, 246, 0.1);
        }
        .custom-tab-button.inactive {
            background-color: transparent;
            color: #64748b;
        }
        .custom-tab-button.inactive:hover {
            color: #3b82f6;
            background-color: rgba(59, 130, 246, 0.05);
        }
        .dark .custom-tab-button.inactive { color: #94a3b8; }
        .dark .custom-tab-button.inactive:hover { background-color: rgba(255, 255, 255, 0.05); }

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
        
        .expand-btn {
            width: 1.25rem; height: 1.25rem; 
            background: rgba(59, 130, 246, 0.1); 
            border: 1px solid rgba(59, 130, 246, 0.2); 
            border-radius: 6px; 
            display: grid; place-items: center;
            line-height: 0;
            cursor: pointer; color: #3b82f6; 
            font-weight: 800; font-size: 0.95rem;
            transition: all 0.2s ease;
            user-select: none;
        }
        .expand-btn:hover { 
            background: #3b82f6; 
            color: white;
            border-color: #3b82f6;
            transform: scale(1.05);
        }
        .dark .expand-btn { background: rgba(59, 130, 246, 0.2); border-color: rgba(59, 130, 246, 0.3); }

        .row-parent { font-weight: 700; color: #1e293b; }
        .dark .row-parent { color: #f1f5f9; }
        .row-child { color: #64748b; }
        .dark .row-child { color: #94a3b8; }
        .child-indent { padding-left: 2.5rem !important; }

        /* Grand Total Row Sync with Buku Besar */
        .row-grand-total { background: rgba(128,128,128,0.05); }
        .dark .row-grand-total { background: rgba(255,255,255,0.02); }
        .row-grand-total td { 
            color: #1e293b; 
            font-weight: 800; 
            font-size: 0.875rem; 
            border-top: 2px solid rgba(128,128,128,0.2);
            text-transform: uppercase;
        }
        .dark .row-grand-total td { color: white; border-top-color: rgba(255,255,255,0.1); }
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
                    <span style="color: #94a3b8">{{ $filterLabel ?? 'vs periode sebelumnya' }}</span>
                </div>
            </div>
        @endforeach
    </div>

    <!-- Secondary Toolbar: Tabs & Range -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.75rem; flex-wrap: wrap; gap: 1rem;">
        <div style="display: flex; align-items: center; background: rgba(128,128,128,0.04); border: 1px solid rgba(128,128,128,0.08); padding: 4px; border-radius: 8px;">
            <button wire:click="setViewMode('compact')" class="custom-tab-button {{ $viewMode === 'compact' ? 'active' : 'inactive' }}">Compact</button>
            <button wire:click="setViewMode('expanded')" class="custom-tab-button {{ $viewMode === 'expanded' ? 'active' : 'inactive' }}">Expanded</button>
        </div>
    </div>

    <!-- Trial Balance Table Container -->
    <div class="trial-balance-section">
        <table class="tb-table">
            <thead>
                <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
                    <th rowspan="2" style="text-align: left;"></th>
                    <th colspan="2">Saldo Awal</th>
                    <th colspan="2">Pergerakan</th>
                    <th colspan="2">Saldo Akhir</th>
                </tr>
                <tr style="background: rgba(148, 163, 184, 0.15); border-bottom: 2px solid rgba(128,128,128,0.2);">
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
                            <td colspan="7" style="padding: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div class="expand-btn">
                                        {{ $isExpanded ? '−' : '+' }}
                                    </div>
                                    <span>{{ $row['category'] }}</span>
                                </div>
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
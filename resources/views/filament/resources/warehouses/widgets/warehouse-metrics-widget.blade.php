<div style="display: flex; flex-direction: column; gap: 1rem; height: 100%;">
    {{-- Total Stok --}}
    <div
        style="flex: 1; background: #10b981; border-radius: 1rem; padding: 1.5rem; color: white; box-shadow: 0 10px 15px -3px rgba(16, 185, 129, 0.2); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -1rem; bottom: -1rem; opacity: 0.2;">
            <x-filament::icon icon="heroicon-s-cube-transparent" style="width: 6rem; height: 6rem;" />
        </div>
        <div style="position: relative; z-index: 10;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="padding: 0.4rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem;">
                    <x-filament::icon icon="heroicon-m-cube" style="width: 1.25rem; height: 1.25rem;" />
                </div>
                <span
                    style="font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8;">TOTAL
                    STOK</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: 0.5rem;">
                <h2 style="font-size: 2.25rem; font-weight: 900; letter-spacing: -0.025em;">
                    {{ number_format($totalStock, 0, ',', '.') }}</h2>
                <span style="font-size: 0.875rem; font-weight: 700; opacity: 0.7;">UNIT</span>
            </div>
        </div>
    </div>

    {{-- Total Nilai Produk --}}
    <div
        style="flex: 1; background: #f59e0b; border-radius: 1rem; padding: 1.5rem; color: white; box-shadow: 0 10px 15px -3px rgba(245, 158, 11, 0.2); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -1rem; bottom: -1rem; opacity: 0.2;">
            <x-filament::icon icon="heroicon-s-currency-dollar" style="width: 6rem; height: 6rem;" />
        </div>
        <div style="position: relative; z-index: 10;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="padding: 0.4rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem;">
                    <x-filament::icon icon="heroicon-m-banknotes" style="width: 1.25rem; height: 1.25rem;" />
                </div>
                <span
                    style="font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8;">NILAI
                    PRODUK</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                <span style="font-size: 1.125rem; font-weight: 900; opacity: 0.7;">Rp</span>
                <h2 style="font-size: 2.25rem; font-weight: 900; letter-spacing: -0.025em;">
                    {{ number_format($totalValue, 0, ',', '.') }}</h2>
            </div>
        </div>
    </div>

    {{-- Rata-Rata HPP --}}
    <div
        style="flex: 1; background: #f43f5e; border-radius: 1rem; padding: 1.5rem; color: white; box-shadow: 0 10px 15px -3px rgba(244, 63, 94, 0.2); position: relative; overflow: hidden;">
        <div style="position: absolute; right: -1rem; bottom: -1rem; opacity: 0.2;">
            <x-filament::icon icon="heroicon-s-chart-bar" style="width: 6rem; height: 6rem;" />
        </div>
        <div style="position: relative; z-index: 10;">
            <div style="display: flex; align-items: center; gap: 0.5rem; margin-bottom: 1rem;">
                <div style="padding: 0.4rem; background: rgba(255, 255, 255, 0.2); border-radius: 0.5rem;">
                    <x-filament::icon icon="heroicon-m-presentation-chart-line"
                        style="width: 1.25rem; height: 1.25rem;" />
                </div>
                <span
                    style="font-size: 0.7rem; font-weight: 900; text-transform: uppercase; letter-spacing: 0.1em; opacity: 0.8;">RATA-RATA
                    HPP</span>
            </div>
            <div style="display: flex; align-items: baseline; gap: 0.25rem;">
                <span style="font-size: 1.125rem; font-weight: 900; opacity: 0.7;">Rp</span>
                <h2 style="font-size: 2.25rem; font-weight: 900; letter-spacing: -0.025em;">
                    {{ number_format($averageHpp, 0, ',', '.') }}</h2>
            </div>
        </div>
    </div>
</div>
<div class="kas-stats-grid">
    @foreach($stats as $stat)
        <div class="kas-stat-card">
            <div class="kas-stat-label">{{ $stat['label'] }}</div>
            <div class="kas-stat-value">Rp {{ $stat['value'] }}</div>
            @if($stat['description'])
                <div class="kas-stat-subtext {{ $stat['description_color'] ?? '' }}">
                    {{ $stat['description'] }}
                </div>
            @endif
        </div>
    @endforeach
</div>
@if(isset($record) && $record)
    @livewire('account-transactions-table', ['accountId' => $record->id])
@else
    <div class="p-4 text-center text-gray-500 italic">Data tidak ditemukan.</div>
@endif
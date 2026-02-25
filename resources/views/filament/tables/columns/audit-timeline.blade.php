<div class="flex gap-4 items-stretch w-full relative group font-sans">
    <!-- Left column: Time Ago -->
    <div class="w-32 flex-shrink-0 text-right text-[13px] text-gray-500 dark:text-gray-400 pt-1 whitespace-nowrap">
        {{ $getRecord()->created_at->diffForHumans() }}
    </div>

    <!-- Middle column: Timeline Line and Dot -->
    <div class="relative flex flex-col items-center w-8 flex-shrink-0">
        <!-- The Line bridging top to bottom -->
        <div class="absolute top-0 bottom-0 w-[2.5px] bg-gray-200 dark:bg-gray-700"></div>
        <!-- The Dot -->
        <div class="absolute top-1.5 w-[16px] h-[16px] rounded-full bg-white border-[4px] border-[#3b82f6] dark:bg-gray-900 z-10"></div>
    </div>

    <!-- Right column: Details -->
    <div class="flex-1 pb-6 pt-0.5">
        @php
            $record = $getRecord();
            $causer = \App\Models\User::find($record->causer_id);
            $causerName = $causer ? $causer->name : 'Sistem';
            $modelName = class_basename($record->subject_type);
            
            // Text logic
            $actionDesc = $record->description;
            if ($actionDesc === 'created') $actionDesc = "Membuat {$modelName}";
            if ($actionDesc === 'updated') $actionDesc = "Mengubah {$modelName}";
            if ($actionDesc === 'deleted') $actionDesc = "Menghapus {$modelName}";
        @endphp
        
        <div class="text-[14px]">
            <span class="text-[#3b82f6] font-medium">{{ $actionDesc }}</span> 
            <span class="text-gray-600 dark:text-gray-300">oleh {{ $causerName }}</span>
        </div>
        
        <div class="mt-1 text-[13px] text-gray-500 dark:text-gray-400 font-medium tracking-wide">
            Type: {{ $modelName }} 
            @if ($record->subject_id)
            ID: {{ $record->subject_id }}
            @endif
        </div>
        
        <div class="mt-1 text-[13px] text-[#3b82f6] font-medium tracking-wide">
            {{ $record->properties['ip'] ?? request()->ip() }}
        </div>
        
        <div class="mt-1 text-[12px] text-gray-400 dark:text-gray-500">
            {{ $record->created_at->format('d/m/Y H:i') }}
        </div>
    </div>
</div>
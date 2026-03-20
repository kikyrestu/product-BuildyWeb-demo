<x-app-layout>
    <x-slot name="header">
        <h2 class="font-semibold text-xl text-slate-800 leading-tight">WA Templates</h2>
    </x-slot>

    <div class="py-8">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-4 bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-lg text-sm">{{ session('status') }}</div>
            @endif

            <form method="POST" action="{{ route('settings.wa-templates.update') }}" class="space-y-4">
                @csrf
                @method('PUT')

                @foreach ($templates as $i => $tpl)
                    <div class="bw-card p-4">
                        <input type="hidden" name="templates[{{ $i }}][id]" value="{{ $tpl->id }}">
                        <div class="flex items-center justify-between">
                            <p class="font-semibold text-sm">{{ $tpl->template_key }}</p>
                            <label class="text-xs text-slate-600 flex items-center gap-2">
                                <input type="checkbox" name="templates[{{ $i }}][is_active]" value="1" @checked($tpl->is_active)> Aktif
                            </label>
                        </div>
                        <input type="text" name="templates[{{ $i }}][title]" value="{{ $tpl->title }}" class="mt-3 w-full bw-input" required>
                        <textarea name="templates[{{ $i }}][content]" rows="3" class="mt-3 w-full bw-input" required>{{ $tpl->content }}</textarea>
                    </div>
                @endforeach

                <button class="bw-btn-primary">Simpan Perubahan</button>
            </form>
        </div>
    </div>
</x-app-layout>

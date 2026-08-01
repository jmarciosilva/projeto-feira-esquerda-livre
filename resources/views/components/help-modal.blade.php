<div
    x-data="{
        show: false,
        title: '',
        intro: '',
        tips: [],
        open(detail) {
            this.title = detail.title ?? 'Ajuda';
            this.intro = detail.intro ?? '';
            this.tips  = detail.tips  ?? [];
            this.show  = true;
        },
    }"
    @open-help.window="open($event.detail)"
    @keydown.escape.window="show = false"
    x-show="show"
    x-cloak
    class="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4"
    @click.self="show = false"
    x-transition:enter="transition ease-out duration-200"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-150"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div
        class="bg-white rounded-2xl shadow-2xl max-w-lg w-full flex flex-col max-h-[85vh]"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="flex items-start gap-4 p-6 pb-4 flex-shrink-0">
            <div class="w-11 h-11 rounded-full flex items-center justify-center flex-shrink-0" style="background:#F4E294;">
                <svg class="w-6 h-6" style="color:#3D3000;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.5M12 17.5h.007v.008H12v-.008z"/>
                    <circle cx="12" cy="12" r="9" stroke-linecap="round" stroke-linejoin="round" stroke-width="2"/>
                </svg>
            </div>

            <div class="pt-1 flex-1 min-w-0">
                <h3 class="text-lg font-bold text-gray-900 leading-snug" x-text="title"></h3>
            </div>

            <button
                type="button"
                @click="show = false"
                class="flex-shrink-0 text-gray-400 hover:text-gray-600 transition-colors p-1 -m-1"
                aria-label="Fechar ajuda"
            >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>
            </button>
        </div>

        <div class="px-6 pb-2 overflow-y-auto flex-1">
            <p class="text-base text-gray-700 leading-relaxed" x-text="intro"></p>

            <ul class="mt-4 space-y-3" x-show="tips.length > 0">
                <template x-for="tip in tips" :key="tip">
                    <li class="flex items-start gap-3">
                        <svg class="w-5 h-5 flex-shrink-0 mt-0.5" style="color:#5C8A3C;" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                        </svg>
                        <span class="text-sm text-gray-600 leading-relaxed" x-text="tip"></span>
                    </li>
                </template>
            </ul>
        </div>

        <div class="flex justify-end p-6 pt-4 flex-shrink-0">
            <button
                type="button"
                @click="show = false"
                class="inline-flex items-center gap-2 font-semibold rounded-lg transition-colors duration-150 px-5 py-2.5 text-sm text-white bg-[#C47A00] hover:bg-[#A86400]"
            >
                Entendi
            </button>
        </div>
    </div>
</div>

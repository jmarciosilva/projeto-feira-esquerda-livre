<div
    x-data="{
        show: false,
        title: '',
        message: '',
        confirmText: 'Confirmar',
        variant: 'danger',
        pendingAction: null,
        open(detail) {
            this.title       = detail.title       ?? 'Confirmar ação';
            this.message     = detail.message     ?? 'Tem certeza que deseja continuar?';
            this.confirmText = detail.confirmText ?? 'Confirmar';
            this.variant     = detail.variant     ?? 'danger';
            this.pendingAction = detail.action    ?? null;
            this.show = true;
        },
        execute() {
            if (this.pendingAction) this.pendingAction();
            this.show = false;
        },
    }"
    @open-confirm.window="open($event.detail)"
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
        class="bg-white rounded-2xl shadow-2xl max-w-sm w-full p-6"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-150"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        <div class="flex items-start gap-4 mb-5">
            <div
                class="w-10 h-10 rounded-full flex items-center justify-center flex-shrink-0"
                :class="{
                    'bg-red-50':    variant === 'danger',
                    'bg-green-50':  variant === 'success',
                    'bg-yellow-50': variant === 'warning',
                    'bg-blue-50':   variant === 'info',
                }"
            >
                <svg
                    class="w-5 h-5"
                    :class="{
                        'text-red-500':    variant === 'danger',
                        'text-green-600':  variant === 'success',
                        'text-yellow-500': variant === 'warning',
                        'text-blue-500':   variant === 'info',
                    }"
                    fill="none" stroke="currentColor" viewBox="0 0 24 24"
                >
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                          d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
            </div>

            <div class="pt-0.5 flex-1 min-w-0">
                <h3 class="text-base font-semibold text-gray-900 leading-snug" x-text="title"></h3>
                <p class="text-sm text-gray-500 mt-1 leading-relaxed" x-text="message"></p>
            </div>
        </div>

        <div class="flex gap-3 justify-end pt-1">
            <button
                type="button"
                @click="show = false"
                class="inline-flex items-center gap-2 font-medium border rounded-lg transition-colors duration-150 px-4 py-2 text-sm bg-white hover:bg-gray-50 text-gray-700 border-gray-300"
            >
                Cancelar
            </button>

            <button
                type="button"
                @click="execute()"
                class="inline-flex items-center gap-2 font-medium border border-transparent rounded-lg transition-colors duration-150 px-4 py-2 text-sm"
                :class="{
                    'bg-red-600 hover:bg-red-700 text-white':         variant === 'danger',
                    'bg-[#52b788] hover:bg-[#2d6a4f] text-white':     variant === 'success',
                    'bg-yellow-500 hover:bg-yellow-600 text-white':   variant === 'warning',
                    'bg-blue-600 hover:bg-blue-700 text-white':       variant === 'info',
                }"
                x-text="confirmText"
            ></button>
        </div>
    </div>
</div>

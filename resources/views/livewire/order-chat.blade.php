<div wire:poll.5s
     x-data="{ scrollToBottom() { const el = $refs.msgs; if (el) el.scrollTop = el.scrollHeight; } }"
     x-init="scrollToBottom()"
     x-on:chat-message-sent.window="$nextTick(() => scrollToBottom())"
     class="flex flex-col rounded-2xl border border-gray-200 overflow-hidden"
     style="height: 440px;">

    {{-- Header --}}
    <div class="flex items-center gap-3 px-4 py-3 border-b border-gray-100 flex-shrink-0" style="background:#FDF8DC;">
        <div class="w-8 h-8 rounded-full flex items-center justify-center flex-shrink-0" style="background:#3D3000;">
            <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                      d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="text-sm font-bold truncate" style="color:#3D3000;">Chat do Pedido</p>
            <p class="text-xs text-gray-500 truncate">{{ $split->expositor->name }}</p>
        </div>
    </div>

    {{-- Messages --}}
    <div x-ref="msgs"
         class="flex-1 overflow-y-auto p-4 space-y-3 bg-white">
        @forelse($messages as $msg)
        @php $mine = $msg->isFromMe(); @endphp
        <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
            <div class="max-w-[78%]">
                <div class="px-3.5 py-2.5 rounded-2xl text-sm leading-relaxed break-words
                            {{ $mine ? 'rounded-br-sm' : 'rounded-bl-sm' }}"
                     style="{{ $mine ? 'background:#E8A000; color:#fff;' : 'background:#f3f4f6; color:#1f2937;' }}">
                    {{ $msg->body }}
                </div>
                <p class="text-xs text-gray-400 mt-0.5 {{ $mine ? 'text-right' : 'text-left' }}">
                    {{ $msg->sender->name }} &middot; {{ $msg->created_at->diffForHumans() }}
                </p>
            </div>
        </div>
        @empty
        <div class="flex items-center justify-center h-full py-10">
            <div class="text-center">
                <div class="text-3xl mb-2">💬</div>
                <p class="text-sm text-gray-400">Nenhuma mensagem ainda.<br>Inicie a conversa!</p>
            </div>
        </div>
        @endforelse
    </div>

    {{-- Input --}}
    <div class="px-4 py-3 border-t border-gray-100 bg-gray-50 flex-shrink-0">
        <form wire:submit="send" class="flex items-end gap-2">
            <div class="flex-1">
                <textarea
                    wire:model="body"
                    rows="2"
                    placeholder="Digite sua mensagem..."
                    maxlength="2000"
                    class="w-full px-3 py-2 rounded-xl border border-gray-200 text-sm resize-none focus:outline-none focus:ring-2 focus:ring-yellow-400"
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.send() }"></textarea>
                @error('body')
                <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                @enderror
            </div>
            <button type="submit"
                    class="flex-shrink-0 px-4 py-2 rounded-xl text-sm font-semibold text-white"
                    style="background:#3D3000; min-height:44px;">
                Enviar
            </button>
        </form>
        <p class="text-xs text-gray-400 mt-1">Enter para enviar &middot; Shift+Enter para nova linha</p>
    </div>
</div>

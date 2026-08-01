<button type="button"
        x-data="{ visible: false }"
        x-init="visible = window.scrollY > 420"
        x-show="visible"
        x-cloak
        x-transition.opacity.duration.200ms
        @scroll.window="visible = window.scrollY > 420"
        @click="window.scrollTo({ top: 0, behavior: 'smooth' })"
        aria-label="Voltar ao topo"
        title="Voltar ao topo"
        class="fixed z-[60] right-4 bottom-5 sm:right-6 sm:bottom-6 inline-flex items-center justify-center rounded-full shadow-lg transition-transform hover:-translate-y-0.5 focus:outline-none focus:ring-4"
        style="width: 46px; height: 46px; background:#3D3000; color:#F4E294; border:2px solid #E8A000; --tw-ring-color: rgba(232,160,0,0.28);">
    <svg class="w-5 h-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2.5" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" d="M5 15l7-7 7 7"/>
    </svg>
</button>

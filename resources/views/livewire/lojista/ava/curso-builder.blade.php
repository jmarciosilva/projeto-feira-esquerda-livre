<div>
    {{-- Flash --}}
    @if(session('success'))
    <div class="mb-5 p-4 bg-green-50 border border-green-200 rounded-xl text-green-800 text-sm font-medium flex items-center gap-2">
        <svg class="w-4 h-4 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        {{ session('success') }}
    </div>
    @endif

    {{-- Cabeçalho --}}
    <div class="mb-6 flex flex-col sm:flex-row sm:items-center gap-4">
        <div class="flex-1">
            <a href="{{ route('lojista.ava.index') }}" class="text-sm font-semibold mb-1 inline-block" style="color:#C47A00;">← Meus Cursos</a>
            <div class="flex items-center gap-3 mt-1">
                <h2 class="text-xl font-bold text-gray-900">{{ $course->product->name }}</h2>
                @if($is_published)
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-green-50 text-green-700">Publicado</span>
                @else
                    <span class="px-2.5 py-0.5 rounded-full text-xs font-semibold bg-yellow-50 text-yellow-700">Rascunho</span>
                @endif
            </div>
        </div>
        <button wire:click="togglePublish"
                class="px-5 py-2.5 rounded-xl text-sm font-semibold transition-colors {{ $is_published ? 'bg-gray-100 text-gray-700 hover:bg-gray-200' : 'text-white' }}"
                style="{{ ! $is_published ? 'background:#E8A000;' : '' }}">
            {{ $is_published ? 'Despublicar' : 'Publicar Curso' }}
        </button>
    </div>

    {{-- Tabs --}}
    <div class="flex gap-1 mb-6 bg-gray-100 p-1 rounded-xl w-fit">
        <button wire:click="$set('activeTab','configuracoes')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'configuracoes' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
            Configurações
        </button>
        <button wire:click="$set('activeTab','conteudo')"
                class="px-4 py-2 rounded-lg text-sm font-medium transition-colors {{ $activeTab === 'conteudo' ? 'bg-white shadow text-gray-900' : 'text-gray-500 hover:text-gray-700' }}">
            Módulos e Aulas
        </button>
    </div>

    {{-- ═══════════════════════════════════════════════════════
         TAB: CONFIGURAÇÕES
    ════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'configuracoes')
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div class="lg:col-span-2 space-y-6">

            {{-- Nível e Carga Horária --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-5">
                <h3 class="text-base font-bold text-gray-900">Dados do Curso</h3>
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Nível</label>
                        <select wire:model="level" class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
                            <option value="iniciante">Iniciante</option>
                            <option value="intermediario">Intermediário</option>
                            <option value="avancado">Avançado</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Carga horária estimada (h)</label>
                        <input wire:model="estimated_hours" type="number" step="0.5" min="0" placeholder="Ex.: 8"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-gray-700 mb-1">Acesso por (dias)</label>
                        <input wire:model="access_duration_days" type="number" min="1" placeholder="Vazio = vitalício"
                               class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Vídeo de Introdução (URL YouTube ou Vimeo)</label>
                    <input wire:model="intro_video_url" type="url" placeholder="https://youtu.be/..."
                           class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
                    @error('intro_video_url')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="flex flex-col sm:flex-row gap-4">
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="is_drip" class="mt-0.5 w-4 h-4 rounded border-gray-300" style="accent-color:#E8A000;">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Liberação gradual (Drip)</span>
                            <p class="text-xs text-gray-400 mt-0.5">Aulas liberadas dia a dia após a matrícula</p>
                        </div>
                    </label>
                    <label class="flex items-start gap-3 cursor-pointer">
                        <input type="checkbox" wire:model="certificate_enabled" class="mt-0.5 w-4 h-4 rounded border-gray-300" style="accent-color:#E8A000;">
                        <div>
                            <span class="text-sm font-medium text-gray-700">Certificado de conclusão</span>
                            <p class="text-xs text-gray-400 mt-0.5">Emitido ao atingir 100% das aulas</p>
                        </div>
                    </label>
                </div>
            </div>

            {{-- O que você vai aprender / Requisitos --}}
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 space-y-4">
                <h3 class="text-base font-bold text-gray-900">Descrição Detalhada</h3>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">O que o aluno vai aprender</label>
                    <textarea wire:model="what_youll_learn" rows="4"
                              placeholder="Liste os principais conhecimentos e habilidades que o aluno vai adquirir..."
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                </div>
                <div>
                    <label class="block text-sm font-semibold text-gray-700 mb-1">Pré-requisitos <span class="font-normal text-gray-400">opcional</span></label>
                    <textarea wire:model="requirements" rows="3"
                              placeholder="O que o aluno já deve saber antes de começar..."
                              class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000] resize-none"></textarea>
                </div>
            </div>
        </div>

        {{-- Sidebar de configurações --}}
        <div class="space-y-4">
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 space-y-3">
                <h3 class="text-sm font-bold text-gray-900">Resumo</h3>
                <div class="text-sm text-gray-600 space-y-1.5">
                    <p>Módulos: <strong class="text-gray-900">{{ $modules->count() }}</strong></p>
                    <p>Aulas: <strong class="text-gray-900">{{ $modules->sum(fn ($m) => $m->lessons->count()) }}</strong></p>
                    <p>Matrículas: <strong class="text-gray-900">{{ $course->enrollments()->count() }}</strong></p>
                    <p>Status: <strong class="{{ $is_published ? 'text-green-700' : 'text-yellow-700' }}">{{ $is_published ? 'Publicado' : 'Rascunho' }}</strong></p>
                </div>
            </div>

            <button wire:click="saveSettings"
                    class="w-full py-3 rounded-xl text-white text-sm font-bold"
                    style="background:#E8A000;">
                <span wire:loading.remove>Salvar Configurações</span>
                <span wire:loading>Salvando...</span>
            </button>
        </div>
    </div>
    @endif

    {{-- ═══════════════════════════════════════════════════════
         TAB: MÓDULOS E AULAS
    ════════════════════════════════════════════════════════ --}}
    @if($activeTab === 'conteudo')
    <div class="space-y-4">

        @forelse($modules as $module)
        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">

            {{-- Cabeçalho do módulo --}}
            <div class="flex items-center gap-3 px-5 py-4 border-b border-gray-100" style="background:#FFFBEB;">

                {{-- Reordenar --}}
                <div class="flex flex-col gap-0.5">
                    <button wire:click="moveModuleUp({{ $module->id }})" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                    </button>
                    <button wire:click="moveModuleDown({{ $module->id }})" class="text-gray-400 hover:text-gray-600">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                </div>

                @if($editingModuleId === $module->id)
                    {{-- Modo edição --}}
                    <div class="flex-1 space-y-2">
                        <input wire:model="editingModuleTitle" type="text"
                               class="w-full px-3 py-1.5 border border-[#E8A000] rounded-lg text-sm font-semibold focus:outline-none"
                               placeholder="Título do módulo">
                        @error('editingModuleTitle')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        <input wire:model="editingModuleDescription" type="text"
                               class="w-full px-3 py-1.5 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]"
                               placeholder="Descrição opcional">
                    </div>
                    <div class="flex gap-2">
                        <button wire:click="saveModule" class="px-3 py-1.5 rounded-lg text-sm font-semibold text-white" style="background:#E8A000;">Salvar</button>
                        <button wire:click="cancelEditModule" class="px-3 py-1.5 rounded-lg text-sm text-gray-600 border border-gray-200">Cancelar</button>
                    </div>
                @else
                    <div class="flex-1">
                        <h4 class="text-sm font-bold text-gray-900">{{ $module->title }}</h4>
                        @if($module->description)
                            <p class="text-xs text-gray-500 mt-0.5">{{ $module->description }}</p>
                        @endif
                    </div>
                    <span class="text-xs text-gray-400">{{ $module->lessons->count() }} aula{{ $module->lessons->count() !== 1 ? 's' : '' }}</span>
                    <button wire:click="startEditModule({{ $module->id }})" class="text-gray-400 hover:text-[#E8A000]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button wire:click="deleteModule({{ $module->id }})"
                            wire:confirm="Excluir este módulo e todas as suas aulas?"
                            class="text-gray-400 hover:text-red-500">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                @endif
            </div>

            {{-- Aulas do módulo --}}
            <div class="divide-y divide-gray-50">
                @foreach($module->lessons as $lesson)
                <div class="px-5 py-3">
                    @if($editingLessonId === $lesson->id)
                    {{-- Formulário de edição de aula --}}
                    <div class="space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Título da aula *</label>
                                <input wire:model="editingLessonTitle" type="text"
                                       class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                                @error('editingLessonTitle')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                            </div>
                            <div>
                                <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo de conteúdo</label>
                                <select wire:model.live="editingLessonContentType"
                                        class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                                    <option value="video">Vídeo (YouTube/Vimeo)</option>
                                    <option value="texto">Texto (HTML/Markdown)</option>
                                    <option value="pdf">PDF (Material complementar)</option>
                                    <option value="audio">Áudio</option>
                                </select>
                            </div>
                        </div>
                        @if($editingLessonContentType === 'video')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">URL do vídeo</label>
                            <input wire:model="editingLessonVideoUrl" type="url" placeholder="https://youtu.be/..."
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                        </div>
                        @elseif($editingLessonContentType === 'texto')
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Conteúdo textual</label>
                            <textarea wire:model="editingLessonTextContent" rows="5"
                                      placeholder="Conteúdo da aula em texto livre..."
                                      class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000] resize-none"></textarea>
                        </div>
                        @endif
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Descrição breve <span class="font-normal text-gray-400">opcional</span></label>
                            <input wire:model="editingLessonDescription" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                        </div>
                        <label class="flex items-center gap-2 cursor-pointer">
                            <input type="checkbox" wire:model="editingLessonIsPreview" class="w-4 h-4 rounded border-gray-300" style="accent-color:#E8A000;">
                            <span class="text-sm text-gray-600">Aula pública (acessível antes da matrícula)</span>
                        </label>

                        {{-- Materiais existentes --}}
                        @if($lesson->materials->count() > 0)
                        <div class="pt-2 border-t border-gray-100">
                            <p class="text-xs font-semibold text-gray-500 mb-2">Materiais desta aula</p>
                            <div class="space-y-1.5">
                                @foreach($lesson->materials as $mat)
                                <div class="flex items-center gap-2 text-sm text-gray-600">
                                    <span class="text-gray-400">📎</span>
                                    <span class="flex-1 truncate">{{ $mat->title }}</span>
                                    <span class="text-xs text-gray-400">{{ $mat->fileSizeLabel() }}</span>
                                    <button wire:click="deleteMaterial({{ $mat->id }})"
                                            wire:confirm="Excluir este material?"
                                            class="text-gray-400 hover:text-red-500 flex-shrink-0">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                    </button>
                                </div>
                                @endforeach
                            </div>
                        </div>
                        @endif

                        {{-- Upload de material --}}
                        @if($uploadingMaterialForLesson === $lesson->id)
                        <div class="pt-2 border-t border-gray-100 space-y-2">
                            <p class="text-xs font-semibold text-gray-600">Adicionar material</p>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2">
                                <div>
                                    <input wire:model="materialTitle" type="text" placeholder="Nome do material (ex.: Slides da aula)"
                                           class="w-full px-3 py-2 border border-gray-300 rounded-lg text-xs focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                                    @error('materialTitle')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                                <div>
                                    <input wire:model="materialFile" type="file" accept=".pdf,.pptx,.docx,.xlsx,.zip,.mp3,.mp4"
                                           class="w-full text-xs text-gray-600 file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:text-xs file:font-semibold file:text-white cursor-pointer"
                                           style="file:background:#E8A000;">
                                    @error('materialFile')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <button wire:click="uploadMaterial" class="px-3 py-1.5 rounded-lg text-xs font-semibold text-white" style="background:#E8A000;">
                                    <span wire:loading.remove wire:target="uploadMaterial">Enviar</span>
                                    <span wire:loading wire:target="uploadMaterial">Enviando...</span>
                                </button>
                                <button wire:click="cancelMaterialUpload" class="px-3 py-1.5 rounded-lg text-xs text-gray-600 border border-gray-200">Cancelar</button>
                            </div>
                        </div>
                        @else
                        <button wire:click="openMaterialUpload({{ $lesson->id }})"
                                class="text-xs text-[#E8A000] font-medium hover:text-[#C47A00] flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                            Adicionar material
                        </button>
                        @endif

                        <div class="flex gap-2 pt-1">
                            <button wire:click="saveLesson" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#E8A000;">Salvar aula</button>
                            <button wire:click="cancelEditLesson" class="px-4 py-2 rounded-lg text-sm text-gray-600 border border-gray-200">Cancelar</button>
                        </div>
                    </div>
                    @else
                    {{-- Linha de aula --}}
                    <div class="flex items-center gap-3">
                        <div class="flex flex-col gap-0.5">
                            <button wire:click="moveLessonUp({{ $lesson->id }})" class="text-gray-300 hover:text-gray-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
                            </button>
                            <button wire:click="moveLessonDown({{ $lesson->id }})" class="text-gray-300 hover:text-gray-500">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                            </button>
                        </div>
                        {{-- Ícone de tipo --}}
                        <span class="w-7 h-7 rounded-lg flex items-center justify-center flex-shrink-0 text-xs"
                              style="background:#F4E294; color:#3D3000;">
                            @if($lesson->content_type === 'video') ▶
                            @elseif($lesson->content_type === 'texto') 📄
                            @elseif($lesson->content_type === 'pdf') 📎
                            @else 🎵
                            @endif
                        </span>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 truncate">{{ $lesson->title }}</p>
                            @if($lesson->description)
                                <p class="text-xs text-gray-400 truncate">{{ $lesson->description }}</p>
                            @endif
                        </div>
                        @if($lesson->is_preview)
                        <span class="text-xs px-1.5 py-0.5 rounded bg-blue-50 text-blue-600 flex-shrink-0">Preview</span>
                        @endif
                        <button wire:click="startEditLesson({{ $lesson->id }})" class="text-gray-400 hover:text-[#E8A000]">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        </button>
                        <button wire:click="deleteLesson({{ $lesson->id }})"
                                wire:confirm="Excluir esta aula?"
                                class="text-gray-400 hover:text-red-500">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </div>
                    @endif
                </div>
                @endforeach

                {{-- Formulário nova aula --}}
                @if($showNewLessonFormForModule && $newLessonModuleId === $module->id)
                <div class="px-5 py-4 bg-gray-50 border-t border-gray-100 space-y-3">
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Título da nova aula *</label>
                            <input wire:model="newLessonTitle" type="text"
                                   class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]"
                                   placeholder="Ex.: Introdução ao módulo">
                            @error('newLessonTitle')<p class="text-xs text-red-600">{{ $message }}</p>@enderror
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-gray-600 mb-1">Tipo</label>
                            <select wire:model.live="newLessonContentType"
                                    class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                                <option value="video">Vídeo</option>
                                <option value="texto">Texto</option>
                                <option value="pdf">PDF</option>
                                <option value="audio">Áudio</option>
                            </select>
                        </div>
                    </div>
                    @if($newLessonContentType === 'video')
                    <div>
                        <label class="block text-xs font-semibold text-gray-600 mb-1">URL do vídeo</label>
                        <input wire:model="newLessonVideoUrl" type="url" placeholder="https://youtu.be/..."
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg text-sm focus:outline-none focus:ring-1 focus:ring-[#E8A000]">
                    </div>
                    @endif
                    <div class="flex gap-2">
                        <button wire:click="addLesson" class="px-4 py-2 rounded-lg text-sm font-semibold text-white" style="background:#E8A000;">Adicionar aula</button>
                        <button wire:click="cancelNewLesson" class="px-4 py-2 rounded-lg text-sm text-gray-600 border border-gray-200">Cancelar</button>
                    </div>
                </div>
                @else
                <div class="px-5 py-3 border-t border-gray-50">
                    <button wire:click="openNewLessonForm({{ $module->id }})"
                            class="flex items-center gap-1.5 text-sm text-[#E8A000] font-medium hover:text-[#C47A00]">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        Adicionar aula
                    </button>
                </div>
                @endif
            </div>
        </div>
        @empty
        <div class="text-center py-10 text-gray-400 text-sm">
            Nenhum módulo criado ainda. Adicione o primeiro módulo abaixo.
        </div>
        @endforelse

        {{-- Adicionar módulo --}}
        @if($showNewModuleForm)
        <div class="bg-white rounded-2xl shadow-sm border border-[#E8A000] p-5 space-y-3">
            <h4 class="text-sm font-bold text-gray-900">Novo Módulo</h4>
            <input wire:model="newModuleTitle" type="text"
                   placeholder="Título do módulo (ex.: Introdução, Módulo 1...)"
                   class="w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-[#E8A000]">
            @error('newModuleTitle')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
            <div class="flex gap-2">
                <button wire:click="addModule" class="px-4 py-2 rounded-xl text-sm font-semibold text-white" style="background:#E8A000;">Criar módulo</button>
                <button wire:click="cancelNewModule" class="px-4 py-2 rounded-xl text-sm text-gray-600 border border-gray-200">Cancelar</button>
            </div>
        </div>
        @else
        <button wire:click="openNewModuleForm"
                class="w-full flex items-center justify-center gap-2 py-4 rounded-2xl border-2 border-dashed border-gray-200 text-sm font-semibold text-gray-500 hover:border-[#E8A000] hover:text-[#E8A000] transition-colors">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            Adicionar módulo
        </button>
        @endif
    </div>
    @endif
</div>

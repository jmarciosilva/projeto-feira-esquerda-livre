<div class="min-h-screen bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
    <div class="max-w-6xl mx-auto space-y-6">

        <div>
            <h1 class="text-3xl font-bold text-gray-900">Auditoria</h1>
            <p class="mt-2 text-gray-600">
                Quem consultou os dados comportamentais e quem executou as operações sensíveis do módulo.
            </p>
        </div>

        {{--
            Dito na tela, e não só na documentação: quem lê a trilha precisa saber
            que ela tem prazo, senão vai procurar por um registro de três anos atrás
            e concluir que ele nunca existiu.
        --}}
        <div class="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
            Os registros são preservados por <strong>{{ $retencaoDias }} dias</strong> e depois removidos
            automaticamente. Esta trilha não pode ser editada nem apagada pelo painel.
        </div>

        <div class="bg-white rounded-lg shadow p-4">
            <label for="action" class="block text-sm font-medium text-gray-700 mb-1">Ação</label>
            <select wire:model.live="action"
                    id="action"
                    class="w-full md:w-96 px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500">
                <option value="">Todas as ações</option>
                @foreach($acoes as $acao)
                    <option value="{{ $acao->value }}">{{ $acao->label() }}</option>
                @endforeach
            </select>
        </div>

        <div class="bg-white rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Quando</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Quem</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Ação</th>
                            <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Recurso</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200">
                        @forelse($registros as $registro)
                            <tr class="hover:bg-gray-50">
                                <td class="px-6 py-4 text-sm text-gray-600 whitespace-nowrap">
                                    {{ $registro->created_at?->format('d/m/Y H:i:s') ?? '—' }}
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-900">
                                    @if($registro->user)
                                        {{ $registro->user->name }}
                                    @else
                                        {{-- Execução agendada, ou conta removida depois do fato. --}}
                                        <span class="text-gray-400 italic">Sistema</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-sm">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-semibold
                                        {{ $registro->action->isWrite() ? 'bg-orange-100 text-orange-800' : 'bg-gray-100 text-gray-700' }}">
                                        {{ $registro->action->label() }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-sm text-gray-600">
                                    @if($registro->resource_type)
                                        <code class="text-xs bg-gray-100 px-2 py-1 rounded">
                                            {{ $registro->resource_type }}#{{ $registro->resource_id }}
                                        </code>
                                    @else
                                        —
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-10 text-center text-sm text-gray-500">
                                    Nenhum registro de auditoria no período preservado.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if($registros->hasPages())
                <div class="px-6 py-4 border-t border-gray-200">
                    {{ $registros->links() }}
                </div>
            @endif
        </div>

    </div>
</div>

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../data/agenda_api.dart';
import '../domain/evento.dart';

const _estados = [
  'AC', 'AL', 'AP', 'AM', 'BA', 'CE', 'DF', 'ES', 'GO', 'MA',
  'MT', 'MS', 'MG', 'PA', 'PB', 'PR', 'PE', 'PI', 'RJ', 'RN',
  'RS', 'RO', 'RR', 'SC', 'SP', 'SE', 'TO',
];

class AgendaListScreen extends ConsumerStatefulWidget {
  const AgendaListScreen({super.key});

  @override
  ConsumerState<AgendaListScreen> createState() => _AgendaListScreenState();
}

class _AgendaListScreenState extends ConsumerState<AgendaListScreen> {
  Paginated<Evento>? _pagina;
  bool _isLoading = true;
  bool _isLoadingMore = false;
  String? _error;
  String? _estado;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final pagina = await ref.read(agendaApiProvider).listar(estado: _estado);
      if (!mounted) return;
      setState(() {
        _pagina = pagina;
        _isLoading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  Future<void> _carregarMais() async {
    if (_pagina == null || !_pagina!.hasMore || _isLoadingMore) return;
    setState(() => _isLoadingMore = true);
    try {
      final proxima = await ref
          .read(agendaApiProvider)
          .listar(page: _pagina!.currentPage + 1, estado: _estado);
      if (!mounted) return;
      setState(() {
        _pagina = _pagina!.appendPage(proxima);
        _isLoadingMore = false;
      });
    } on ApiException {
      if (mounted) setState(() => _isLoadingMore = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const FelAppBar(title: 'Agenda de Feiras'),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.all(16),
            child: Row(
              children: [
                const Text('Estado:', style: TextStyle(fontWeight: FontWeight.w600)),
                const SizedBox(width: 12),
                Expanded(
                  child: DropdownButton<String?>(
                    value: _estado,
                    isExpanded: true,
                    hint: const Text('Todos'),
                    items: [
                      const DropdownMenuItem(value: null, child: Text('Todos')),
                      for (final uf in _estados) DropdownMenuItem(value: uf, child: Text(uf)),
                    ],
                    onChanged: (value) {
                      setState(() => _estado = value);
                      _carregar();
                    },
                  ),
                ),
              ],
            ),
          ),
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!))
                    : (_pagina?.data.isEmpty ?? true)
                        ? const Center(child: Text('Nenhuma feira encontrada.'))
                        : ListView.separated(
                            padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
                            itemCount: _pagina!.data.length + 1,
                            separatorBuilder: (context, index) => const SizedBox(height: 12),
                            itemBuilder: (context, index) {
                              if (index == _pagina!.data.length) {
                                if (!_pagina!.hasMore) return const SizedBox.shrink();
                                return Center(
                                  child: Padding(
                                    padding: const EdgeInsets.only(top: 8),
                                    child: OutlinedButton(
                                      onPressed: _isLoadingMore ? null : _carregarMais,
                                      child: _isLoadingMore
                                          ? const SizedBox(
                                              height: 20,
                                              width: 20,
                                              child: CircularProgressIndicator(strokeWidth: 2),
                                            )
                                          : const Text('Carregar mais'),
                                    ),
                                  ),
                                );
                              }
                              final evento = _pagina!.data[index];
                              return _EventoCard(evento: evento);
                            },
                          ),
          ),
        ],
      ),
    );
  }
}

class _EventoCard extends StatelessWidget {
  const _EventoCard({required this.evento});

  final Evento evento;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: () => context.push('/agenda/${evento.slug}'),
      borderRadius: BorderRadius.circular(16),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE5E5E5)),
        ),
        clipBehavior: Clip.antiAlias,
        child: Row(
          children: [
            Container(
              width: 72,
              height: 88,
              color: AppColors.brown,
              alignment: Alignment.center,
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Text(
                    evento.startDate != null ? '${evento.startDate!.day}' : '-',
                    style: const TextStyle(
                      color: AppColors.accentYellow,
                      fontSize: 24,
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    evento.startDate != null ? _mesAbreviado(evento.startDate!.month) : '',
                    style: const TextStyle(color: Colors.white, fontSize: 12),
                  ),
                ],
              ),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      evento.title,
                      style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 16),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    if (evento.city != null)
                      Text(
                        [evento.city, evento.state].whereType<String>().join(' / '),
                        style: const TextStyle(color: AppColors.textSecondary),
                      ),
                  ],
                ),
              ),
            ),
            const Icon(Icons.chevron_right, color: AppColors.textSecondary),
            const SizedBox(width: 8),
          ],
        ),
      ),
    );
  }

  String _mesAbreviado(int mes) {
    const meses = [
      'JAN', 'FEV', 'MAR', 'ABR', 'MAI', 'JUN',
      'JUL', 'AGO', 'SET', 'OUT', 'NOV', 'DEZ',
    ];
    return meses[mes - 1];
  }
}

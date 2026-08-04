import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/formatters.dart';
import '../data/agenda_api.dart';
import '../domain/evento.dart';

class AgendaDetailScreen extends ConsumerStatefulWidget {
  const AgendaDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  ConsumerState<AgendaDetailScreen> createState() => _AgendaDetailScreenState();
}

class _AgendaDetailScreenState extends ConsumerState<AgendaDetailScreen> {
  Evento? _evento;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    try {
      final evento = await ref.read(agendaApiProvider).detalhe(widget.slug);
      if (!mounted) return;
      setState(() {
        _evento = evento;
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

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_error != null || _evento == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(_error ?? 'Evento não encontrado.')),
      );
    }

    final evento = _evento!;
    final imagem = evento.bannerImageUrl ?? evento.imageUrl;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 200,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: imagem != null
                  ? CachedNetworkImage(imageUrl: imagem, fit: BoxFit.cover)
                  : Container(color: AppColors.brown),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(evento.title, style: Theme.of(context).textTheme.titleLarge),
                  const SizedBox(height: 12),
                  if (evento.startDate != null)
                    _InfoRow(icon: Icons.calendar_today_outlined, text: formatarDataHora(evento.startDate)),
                  if (evento.address != null)
                    _InfoRow(icon: Icons.location_on_outlined, text: evento.address!),
                  if (evento.city != null)
                    _InfoRow(
                      icon: Icons.map_outlined,
                      text: [evento.city, evento.state].whereType<String>().join(' / '),
                    ),
                  if (evento.address != null) ...[
                    const SizedBox(height: 12),
                    OutlinedButton.icon(
                      onPressed: () => launchUrl(
                        Uri.https('maps.google.com', '/', {'q': evento.address!}),
                        mode: LaunchMode.externalApplication,
                      ),
                      icon: const Icon(Icons.map),
                      label: const Text('Ver no Google Maps'),
                    ),
                  ],
                  if (evento.description != null && evento.description!.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    Text(evento.description!),
                  ],
                  const SizedBox(height: 24),
                  Text('Expositores Confirmados', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  if (evento.expositores == null || evento.expositores!.isEmpty)
                    const Text('Nenhum expositor confirmado ainda.')
                  else
                    Wrap(
                      spacing: 12,
                      runSpacing: 12,
                      children: [
                        for (final expositor in evento.expositores!)
                          InkWell(
                            onTap: () => context.push('/lojas/${expositor.slug}'),
                            child: Column(
                              children: [
                                CircleAvatar(
                                  radius: 28,
                                  backgroundColor: AppColors.accentYellow,
                                  backgroundImage: expositor.logoUrl != null
                                      ? CachedNetworkImageProvider(expositor.logoUrl!)
                                      : null,
                                  child: expositor.logoUrl == null
                                      ? const Icon(Icons.storefront_rounded, color: AppColors.brown)
                                      : null,
                                ),
                                const SizedBox(height: 4),
                                SizedBox(
                                  width: 72,
                                  child: Text(
                                    expositor.name,
                                    textAlign: TextAlign.center,
                                    maxLines: 2,
                                    overflow: TextOverflow.ellipsis,
                                    style: const TextStyle(fontSize: 12),
                                  ),
                                ),
                              ],
                            ),
                          ),
                      ],
                    ),
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  const _InfoRow({required this.icon, required this.text});

  final IconData icon;
  final String text;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(icon, size: 18, color: AppColors.textSecondary),
          const SizedBox(width: 8),
          Expanded(child: Text(text)),
        ],
      ),
    );
  }
}

import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../application/noticias_controller.dart';
import '../domain/noticia.dart';

/// Lista as notícias/posts publicados — destino do "Ver tudo" do carrossel
/// "Nossas Notícias e Blog" da Home. Cada card abre a notícia completa
/// dentro do próprio app (nunca no navegador).
class NoticiasListScreen extends ConsumerWidget {
  const NoticiasListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(noticiasControllerProvider);
    final controller = ref.read(noticiasControllerProvider.notifier);

    return Scaffold(
      appBar: const FelAppBar(title: 'Notícias e Blog'),
      body: RefreshIndicator(
        onRefresh: controller.tentarNovamente,
        child: state.isLoading
            ? const Center(child: CircularProgressIndicator())
            : state.error != null && state.items.isEmpty
                ? _ErrorState(message: state.error!, onRetry: controller.tentarNovamente)
                : state.items.isEmpty
                    ? const Center(child: Text('Nenhuma notícia encontrada.'))
                    : ListView.separated(
                        padding: const EdgeInsets.all(16),
                        itemCount: state.items.length + 1,
                        separatorBuilder: (context, index) => const SizedBox(height: 12),
                        itemBuilder: (context, index) {
                          if (index == state.items.length) {
                            return Center(
                              child: state.hasMore
                                  ? OutlinedButton(
                                      onPressed:
                                          state.isLoadingMore ? null : controller.carregarMais,
                                      child: state.isLoadingMore
                                          ? const SizedBox(
                                              height: 20,
                                              width: 20,
                                              child: CircularProgressIndicator(strokeWidth: 2),
                                            )
                                          : const Text('Carregar mais'),
                                    )
                                  : null,
                            );
                          }
                          final noticia = state.items[index];
                          return _NoticiaCard(
                            noticia: noticia,
                            onTap: () => context.push('/noticias/${noticia.slug}'),
                          );
                        },
                      ),
      ),
    );
  }
}

class _NoticiaCard extends StatelessWidget {
  const _NoticiaCard({required this.noticia, required this.onTap});

  final Noticia noticia;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return InkWell(
      onTap: onTap,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        decoration: BoxDecoration(
          color: AppColors.surface,
          borderRadius: BorderRadius.circular(16),
          border: Border.all(color: const Color(0xFFE5E5E5)),
        ),
        clipBehavior: Clip.antiAlias,
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            SizedBox(
              width: 96,
              height: 96,
              child: noticia.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: noticia.imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
                      errorWidget: (context, url, error) => const _PlaceholderImage(),
                    )
                  : const _PlaceholderImage(),
            ),
            Expanded(
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      noticia.title,
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                    ),
                    if (noticia.publishedAt != null) ...[
                      const SizedBox(height: 6),
                      Text(
                        DateFormat('dd/MM/yyyy').format(noticia.publishedAt!),
                        style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                      ),
                    ],
                  ],
                ),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _PlaceholderImage extends StatelessWidget {
  const _PlaceholderImage();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF0F0F0),
      child: const Icon(Icons.article_outlined, color: Color(0xFFBDBDBD), size: 28),
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded, size: 48, color: AppColors.textSecondary),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: onRetry, child: const Text('Tentar novamente')),
          ],
        ),
      ),
    );
  }
}

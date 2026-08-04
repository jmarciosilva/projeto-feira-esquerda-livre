import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../../auth/domain/expositor_summary.dart';
import '../application/lojas_controller.dart';

/// Lista todas as lojas ativas — destino do "ver tudo" do carrossel de
/// expositores na Home.
class LojasListScreen extends ConsumerWidget {
  const LojasListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(lojasControllerProvider);
    final controller = ref.read(lojasControllerProvider.notifier);

    return Scaffold(
      appBar: const FelAppBar(title: 'Lojas'),
      body: RefreshIndicator(
        onRefresh: controller.tentarNovamente,
        child: state.isLoading
            ? const Center(child: CircularProgressIndicator())
            : state.error != null && state.items.isEmpty
                ? _ErrorState(message: state.error!, onRetry: controller.tentarNovamente)
                : state.items.isEmpty
                    ? const Center(child: Text('Nenhuma loja encontrada.'))
                    : CustomScrollView(
                        slivers: [
                          SliverPadding(
                            padding: const EdgeInsets.all(16),
                            sliver: SliverGrid(
                              gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                                crossAxisCount: 2,
                                mainAxisSpacing: 16,
                                crossAxisSpacing: 16,
                                mainAxisExtent: 200,
                              ),
                              delegate: SliverChildBuilderDelegate(
                                (context, index) {
                                  final loja = state.items[index];
                                  return _LojaCard(
                                    expositor: loja,
                                    onTap: () => context.push('/lojas/${loja.slug}'),
                                  );
                                },
                                childCount: state.items.length,
                              ),
                            ),
                          ),
                          SliverToBoxAdapter(
                            child: Padding(
                              padding: const EdgeInsets.only(bottom: 24),
                              child: Center(
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
                              ),
                            ),
                          ),
                        ],
                      ),
      ),
    );
  }
}

class _LojaCard extends StatelessWidget {
  const _LojaCard({required this.expositor, required this.onTap});

  final ExpositorSummary expositor;
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
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: expositor.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: expositor.imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
                      errorWidget: (context, url, error) => const _PlaceholderImage(),
                    )
                  : const _PlaceholderImage(),
            ),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    expositor.name,
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 15, fontWeight: FontWeight.w600),
                  ),
                  if (expositor.city != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      [expositor.city, expositor.state].whereType<String>().join(' - '),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                      style: const TextStyle(fontSize: 13, color: AppColors.textSecondary),
                    ),
                  ],
                ],
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
      child: const Icon(Icons.storefront_outlined, color: Color(0xFFBDBDBD), size: 32),
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

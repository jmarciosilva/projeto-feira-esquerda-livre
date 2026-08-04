import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../../../shared/widgets/product_card.dart';
import '../application/catalogo_controller.dart';
import '../data/catalogo_api.dart';

class CatalogoListScreen extends ConsumerStatefulWidget {
  const CatalogoListScreen({
    super.key,
    required this.eixo,
    required this.title,
    this.initialCategoriaId,
  });

  final Eixo eixo;
  final String title;

  /// Categoria pré-selecionada ao chegar por um deep link (ex.: chip de
  /// categoria na Home) — aplicada uma vez, no primeiro frame.
  final int? initialCategoriaId;

  @override
  ConsumerState<CatalogoListScreen> createState() => _CatalogoListScreenState();
}

class _CatalogoListScreenState extends ConsumerState<CatalogoListScreen> {
  final _buscaController = TextEditingController();

  @override
  void initState() {
    super.initState();
    final categoriaId = widget.initialCategoriaId;
    if (categoriaId != null) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          ref.read(catalogoControllerProvider(widget.eixo).notifier).filtrarPorCategoria(categoriaId);
        }
      });
    }
  }

  @override
  void dispose() {
    _buscaController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(catalogoControllerProvider(widget.eixo));
    final controller = ref.read(catalogoControllerProvider(widget.eixo).notifier);

    return Scaffold(
      appBar: FelAppBar(title: widget.title),
      body: RefreshIndicator(
        onRefresh: () => controller.buscar(_buscaController.text.trim()),
        child: CustomScrollView(
          slivers: [
            SliverToBoxAdapter(
              child: Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: TextField(
                  controller: _buscaController,
                  textInputAction: TextInputAction.search,
                  decoration: const InputDecoration(
                    hintText: 'Buscar...',
                    prefixIcon: Icon(Icons.search),
                  ),
                  onSubmitted: controller.buscar,
                ),
              ),
            ),
            if (state.categorias.isNotEmpty)
              SliverToBoxAdapter(
                child: SizedBox(
                  height: 44,
                  child: ListView(
                    scrollDirection: Axis.horizontal,
                    padding: const EdgeInsets.symmetric(horizontal: 16),
                    children: [
                      _CategoriaChip(
                        label: 'Todas',
                        selected: state.categoriaId == null,
                        onTap: () => controller.filtrarPorCategoria(null),
                      ),
                      for (final categoria in state.categorias)
                        _CategoriaChip(
                          label: categoria.name,
                          selected: state.categoriaId == categoria.id,
                          onTap: () => controller.filtrarPorCategoria(categoria.id),
                        ),
                    ],
                  ),
                ),
              ),
            const SliverToBoxAdapter(child: SizedBox(height: 8)),
            if (state.isLoading)
              const SliverFillRemaining(child: Center(child: CircularProgressIndicator()))
            else if (state.error != null && state.items.isEmpty)
              SliverFillRemaining(
                child: _ErrorState(message: state.error!, onRetry: controller.tentarNovamente),
              )
            else if (state.items.isEmpty)
              const SliverFillRemaining(
                child: Center(child: Text('Nenhum item encontrado.')),
              )
            else ...[
              SliverPadding(
                padding: const EdgeInsets.all(16),
                sliver: SliverGrid(
                  gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                    crossAxisCount: 2,
                    mainAxisSpacing: 16,
                    crossAxisSpacing: 16,
                    mainAxisExtent: 270,
                  ),
                  delegate: SliverChildBuilderDelegate(
                    (context, index) {
                      final product = state.items[index];
                      return ProductCard(
                        product: product,
                        onTap: () => context.push('/${widget.eixo.path}/${product.id}'),
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
                            onPressed: state.isLoadingMore ? null : controller.carregarMais,
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
          ],
        ),
      ),
    );
  }
}

class _CategoriaChip extends StatelessWidget {
  const _CategoriaChip({required this.label, required this.selected, required this.onTap});

  final String label;
  final bool selected;
  final VoidCallback onTap;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(right: 8),
      child: ChoiceChip(
        label: Text(label),
        selected: selected,
        onSelected: (_) => onTap(),
        selectedColor: AppColors.accentYellow,
        labelStyle: TextStyle(
          color: selected ? AppColors.brown : AppColors.textSecondary,
          fontWeight: selected ? FontWeight.w600 : FontWeight.normal,
        ),
      ),
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

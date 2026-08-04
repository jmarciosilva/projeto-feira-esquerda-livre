import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../../../shared/widgets/product_card.dart';
import '../../../shared/widgets/whatsapp_button.dart';
import '../../auth/domain/expositor_summary.dart';
import '../../catalogo/data/catalogo_api.dart';
import '../../catalogo/domain/categoria.dart';
import '../../contato/domain/contato_info.dart';
import '../../noticias/domain/noticia.dart';
import '../application/home_controller.dart';

/// Primeira tela do app: carrosséis de Produtos, Expositores (lojas),
/// Serviços, Cuidados & Bem Viver e Notícias e Blog, seguidos da seção
/// "Quer vender seus produtos na Feira?".
class HomeScreen extends ConsumerWidget {
  const HomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(homeControllerProvider);
    final controller = ref.read(homeControllerProvider.notifier);

    return Scaffold(
      appBar: const FelAppBar(),
      body: RefreshIndicator(
        onRefresh: controller.tentarNovamente,
        child: state.isLoading
            ? const Center(child: CircularProgressIndicator())
            : state.error != null &&
                    state.produtos.isEmpty &&
                    state.lojas.isEmpty &&
                    state.servicos.isEmpty &&
                    state.cuidados.isEmpty &&
                    state.noticias.isEmpty
                ? _ErrorState(message: state.error!, onRetry: controller.tentarNovamente)
                : ListView(
                    padding: const EdgeInsets.only(bottom: 24),
                    children: [
                      if (state.categoriasProdutos.isNotEmpty)
                        _CategoriaChips(categorias: state.categoriasProdutos),
                      _CarouselSection(
                        title: 'Nossos Produtos',
                        onVerTudo: () => context.push('/${Eixo.produto.path}'),
                        itemCount: state.produtos.length,
                        itemBuilder: (context, index) {
                          final produto = state.produtos[index];
                          return SizedBox(
                            width: 160,
                            child: ProductCard(
                              product: produto,
                              onTap: () =>
                                  context.push('/${Eixo.produto.path}/${produto.id}'),
                            ),
                          );
                        },
                      ),
                      _CarouselSection(
                        title: 'Nossas Principais Lojas',
                        onVerTudo: () => context.push('/lojas'),
                        itemCount: state.lojas.length,
                        itemBuilder: (context, index) {
                          final loja = state.lojas[index];
                          return SizedBox(
                            width: 150,
                            child: _ExpositorCard(
                              expositor: loja,
                              onTap: () => context.push('/lojas/${loja.slug}'),
                            ),
                          );
                        },
                      ),
                      _CarouselSection(
                        title: 'Profissionais e Serviços',
                        onVerTudo: () => context.push('/${Eixo.servico.path}'),
                        itemCount: state.servicos.length,
                        itemBuilder: (context, index) {
                          final servico = state.servicos[index];
                          return SizedBox(
                            width: 160,
                            child: ProductCard(
                              product: servico,
                              onTap: () =>
                                  context.push('/${Eixo.servico.path}/${servico.id}'),
                            ),
                          );
                        },
                      ),
                      _CarouselSection(
                        title: 'Cuidados & Bem Viver',
                        onVerTudo: () => context.push('/${Eixo.cuidado.path}'),
                        itemCount: state.cuidados.length,
                        itemBuilder: (context, index) {
                          final cuidado = state.cuidados[index];
                          return SizedBox(
                            width: 160,
                            child: ProductCard(
                              product: cuidado,
                              onTap: () =>
                                  context.push('/${Eixo.cuidado.path}/${cuidado.id}'),
                            ),
                          );
                        },
                      ),
                      _CarouselSection(
                        title: 'Nossas Notícias e Blog',
                        onVerTudo: () => context.push('/noticias'),
                        itemCount: state.noticias.length,
                        itemBuilder: (context, index) {
                          final noticia = state.noticias[index];
                          return SizedBox(
                            width: 200,
                            child: _NoticiaCarouselCard(
                              noticia: noticia,
                              onTap: () => context.push('/noticias/${noticia.slug}'),
                            ),
                          );
                        },
                      ),
                      _VenderNaFeiraSection(contato: state.contato),
                    ],
                  ),
      ),
    );
  }
}

/// Fileira de categorias de produtos — mostrada antes do carrossel "Nossos
/// Produtos". Ao tocar numa categoria, abre o catálogo completo de produtos
/// já filtrado por ela (não filtra o carrossel da própria Home).
class _CategoriaChips extends StatelessWidget {
  const _CategoriaChips({required this.categorias});

  final List<Categoria> categorias;

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.only(top: 16),
      child: SizedBox(
        height: 40,
        child: ListView.separated(
          scrollDirection: Axis.horizontal,
          padding: const EdgeInsets.symmetric(horizontal: 16),
          itemCount: categorias.length,
          separatorBuilder: (context, index) => const SizedBox(width: 8),
          itemBuilder: (context, index) {
            final categoria = categorias[index];
            return ActionChip(
              label: Text(categoria.name),
              backgroundColor: AppColors.surface,
              side: const BorderSide(color: Color(0xFFE5E5E5)),
              labelStyle: const TextStyle(color: AppColors.brown, fontWeight: FontWeight.w600),
              onPressed: () => context.push('/${Eixo.produto.path}?categoria=${categoria.id}'),
            );
          },
        ),
      ),
    );
  }
}

class _CarouselSection extends StatelessWidget {
  const _CarouselSection({
    required this.title,
    required this.onVerTudo,
    required this.itemCount,
    required this.itemBuilder,
  });

  final String title;
  final VoidCallback onVerTudo;
  final int itemCount;
  final Widget Function(BuildContext, int) itemBuilder;

  @override
  Widget build(BuildContext context) {
    if (itemCount == 0) return const SizedBox.shrink();

    return Padding(
      padding: const EdgeInsets.only(top: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 0, 8, 8),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Expanded(
                  child: Row(
                    children: [
                      Image.asset('assets/images/logo_bird_transparent.png', height: 22),
                      const SizedBox(width: 8),
                      Flexible(
                        child: Text(
                          title,
                          overflow: TextOverflow.ellipsis,
                          style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                    ],
                  ),
                ),
                TextButton(onPressed: onVerTudo, child: const Text('Ver tudo')),
              ],
            ),
          ),
          SizedBox(
            height: 244,
            child: ListView.separated(
              scrollDirection: Axis.horizontal,
              padding: const EdgeInsets.symmetric(horizontal: 16),
              itemCount: itemCount,
              separatorBuilder: (context, index) => const SizedBox(width: 12),
              itemBuilder: itemBuilder,
            ),
          ),
        ],
      ),
    );
  }
}

class _ExpositorCard extends StatelessWidget {
  const _ExpositorCard({required this.expositor, required this.onTap});

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
              child: expositor.logoUrl != null
                  ? CachedNetworkImage(
                      imageUrl: expositor.logoUrl!,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
                      errorWidget: (context, url, error) => const _PlaceholderLoja(),
                    )
                  : const _PlaceholderLoja(),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Text(
                expositor.name,
                maxLines: 1,
                overflow: TextOverflow.ellipsis,
                style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
              ),
            ),
          ],
        ),
      ),
    );
  }
}

class _NoticiaCarouselCard extends StatelessWidget {
  const _NoticiaCarouselCard({required this.noticia, required this.onTap});

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
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: noticia.imageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: noticia.imageUrl!,
                      fit: BoxFit.cover,
                      placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
                      errorWidget: (context, url, error) => const _PlaceholderNoticia(),
                    )
                  : const _PlaceholderNoticia(),
            ),
            Padding(
              padding: const EdgeInsets.all(10),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    noticia.title,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 14, fontWeight: FontWeight.w600),
                  ),
                  if (noticia.publishedAt != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      DateFormat('dd/MM/yyyy').format(noticia.publishedAt!),
                      style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
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

class _PlaceholderNoticia extends StatelessWidget {
  const _PlaceholderNoticia();

  @override
  Widget build(BuildContext context) {
    return Container(
      color: const Color(0xFFF0F0F0),
      child: const Icon(Icons.article_outlined, color: Color(0xFFBDBDBD), size: 32),
    );
  }
}

/// Seção final da Home — convida quem visita a virar expositor, com três
/// canais de contato: página do site, e-mail e WhatsApp (sempre verde).
class _VenderNaFeiraSection extends StatelessWidget {
  const _VenderNaFeiraSection({required this.contato});

  final ContatoInfo? contato;

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.fromLTRB(16, 28, 16, 8),
      padding: const EdgeInsets.all(20),
      decoration: BoxDecoration(
        color: AppColors.accentYellow,
        borderRadius: BorderRadius.circular(16),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          Row(
            children: [
              Image.asset('assets/images/logo_bird_transparent.png', height: 26),
              const SizedBox(width: 10),
              const Expanded(
                child: Text(
                  'Quer vender seus produtos na nossa Feira?',
                  style: TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                    color: AppColors.brown,
                  ),
                ),
              ),
            ],
          ),
          const SizedBox(height: 10),
          const Text(
            'Junte-se aos nossos expositores e alcance quem valoriza produtos e '
            'serviços da economia solidária.',
            style: TextStyle(color: AppColors.brown),
          ),
          const SizedBox(height: 18),
          ElevatedButton(
            onPressed: () => context.push('/seja-um-expositor'),
            child: const Text('Quero ser um Expositor'),
          ),
          const SizedBox(height: 10),
          OutlinedButton.icon(
            onPressed: () => context.push('/contato'),
            icon: const Icon(Icons.email_outlined),
            label: const Text('Enviar Mensagem'),
          ),
          if (contato?.whatsapp != null && contato!.whatsapp!.isNotEmpty) ...[
            const SizedBox(height: 10),
            WhatsAppButton(
              telefone: contato!.whatsapp!,
              mensagem: 'Olá! Tenho interesse em vender meus produtos na Feira Esquerda Livre.',
              label: 'Falar no WhatsApp',
            ),
          ],
        ],
      ),
    );
  }
}

class _PlaceholderLoja extends StatelessWidget {
  const _PlaceholderLoja();

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
    return LayoutBuilder(
      builder: (context, constraints) => SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        child: ConstrainedBox(
          constraints: BoxConstraints(minHeight: constraints.maxHeight),
          child: Center(
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
          ),
        ),
      ),
    );
  }
}

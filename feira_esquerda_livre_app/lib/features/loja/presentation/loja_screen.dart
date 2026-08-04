import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/product_card.dart';
import '../../../shared/widgets/whatsapp_button.dart';
import '../data/loja_api.dart';
import '../data/loja_detalhe.dart';

class LojaScreen extends ConsumerStatefulWidget {
  const LojaScreen({super.key, required this.slug});

  final String slug;

  @override
  ConsumerState<LojaScreen> createState() => _LojaScreenState();
}

class _LojaScreenState extends ConsumerState<LojaScreen> {
  LojaDetalhe? _loja;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    try {
      final loja = await ref.read(lojaApiProvider).detalhe(widget.slug);
      if (!mounted) return;
      setState(() {
        _loja = loja;
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

    if (_error != null || _loja == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(_error ?? 'Loja não encontrada.')),
      );
    }

    final expositor = _loja!.expositor;
    final products = _loja!.products;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: 160,
            pinned: true,
            title: Text(expositor.name),
            flexibleSpace: FlexibleSpaceBar(
              background: expositor.imageUrl != null
                  ? CachedNetworkImage(imageUrl: expositor.imageUrl!, fit: BoxFit.cover)
                  : Container(color: AppColors.brown),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      CircleAvatar(
                        radius: 32,
                        backgroundColor: AppColors.accentYellow,
                        backgroundImage:
                            expositor.logoUrl != null ? CachedNetworkImageProvider(expositor.logoUrl!) : null,
                        child: expositor.logoUrl == null
                            ? const Icon(Icons.storefront_rounded, color: AppColors.brown)
                            : null,
                      ),
                      const SizedBox(width: 16),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(expositor.name, style: Theme.of(context).textTheme.titleLarge),
                            if (expositor.city != null)
                              Text(
                                [expositor.city, expositor.state].whereType<String>().join(' / '),
                                style: const TextStyle(color: AppColors.textSecondary),
                              ),
                          ],
                        ),
                      ),
                    ],
                  ),
                  if (expositor.description != null && expositor.description!.isNotEmpty) ...[
                    const SizedBox(height: 16),
                    Text(expositor.description!),
                  ],
                  const SizedBox(height: 16),
                  Wrap(
                    spacing: 8,
                    children: [
                      if (expositor.whatsapp != null && expositor.whatsapp!.isNotEmpty)
                        WhatsAppButton(telefone: expositor.whatsapp!),
                      if (expositor.instagramUrl != null && expositor.instagramUrl!.isNotEmpty)
                        OutlinedButton.icon(
                          onPressed: () => launchUrl(
                            Uri.parse(expositor.instagramUrl!),
                            mode: LaunchMode.externalApplication,
                          ),
                          icon: const Icon(Icons.camera_alt_outlined, size: 18),
                          label: const Text('Instagram'),
                        ),
                    ],
                  ),
                  const SizedBox(height: 20),
                  Text('Produtos', style: Theme.of(context).textTheme.titleMedium),
                ],
              ),
            ),
          ),
          if (products.isEmpty)
            const SliverToBoxAdapter(
              child: Padding(
                padding: EdgeInsets.all(24),
                child: Center(child: Text('Esta loja ainda não tem itens disponíveis.')),
              ),
            )
          else
            SliverPadding(
              padding: const EdgeInsets.fromLTRB(16, 0, 16, 24),
              sliver: SliverGrid(
                gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
                  crossAxisCount: 2,
                  mainAxisSpacing: 16,
                  crossAxisSpacing: 16,
                  mainAxisExtent: 270,
                ),
                delegate: SliverChildBuilderDelegate(
                  (context, index) {
                    final product = products[index];
                    return ProductCard(
                      product: product,
                      onTap: () => context.push('/${_pathForItemType(product.itemType)}/${product.id}'),
                    );
                  },
                  childCount: products.length,
                ),
              ),
            ),
        ],
      ),
    );
  }

  String _pathForItemType(String itemType) {
    return switch (itemType) {
      'servico' => 'servicos',
      'cuidado' => 'cuidados',
      _ => 'produtos',
    };
  }
}

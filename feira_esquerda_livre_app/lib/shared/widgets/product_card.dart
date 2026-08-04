import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../../features/catalogo/domain/product.dart';

/// Card de produto usado nas grades de catálogo. Segue o padrão do site:
/// imagem quadrada, nome, preço em destaque, sem modais — toque leva para
/// a página do produto (ver módulo 3.2 do roadmap do backend).
class ProductCard extends StatelessWidget {
  const ProductCard({super.key, required this.product, required this.onTap});

  final Product product;
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
          // stretch (em vez de start) faz a imagem e o texto ocuparem toda
          // a largura do card; Expanded (em vez de AspectRatio fixo) faz a
          // imagem absorver o espaço restante da altura fixa do grid
          // (mainAxisExtent) — assim o texto abaixo nunca estoura o card,
          // mesmo com fonte do sistema maior (acessibilidade) ou nomes de
          // 2 linhas.
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Expanded(
              child: product.mainImageUrl != null
                  ? CachedNetworkImage(
                      imageUrl: product.mainImageUrl!,
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
                    product.name,
                    maxLines: 2,
                    overflow: TextOverflow.ellipsis,
                    style: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 6),
                  Text(
                    product.priceLabel,
                    style: const TextStyle(
                      fontSize: 16,
                      fontWeight: FontWeight.bold,
                      color: AppColors.warning,
                    ),
                  ),
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
      child: const Icon(Icons.image_not_supported_outlined, color: Color(0xFFBDBDBD), size: 32),
    );
  }
}

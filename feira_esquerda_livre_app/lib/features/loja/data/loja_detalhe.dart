import '../../auth/domain/expositor_summary.dart';
import '../../catalogo/domain/product.dart';

/// Resposta de `GET /lojas/{slug}`: `{ expositor, products }`.
class LojaDetalhe {
  LojaDetalhe({required this.expositor, required this.products});

  final ExpositorSummary expositor;
  final List<Product> products;

  factory LojaDetalhe.fromJson(Map<String, dynamic> json) {
    return LojaDetalhe(
      expositor: ExpositorSummary.fromJson(json['expositor'] as Map<String, dynamic>),
      products: (json['products'] as List)
          .map((e) => Product.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

import 'package:freezed_annotation/freezed_annotation.dart';

import '../../auth/domain/expositor_summary.dart';
import 'product_category.dart';
import 'product_faq.dart';
import 'product_image.dart';

part 'product.freezed.dart';
part 'product.g.dart';

/// Espelha `ProductResource` (`app/Http/Resources/Api/V1/ProductResource.php`
/// no backend). Usado tanto na listagem quanto no detalhe — os campos que só
/// vêm quando a relação é carregada (`expositor`, `category`, `faqs`) são
/// nulos na listagem e preenchidos no detalhe.
@freezed
abstract class Product with _$Product {
  const Product._();

  const factory Product({
    required int id,
    required String name,
    required String slug,
    @JsonKey(name: 'item_type') required String itemType,
    String? description,
    required double price,
    @JsonKey(name: 'price_type') String? priceType,
    String? modality,
    @JsonKey(name: 'duration_min') int? durationMin,
    @JsonKey(name: 'has_stock') required bool hasStock,
    @JsonKey(name: 'stock_quantity') int? stockQuantity,
    @JsonKey(name: 'is_digital') required bool isDigital,
    @JsonKey(name: 'is_featured') required bool isFeatured,
    @JsonKey(name: 'is_active') required bool isActive,
    double? weight,
    double? height,
    double? width,
    double? length,
    @JsonKey(name: 'main_image_url') String? mainImageUrl,
    @Default([]) List<ProductImage> images,
    ExpositorSummary? expositor,
    ProductCategory? category,
    List<ProductFaq>? faqs,
    @JsonKey(name: 'created_at') DateTime? createdAt,
  }) = _Product;

  factory Product.fromJson(Map<String, dynamic> json) => _$ProductFromJson(json);

  String get priceLabel {
    if (priceType == 'sob_consulta') return 'Sob consulta';
    final formatted = price.toStringAsFixed(2).replaceAll('.', ',');
    final suffix = switch (priceType) {
      'por_hora' => '/hora',
      'por_sessao' => '/sessão',
      _ => '',
    };
    return 'R\$ $formatted$suffix';
  }
}

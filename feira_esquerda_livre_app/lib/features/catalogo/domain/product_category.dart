import 'package:freezed_annotation/freezed_annotation.dart';

part 'product_category.freezed.dart';
part 'product_category.g.dart';

/// Categoria resumida embutida em um produto (`ProductResource.category`).
@freezed
abstract class ProductCategory with _$ProductCategory {
  const factory ProductCategory({required int id, required String name, required String slug}) =
      _ProductCategory;

  factory ProductCategory.fromJson(Map<String, dynamic> json) => _$ProductCategoryFromJson(json);
}

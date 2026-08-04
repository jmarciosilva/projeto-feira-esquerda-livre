// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'product.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Product _$ProductFromJson(Map<String, dynamic> json) => _Product(
  id: (json['id'] as num).toInt(),
  name: json['name'] as String,
  slug: json['slug'] as String,
  itemType: json['item_type'] as String,
  description: json['description'] as String?,
  price: (json['price'] as num).toDouble(),
  priceType: json['price_type'] as String?,
  modality: json['modality'] as String?,
  durationMin: (json['duration_min'] as num?)?.toInt(),
  hasStock: json['has_stock'] as bool,
  stockQuantity: (json['stock_quantity'] as num?)?.toInt(),
  isDigital: json['is_digital'] as bool,
  isFeatured: json['is_featured'] as bool,
  isActive: json['is_active'] as bool,
  weight: (json['weight'] as num?)?.toDouble(),
  height: (json['height'] as num?)?.toDouble(),
  width: (json['width'] as num?)?.toDouble(),
  length: (json['length'] as num?)?.toDouble(),
  mainImageUrl: json['main_image_url'] as String?,
  images:
      (json['images'] as List<dynamic>?)
          ?.map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
          .toList() ??
      const [],
  expositor: json['expositor'] == null
      ? null
      : ExpositorSummary.fromJson(json['expositor'] as Map<String, dynamic>),
  category: json['category'] == null
      ? null
      : ProductCategory.fromJson(json['category'] as Map<String, dynamic>),
  faqs: (json['faqs'] as List<dynamic>?)
      ?.map((e) => ProductFaq.fromJson(e as Map<String, dynamic>))
      .toList(),
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
);

Map<String, dynamic> _$ProductToJson(_Product instance) => <String, dynamic>{
  'id': instance.id,
  'name': instance.name,
  'slug': instance.slug,
  'item_type': instance.itemType,
  'description': instance.description,
  'price': instance.price,
  'price_type': instance.priceType,
  'modality': instance.modality,
  'duration_min': instance.durationMin,
  'has_stock': instance.hasStock,
  'stock_quantity': instance.stockQuantity,
  'is_digital': instance.isDigital,
  'is_featured': instance.isFeatured,
  'is_active': instance.isActive,
  'weight': instance.weight,
  'height': instance.height,
  'width': instance.width,
  'length': instance.length,
  'main_image_url': instance.mainImageUrl,
  'images': instance.images,
  'expositor': instance.expositor,
  'category': instance.category,
  'faqs': instance.faqs,
  'created_at': instance.createdAt?.toIso8601String(),
};

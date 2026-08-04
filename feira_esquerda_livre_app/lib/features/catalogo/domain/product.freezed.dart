// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'product.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$Product {

 int get id; String get name; String get slug;@JsonKey(name: 'item_type') String get itemType; String? get description; double get price;@JsonKey(name: 'price_type') String? get priceType; String? get modality;@JsonKey(name: 'duration_min') int? get durationMin;@JsonKey(name: 'has_stock') bool get hasStock;@JsonKey(name: 'stock_quantity') int? get stockQuantity;@JsonKey(name: 'is_digital') bool get isDigital;@JsonKey(name: 'is_featured') bool get isFeatured;@JsonKey(name: 'is_active') bool get isActive; double? get weight; double? get height; double? get width; double? get length;@JsonKey(name: 'main_image_url') String? get mainImageUrl; List<ProductImage> get images; ExpositorSummary? get expositor; ProductCategory? get category; List<ProductFaq>? get faqs;@JsonKey(name: 'created_at') DateTime? get createdAt;
/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$ProductCopyWith<Product> get copyWith => _$ProductCopyWithImpl<Product>(this as Product, _$identity);

  /// Serializes this Product to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Product&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.itemType, itemType) || other.itemType == itemType)&&(identical(other.description, description) || other.description == description)&&(identical(other.price, price) || other.price == price)&&(identical(other.priceType, priceType) || other.priceType == priceType)&&(identical(other.modality, modality) || other.modality == modality)&&(identical(other.durationMin, durationMin) || other.durationMin == durationMin)&&(identical(other.hasStock, hasStock) || other.hasStock == hasStock)&&(identical(other.stockQuantity, stockQuantity) || other.stockQuantity == stockQuantity)&&(identical(other.isDigital, isDigital) || other.isDigital == isDigital)&&(identical(other.isFeatured, isFeatured) || other.isFeatured == isFeatured)&&(identical(other.isActive, isActive) || other.isActive == isActive)&&(identical(other.weight, weight) || other.weight == weight)&&(identical(other.height, height) || other.height == height)&&(identical(other.width, width) || other.width == width)&&(identical(other.length, length) || other.length == length)&&(identical(other.mainImageUrl, mainImageUrl) || other.mainImageUrl == mainImageUrl)&&const DeepCollectionEquality().equals(other.images, images)&&(identical(other.expositor, expositor) || other.expositor == expositor)&&(identical(other.category, category) || other.category == category)&&const DeepCollectionEquality().equals(other.faqs, faqs)&&(identical(other.createdAt, createdAt) || other.createdAt == createdAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hashAll([runtimeType,id,name,slug,itemType,description,price,priceType,modality,durationMin,hasStock,stockQuantity,isDigital,isFeatured,isActive,weight,height,width,length,mainImageUrl,const DeepCollectionEquality().hash(images),expositor,category,const DeepCollectionEquality().hash(faqs),createdAt]);

@override
String toString() {
  return 'Product(id: $id, name: $name, slug: $slug, itemType: $itemType, description: $description, price: $price, priceType: $priceType, modality: $modality, durationMin: $durationMin, hasStock: $hasStock, stockQuantity: $stockQuantity, isDigital: $isDigital, isFeatured: $isFeatured, isActive: $isActive, weight: $weight, height: $height, width: $width, length: $length, mainImageUrl: $mainImageUrl, images: $images, expositor: $expositor, category: $category, faqs: $faqs, createdAt: $createdAt)';
}


}

/// @nodoc
abstract mixin class $ProductCopyWith<$Res>  {
  factory $ProductCopyWith(Product value, $Res Function(Product) _then) = _$ProductCopyWithImpl;
@useResult
$Res call({
 int id, String name, String slug,@JsonKey(name: 'item_type') String itemType, String? description, double price,@JsonKey(name: 'price_type') String? priceType, String? modality,@JsonKey(name: 'duration_min') int? durationMin,@JsonKey(name: 'has_stock') bool hasStock,@JsonKey(name: 'stock_quantity') int? stockQuantity,@JsonKey(name: 'is_digital') bool isDigital,@JsonKey(name: 'is_featured') bool isFeatured,@JsonKey(name: 'is_active') bool isActive, double? weight, double? height, double? width, double? length,@JsonKey(name: 'main_image_url') String? mainImageUrl, List<ProductImage> images, ExpositorSummary? expositor, ProductCategory? category, List<ProductFaq>? faqs,@JsonKey(name: 'created_at') DateTime? createdAt
});


$ExpositorSummaryCopyWith<$Res>? get expositor;$ProductCategoryCopyWith<$Res>? get category;

}
/// @nodoc
class _$ProductCopyWithImpl<$Res>
    implements $ProductCopyWith<$Res> {
  _$ProductCopyWithImpl(this._self, this._then);

  final Product _self;
  final $Res Function(Product) _then;

/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? itemType = null,Object? description = freezed,Object? price = null,Object? priceType = freezed,Object? modality = freezed,Object? durationMin = freezed,Object? hasStock = null,Object? stockQuantity = freezed,Object? isDigital = null,Object? isFeatured = null,Object? isActive = null,Object? weight = freezed,Object? height = freezed,Object? width = freezed,Object? length = freezed,Object? mainImageUrl = freezed,Object? images = null,Object? expositor = freezed,Object? category = freezed,Object? faqs = freezed,Object? createdAt = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,itemType: null == itemType ? _self.itemType : itemType // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,price: null == price ? _self.price : price // ignore: cast_nullable_to_non_nullable
as double,priceType: freezed == priceType ? _self.priceType : priceType // ignore: cast_nullable_to_non_nullable
as String?,modality: freezed == modality ? _self.modality : modality // ignore: cast_nullable_to_non_nullable
as String?,durationMin: freezed == durationMin ? _self.durationMin : durationMin // ignore: cast_nullable_to_non_nullable
as int?,hasStock: null == hasStock ? _self.hasStock : hasStock // ignore: cast_nullable_to_non_nullable
as bool,stockQuantity: freezed == stockQuantity ? _self.stockQuantity : stockQuantity // ignore: cast_nullable_to_non_nullable
as int?,isDigital: null == isDigital ? _self.isDigital : isDigital // ignore: cast_nullable_to_non_nullable
as bool,isFeatured: null == isFeatured ? _self.isFeatured : isFeatured // ignore: cast_nullable_to_non_nullable
as bool,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,weight: freezed == weight ? _self.weight : weight // ignore: cast_nullable_to_non_nullable
as double?,height: freezed == height ? _self.height : height // ignore: cast_nullable_to_non_nullable
as double?,width: freezed == width ? _self.width : width // ignore: cast_nullable_to_non_nullable
as double?,length: freezed == length ? _self.length : length // ignore: cast_nullable_to_non_nullable
as double?,mainImageUrl: freezed == mainImageUrl ? _self.mainImageUrl : mainImageUrl // ignore: cast_nullable_to_non_nullable
as String?,images: null == images ? _self.images : images // ignore: cast_nullable_to_non_nullable
as List<ProductImage>,expositor: freezed == expositor ? _self.expositor : expositor // ignore: cast_nullable_to_non_nullable
as ExpositorSummary?,category: freezed == category ? _self.category : category // ignore: cast_nullable_to_non_nullable
as ProductCategory?,faqs: freezed == faqs ? _self.faqs : faqs // ignore: cast_nullable_to_non_nullable
as List<ProductFaq>?,createdAt: freezed == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}
/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ExpositorSummaryCopyWith<$Res>? get expositor {
    if (_self.expositor == null) {
    return null;
  }

  return $ExpositorSummaryCopyWith<$Res>(_self.expositor!, (value) {
    return _then(_self.copyWith(expositor: value));
  });
}/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ProductCategoryCopyWith<$Res>? get category {
    if (_self.category == null) {
    return null;
  }

  return $ProductCategoryCopyWith<$Res>(_self.category!, (value) {
    return _then(_self.copyWith(category: value));
  });
}
}


/// Adds pattern-matching-related methods to [Product].
extension ProductPatterns on Product {
/// A variant of `map` that fallback to returning `orElse`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Product value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Product() when $default != null:
return $default(_that);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// Callbacks receives the raw object, upcasted.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case final Subclass2 value:
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Product value)  $default,){
final _that = this;
switch (_that) {
case _Product():
return $default(_that);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `map` that fallback to returning `null`.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case final Subclass value:
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Product value)?  $default,){
final _that = this;
switch (_that) {
case _Product() when $default != null:
return $default(_that);case _:
  return null;

}
}
/// A variant of `when` that fallback to an `orElse` callback.
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return orElse();
/// }
/// ```

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String name,  String slug, @JsonKey(name: 'item_type')  String itemType,  String? description,  double price, @JsonKey(name: 'price_type')  String? priceType,  String? modality, @JsonKey(name: 'duration_min')  int? durationMin, @JsonKey(name: 'has_stock')  bool hasStock, @JsonKey(name: 'stock_quantity')  int? stockQuantity, @JsonKey(name: 'is_digital')  bool isDigital, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'is_active')  bool isActive,  double? weight,  double? height,  double? width,  double? length, @JsonKey(name: 'main_image_url')  String? mainImageUrl,  List<ProductImage> images,  ExpositorSummary? expositor,  ProductCategory? category,  List<ProductFaq>? faqs, @JsonKey(name: 'created_at')  DateTime? createdAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Product() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.itemType,_that.description,_that.price,_that.priceType,_that.modality,_that.durationMin,_that.hasStock,_that.stockQuantity,_that.isDigital,_that.isFeatured,_that.isActive,_that.weight,_that.height,_that.width,_that.length,_that.mainImageUrl,_that.images,_that.expositor,_that.category,_that.faqs,_that.createdAt);case _:
  return orElse();

}
}
/// A `switch`-like method, using callbacks.
///
/// As opposed to `map`, this offers destructuring.
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case Subclass2(:final field2):
///     return ...;
/// }
/// ```

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String name,  String slug, @JsonKey(name: 'item_type')  String itemType,  String? description,  double price, @JsonKey(name: 'price_type')  String? priceType,  String? modality, @JsonKey(name: 'duration_min')  int? durationMin, @JsonKey(name: 'has_stock')  bool hasStock, @JsonKey(name: 'stock_quantity')  int? stockQuantity, @JsonKey(name: 'is_digital')  bool isDigital, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'is_active')  bool isActive,  double? weight,  double? height,  double? width,  double? length, @JsonKey(name: 'main_image_url')  String? mainImageUrl,  List<ProductImage> images,  ExpositorSummary? expositor,  ProductCategory? category,  List<ProductFaq>? faqs, @JsonKey(name: 'created_at')  DateTime? createdAt)  $default,) {final _that = this;
switch (_that) {
case _Product():
return $default(_that.id,_that.name,_that.slug,_that.itemType,_that.description,_that.price,_that.priceType,_that.modality,_that.durationMin,_that.hasStock,_that.stockQuantity,_that.isDigital,_that.isFeatured,_that.isActive,_that.weight,_that.height,_that.width,_that.length,_that.mainImageUrl,_that.images,_that.expositor,_that.category,_that.faqs,_that.createdAt);case _:
  throw StateError('Unexpected subclass');

}
}
/// A variant of `when` that fallback to returning `null`
///
/// It is equivalent to doing:
/// ```dart
/// switch (sealedClass) {
///   case Subclass(:final field):
///     return ...;
///   case _:
///     return null;
/// }
/// ```

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String name,  String slug, @JsonKey(name: 'item_type')  String itemType,  String? description,  double price, @JsonKey(name: 'price_type')  String? priceType,  String? modality, @JsonKey(name: 'duration_min')  int? durationMin, @JsonKey(name: 'has_stock')  bool hasStock, @JsonKey(name: 'stock_quantity')  int? stockQuantity, @JsonKey(name: 'is_digital')  bool isDigital, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'is_active')  bool isActive,  double? weight,  double? height,  double? width,  double? length, @JsonKey(name: 'main_image_url')  String? mainImageUrl,  List<ProductImage> images,  ExpositorSummary? expositor,  ProductCategory? category,  List<ProductFaq>? faqs, @JsonKey(name: 'created_at')  DateTime? createdAt)?  $default,) {final _that = this;
switch (_that) {
case _Product() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.itemType,_that.description,_that.price,_that.priceType,_that.modality,_that.durationMin,_that.hasStock,_that.stockQuantity,_that.isDigital,_that.isFeatured,_that.isActive,_that.weight,_that.height,_that.width,_that.length,_that.mainImageUrl,_that.images,_that.expositor,_that.category,_that.faqs,_that.createdAt);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _Product extends Product {
  const _Product({required this.id, required this.name, required this.slug, @JsonKey(name: 'item_type') required this.itemType, this.description, required this.price, @JsonKey(name: 'price_type') this.priceType, this.modality, @JsonKey(name: 'duration_min') this.durationMin, @JsonKey(name: 'has_stock') required this.hasStock, @JsonKey(name: 'stock_quantity') this.stockQuantity, @JsonKey(name: 'is_digital') required this.isDigital, @JsonKey(name: 'is_featured') required this.isFeatured, @JsonKey(name: 'is_active') required this.isActive, this.weight, this.height, this.width, this.length, @JsonKey(name: 'main_image_url') this.mainImageUrl, final  List<ProductImage> images = const [], this.expositor, this.category, final  List<ProductFaq>? faqs, @JsonKey(name: 'created_at') this.createdAt}): _images = images,_faqs = faqs,super._();
  factory _Product.fromJson(Map<String, dynamic> json) => _$ProductFromJson(json);

@override final  int id;
@override final  String name;
@override final  String slug;
@override@JsonKey(name: 'item_type') final  String itemType;
@override final  String? description;
@override final  double price;
@override@JsonKey(name: 'price_type') final  String? priceType;
@override final  String? modality;
@override@JsonKey(name: 'duration_min') final  int? durationMin;
@override@JsonKey(name: 'has_stock') final  bool hasStock;
@override@JsonKey(name: 'stock_quantity') final  int? stockQuantity;
@override@JsonKey(name: 'is_digital') final  bool isDigital;
@override@JsonKey(name: 'is_featured') final  bool isFeatured;
@override@JsonKey(name: 'is_active') final  bool isActive;
@override final  double? weight;
@override final  double? height;
@override final  double? width;
@override final  double? length;
@override@JsonKey(name: 'main_image_url') final  String? mainImageUrl;
 final  List<ProductImage> _images;
@override@JsonKey() List<ProductImage> get images {
  if (_images is EqualUnmodifiableListView) return _images;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_images);
}

@override final  ExpositorSummary? expositor;
@override final  ProductCategory? category;
 final  List<ProductFaq>? _faqs;
@override List<ProductFaq>? get faqs {
  final value = _faqs;
  if (value == null) return null;
  if (_faqs is EqualUnmodifiableListView) return _faqs;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(value);
}

@override@JsonKey(name: 'created_at') final  DateTime? createdAt;

/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$ProductCopyWith<_Product> get copyWith => __$ProductCopyWithImpl<_Product>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$ProductToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _Product&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.itemType, itemType) || other.itemType == itemType)&&(identical(other.description, description) || other.description == description)&&(identical(other.price, price) || other.price == price)&&(identical(other.priceType, priceType) || other.priceType == priceType)&&(identical(other.modality, modality) || other.modality == modality)&&(identical(other.durationMin, durationMin) || other.durationMin == durationMin)&&(identical(other.hasStock, hasStock) || other.hasStock == hasStock)&&(identical(other.stockQuantity, stockQuantity) || other.stockQuantity == stockQuantity)&&(identical(other.isDigital, isDigital) || other.isDigital == isDigital)&&(identical(other.isFeatured, isFeatured) || other.isFeatured == isFeatured)&&(identical(other.isActive, isActive) || other.isActive == isActive)&&(identical(other.weight, weight) || other.weight == weight)&&(identical(other.height, height) || other.height == height)&&(identical(other.width, width) || other.width == width)&&(identical(other.length, length) || other.length == length)&&(identical(other.mainImageUrl, mainImageUrl) || other.mainImageUrl == mainImageUrl)&&const DeepCollectionEquality().equals(other._images, _images)&&(identical(other.expositor, expositor) || other.expositor == expositor)&&(identical(other.category, category) || other.category == category)&&const DeepCollectionEquality().equals(other._faqs, _faqs)&&(identical(other.createdAt, createdAt) || other.createdAt == createdAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hashAll([runtimeType,id,name,slug,itemType,description,price,priceType,modality,durationMin,hasStock,stockQuantity,isDigital,isFeatured,isActive,weight,height,width,length,mainImageUrl,const DeepCollectionEquality().hash(_images),expositor,category,const DeepCollectionEquality().hash(_faqs),createdAt]);

@override
String toString() {
  return 'Product(id: $id, name: $name, slug: $slug, itemType: $itemType, description: $description, price: $price, priceType: $priceType, modality: $modality, durationMin: $durationMin, hasStock: $hasStock, stockQuantity: $stockQuantity, isDigital: $isDigital, isFeatured: $isFeatured, isActive: $isActive, weight: $weight, height: $height, width: $width, length: $length, mainImageUrl: $mainImageUrl, images: $images, expositor: $expositor, category: $category, faqs: $faqs, createdAt: $createdAt)';
}


}

/// @nodoc
abstract mixin class _$ProductCopyWith<$Res> implements $ProductCopyWith<$Res> {
  factory _$ProductCopyWith(_Product value, $Res Function(_Product) _then) = __$ProductCopyWithImpl;
@override @useResult
$Res call({
 int id, String name, String slug,@JsonKey(name: 'item_type') String itemType, String? description, double price,@JsonKey(name: 'price_type') String? priceType, String? modality,@JsonKey(name: 'duration_min') int? durationMin,@JsonKey(name: 'has_stock') bool hasStock,@JsonKey(name: 'stock_quantity') int? stockQuantity,@JsonKey(name: 'is_digital') bool isDigital,@JsonKey(name: 'is_featured') bool isFeatured,@JsonKey(name: 'is_active') bool isActive, double? weight, double? height, double? width, double? length,@JsonKey(name: 'main_image_url') String? mainImageUrl, List<ProductImage> images, ExpositorSummary? expositor, ProductCategory? category, List<ProductFaq>? faqs,@JsonKey(name: 'created_at') DateTime? createdAt
});


@override $ExpositorSummaryCopyWith<$Res>? get expositor;@override $ProductCategoryCopyWith<$Res>? get category;

}
/// @nodoc
class __$ProductCopyWithImpl<$Res>
    implements _$ProductCopyWith<$Res> {
  __$ProductCopyWithImpl(this._self, this._then);

  final _Product _self;
  final $Res Function(_Product) _then;

/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? itemType = null,Object? description = freezed,Object? price = null,Object? priceType = freezed,Object? modality = freezed,Object? durationMin = freezed,Object? hasStock = null,Object? stockQuantity = freezed,Object? isDigital = null,Object? isFeatured = null,Object? isActive = null,Object? weight = freezed,Object? height = freezed,Object? width = freezed,Object? length = freezed,Object? mainImageUrl = freezed,Object? images = null,Object? expositor = freezed,Object? category = freezed,Object? faqs = freezed,Object? createdAt = freezed,}) {
  return _then(_Product(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,itemType: null == itemType ? _self.itemType : itemType // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,price: null == price ? _self.price : price // ignore: cast_nullable_to_non_nullable
as double,priceType: freezed == priceType ? _self.priceType : priceType // ignore: cast_nullable_to_non_nullable
as String?,modality: freezed == modality ? _self.modality : modality // ignore: cast_nullable_to_non_nullable
as String?,durationMin: freezed == durationMin ? _self.durationMin : durationMin // ignore: cast_nullable_to_non_nullable
as int?,hasStock: null == hasStock ? _self.hasStock : hasStock // ignore: cast_nullable_to_non_nullable
as bool,stockQuantity: freezed == stockQuantity ? _self.stockQuantity : stockQuantity // ignore: cast_nullable_to_non_nullable
as int?,isDigital: null == isDigital ? _self.isDigital : isDigital // ignore: cast_nullable_to_non_nullable
as bool,isFeatured: null == isFeatured ? _self.isFeatured : isFeatured // ignore: cast_nullable_to_non_nullable
as bool,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,weight: freezed == weight ? _self.weight : weight // ignore: cast_nullable_to_non_nullable
as double?,height: freezed == height ? _self.height : height // ignore: cast_nullable_to_non_nullable
as double?,width: freezed == width ? _self.width : width // ignore: cast_nullable_to_non_nullable
as double?,length: freezed == length ? _self.length : length // ignore: cast_nullable_to_non_nullable
as double?,mainImageUrl: freezed == mainImageUrl ? _self.mainImageUrl : mainImageUrl // ignore: cast_nullable_to_non_nullable
as String?,images: null == images ? _self._images : images // ignore: cast_nullable_to_non_nullable
as List<ProductImage>,expositor: freezed == expositor ? _self.expositor : expositor // ignore: cast_nullable_to_non_nullable
as ExpositorSummary?,category: freezed == category ? _self.category : category // ignore: cast_nullable_to_non_nullable
as ProductCategory?,faqs: freezed == faqs ? _self._faqs : faqs // ignore: cast_nullable_to_non_nullable
as List<ProductFaq>?,createdAt: freezed == createdAt ? _self.createdAt : createdAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ExpositorSummaryCopyWith<$Res>? get expositor {
    if (_self.expositor == null) {
    return null;
  }

  return $ExpositorSummaryCopyWith<$Res>(_self.expositor!, (value) {
    return _then(_self.copyWith(expositor: value));
  });
}/// Create a copy of Product
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ProductCategoryCopyWith<$Res>? get category {
    if (_self.category == null) {
    return null;
  }

  return $ProductCategoryCopyWith<$Res>(_self.category!, (value) {
    return _then(_self.copyWith(category: value));
  });
}
}

// dart format on

// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'evento.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$Evento {

 int get id; String get title; String get slug; String? get description; String? get city; String? get state; String? get address; double? get latitude; double? get longitude;@JsonKey(name: 'start_date') DateTime? get startDate;@JsonKey(name: 'end_date') DateTime? get endDate;@JsonKey(name: 'image_url') String? get imageUrl;@JsonKey(name: 'banner_image_url') String? get bannerImageUrl;@JsonKey(name: 'is_featured') bool get isFeatured;@JsonKey(name: 'capacidade_expositores') int? get capacidadeExpositores;@JsonKey(name: 'vagas_restantes') int? get vagasRestantes; List<ExpositorSummary>? get expositores;
/// Create a copy of Evento
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$EventoCopyWith<Evento> get copyWith => _$EventoCopyWithImpl<Evento>(this as Evento, _$identity);

  /// Serializes this Evento to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Evento&&(identical(other.id, id) || other.id == id)&&(identical(other.title, title) || other.title == title)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.description, description) || other.description == description)&&(identical(other.city, city) || other.city == city)&&(identical(other.state, state) || other.state == state)&&(identical(other.address, address) || other.address == address)&&(identical(other.latitude, latitude) || other.latitude == latitude)&&(identical(other.longitude, longitude) || other.longitude == longitude)&&(identical(other.startDate, startDate) || other.startDate == startDate)&&(identical(other.endDate, endDate) || other.endDate == endDate)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.bannerImageUrl, bannerImageUrl) || other.bannerImageUrl == bannerImageUrl)&&(identical(other.isFeatured, isFeatured) || other.isFeatured == isFeatured)&&(identical(other.capacidadeExpositores, capacidadeExpositores) || other.capacidadeExpositores == capacidadeExpositores)&&(identical(other.vagasRestantes, vagasRestantes) || other.vagasRestantes == vagasRestantes)&&const DeepCollectionEquality().equals(other.expositores, expositores));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,title,slug,description,city,state,address,latitude,longitude,startDate,endDate,imageUrl,bannerImageUrl,isFeatured,capacidadeExpositores,vagasRestantes,const DeepCollectionEquality().hash(expositores));

@override
String toString() {
  return 'Evento(id: $id, title: $title, slug: $slug, description: $description, city: $city, state: $state, address: $address, latitude: $latitude, longitude: $longitude, startDate: $startDate, endDate: $endDate, imageUrl: $imageUrl, bannerImageUrl: $bannerImageUrl, isFeatured: $isFeatured, capacidadeExpositores: $capacidadeExpositores, vagasRestantes: $vagasRestantes, expositores: $expositores)';
}


}

/// @nodoc
abstract mixin class $EventoCopyWith<$Res>  {
  factory $EventoCopyWith(Evento value, $Res Function(Evento) _then) = _$EventoCopyWithImpl;
@useResult
$Res call({
 int id, String title, String slug, String? description, String? city, String? state, String? address, double? latitude, double? longitude,@JsonKey(name: 'start_date') DateTime? startDate,@JsonKey(name: 'end_date') DateTime? endDate,@JsonKey(name: 'image_url') String? imageUrl,@JsonKey(name: 'banner_image_url') String? bannerImageUrl,@JsonKey(name: 'is_featured') bool isFeatured,@JsonKey(name: 'capacidade_expositores') int? capacidadeExpositores,@JsonKey(name: 'vagas_restantes') int? vagasRestantes, List<ExpositorSummary>? expositores
});




}
/// @nodoc
class _$EventoCopyWithImpl<$Res>
    implements $EventoCopyWith<$Res> {
  _$EventoCopyWithImpl(this._self, this._then);

  final Evento _self;
  final $Res Function(Evento) _then;

/// Create a copy of Evento
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? title = null,Object? slug = null,Object? description = freezed,Object? city = freezed,Object? state = freezed,Object? address = freezed,Object? latitude = freezed,Object? longitude = freezed,Object? startDate = freezed,Object? endDate = freezed,Object? imageUrl = freezed,Object? bannerImageUrl = freezed,Object? isFeatured = null,Object? capacidadeExpositores = freezed,Object? vagasRestantes = freezed,Object? expositores = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,city: freezed == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String?,state: freezed == state ? _self.state : state // ignore: cast_nullable_to_non_nullable
as String?,address: freezed == address ? _self.address : address // ignore: cast_nullable_to_non_nullable
as String?,latitude: freezed == latitude ? _self.latitude : latitude // ignore: cast_nullable_to_non_nullable
as double?,longitude: freezed == longitude ? _self.longitude : longitude // ignore: cast_nullable_to_non_nullable
as double?,startDate: freezed == startDate ? _self.startDate : startDate // ignore: cast_nullable_to_non_nullable
as DateTime?,endDate: freezed == endDate ? _self.endDate : endDate // ignore: cast_nullable_to_non_nullable
as DateTime?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,bannerImageUrl: freezed == bannerImageUrl ? _self.bannerImageUrl : bannerImageUrl // ignore: cast_nullable_to_non_nullable
as String?,isFeatured: null == isFeatured ? _self.isFeatured : isFeatured // ignore: cast_nullable_to_non_nullable
as bool,capacidadeExpositores: freezed == capacidadeExpositores ? _self.capacidadeExpositores : capacidadeExpositores // ignore: cast_nullable_to_non_nullable
as int?,vagasRestantes: freezed == vagasRestantes ? _self.vagasRestantes : vagasRestantes // ignore: cast_nullable_to_non_nullable
as int?,expositores: freezed == expositores ? _self.expositores : expositores // ignore: cast_nullable_to_non_nullable
as List<ExpositorSummary>?,
  ));
}

}


/// Adds pattern-matching-related methods to [Evento].
extension EventoPatterns on Evento {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Evento value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Evento() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Evento value)  $default,){
final _that = this;
switch (_that) {
case _Evento():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Evento value)?  $default,){
final _that = this;
switch (_that) {
case _Evento() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String title,  String slug,  String? description,  String? city,  String? state,  String? address,  double? latitude,  double? longitude, @JsonKey(name: 'start_date')  DateTime? startDate, @JsonKey(name: 'end_date')  DateTime? endDate, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'banner_image_url')  String? bannerImageUrl, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'capacidade_expositores')  int? capacidadeExpositores, @JsonKey(name: 'vagas_restantes')  int? vagasRestantes,  List<ExpositorSummary>? expositores)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Evento() when $default != null:
return $default(_that.id,_that.title,_that.slug,_that.description,_that.city,_that.state,_that.address,_that.latitude,_that.longitude,_that.startDate,_that.endDate,_that.imageUrl,_that.bannerImageUrl,_that.isFeatured,_that.capacidadeExpositores,_that.vagasRestantes,_that.expositores);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String title,  String slug,  String? description,  String? city,  String? state,  String? address,  double? latitude,  double? longitude, @JsonKey(name: 'start_date')  DateTime? startDate, @JsonKey(name: 'end_date')  DateTime? endDate, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'banner_image_url')  String? bannerImageUrl, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'capacidade_expositores')  int? capacidadeExpositores, @JsonKey(name: 'vagas_restantes')  int? vagasRestantes,  List<ExpositorSummary>? expositores)  $default,) {final _that = this;
switch (_that) {
case _Evento():
return $default(_that.id,_that.title,_that.slug,_that.description,_that.city,_that.state,_that.address,_that.latitude,_that.longitude,_that.startDate,_that.endDate,_that.imageUrl,_that.bannerImageUrl,_that.isFeatured,_that.capacidadeExpositores,_that.vagasRestantes,_that.expositores);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String title,  String slug,  String? description,  String? city,  String? state,  String? address,  double? latitude,  double? longitude, @JsonKey(name: 'start_date')  DateTime? startDate, @JsonKey(name: 'end_date')  DateTime? endDate, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'banner_image_url')  String? bannerImageUrl, @JsonKey(name: 'is_featured')  bool isFeatured, @JsonKey(name: 'capacidade_expositores')  int? capacidadeExpositores, @JsonKey(name: 'vagas_restantes')  int? vagasRestantes,  List<ExpositorSummary>? expositores)?  $default,) {final _that = this;
switch (_that) {
case _Evento() when $default != null:
return $default(_that.id,_that.title,_that.slug,_that.description,_that.city,_that.state,_that.address,_that.latitude,_that.longitude,_that.startDate,_that.endDate,_that.imageUrl,_that.bannerImageUrl,_that.isFeatured,_that.capacidadeExpositores,_that.vagasRestantes,_that.expositores);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _Evento implements Evento {
  const _Evento({required this.id, required this.title, required this.slug, this.description, this.city, this.state, this.address, this.latitude, this.longitude, @JsonKey(name: 'start_date') this.startDate, @JsonKey(name: 'end_date') this.endDate, @JsonKey(name: 'image_url') this.imageUrl, @JsonKey(name: 'banner_image_url') this.bannerImageUrl, @JsonKey(name: 'is_featured') required this.isFeatured, @JsonKey(name: 'capacidade_expositores') this.capacidadeExpositores, @JsonKey(name: 'vagas_restantes') this.vagasRestantes, final  List<ExpositorSummary>? expositores}): _expositores = expositores;
  factory _Evento.fromJson(Map<String, dynamic> json) => _$EventoFromJson(json);

@override final  int id;
@override final  String title;
@override final  String slug;
@override final  String? description;
@override final  String? city;
@override final  String? state;
@override final  String? address;
@override final  double? latitude;
@override final  double? longitude;
@override@JsonKey(name: 'start_date') final  DateTime? startDate;
@override@JsonKey(name: 'end_date') final  DateTime? endDate;
@override@JsonKey(name: 'image_url') final  String? imageUrl;
@override@JsonKey(name: 'banner_image_url') final  String? bannerImageUrl;
@override@JsonKey(name: 'is_featured') final  bool isFeatured;
@override@JsonKey(name: 'capacidade_expositores') final  int? capacidadeExpositores;
@override@JsonKey(name: 'vagas_restantes') final  int? vagasRestantes;
 final  List<ExpositorSummary>? _expositores;
@override List<ExpositorSummary>? get expositores {
  final value = _expositores;
  if (value == null) return null;
  if (_expositores is EqualUnmodifiableListView) return _expositores;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(value);
}


/// Create a copy of Evento
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$EventoCopyWith<_Evento> get copyWith => __$EventoCopyWithImpl<_Evento>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$EventoToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _Evento&&(identical(other.id, id) || other.id == id)&&(identical(other.title, title) || other.title == title)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.description, description) || other.description == description)&&(identical(other.city, city) || other.city == city)&&(identical(other.state, state) || other.state == state)&&(identical(other.address, address) || other.address == address)&&(identical(other.latitude, latitude) || other.latitude == latitude)&&(identical(other.longitude, longitude) || other.longitude == longitude)&&(identical(other.startDate, startDate) || other.startDate == startDate)&&(identical(other.endDate, endDate) || other.endDate == endDate)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.bannerImageUrl, bannerImageUrl) || other.bannerImageUrl == bannerImageUrl)&&(identical(other.isFeatured, isFeatured) || other.isFeatured == isFeatured)&&(identical(other.capacidadeExpositores, capacidadeExpositores) || other.capacidadeExpositores == capacidadeExpositores)&&(identical(other.vagasRestantes, vagasRestantes) || other.vagasRestantes == vagasRestantes)&&const DeepCollectionEquality().equals(other._expositores, _expositores));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,title,slug,description,city,state,address,latitude,longitude,startDate,endDate,imageUrl,bannerImageUrl,isFeatured,capacidadeExpositores,vagasRestantes,const DeepCollectionEquality().hash(_expositores));

@override
String toString() {
  return 'Evento(id: $id, title: $title, slug: $slug, description: $description, city: $city, state: $state, address: $address, latitude: $latitude, longitude: $longitude, startDate: $startDate, endDate: $endDate, imageUrl: $imageUrl, bannerImageUrl: $bannerImageUrl, isFeatured: $isFeatured, capacidadeExpositores: $capacidadeExpositores, vagasRestantes: $vagasRestantes, expositores: $expositores)';
}


}

/// @nodoc
abstract mixin class _$EventoCopyWith<$Res> implements $EventoCopyWith<$Res> {
  factory _$EventoCopyWith(_Evento value, $Res Function(_Evento) _then) = __$EventoCopyWithImpl;
@override @useResult
$Res call({
 int id, String title, String slug, String? description, String? city, String? state, String? address, double? latitude, double? longitude,@JsonKey(name: 'start_date') DateTime? startDate,@JsonKey(name: 'end_date') DateTime? endDate,@JsonKey(name: 'image_url') String? imageUrl,@JsonKey(name: 'banner_image_url') String? bannerImageUrl,@JsonKey(name: 'is_featured') bool isFeatured,@JsonKey(name: 'capacidade_expositores') int? capacidadeExpositores,@JsonKey(name: 'vagas_restantes') int? vagasRestantes, List<ExpositorSummary>? expositores
});




}
/// @nodoc
class __$EventoCopyWithImpl<$Res>
    implements _$EventoCopyWith<$Res> {
  __$EventoCopyWithImpl(this._self, this._then);

  final _Evento _self;
  final $Res Function(_Evento) _then;

/// Create a copy of Evento
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? title = null,Object? slug = null,Object? description = freezed,Object? city = freezed,Object? state = freezed,Object? address = freezed,Object? latitude = freezed,Object? longitude = freezed,Object? startDate = freezed,Object? endDate = freezed,Object? imageUrl = freezed,Object? bannerImageUrl = freezed,Object? isFeatured = null,Object? capacidadeExpositores = freezed,Object? vagasRestantes = freezed,Object? expositores = freezed,}) {
  return _then(_Evento(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,city: freezed == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String?,state: freezed == state ? _self.state : state // ignore: cast_nullable_to_non_nullable
as String?,address: freezed == address ? _self.address : address // ignore: cast_nullable_to_non_nullable
as String?,latitude: freezed == latitude ? _self.latitude : latitude // ignore: cast_nullable_to_non_nullable
as double?,longitude: freezed == longitude ? _self.longitude : longitude // ignore: cast_nullable_to_non_nullable
as double?,startDate: freezed == startDate ? _self.startDate : startDate // ignore: cast_nullable_to_non_nullable
as DateTime?,endDate: freezed == endDate ? _self.endDate : endDate // ignore: cast_nullable_to_non_nullable
as DateTime?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,bannerImageUrl: freezed == bannerImageUrl ? _self.bannerImageUrl : bannerImageUrl // ignore: cast_nullable_to_non_nullable
as String?,isFeatured: null == isFeatured ? _self.isFeatured : isFeatured // ignore: cast_nullable_to_non_nullable
as bool,capacidadeExpositores: freezed == capacidadeExpositores ? _self.capacidadeExpositores : capacidadeExpositores // ignore: cast_nullable_to_non_nullable
as int?,vagasRestantes: freezed == vagasRestantes ? _self.vagasRestantes : vagasRestantes // ignore: cast_nullable_to_non_nullable
as int?,expositores: freezed == expositores ? _self._expositores : expositores // ignore: cast_nullable_to_non_nullable
as List<ExpositorSummary>?,
  ));
}


}

// dart format on

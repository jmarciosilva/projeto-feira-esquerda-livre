// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'expositor_summary.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$ExpositorSummary {

 int get id; String get name; String get slug; String? get description; List<String>? get eixos;@JsonKey(name: 'logo_url') String? get logoUrl;@JsonKey(name: 'image_url') String? get imageUrl; String? get city; String? get state; String? get whatsapp;@JsonKey(name: 'instagram_url') String? get instagramUrl;@JsonKey(name: 'facebook_url') String? get facebookUrl;@JsonKey(name: 'is_active') bool get isActive;
/// Create a copy of ExpositorSummary
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$ExpositorSummaryCopyWith<ExpositorSummary> get copyWith => _$ExpositorSummaryCopyWithImpl<ExpositorSummary>(this as ExpositorSummary, _$identity);

  /// Serializes this ExpositorSummary to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is ExpositorSummary&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.description, description) || other.description == description)&&const DeepCollectionEquality().equals(other.eixos, eixos)&&(identical(other.logoUrl, logoUrl) || other.logoUrl == logoUrl)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.city, city) || other.city == city)&&(identical(other.state, state) || other.state == state)&&(identical(other.whatsapp, whatsapp) || other.whatsapp == whatsapp)&&(identical(other.instagramUrl, instagramUrl) || other.instagramUrl == instagramUrl)&&(identical(other.facebookUrl, facebookUrl) || other.facebookUrl == facebookUrl)&&(identical(other.isActive, isActive) || other.isActive == isActive));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,slug,description,const DeepCollectionEquality().hash(eixos),logoUrl,imageUrl,city,state,whatsapp,instagramUrl,facebookUrl,isActive);

@override
String toString() {
  return 'ExpositorSummary(id: $id, name: $name, slug: $slug, description: $description, eixos: $eixos, logoUrl: $logoUrl, imageUrl: $imageUrl, city: $city, state: $state, whatsapp: $whatsapp, instagramUrl: $instagramUrl, facebookUrl: $facebookUrl, isActive: $isActive)';
}


}

/// @nodoc
abstract mixin class $ExpositorSummaryCopyWith<$Res>  {
  factory $ExpositorSummaryCopyWith(ExpositorSummary value, $Res Function(ExpositorSummary) _then) = _$ExpositorSummaryCopyWithImpl;
@useResult
$Res call({
 int id, String name, String slug, String? description, List<String>? eixos,@JsonKey(name: 'logo_url') String? logoUrl,@JsonKey(name: 'image_url') String? imageUrl, String? city, String? state, String? whatsapp,@JsonKey(name: 'instagram_url') String? instagramUrl,@JsonKey(name: 'facebook_url') String? facebookUrl,@JsonKey(name: 'is_active') bool isActive
});




}
/// @nodoc
class _$ExpositorSummaryCopyWithImpl<$Res>
    implements $ExpositorSummaryCopyWith<$Res> {
  _$ExpositorSummaryCopyWithImpl(this._self, this._then);

  final ExpositorSummary _self;
  final $Res Function(ExpositorSummary) _then;

/// Create a copy of ExpositorSummary
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? description = freezed,Object? eixos = freezed,Object? logoUrl = freezed,Object? imageUrl = freezed,Object? city = freezed,Object? state = freezed,Object? whatsapp = freezed,Object? instagramUrl = freezed,Object? facebookUrl = freezed,Object? isActive = null,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,eixos: freezed == eixos ? _self.eixos : eixos // ignore: cast_nullable_to_non_nullable
as List<String>?,logoUrl: freezed == logoUrl ? _self.logoUrl : logoUrl // ignore: cast_nullable_to_non_nullable
as String?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,city: freezed == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String?,state: freezed == state ? _self.state : state // ignore: cast_nullable_to_non_nullable
as String?,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,instagramUrl: freezed == instagramUrl ? _self.instagramUrl : instagramUrl // ignore: cast_nullable_to_non_nullable
as String?,facebookUrl: freezed == facebookUrl ? _self.facebookUrl : facebookUrl // ignore: cast_nullable_to_non_nullable
as String?,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}

}


/// Adds pattern-matching-related methods to [ExpositorSummary].
extension ExpositorSummaryPatterns on ExpositorSummary {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _ExpositorSummary value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _ExpositorSummary() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _ExpositorSummary value)  $default,){
final _that = this;
switch (_that) {
case _ExpositorSummary():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _ExpositorSummary value)?  $default,){
final _that = this;
switch (_that) {
case _ExpositorSummary() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String name,  String slug,  String? description,  List<String>? eixos, @JsonKey(name: 'logo_url')  String? logoUrl, @JsonKey(name: 'image_url')  String? imageUrl,  String? city,  String? state,  String? whatsapp, @JsonKey(name: 'instagram_url')  String? instagramUrl, @JsonKey(name: 'facebook_url')  String? facebookUrl, @JsonKey(name: 'is_active')  bool isActive)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _ExpositorSummary() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.description,_that.eixos,_that.logoUrl,_that.imageUrl,_that.city,_that.state,_that.whatsapp,_that.instagramUrl,_that.facebookUrl,_that.isActive);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String name,  String slug,  String? description,  List<String>? eixos, @JsonKey(name: 'logo_url')  String? logoUrl, @JsonKey(name: 'image_url')  String? imageUrl,  String? city,  String? state,  String? whatsapp, @JsonKey(name: 'instagram_url')  String? instagramUrl, @JsonKey(name: 'facebook_url')  String? facebookUrl, @JsonKey(name: 'is_active')  bool isActive)  $default,) {final _that = this;
switch (_that) {
case _ExpositorSummary():
return $default(_that.id,_that.name,_that.slug,_that.description,_that.eixos,_that.logoUrl,_that.imageUrl,_that.city,_that.state,_that.whatsapp,_that.instagramUrl,_that.facebookUrl,_that.isActive);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String name,  String slug,  String? description,  List<String>? eixos, @JsonKey(name: 'logo_url')  String? logoUrl, @JsonKey(name: 'image_url')  String? imageUrl,  String? city,  String? state,  String? whatsapp, @JsonKey(name: 'instagram_url')  String? instagramUrl, @JsonKey(name: 'facebook_url')  String? facebookUrl, @JsonKey(name: 'is_active')  bool isActive)?  $default,) {final _that = this;
switch (_that) {
case _ExpositorSummary() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.description,_that.eixos,_that.logoUrl,_that.imageUrl,_that.city,_that.state,_that.whatsapp,_that.instagramUrl,_that.facebookUrl,_that.isActive);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _ExpositorSummary implements ExpositorSummary {
  const _ExpositorSummary({required this.id, required this.name, required this.slug, this.description, final  List<String>? eixos, @JsonKey(name: 'logo_url') this.logoUrl, @JsonKey(name: 'image_url') this.imageUrl, this.city, this.state, this.whatsapp, @JsonKey(name: 'instagram_url') this.instagramUrl, @JsonKey(name: 'facebook_url') this.facebookUrl, @JsonKey(name: 'is_active') required this.isActive}): _eixos = eixos;
  factory _ExpositorSummary.fromJson(Map<String, dynamic> json) => _$ExpositorSummaryFromJson(json);

@override final  int id;
@override final  String name;
@override final  String slug;
@override final  String? description;
 final  List<String>? _eixos;
@override List<String>? get eixos {
  final value = _eixos;
  if (value == null) return null;
  if (_eixos is EqualUnmodifiableListView) return _eixos;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(value);
}

@override@JsonKey(name: 'logo_url') final  String? logoUrl;
@override@JsonKey(name: 'image_url') final  String? imageUrl;
@override final  String? city;
@override final  String? state;
@override final  String? whatsapp;
@override@JsonKey(name: 'instagram_url') final  String? instagramUrl;
@override@JsonKey(name: 'facebook_url') final  String? facebookUrl;
@override@JsonKey(name: 'is_active') final  bool isActive;

/// Create a copy of ExpositorSummary
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$ExpositorSummaryCopyWith<_ExpositorSummary> get copyWith => __$ExpositorSummaryCopyWithImpl<_ExpositorSummary>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$ExpositorSummaryToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _ExpositorSummary&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.description, description) || other.description == description)&&const DeepCollectionEquality().equals(other._eixos, _eixos)&&(identical(other.logoUrl, logoUrl) || other.logoUrl == logoUrl)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.city, city) || other.city == city)&&(identical(other.state, state) || other.state == state)&&(identical(other.whatsapp, whatsapp) || other.whatsapp == whatsapp)&&(identical(other.instagramUrl, instagramUrl) || other.instagramUrl == instagramUrl)&&(identical(other.facebookUrl, facebookUrl) || other.facebookUrl == facebookUrl)&&(identical(other.isActive, isActive) || other.isActive == isActive));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,slug,description,const DeepCollectionEquality().hash(_eixos),logoUrl,imageUrl,city,state,whatsapp,instagramUrl,facebookUrl,isActive);

@override
String toString() {
  return 'ExpositorSummary(id: $id, name: $name, slug: $slug, description: $description, eixos: $eixos, logoUrl: $logoUrl, imageUrl: $imageUrl, city: $city, state: $state, whatsapp: $whatsapp, instagramUrl: $instagramUrl, facebookUrl: $facebookUrl, isActive: $isActive)';
}


}

/// @nodoc
abstract mixin class _$ExpositorSummaryCopyWith<$Res> implements $ExpositorSummaryCopyWith<$Res> {
  factory _$ExpositorSummaryCopyWith(_ExpositorSummary value, $Res Function(_ExpositorSummary) _then) = __$ExpositorSummaryCopyWithImpl;
@override @useResult
$Res call({
 int id, String name, String slug, String? description, List<String>? eixos,@JsonKey(name: 'logo_url') String? logoUrl,@JsonKey(name: 'image_url') String? imageUrl, String? city, String? state, String? whatsapp,@JsonKey(name: 'instagram_url') String? instagramUrl,@JsonKey(name: 'facebook_url') String? facebookUrl,@JsonKey(name: 'is_active') bool isActive
});




}
/// @nodoc
class __$ExpositorSummaryCopyWithImpl<$Res>
    implements _$ExpositorSummaryCopyWith<$Res> {
  __$ExpositorSummaryCopyWithImpl(this._self, this._then);

  final _ExpositorSummary _self;
  final $Res Function(_ExpositorSummary) _then;

/// Create a copy of ExpositorSummary
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? description = freezed,Object? eixos = freezed,Object? logoUrl = freezed,Object? imageUrl = freezed,Object? city = freezed,Object? state = freezed,Object? whatsapp = freezed,Object? instagramUrl = freezed,Object? facebookUrl = freezed,Object? isActive = null,}) {
  return _then(_ExpositorSummary(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,description: freezed == description ? _self.description : description // ignore: cast_nullable_to_non_nullable
as String?,eixos: freezed == eixos ? _self._eixos : eixos // ignore: cast_nullable_to_non_nullable
as List<String>?,logoUrl: freezed == logoUrl ? _self.logoUrl : logoUrl // ignore: cast_nullable_to_non_nullable
as String?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,city: freezed == city ? _self.city : city // ignore: cast_nullable_to_non_nullable
as String?,state: freezed == state ? _self.state : state // ignore: cast_nullable_to_non_nullable
as String?,whatsapp: freezed == whatsapp ? _self.whatsapp : whatsapp // ignore: cast_nullable_to_non_nullable
as String?,instagramUrl: freezed == instagramUrl ? _self.instagramUrl : instagramUrl // ignore: cast_nullable_to_non_nullable
as String?,facebookUrl: freezed == facebookUrl ? _self.facebookUrl : facebookUrl // ignore: cast_nullable_to_non_nullable
as String?,isActive: null == isActive ? _self.isActive : isActive // ignore: cast_nullable_to_non_nullable
as bool,
  ));
}


}

// dart format on

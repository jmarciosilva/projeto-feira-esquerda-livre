// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'feed_expositor.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$FeedExpositor {

 int get id; String get name; String get slug;@JsonKey(name: 'logo_url') String? get logoUrl;
/// Create a copy of FeedExpositor
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$FeedExpositorCopyWith<FeedExpositor> get copyWith => _$FeedExpositorCopyWithImpl<FeedExpositor>(this as FeedExpositor, _$identity);

  /// Serializes this FeedExpositor to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is FeedExpositor&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.logoUrl, logoUrl) || other.logoUrl == logoUrl));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,slug,logoUrl);

@override
String toString() {
  return 'FeedExpositor(id: $id, name: $name, slug: $slug, logoUrl: $logoUrl)';
}


}

/// @nodoc
abstract mixin class $FeedExpositorCopyWith<$Res>  {
  factory $FeedExpositorCopyWith(FeedExpositor value, $Res Function(FeedExpositor) _then) = _$FeedExpositorCopyWithImpl;
@useResult
$Res call({
 int id, String name, String slug,@JsonKey(name: 'logo_url') String? logoUrl
});




}
/// @nodoc
class _$FeedExpositorCopyWithImpl<$Res>
    implements $FeedExpositorCopyWith<$Res> {
  _$FeedExpositorCopyWithImpl(this._self, this._then);

  final FeedExpositor _self;
  final $Res Function(FeedExpositor) _then;

/// Create a copy of FeedExpositor
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? logoUrl = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,logoUrl: freezed == logoUrl ? _self.logoUrl : logoUrl // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [FeedExpositor].
extension FeedExpositorPatterns on FeedExpositor {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _FeedExpositor value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _FeedExpositor() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _FeedExpositor value)  $default,){
final _that = this;
switch (_that) {
case _FeedExpositor():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _FeedExpositor value)?  $default,){
final _that = this;
switch (_that) {
case _FeedExpositor() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String name,  String slug, @JsonKey(name: 'logo_url')  String? logoUrl)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _FeedExpositor() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.logoUrl);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String name,  String slug, @JsonKey(name: 'logo_url')  String? logoUrl)  $default,) {final _that = this;
switch (_that) {
case _FeedExpositor():
return $default(_that.id,_that.name,_that.slug,_that.logoUrl);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String name,  String slug, @JsonKey(name: 'logo_url')  String? logoUrl)?  $default,) {final _that = this;
switch (_that) {
case _FeedExpositor() when $default != null:
return $default(_that.id,_that.name,_that.slug,_that.logoUrl);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _FeedExpositor implements FeedExpositor {
  const _FeedExpositor({required this.id, required this.name, required this.slug, @JsonKey(name: 'logo_url') this.logoUrl});
  factory _FeedExpositor.fromJson(Map<String, dynamic> json) => _$FeedExpositorFromJson(json);

@override final  int id;
@override final  String name;
@override final  String slug;
@override@JsonKey(name: 'logo_url') final  String? logoUrl;

/// Create a copy of FeedExpositor
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$FeedExpositorCopyWith<_FeedExpositor> get copyWith => __$FeedExpositorCopyWithImpl<_FeedExpositor>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$FeedExpositorToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _FeedExpositor&&(identical(other.id, id) || other.id == id)&&(identical(other.name, name) || other.name == name)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.logoUrl, logoUrl) || other.logoUrl == logoUrl));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,name,slug,logoUrl);

@override
String toString() {
  return 'FeedExpositor(id: $id, name: $name, slug: $slug, logoUrl: $logoUrl)';
}


}

/// @nodoc
abstract mixin class _$FeedExpositorCopyWith<$Res> implements $FeedExpositorCopyWith<$Res> {
  factory _$FeedExpositorCopyWith(_FeedExpositor value, $Res Function(_FeedExpositor) _then) = __$FeedExpositorCopyWithImpl;
@override @useResult
$Res call({
 int id, String name, String slug,@JsonKey(name: 'logo_url') String? logoUrl
});




}
/// @nodoc
class __$FeedExpositorCopyWithImpl<$Res>
    implements _$FeedExpositorCopyWith<$Res> {
  __$FeedExpositorCopyWithImpl(this._self, this._then);

  final _FeedExpositor _self;
  final $Res Function(_FeedExpositor) _then;

/// Create a copy of FeedExpositor
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? name = null,Object? slug = null,Object? logoUrl = freezed,}) {
  return _then(_FeedExpositor(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,name: null == name ? _self.name : name // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,logoUrl: freezed == logoUrl ? _self.logoUrl : logoUrl // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

// dart format on

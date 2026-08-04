// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'noticia.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;

/// @nodoc
mixin _$Noticia {

 int get id; String get title; String get slug; String? get excerpt; String? get content;@JsonKey(name: 'image_url') String? get imageUrl;@JsonKey(name: 'author_name') String? get authorName;@JsonKey(name: 'published_at') DateTime? get publishedAt;
/// Create a copy of Noticia
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$NoticiaCopyWith<Noticia> get copyWith => _$NoticiaCopyWithImpl<Noticia>(this as Noticia, _$identity);

  /// Serializes this Noticia to a JSON map.
  Map<String, dynamic> toJson();


@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is Noticia&&(identical(other.id, id) || other.id == id)&&(identical(other.title, title) || other.title == title)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.excerpt, excerpt) || other.excerpt == excerpt)&&(identical(other.content, content) || other.content == content)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.authorName, authorName) || other.authorName == authorName)&&(identical(other.publishedAt, publishedAt) || other.publishedAt == publishedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,title,slug,excerpt,content,imageUrl,authorName,publishedAt);

@override
String toString() {
  return 'Noticia(id: $id, title: $title, slug: $slug, excerpt: $excerpt, content: $content, imageUrl: $imageUrl, authorName: $authorName, publishedAt: $publishedAt)';
}


}

/// @nodoc
abstract mixin class $NoticiaCopyWith<$Res>  {
  factory $NoticiaCopyWith(Noticia value, $Res Function(Noticia) _then) = _$NoticiaCopyWithImpl;
@useResult
$Res call({
 int id, String title, String slug, String? excerpt, String? content,@JsonKey(name: 'image_url') String? imageUrl,@JsonKey(name: 'author_name') String? authorName,@JsonKey(name: 'published_at') DateTime? publishedAt
});




}
/// @nodoc
class _$NoticiaCopyWithImpl<$Res>
    implements $NoticiaCopyWith<$Res> {
  _$NoticiaCopyWithImpl(this._self, this._then);

  final Noticia _self;
  final $Res Function(Noticia) _then;

/// Create a copy of Noticia
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? id = null,Object? title = null,Object? slug = null,Object? excerpt = freezed,Object? content = freezed,Object? imageUrl = freezed,Object? authorName = freezed,Object? publishedAt = freezed,}) {
  return _then(_self.copyWith(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,excerpt: freezed == excerpt ? _self.excerpt : excerpt // ignore: cast_nullable_to_non_nullable
as String?,content: freezed == content ? _self.content : content // ignore: cast_nullable_to_non_nullable
as String?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,authorName: freezed == authorName ? _self.authorName : authorName // ignore: cast_nullable_to_non_nullable
as String?,publishedAt: freezed == publishedAt ? _self.publishedAt : publishedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}

}


/// Adds pattern-matching-related methods to [Noticia].
extension NoticiaPatterns on Noticia {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _Noticia value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _Noticia() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _Noticia value)  $default,){
final _that = this;
switch (_that) {
case _Noticia():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _Noticia value)?  $default,){
final _that = this;
switch (_that) {
case _Noticia() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( int id,  String title,  String slug,  String? excerpt,  String? content, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'author_name')  String? authorName, @JsonKey(name: 'published_at')  DateTime? publishedAt)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _Noticia() when $default != null:
return $default(_that.id,_that.title,_that.slug,_that.excerpt,_that.content,_that.imageUrl,_that.authorName,_that.publishedAt);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( int id,  String title,  String slug,  String? excerpt,  String? content, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'author_name')  String? authorName, @JsonKey(name: 'published_at')  DateTime? publishedAt)  $default,) {final _that = this;
switch (_that) {
case _Noticia():
return $default(_that.id,_that.title,_that.slug,_that.excerpt,_that.content,_that.imageUrl,_that.authorName,_that.publishedAt);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( int id,  String title,  String slug,  String? excerpt,  String? content, @JsonKey(name: 'image_url')  String? imageUrl, @JsonKey(name: 'author_name')  String? authorName, @JsonKey(name: 'published_at')  DateTime? publishedAt)?  $default,) {final _that = this;
switch (_that) {
case _Noticia() when $default != null:
return $default(_that.id,_that.title,_that.slug,_that.excerpt,_that.content,_that.imageUrl,_that.authorName,_that.publishedAt);case _:
  return null;

}
}

}

/// @nodoc
@JsonSerializable()

class _Noticia implements Noticia {
  const _Noticia({required this.id, required this.title, required this.slug, this.excerpt, this.content, @JsonKey(name: 'image_url') this.imageUrl, @JsonKey(name: 'author_name') this.authorName, @JsonKey(name: 'published_at') this.publishedAt});
  factory _Noticia.fromJson(Map<String, dynamic> json) => _$NoticiaFromJson(json);

@override final  int id;
@override final  String title;
@override final  String slug;
@override final  String? excerpt;
@override final  String? content;
@override@JsonKey(name: 'image_url') final  String? imageUrl;
@override@JsonKey(name: 'author_name') final  String? authorName;
@override@JsonKey(name: 'published_at') final  DateTime? publishedAt;

/// Create a copy of Noticia
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$NoticiaCopyWith<_Noticia> get copyWith => __$NoticiaCopyWithImpl<_Noticia>(this, _$identity);

@override
Map<String, dynamic> toJson() {
  return _$NoticiaToJson(this, );
}

@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _Noticia&&(identical(other.id, id) || other.id == id)&&(identical(other.title, title) || other.title == title)&&(identical(other.slug, slug) || other.slug == slug)&&(identical(other.excerpt, excerpt) || other.excerpt == excerpt)&&(identical(other.content, content) || other.content == content)&&(identical(other.imageUrl, imageUrl) || other.imageUrl == imageUrl)&&(identical(other.authorName, authorName) || other.authorName == authorName)&&(identical(other.publishedAt, publishedAt) || other.publishedAt == publishedAt));
}

@JsonKey(includeFromJson: false, includeToJson: false)
@override
int get hashCode => Object.hash(runtimeType,id,title,slug,excerpt,content,imageUrl,authorName,publishedAt);

@override
String toString() {
  return 'Noticia(id: $id, title: $title, slug: $slug, excerpt: $excerpt, content: $content, imageUrl: $imageUrl, authorName: $authorName, publishedAt: $publishedAt)';
}


}

/// @nodoc
abstract mixin class _$NoticiaCopyWith<$Res> implements $NoticiaCopyWith<$Res> {
  factory _$NoticiaCopyWith(_Noticia value, $Res Function(_Noticia) _then) = __$NoticiaCopyWithImpl;
@override @useResult
$Res call({
 int id, String title, String slug, String? excerpt, String? content,@JsonKey(name: 'image_url') String? imageUrl,@JsonKey(name: 'author_name') String? authorName,@JsonKey(name: 'published_at') DateTime? publishedAt
});




}
/// @nodoc
class __$NoticiaCopyWithImpl<$Res>
    implements _$NoticiaCopyWith<$Res> {
  __$NoticiaCopyWithImpl(this._self, this._then);

  final _Noticia _self;
  final $Res Function(_Noticia) _then;

/// Create a copy of Noticia
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? id = null,Object? title = null,Object? slug = null,Object? excerpt = freezed,Object? content = freezed,Object? imageUrl = freezed,Object? authorName = freezed,Object? publishedAt = freezed,}) {
  return _then(_Noticia(
id: null == id ? _self.id : id // ignore: cast_nullable_to_non_nullable
as int,title: null == title ? _self.title : title // ignore: cast_nullable_to_non_nullable
as String,slug: null == slug ? _self.slug : slug // ignore: cast_nullable_to_non_nullable
as String,excerpt: freezed == excerpt ? _self.excerpt : excerpt // ignore: cast_nullable_to_non_nullable
as String?,content: freezed == content ? _self.content : content // ignore: cast_nullable_to_non_nullable
as String?,imageUrl: freezed == imageUrl ? _self.imageUrl : imageUrl // ignore: cast_nullable_to_non_nullable
as String?,authorName: freezed == authorName ? _self.authorName : authorName // ignore: cast_nullable_to_non_nullable
as String?,publishedAt: freezed == publishedAt ? _self.publishedAt : publishedAt // ignore: cast_nullable_to_non_nullable
as DateTime?,
  ));
}


}

// dart format on

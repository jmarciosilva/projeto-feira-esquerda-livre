// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'noticias_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$NoticiasState {

 List<Noticia> get items; int get currentPage; int get lastPage; bool get isLoading; bool get isLoadingMore; String? get error;
/// Create a copy of NoticiasState
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$NoticiasStateCopyWith<NoticiasState> get copyWith => _$NoticiasStateCopyWithImpl<NoticiasState>(this as NoticiasState, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is NoticiasState&&const DeepCollectionEquality().equals(other.items, items)&&(identical(other.currentPage, currentPage) || other.currentPage == currentPage)&&(identical(other.lastPage, lastPage) || other.lastPage == lastPage)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.isLoadingMore, isLoadingMore) || other.isLoadingMore == isLoadingMore)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(items),currentPage,lastPage,isLoading,isLoadingMore,error);

@override
String toString() {
  return 'NoticiasState(items: $items, currentPage: $currentPage, lastPage: $lastPage, isLoading: $isLoading, isLoadingMore: $isLoadingMore, error: $error)';
}


}

/// @nodoc
abstract mixin class $NoticiasStateCopyWith<$Res>  {
  factory $NoticiasStateCopyWith(NoticiasState value, $Res Function(NoticiasState) _then) = _$NoticiasStateCopyWithImpl;
@useResult
$Res call({
 List<Noticia> items, int currentPage, int lastPage, bool isLoading, bool isLoadingMore, String? error
});




}
/// @nodoc
class _$NoticiasStateCopyWithImpl<$Res>
    implements $NoticiasStateCopyWith<$Res> {
  _$NoticiasStateCopyWithImpl(this._self, this._then);

  final NoticiasState _self;
  final $Res Function(NoticiasState) _then;

/// Create a copy of NoticiasState
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? items = null,Object? currentPage = null,Object? lastPage = null,Object? isLoading = null,Object? isLoadingMore = null,Object? error = freezed,}) {
  return _then(_self.copyWith(
items: null == items ? _self.items : items // ignore: cast_nullable_to_non_nullable
as List<Noticia>,currentPage: null == currentPage ? _self.currentPage : currentPage // ignore: cast_nullable_to_non_nullable
as int,lastPage: null == lastPage ? _self.lastPage : lastPage // ignore: cast_nullable_to_non_nullable
as int,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,isLoadingMore: null == isLoadingMore ? _self.isLoadingMore : isLoadingMore // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [NoticiasState].
extension NoticiasStatePatterns on NoticiasState {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _NoticiasState value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _NoticiasState() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _NoticiasState value)  $default,){
final _that = this;
switch (_that) {
case _NoticiasState():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _NoticiasState value)?  $default,){
final _that = this;
switch (_that) {
case _NoticiasState() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( List<Noticia> items,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String? error)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _NoticiasState() when $default != null:
return $default(_that.items,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.error);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( List<Noticia> items,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String? error)  $default,) {final _that = this;
switch (_that) {
case _NoticiasState():
return $default(_that.items,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.error);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( List<Noticia> items,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String? error)?  $default,) {final _that = this;
switch (_that) {
case _NoticiasState() when $default != null:
return $default(_that.items,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.error);case _:
  return null;

}
}

}

/// @nodoc


class _NoticiasState extends NoticiasState {
  const _NoticiasState({final  List<Noticia> items = const [], this.currentPage = 1, this.lastPage = 1, this.isLoading = true, this.isLoadingMore = false, this.error}): _items = items,super._();
  

 final  List<Noticia> _items;
@override@JsonKey() List<Noticia> get items {
  if (_items is EqualUnmodifiableListView) return _items;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_items);
}

@override@JsonKey() final  int currentPage;
@override@JsonKey() final  int lastPage;
@override@JsonKey() final  bool isLoading;
@override@JsonKey() final  bool isLoadingMore;
@override final  String? error;

/// Create a copy of NoticiasState
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$NoticiasStateCopyWith<_NoticiasState> get copyWith => __$NoticiasStateCopyWithImpl<_NoticiasState>(this, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _NoticiasState&&const DeepCollectionEquality().equals(other._items, _items)&&(identical(other.currentPage, currentPage) || other.currentPage == currentPage)&&(identical(other.lastPage, lastPage) || other.lastPage == lastPage)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.isLoadingMore, isLoadingMore) || other.isLoadingMore == isLoadingMore)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(_items),currentPage,lastPage,isLoading,isLoadingMore,error);

@override
String toString() {
  return 'NoticiasState(items: $items, currentPage: $currentPage, lastPage: $lastPage, isLoading: $isLoading, isLoadingMore: $isLoadingMore, error: $error)';
}


}

/// @nodoc
abstract mixin class _$NoticiasStateCopyWith<$Res> implements $NoticiasStateCopyWith<$Res> {
  factory _$NoticiasStateCopyWith(_NoticiasState value, $Res Function(_NoticiasState) _then) = __$NoticiasStateCopyWithImpl;
@override @useResult
$Res call({
 List<Noticia> items, int currentPage, int lastPage, bool isLoading, bool isLoadingMore, String? error
});




}
/// @nodoc
class __$NoticiasStateCopyWithImpl<$Res>
    implements _$NoticiasStateCopyWith<$Res> {
  __$NoticiasStateCopyWithImpl(this._self, this._then);

  final _NoticiasState _self;
  final $Res Function(_NoticiasState) _then;

/// Create a copy of NoticiasState
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? items = null,Object? currentPage = null,Object? lastPage = null,Object? isLoading = null,Object? isLoadingMore = null,Object? error = freezed,}) {
  return _then(_NoticiasState(
items: null == items ? _self._items : items // ignore: cast_nullable_to_non_nullable
as List<Noticia>,currentPage: null == currentPage ? _self.currentPage : currentPage // ignore: cast_nullable_to_non_nullable
as int,lastPage: null == lastPage ? _self.lastPage : lastPage // ignore: cast_nullable_to_non_nullable
as int,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,isLoadingMore: null == isLoadingMore ? _self.isLoadingMore : isLoadingMore // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

// dart format on

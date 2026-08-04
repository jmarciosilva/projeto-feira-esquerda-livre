// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'catalogo_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$CatalogoState {

 List<Product> get items; List<Categoria> get categorias; int get currentPage; int get lastPage; bool get isLoading; bool get isLoadingMore; String get busca; int? get categoriaId; String? get error;
/// Create a copy of CatalogoState
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$CatalogoStateCopyWith<CatalogoState> get copyWith => _$CatalogoStateCopyWithImpl<CatalogoState>(this as CatalogoState, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is CatalogoState&&const DeepCollectionEquality().equals(other.items, items)&&const DeepCollectionEquality().equals(other.categorias, categorias)&&(identical(other.currentPage, currentPage) || other.currentPage == currentPage)&&(identical(other.lastPage, lastPage) || other.lastPage == lastPage)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.isLoadingMore, isLoadingMore) || other.isLoadingMore == isLoadingMore)&&(identical(other.busca, busca) || other.busca == busca)&&(identical(other.categoriaId, categoriaId) || other.categoriaId == categoriaId)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(items),const DeepCollectionEquality().hash(categorias),currentPage,lastPage,isLoading,isLoadingMore,busca,categoriaId,error);

@override
String toString() {
  return 'CatalogoState(items: $items, categorias: $categorias, currentPage: $currentPage, lastPage: $lastPage, isLoading: $isLoading, isLoadingMore: $isLoadingMore, busca: $busca, categoriaId: $categoriaId, error: $error)';
}


}

/// @nodoc
abstract mixin class $CatalogoStateCopyWith<$Res>  {
  factory $CatalogoStateCopyWith(CatalogoState value, $Res Function(CatalogoState) _then) = _$CatalogoStateCopyWithImpl;
@useResult
$Res call({
 List<Product> items, List<Categoria> categorias, int currentPage, int lastPage, bool isLoading, bool isLoadingMore, String busca, int? categoriaId, String? error
});




}
/// @nodoc
class _$CatalogoStateCopyWithImpl<$Res>
    implements $CatalogoStateCopyWith<$Res> {
  _$CatalogoStateCopyWithImpl(this._self, this._then);

  final CatalogoState _self;
  final $Res Function(CatalogoState) _then;

/// Create a copy of CatalogoState
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? items = null,Object? categorias = null,Object? currentPage = null,Object? lastPage = null,Object? isLoading = null,Object? isLoadingMore = null,Object? busca = null,Object? categoriaId = freezed,Object? error = freezed,}) {
  return _then(_self.copyWith(
items: null == items ? _self.items : items // ignore: cast_nullable_to_non_nullable
as List<Product>,categorias: null == categorias ? _self.categorias : categorias // ignore: cast_nullable_to_non_nullable
as List<Categoria>,currentPage: null == currentPage ? _self.currentPage : currentPage // ignore: cast_nullable_to_non_nullable
as int,lastPage: null == lastPage ? _self.lastPage : lastPage // ignore: cast_nullable_to_non_nullable
as int,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,isLoadingMore: null == isLoadingMore ? _self.isLoadingMore : isLoadingMore // ignore: cast_nullable_to_non_nullable
as bool,busca: null == busca ? _self.busca : busca // ignore: cast_nullable_to_non_nullable
as String,categoriaId: freezed == categoriaId ? _self.categoriaId : categoriaId // ignore: cast_nullable_to_non_nullable
as int?,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

}


/// Adds pattern-matching-related methods to [CatalogoState].
extension CatalogoStatePatterns on CatalogoState {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _CatalogoState value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _CatalogoState() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _CatalogoState value)  $default,){
final _that = this;
switch (_that) {
case _CatalogoState():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _CatalogoState value)?  $default,){
final _that = this;
switch (_that) {
case _CatalogoState() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( List<Product> items,  List<Categoria> categorias,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String busca,  int? categoriaId,  String? error)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _CatalogoState() when $default != null:
return $default(_that.items,_that.categorias,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.busca,_that.categoriaId,_that.error);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( List<Product> items,  List<Categoria> categorias,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String busca,  int? categoriaId,  String? error)  $default,) {final _that = this;
switch (_that) {
case _CatalogoState():
return $default(_that.items,_that.categorias,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.busca,_that.categoriaId,_that.error);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( List<Product> items,  List<Categoria> categorias,  int currentPage,  int lastPage,  bool isLoading,  bool isLoadingMore,  String busca,  int? categoriaId,  String? error)?  $default,) {final _that = this;
switch (_that) {
case _CatalogoState() when $default != null:
return $default(_that.items,_that.categorias,_that.currentPage,_that.lastPage,_that.isLoading,_that.isLoadingMore,_that.busca,_that.categoriaId,_that.error);case _:
  return null;

}
}

}

/// @nodoc


class _CatalogoState extends CatalogoState {
  const _CatalogoState({final  List<Product> items = const [], final  List<Categoria> categorias = const [], this.currentPage = 1, this.lastPage = 1, this.isLoading = true, this.isLoadingMore = false, this.busca = '', this.categoriaId, this.error}): _items = items,_categorias = categorias,super._();
  

 final  List<Product> _items;
@override@JsonKey() List<Product> get items {
  if (_items is EqualUnmodifiableListView) return _items;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_items);
}

 final  List<Categoria> _categorias;
@override@JsonKey() List<Categoria> get categorias {
  if (_categorias is EqualUnmodifiableListView) return _categorias;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_categorias);
}

@override@JsonKey() final  int currentPage;
@override@JsonKey() final  int lastPage;
@override@JsonKey() final  bool isLoading;
@override@JsonKey() final  bool isLoadingMore;
@override@JsonKey() final  String busca;
@override final  int? categoriaId;
@override final  String? error;

/// Create a copy of CatalogoState
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$CatalogoStateCopyWith<_CatalogoState> get copyWith => __$CatalogoStateCopyWithImpl<_CatalogoState>(this, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _CatalogoState&&const DeepCollectionEquality().equals(other._items, _items)&&const DeepCollectionEquality().equals(other._categorias, _categorias)&&(identical(other.currentPage, currentPage) || other.currentPage == currentPage)&&(identical(other.lastPage, lastPage) || other.lastPage == lastPage)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.isLoadingMore, isLoadingMore) || other.isLoadingMore == isLoadingMore)&&(identical(other.busca, busca) || other.busca == busca)&&(identical(other.categoriaId, categoriaId) || other.categoriaId == categoriaId)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(_items),const DeepCollectionEquality().hash(_categorias),currentPage,lastPage,isLoading,isLoadingMore,busca,categoriaId,error);

@override
String toString() {
  return 'CatalogoState(items: $items, categorias: $categorias, currentPage: $currentPage, lastPage: $lastPage, isLoading: $isLoading, isLoadingMore: $isLoadingMore, busca: $busca, categoriaId: $categoriaId, error: $error)';
}


}

/// @nodoc
abstract mixin class _$CatalogoStateCopyWith<$Res> implements $CatalogoStateCopyWith<$Res> {
  factory _$CatalogoStateCopyWith(_CatalogoState value, $Res Function(_CatalogoState) _then) = __$CatalogoStateCopyWithImpl;
@override @useResult
$Res call({
 List<Product> items, List<Categoria> categorias, int currentPage, int lastPage, bool isLoading, bool isLoadingMore, String busca, int? categoriaId, String? error
});




}
/// @nodoc
class __$CatalogoStateCopyWithImpl<$Res>
    implements _$CatalogoStateCopyWith<$Res> {
  __$CatalogoStateCopyWithImpl(this._self, this._then);

  final _CatalogoState _self;
  final $Res Function(_CatalogoState) _then;

/// Create a copy of CatalogoState
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? items = null,Object? categorias = null,Object? currentPage = null,Object? lastPage = null,Object? isLoading = null,Object? isLoadingMore = null,Object? busca = null,Object? categoriaId = freezed,Object? error = freezed,}) {
  return _then(_CatalogoState(
items: null == items ? _self._items : items // ignore: cast_nullable_to_non_nullable
as List<Product>,categorias: null == categorias ? _self._categorias : categorias // ignore: cast_nullable_to_non_nullable
as List<Categoria>,currentPage: null == currentPage ? _self.currentPage : currentPage // ignore: cast_nullable_to_non_nullable
as int,lastPage: null == lastPage ? _self.lastPage : lastPage // ignore: cast_nullable_to_non_nullable
as int,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,isLoadingMore: null == isLoadingMore ? _self.isLoadingMore : isLoadingMore // ignore: cast_nullable_to_non_nullable
as bool,busca: null == busca ? _self.busca : busca // ignore: cast_nullable_to_non_nullable
as String,categoriaId: freezed == categoriaId ? _self.categoriaId : categoriaId // ignore: cast_nullable_to_non_nullable
as int?,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}


}

// dart format on

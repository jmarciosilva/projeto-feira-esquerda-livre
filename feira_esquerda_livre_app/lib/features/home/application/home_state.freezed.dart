// GENERATED CODE - DO NOT MODIFY BY HAND
// coverage:ignore-file
// ignore_for_file: type=lint
// ignore_for_file: unused_element, deprecated_member_use, deprecated_member_use_from_same_package, use_function_type_syntax_for_parameters, unnecessary_const, avoid_init_to_null, invalid_override_different_default_values_named, prefer_expression_function_bodies, annotate_overrides, invalid_annotation_target, unnecessary_question_mark

part of 'home_state.dart';

// **************************************************************************
// FreezedGenerator
// **************************************************************************

// dart format off
T _$identity<T>(T value) => value;
/// @nodoc
mixin _$HomeState {

 List<Product> get produtos; List<Categoria> get categoriasProdutos; List<ExpositorSummary> get lojas; List<Product> get servicos; List<Product> get cuidados; List<Noticia> get noticias; ContatoInfo? get contato; bool get isLoading; String? get error;
/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
$HomeStateCopyWith<HomeState> get copyWith => _$HomeStateCopyWithImpl<HomeState>(this as HomeState, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is HomeState&&const DeepCollectionEquality().equals(other.produtos, produtos)&&const DeepCollectionEquality().equals(other.categoriasProdutos, categoriasProdutos)&&const DeepCollectionEquality().equals(other.lojas, lojas)&&const DeepCollectionEquality().equals(other.servicos, servicos)&&const DeepCollectionEquality().equals(other.cuidados, cuidados)&&const DeepCollectionEquality().equals(other.noticias, noticias)&&(identical(other.contato, contato) || other.contato == contato)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(produtos),const DeepCollectionEquality().hash(categoriasProdutos),const DeepCollectionEquality().hash(lojas),const DeepCollectionEquality().hash(servicos),const DeepCollectionEquality().hash(cuidados),const DeepCollectionEquality().hash(noticias),contato,isLoading,error);

@override
String toString() {
  return 'HomeState(produtos: $produtos, categoriasProdutos: $categoriasProdutos, lojas: $lojas, servicos: $servicos, cuidados: $cuidados, noticias: $noticias, contato: $contato, isLoading: $isLoading, error: $error)';
}


}

/// @nodoc
abstract mixin class $HomeStateCopyWith<$Res>  {
  factory $HomeStateCopyWith(HomeState value, $Res Function(HomeState) _then) = _$HomeStateCopyWithImpl;
@useResult
$Res call({
 List<Product> produtos, List<Categoria> categoriasProdutos, List<ExpositorSummary> lojas, List<Product> servicos, List<Product> cuidados, List<Noticia> noticias, ContatoInfo? contato, bool isLoading, String? error
});


$ContatoInfoCopyWith<$Res>? get contato;

}
/// @nodoc
class _$HomeStateCopyWithImpl<$Res>
    implements $HomeStateCopyWith<$Res> {
  _$HomeStateCopyWithImpl(this._self, this._then);

  final HomeState _self;
  final $Res Function(HomeState) _then;

/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@pragma('vm:prefer-inline') @override $Res call({Object? produtos = null,Object? categoriasProdutos = null,Object? lojas = null,Object? servicos = null,Object? cuidados = null,Object? noticias = null,Object? contato = freezed,Object? isLoading = null,Object? error = freezed,}) {
  return _then(_self.copyWith(
produtos: null == produtos ? _self.produtos : produtos // ignore: cast_nullable_to_non_nullable
as List<Product>,categoriasProdutos: null == categoriasProdutos ? _self.categoriasProdutos : categoriasProdutos // ignore: cast_nullable_to_non_nullable
as List<Categoria>,lojas: null == lojas ? _self.lojas : lojas // ignore: cast_nullable_to_non_nullable
as List<ExpositorSummary>,servicos: null == servicos ? _self.servicos : servicos // ignore: cast_nullable_to_non_nullable
as List<Product>,cuidados: null == cuidados ? _self.cuidados : cuidados // ignore: cast_nullable_to_non_nullable
as List<Product>,noticias: null == noticias ? _self.noticias : noticias // ignore: cast_nullable_to_non_nullable
as List<Noticia>,contato: freezed == contato ? _self.contato : contato // ignore: cast_nullable_to_non_nullable
as ContatoInfo?,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}
/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ContatoInfoCopyWith<$Res>? get contato {
    if (_self.contato == null) {
    return null;
  }

  return $ContatoInfoCopyWith<$Res>(_self.contato!, (value) {
    return _then(_self.copyWith(contato: value));
  });
}
}


/// Adds pattern-matching-related methods to [HomeState].
extension HomeStatePatterns on HomeState {
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

@optionalTypeArgs TResult maybeMap<TResult extends Object?>(TResult Function( _HomeState value)?  $default,{required TResult orElse(),}){
final _that = this;
switch (_that) {
case _HomeState() when $default != null:
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

@optionalTypeArgs TResult map<TResult extends Object?>(TResult Function( _HomeState value)  $default,){
final _that = this;
switch (_that) {
case _HomeState():
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

@optionalTypeArgs TResult? mapOrNull<TResult extends Object?>(TResult? Function( _HomeState value)?  $default,){
final _that = this;
switch (_that) {
case _HomeState() when $default != null:
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

@optionalTypeArgs TResult maybeWhen<TResult extends Object?>(TResult Function( List<Product> produtos,  List<Categoria> categoriasProdutos,  List<ExpositorSummary> lojas,  List<Product> servicos,  List<Product> cuidados,  List<Noticia> noticias,  ContatoInfo? contato,  bool isLoading,  String? error)?  $default,{required TResult orElse(),}) {final _that = this;
switch (_that) {
case _HomeState() when $default != null:
return $default(_that.produtos,_that.categoriasProdutos,_that.lojas,_that.servicos,_that.cuidados,_that.noticias,_that.contato,_that.isLoading,_that.error);case _:
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

@optionalTypeArgs TResult when<TResult extends Object?>(TResult Function( List<Product> produtos,  List<Categoria> categoriasProdutos,  List<ExpositorSummary> lojas,  List<Product> servicos,  List<Product> cuidados,  List<Noticia> noticias,  ContatoInfo? contato,  bool isLoading,  String? error)  $default,) {final _that = this;
switch (_that) {
case _HomeState():
return $default(_that.produtos,_that.categoriasProdutos,_that.lojas,_that.servicos,_that.cuidados,_that.noticias,_that.contato,_that.isLoading,_that.error);case _:
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

@optionalTypeArgs TResult? whenOrNull<TResult extends Object?>(TResult? Function( List<Product> produtos,  List<Categoria> categoriasProdutos,  List<ExpositorSummary> lojas,  List<Product> servicos,  List<Product> cuidados,  List<Noticia> noticias,  ContatoInfo? contato,  bool isLoading,  String? error)?  $default,) {final _that = this;
switch (_that) {
case _HomeState() when $default != null:
return $default(_that.produtos,_that.categoriasProdutos,_that.lojas,_that.servicos,_that.cuidados,_that.noticias,_that.contato,_that.isLoading,_that.error);case _:
  return null;

}
}

}

/// @nodoc


class _HomeState implements HomeState {
  const _HomeState({final  List<Product> produtos = const [], final  List<Categoria> categoriasProdutos = const [], final  List<ExpositorSummary> lojas = const [], final  List<Product> servicos = const [], final  List<Product> cuidados = const [], final  List<Noticia> noticias = const [], this.contato, this.isLoading = true, this.error}): _produtos = produtos,_categoriasProdutos = categoriasProdutos,_lojas = lojas,_servicos = servicos,_cuidados = cuidados,_noticias = noticias;
  

 final  List<Product> _produtos;
@override@JsonKey() List<Product> get produtos {
  if (_produtos is EqualUnmodifiableListView) return _produtos;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_produtos);
}

 final  List<Categoria> _categoriasProdutos;
@override@JsonKey() List<Categoria> get categoriasProdutos {
  if (_categoriasProdutos is EqualUnmodifiableListView) return _categoriasProdutos;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_categoriasProdutos);
}

 final  List<ExpositorSummary> _lojas;
@override@JsonKey() List<ExpositorSummary> get lojas {
  if (_lojas is EqualUnmodifiableListView) return _lojas;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_lojas);
}

 final  List<Product> _servicos;
@override@JsonKey() List<Product> get servicos {
  if (_servicos is EqualUnmodifiableListView) return _servicos;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_servicos);
}

 final  List<Product> _cuidados;
@override@JsonKey() List<Product> get cuidados {
  if (_cuidados is EqualUnmodifiableListView) return _cuidados;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_cuidados);
}

 final  List<Noticia> _noticias;
@override@JsonKey() List<Noticia> get noticias {
  if (_noticias is EqualUnmodifiableListView) return _noticias;
  // ignore: implicit_dynamic_type
  return EqualUnmodifiableListView(_noticias);
}

@override final  ContatoInfo? contato;
@override@JsonKey() final  bool isLoading;
@override final  String? error;

/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@override @JsonKey(includeFromJson: false, includeToJson: false)
@pragma('vm:prefer-inline')
_$HomeStateCopyWith<_HomeState> get copyWith => __$HomeStateCopyWithImpl<_HomeState>(this, _$identity);



@override
bool operator ==(Object other) {
  return identical(this, other) || (other.runtimeType == runtimeType&&other is _HomeState&&const DeepCollectionEquality().equals(other._produtos, _produtos)&&const DeepCollectionEquality().equals(other._categoriasProdutos, _categoriasProdutos)&&const DeepCollectionEquality().equals(other._lojas, _lojas)&&const DeepCollectionEquality().equals(other._servicos, _servicos)&&const DeepCollectionEquality().equals(other._cuidados, _cuidados)&&const DeepCollectionEquality().equals(other._noticias, _noticias)&&(identical(other.contato, contato) || other.contato == contato)&&(identical(other.isLoading, isLoading) || other.isLoading == isLoading)&&(identical(other.error, error) || other.error == error));
}


@override
int get hashCode => Object.hash(runtimeType,const DeepCollectionEquality().hash(_produtos),const DeepCollectionEquality().hash(_categoriasProdutos),const DeepCollectionEquality().hash(_lojas),const DeepCollectionEquality().hash(_servicos),const DeepCollectionEquality().hash(_cuidados),const DeepCollectionEquality().hash(_noticias),contato,isLoading,error);

@override
String toString() {
  return 'HomeState(produtos: $produtos, categoriasProdutos: $categoriasProdutos, lojas: $lojas, servicos: $servicos, cuidados: $cuidados, noticias: $noticias, contato: $contato, isLoading: $isLoading, error: $error)';
}


}

/// @nodoc
abstract mixin class _$HomeStateCopyWith<$Res> implements $HomeStateCopyWith<$Res> {
  factory _$HomeStateCopyWith(_HomeState value, $Res Function(_HomeState) _then) = __$HomeStateCopyWithImpl;
@override @useResult
$Res call({
 List<Product> produtos, List<Categoria> categoriasProdutos, List<ExpositorSummary> lojas, List<Product> servicos, List<Product> cuidados, List<Noticia> noticias, ContatoInfo? contato, bool isLoading, String? error
});


@override $ContatoInfoCopyWith<$Res>? get contato;

}
/// @nodoc
class __$HomeStateCopyWithImpl<$Res>
    implements _$HomeStateCopyWith<$Res> {
  __$HomeStateCopyWithImpl(this._self, this._then);

  final _HomeState _self;
  final $Res Function(_HomeState) _then;

/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@override @pragma('vm:prefer-inline') $Res call({Object? produtos = null,Object? categoriasProdutos = null,Object? lojas = null,Object? servicos = null,Object? cuidados = null,Object? noticias = null,Object? contato = freezed,Object? isLoading = null,Object? error = freezed,}) {
  return _then(_HomeState(
produtos: null == produtos ? _self._produtos : produtos // ignore: cast_nullable_to_non_nullable
as List<Product>,categoriasProdutos: null == categoriasProdutos ? _self._categoriasProdutos : categoriasProdutos // ignore: cast_nullable_to_non_nullable
as List<Categoria>,lojas: null == lojas ? _self._lojas : lojas // ignore: cast_nullable_to_non_nullable
as List<ExpositorSummary>,servicos: null == servicos ? _self._servicos : servicos // ignore: cast_nullable_to_non_nullable
as List<Product>,cuidados: null == cuidados ? _self._cuidados : cuidados // ignore: cast_nullable_to_non_nullable
as List<Product>,noticias: null == noticias ? _self._noticias : noticias // ignore: cast_nullable_to_non_nullable
as List<Noticia>,contato: freezed == contato ? _self.contato : contato // ignore: cast_nullable_to_non_nullable
as ContatoInfo?,isLoading: null == isLoading ? _self.isLoading : isLoading // ignore: cast_nullable_to_non_nullable
as bool,error: freezed == error ? _self.error : error // ignore: cast_nullable_to_non_nullable
as String?,
  ));
}

/// Create a copy of HomeState
/// with the given fields replaced by the non-null parameter values.
@override
@pragma('vm:prefer-inline')
$ContatoInfoCopyWith<$Res>? get contato {
    if (_self.contato == null) {
    return null;
  }

  return $ContatoInfoCopyWith<$Res>(_self.contato!, (value) {
    return _then(_self.copyWith(contato: value));
  });
}
}

// dart format on

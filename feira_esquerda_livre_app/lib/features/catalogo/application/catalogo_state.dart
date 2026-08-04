import 'package:freezed_annotation/freezed_annotation.dart';

import '../domain/categoria.dart';
import '../domain/product.dart';

part 'catalogo_state.freezed.dart';

@freezed
abstract class CatalogoState with _$CatalogoState {
  const CatalogoState._();

  const factory CatalogoState({
    @Default([]) List<Product> items,
    @Default([]) List<Categoria> categorias,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(true) bool isLoading,
    @Default(false) bool isLoadingMore,
    @Default('') String busca,
    int? categoriaId,
    String? error,
  }) = _CatalogoState;

  bool get hasMore => currentPage < lastPage;
}

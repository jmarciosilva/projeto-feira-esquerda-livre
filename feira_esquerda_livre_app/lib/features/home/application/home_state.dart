import 'package:freezed_annotation/freezed_annotation.dart';

import '../../auth/domain/expositor_summary.dart';
import '../../catalogo/domain/categoria.dart';
import '../../catalogo/domain/product.dart';

part 'home_state.freezed.dart';

@freezed
abstract class HomeState with _$HomeState {
  const factory HomeState({
    @Default([]) List<Product> produtos,
    @Default([]) List<Categoria> categoriasProdutos,
    @Default([]) List<ExpositorSummary> lojas,
    @Default([]) List<Product> servicos,
    @Default([]) List<Product> cuidados,
    @Default(true) bool isLoading,
    String? error,
  }) = _HomeState;
}

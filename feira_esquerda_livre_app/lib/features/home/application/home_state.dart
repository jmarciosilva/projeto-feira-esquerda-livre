import 'package:freezed_annotation/freezed_annotation.dart';

import '../../auth/domain/expositor_summary.dart';
import '../../catalogo/domain/categoria.dart';
import '../../catalogo/domain/product.dart';
import '../../contato/domain/contato_info.dart';
import '../../noticias/domain/noticia.dart';

part 'home_state.freezed.dart';

@freezed
abstract class HomeState with _$HomeState {
  const factory HomeState({
    @Default([]) List<Product> produtos,
    @Default([]) List<Categoria> categoriasProdutos,
    @Default([]) List<ExpositorSummary> lojas,
    @Default([]) List<Product> servicos,
    @Default([]) List<Product> cuidados,
    @Default([]) List<Noticia> noticias,
    ContatoInfo? contato,
    @Default(true) bool isLoading,
    String? error,
  }) = _HomeState;
}

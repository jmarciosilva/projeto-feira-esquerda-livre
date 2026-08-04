import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../../auth/domain/expositor_summary.dart';
import '../../catalogo/data/catalogo_api.dart';
import '../../catalogo/domain/categoria.dart';
import '../../catalogo/domain/product.dart';
import '../../loja/data/loja_api.dart';
import 'home_state.dart';

/// Carrega os dados dos 4 carrosséis da Home (produtos, lojas, serviços e
/// cuidados) em paralelo — cada carrossel mostra só a primeira página.
class HomeController extends Notifier<HomeState> {
  @override
  HomeState build() {
    Future.microtask(_carregar);
    return const HomeState();
  }

  Future<void> _carregar() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final catalogoApi = ref.read(catalogoApiProvider);
      final lojaApi = ref.read(lojaApiProvider);

      // Future.wait garante que todas as chamadas sejam aguardadas mesmo se
      // uma delas lançar antes das outras terminarem.
      final results = await Future.wait([
        catalogoApi.listar(Eixo.produto),
        catalogoApi.categorias(eixo: Eixo.produto.name),
        lojaApi.listar(),
        catalogoApi.listar(Eixo.servico),
        catalogoApi.listar(Eixo.cuidado),
      ]);

      state = state.copyWith(
        produtos: (results[0] as Paginated<Product>).data,
        categoriasProdutos: results[1] as List<Categoria>,
        lojas: (results[2] as Paginated<ExpositorSummary>).data,
        servicos: (results[3] as Paginated<Product>).data,
        cuidados: (results[4] as Paginated<Product>).data,
        isLoading: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoading: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar a home.');
    }
  }

  Future<void> tentarNovamente() => _carregar();
}

final homeControllerProvider = NotifierProvider<HomeController, HomeState>(HomeController.new);

import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../data/catalogo_api.dart';
import '../domain/categoria.dart';
import '../domain/product.dart';
import 'catalogo_state.dart';

/// Um controller por eixo (Produtos/Serviços/Cuidados) — cada aba tem seu
/// próprio estado de paginação/filtro, mantidos independentes pelo `family`.
class CatalogoController extends FamilyNotifier<CatalogoState, Eixo> {
  late final Eixo _eixo;

  @override
  CatalogoState build(Eixo arg) {
    _eixo = arg;
    Future.microtask(_carregarInicial);
    return const CatalogoState();
  }

  Future<void> _carregarInicial() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final api = ref.read(catalogoApiProvider);
      // Dispara as duas chamadas em paralelo, mas usa Future.wait para que
      // ambas sejam sempre aguardadas — se awaitássemos uma de cada vez e a
      // primeira lançasse, a segunda rejeitaria sem ninguém ouvindo.
      final results = await Future.wait([
        api.listar(_eixo, busca: state.busca, categoriaId: state.categoriaId),
        api.categorias(eixo: _eixo.name),
      ]);
      final page = results[0] as Paginated<Product>;
      final categorias = results[1] as List<Categoria>;
      state = state.copyWith(
        items: page.data,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        categorias: categorias,
        isLoading: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoading: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar o catálogo.');
    }
  }

  /// Usado pelo botão "Tentar novamente" da tela de erro — refaz a carga
  /// completa (produtos + categorias) com os filtros atuais.
  Future<void> tentarNovamente() => _carregarInicial();

  Future<void> carregarMais() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);
    try {
      final api = ref.read(catalogoApiProvider);
      final page = await api.listar(
        _eixo,
        page: state.currentPage + 1,
        busca: state.busca,
        categoriaId: state.categoriaId,
      );
      state = state.copyWith(
        items: [...state.items, ...page.data],
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        isLoadingMore: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoadingMore: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoadingMore: false, error: 'Não foi possível carregar mais itens.');
    }
  }

  Future<void> buscar(String termo) async {
    state = state.copyWith(busca: termo);
    await _recarregar();
  }

  Future<void> filtrarPorCategoria(int? categoriaId) async {
    state = state.copyWith(categoriaId: categoriaId);
    await _recarregar();
  }

  Future<void> _recarregar() async {
    state = state.copyWith(isLoading: true, error: null, currentPage: 1, lastPage: 1);
    try {
      final api = ref.read(catalogoApiProvider);
      final page = await api.listar(_eixo, busca: state.busca, categoriaId: state.categoriaId);
      state = state.copyWith(
        items: page.data,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        isLoading: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoading: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar o catálogo.');
    }
  }
}

final catalogoControllerProvider =
    NotifierProvider.family<CatalogoController, CatalogoState, Eixo>(CatalogoController.new);

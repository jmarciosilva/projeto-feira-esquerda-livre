import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_exception.dart';
import '../data/noticia_api.dart';
import 'noticias_state.dart';

/// Lista paginada de notícias publicadas — usada na tela "Notícias"
/// (destino do "Ver tudo" do carrossel de notícias da Home).
class NoticiasController extends Notifier<NoticiasState> {
  @override
  NoticiasState build() {
    Future.microtask(_carregarInicial);
    return const NoticiasState();
  }

  Future<void> _carregarInicial() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final api = ref.read(noticiaApiProvider);
      final page = await api.listar();
      state = state.copyWith(
        items: page.data,
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        isLoading: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoading: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar as notícias.');
    }
  }

  Future<void> tentarNovamente() => _carregarInicial();

  Future<void> carregarMais() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);
    try {
      final api = ref.read(noticiaApiProvider);
      final page = await api.listar(page: state.currentPage + 1);
      state = state.copyWith(
        items: [...state.items, ...page.data],
        currentPage: page.currentPage,
        lastPage: page.lastPage,
        isLoadingMore: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoadingMore: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoadingMore: false, error: 'Não foi possível carregar mais notícias.');
    }
  }
}

final noticiasControllerProvider =
    NotifierProvider<NoticiasController, NoticiasState>(NoticiasController.new);

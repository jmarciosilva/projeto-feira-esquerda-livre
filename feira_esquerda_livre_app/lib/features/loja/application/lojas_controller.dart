import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_exception.dart';
import '../data/loja_api.dart';
import 'lojas_state.dart';

/// Lista paginada de todas as lojas ativas — usada na tela "Lojas" (destino
/// do "ver tudo" do carrossel de expositores da Home).
class LojasController extends Notifier<LojasState> {
  @override
  LojasState build() {
    Future.microtask(_carregarInicial);
    return const LojasState();
  }

  Future<void> _carregarInicial() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final api = ref.read(lojaApiProvider);
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
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar as lojas.');
    }
  }

  Future<void> tentarNovamente() => _carregarInicial();

  Future<void> carregarMais() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);
    try {
      final api = ref.read(lojaApiProvider);
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
      state = state.copyWith(isLoadingMore: false, error: 'Não foi possível carregar mais lojas.');
    }
  }
}

final lojasControllerProvider = NotifierProvider<LojasController, LojasState>(LojasController.new);

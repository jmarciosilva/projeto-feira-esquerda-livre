import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_exception.dart';
import '../data/feed_api.dart';
import 'feed_state.dart';

class FeedController extends Notifier<FeedState> {
  @override
  FeedState build() {
    Future.microtask(_carregarInicial);
    return const FeedState();
  }

  Future<void> _carregarInicial() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final pagina = await ref.read(feedApiProvider).listar();
      state = state.copyWith(
        items: pagina.data,
        currentPage: pagina.currentPage,
        lastPage: pagina.lastPage,
        isLoading: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoading: false, error: error.message);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'Não foi possível carregar o feed.');
    }
  }

  Future<void> tentarNovamente() => _carregarInicial();

  Future<void> carregarMais() async {
    if (state.isLoadingMore || !state.hasMore) return;

    state = state.copyWith(isLoadingMore: true);
    try {
      final pagina = await ref.read(feedApiProvider).listar(page: state.currentPage + 1);
      state = state.copyWith(
        items: [...state.items, ...pagina.data],
        currentPage: pagina.currentPage,
        lastPage: pagina.lastPage,
        isLoadingMore: false,
      );
    } on ApiException catch (error) {
      state = state.copyWith(isLoadingMore: false, error: error.message);
    }
  }

  /// Atualização otimista: alterna curtir na hora, sem esperar a resposta;
  /// se a chamada falhar (ex.: sessão expirou), desfaz.
  Future<void> curtir(int postId) async {
    final index = state.items.indexWhere((p) => p.id == postId);
    if (index == -1) return;

    final original = state.items[index];
    final otimista = original.copyWith(
      likedByMe: !original.likedByMe,
      likesCount: original.likedByMe ? original.likesCount - 1 : original.likesCount + 1,
    );
    state = state.copyWith(items: _replace(state.items, index, otimista));

    try {
      final (liked, likesCount) = await ref.read(feedApiProvider).curtir(postId);
      final atualIndex = state.items.indexWhere((p) => p.id == postId);
      if (atualIndex == -1) return;
      state = state.copyWith(
        items: _replace(
          state.items,
          atualIndex,
          state.items[atualIndex].copyWith(likedByMe: liked, likesCount: likesCount),
        ),
      );
    } catch (_) {
      final atualIndex = state.items.indexWhere((p) => p.id == postId);
      if (atualIndex == -1) return;
      state = state.copyWith(items: _replace(state.items, atualIndex, original));
    }
  }

  void atualizarContagemComentarios(int postId, int novaContagem) {
    final index = state.items.indexWhere((p) => p.id == postId);
    if (index == -1) return;
    state = state.copyWith(
      items: _replace(state.items, index, state.items[index].copyWith(commentsCount: novaContagem)),
    );
  }

  List<T> _replace<T>(List<T> list, int index, T value) {
    final copy = [...list];
    copy[index] = value;
    return copy;
  }
}

final feedControllerProvider = NotifierProvider<FeedController, FeedState>(FeedController.new);

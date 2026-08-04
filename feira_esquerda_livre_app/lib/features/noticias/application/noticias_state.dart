import 'package:freezed_annotation/freezed_annotation.dart';

import '../domain/noticia.dart';

part 'noticias_state.freezed.dart';

@freezed
abstract class NoticiasState with _$NoticiasState {
  const NoticiasState._();

  const factory NoticiasState({
    @Default([]) List<Noticia> items,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(true) bool isLoading,
    @Default(false) bool isLoadingMore,
    String? error,
  }) = _NoticiasState;

  bool get hasMore => currentPage < lastPage;
}

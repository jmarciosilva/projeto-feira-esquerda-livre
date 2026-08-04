import 'package:freezed_annotation/freezed_annotation.dart';

import '../../auth/domain/expositor_summary.dart';

part 'lojas_state.freezed.dart';

@freezed
abstract class LojasState with _$LojasState {
  const LojasState._();

  const factory LojasState({
    @Default([]) List<ExpositorSummary> items,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(true) bool isLoading,
    @Default(false) bool isLoadingMore,
    String? error,
  }) = _LojasState;

  bool get hasMore => currentPage < lastPage;
}

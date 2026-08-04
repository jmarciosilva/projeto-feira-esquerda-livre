import 'package:freezed_annotation/freezed_annotation.dart';

import '../domain/feed_post.dart';

part 'feed_state.freezed.dart';

@freezed
abstract class FeedState with _$FeedState {
  const FeedState._();

  const factory FeedState({
    @Default([]) List<FeedPost> items,
    @Default(1) int currentPage,
    @Default(1) int lastPage,
    @Default(true) bool isLoading,
    @Default(false) bool isLoadingMore,
    String? error,
  }) = _FeedState;

  bool get hasMore => currentPage < lastPage;
}

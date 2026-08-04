import 'package:freezed_annotation/freezed_annotation.dart';

part 'feed_comment.freezed.dart';
part 'feed_comment.g.dart';

/// Espelha `FeedCommentResource` no backend.
@freezed
abstract class FeedComment with _$FeedComment {
  const factory FeedComment({
    required int id,
    required String content,
    @JsonKey(name: 'user_name') String? userName,
    @JsonKey(name: 'created_at') DateTime? createdAt,
  }) = _FeedComment;

  factory FeedComment.fromJson(Map<String, dynamic> json) => _$FeedCommentFromJson(json);
}

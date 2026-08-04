import 'package:freezed_annotation/freezed_annotation.dart';

import '../../catalogo/domain/product_image.dart';
import 'feed_expositor.dart';

part 'feed_post.freezed.dart';
part 'feed_post.g.dart';

/// Espelha `FeedPostResource` no backend.
@freezed
abstract class FeedPost with _$FeedPost {
  const factory FeedPost({
    required int id,
    String? type,
    @JsonKey(name: 'type_label') String? typeLabel,
    required String content,
    @Default([]) List<ProductImage> images,
    FeedExpositor? expositor,
    @JsonKey(name: 'likes_count') required int likesCount,
    @JsonKey(name: 'comments_count') required int commentsCount,
    @JsonKey(name: 'liked_by_me') required bool likedByMe,
    @JsonKey(name: 'created_at') DateTime? createdAt,
  }) = _FeedPost;

  factory FeedPost.fromJson(Map<String, dynamic> json) => _$FeedPostFromJson(json);
}

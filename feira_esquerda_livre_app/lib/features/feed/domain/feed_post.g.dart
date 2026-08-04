// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'feed_post.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_FeedPost _$FeedPostFromJson(Map<String, dynamic> json) => _FeedPost(
  id: (json['id'] as num).toInt(),
  type: json['type'] as String?,
  typeLabel: json['type_label'] as String?,
  content: json['content'] as String,
  images:
      (json['images'] as List<dynamic>?)
          ?.map((e) => ProductImage.fromJson(e as Map<String, dynamic>))
          .toList() ??
      const [],
  expositor: json['expositor'] == null
      ? null
      : FeedExpositor.fromJson(json['expositor'] as Map<String, dynamic>),
  likesCount: (json['likes_count'] as num).toInt(),
  commentsCount: (json['comments_count'] as num).toInt(),
  likedByMe: json['liked_by_me'] as bool,
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
);

Map<String, dynamic> _$FeedPostToJson(_FeedPost instance) => <String, dynamic>{
  'id': instance.id,
  'type': instance.type,
  'type_label': instance.typeLabel,
  'content': instance.content,
  'images': instance.images,
  'expositor': instance.expositor,
  'likes_count': instance.likesCount,
  'comments_count': instance.commentsCount,
  'liked_by_me': instance.likedByMe,
  'created_at': instance.createdAt?.toIso8601String(),
};

// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'feed_comment.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_FeedComment _$FeedCommentFromJson(Map<String, dynamic> json) => _FeedComment(
  id: (json['id'] as num).toInt(),
  content: json['content'] as String,
  userName: json['user_name'] as String?,
  createdAt: json['created_at'] == null
      ? null
      : DateTime.parse(json['created_at'] as String),
);

Map<String, dynamic> _$FeedCommentToJson(_FeedComment instance) =>
    <String, dynamic>{
      'id': instance.id,
      'content': instance.content,
      'user_name': instance.userName,
      'created_at': instance.createdAt?.toIso8601String(),
    };

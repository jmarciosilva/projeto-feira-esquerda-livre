// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'noticia.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Noticia _$NoticiaFromJson(Map<String, dynamic> json) => _Noticia(
  id: (json['id'] as num).toInt(),
  title: json['title'] as String,
  slug: json['slug'] as String,
  excerpt: json['excerpt'] as String?,
  content: json['content'] as String?,
  imageUrl: json['image_url'] as String?,
  authorName: json['author_name'] as String?,
  publishedAt: json['published_at'] == null
      ? null
      : DateTime.parse(json['published_at'] as String),
);

Map<String, dynamic> _$NoticiaToJson(_Noticia instance) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'slug': instance.slug,
  'excerpt': instance.excerpt,
  'content': instance.content,
  'image_url': instance.imageUrl,
  'author_name': instance.authorName,
  'published_at': instance.publishedAt?.toIso8601String(),
};

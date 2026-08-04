// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'feed_expositor.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_FeedExpositor _$FeedExpositorFromJson(Map<String, dynamic> json) =>
    _FeedExpositor(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      slug: json['slug'] as String,
      logoUrl: json['logo_url'] as String?,
    );

Map<String, dynamic> _$FeedExpositorToJson(_FeedExpositor instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
      'logo_url': instance.logoUrl,
    };

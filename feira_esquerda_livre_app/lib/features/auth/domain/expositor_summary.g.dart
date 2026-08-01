// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'expositor_summary.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_ExpositorSummary _$ExpositorSummaryFromJson(Map<String, dynamic> json) =>
    _ExpositorSummary(
      id: (json['id'] as num).toInt(),
      name: json['name'] as String,
      slug: json['slug'] as String,
      description: json['description'] as String?,
      eixos: (json['eixos'] as List<dynamic>?)
          ?.map((e) => e as String)
          .toList(),
      logoUrl: json['logo_url'] as String?,
      imageUrl: json['image_url'] as String?,
      city: json['city'] as String?,
      state: json['state'] as String?,
      whatsapp: json['whatsapp'] as String?,
      instagramUrl: json['instagram_url'] as String?,
      facebookUrl: json['facebook_url'] as String?,
      isActive: json['is_active'] as bool,
    );

Map<String, dynamic> _$ExpositorSummaryToJson(_ExpositorSummary instance) =>
    <String, dynamic>{
      'id': instance.id,
      'name': instance.name,
      'slug': instance.slug,
      'description': instance.description,
      'eixos': instance.eixos,
      'logo_url': instance.logoUrl,
      'image_url': instance.imageUrl,
      'city': instance.city,
      'state': instance.state,
      'whatsapp': instance.whatsapp,
      'instagram_url': instance.instagramUrl,
      'facebook_url': instance.facebookUrl,
      'is_active': instance.isActive,
    };

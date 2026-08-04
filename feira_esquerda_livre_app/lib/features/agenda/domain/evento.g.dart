// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'evento.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Evento _$EventoFromJson(Map<String, dynamic> json) => _Evento(
  id: (json['id'] as num).toInt(),
  title: json['title'] as String,
  slug: json['slug'] as String,
  description: json['description'] as String?,
  city: json['city'] as String?,
  state: json['state'] as String?,
  address: json['address'] as String?,
  latitude: (json['latitude'] as num?)?.toDouble(),
  longitude: (json['longitude'] as num?)?.toDouble(),
  startDate: json['start_date'] == null
      ? null
      : DateTime.parse(json['start_date'] as String),
  endDate: json['end_date'] == null
      ? null
      : DateTime.parse(json['end_date'] as String),
  imageUrl: json['image_url'] as String?,
  bannerImageUrl: json['banner_image_url'] as String?,
  isFeatured: json['is_featured'] as bool,
  capacidadeExpositores: (json['capacidade_expositores'] as num?)?.toInt(),
  vagasRestantes: (json['vagas_restantes'] as num?)?.toInt(),
  expositores: (json['expositores'] as List<dynamic>?)
      ?.map((e) => ExpositorSummary.fromJson(e as Map<String, dynamic>))
      .toList(),
);

Map<String, dynamic> _$EventoToJson(_Evento instance) => <String, dynamic>{
  'id': instance.id,
  'title': instance.title,
  'slug': instance.slug,
  'description': instance.description,
  'city': instance.city,
  'state': instance.state,
  'address': instance.address,
  'latitude': instance.latitude,
  'longitude': instance.longitude,
  'start_date': instance.startDate?.toIso8601String(),
  'end_date': instance.endDate?.toIso8601String(),
  'image_url': instance.imageUrl,
  'banner_image_url': instance.bannerImageUrl,
  'is_featured': instance.isFeatured,
  'capacidade_expositores': instance.capacidadeExpositores,
  'vagas_restantes': instance.vagasRestantes,
  'expositores': instance.expositores,
};

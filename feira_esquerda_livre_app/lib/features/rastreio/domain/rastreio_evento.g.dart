// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'rastreio_evento.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_RastreioEvento _$RastreioEventoFromJson(Map<String, dynamic> json) =>
    _RastreioEvento(
      status: json['status'] as String,
      description: json['description'] as String,
      location: json['location'] as String?,
      happenedAt: json['happened_at'] == null
          ? null
          : DateTime.parse(json['happened_at'] as String),
    );

Map<String, dynamic> _$RastreioEventoToJson(_RastreioEvento instance) =>
    <String, dynamic>{
      'status': instance.status,
      'description': instance.description,
      'location': instance.location,
      'happened_at': instance.happenedAt?.toIso8601String(),
    };

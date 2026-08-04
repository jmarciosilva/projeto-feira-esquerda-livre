// GENERATED CODE - DO NOT MODIFY BY HAND

part of 'rastreio.dart';

// **************************************************************************
// JsonSerializableGenerator
// **************************************************************************

_Rastreio _$RastreioFromJson(Map<String, dynamic> json) => _Rastreio(
  status: json['status'] as String?,
  carrier: json['carrier'] as String?,
  serviceName: json['service_name'] as String?,
  trackingCode: json['tracking_code'] as String?,
  shippedAt: json['shipped_at'] == null
      ? null
      : DateTime.parse(json['shipped_at'] as String),
  deliveredAt: json['delivered_at'] == null
      ? null
      : DateTime.parse(json['delivered_at'] as String),
  estimatedDeliveryDate: json['estimated_delivery_date'] == null
      ? null
      : DateTime.parse(json['estimated_delivery_date'] as String),
  carrierTrackingUrl: json['carrier_tracking_url'] as String?,
  expositor: json['expositor'] as Map<String, dynamic>?,
  events:
      (json['events'] as List<dynamic>?)
          ?.map((e) => RastreioEvento.fromJson(e as Map<String, dynamic>))
          .toList() ??
      const [],
);

Map<String, dynamic> _$RastreioToJson(_Rastreio instance) => <String, dynamic>{
  'status': instance.status,
  'carrier': instance.carrier,
  'service_name': instance.serviceName,
  'tracking_code': instance.trackingCode,
  'shipped_at': instance.shippedAt?.toIso8601String(),
  'delivered_at': instance.deliveredAt?.toIso8601String(),
  'estimated_delivery_date': instance.estimatedDeliveryDate?.toIso8601String(),
  'carrier_tracking_url': instance.carrierTrackingUrl,
  'expositor': instance.expositor,
  'events': instance.events,
};

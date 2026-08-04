import 'package:freezed_annotation/freezed_annotation.dart';

import 'rastreio_evento.dart';

part 'rastreio.freezed.dart';
part 'rastreio.g.dart';

/// Espelha `RastreioResource` no backend.
@freezed
abstract class Rastreio with _$Rastreio {
  const factory Rastreio({
    String? status,
    String? carrier,
    @JsonKey(name: 'service_name') String? serviceName,
    @JsonKey(name: 'tracking_code') String? trackingCode,
    @JsonKey(name: 'shipped_at') DateTime? shippedAt,
    @JsonKey(name: 'delivered_at') DateTime? deliveredAt,
    @JsonKey(name: 'estimated_delivery_date') DateTime? estimatedDeliveryDate,
    @JsonKey(name: 'carrier_tracking_url') String? carrierTrackingUrl,
    Map<String, dynamic>? expositor,
    @Default([]) List<RastreioEvento> events,
  }) = _Rastreio;

  factory Rastreio.fromJson(Map<String, dynamic> json) => _$RastreioFromJson(json);
}

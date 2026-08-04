import 'package:freezed_annotation/freezed_annotation.dart';

part 'rastreio_evento.freezed.dart';
part 'rastreio_evento.g.dart';

@freezed
abstract class RastreioEvento with _$RastreioEvento {
  const factory RastreioEvento({
    required String status,
    required String description,
    String? location,
    @JsonKey(name: 'happened_at') DateTime? happenedAt,
  }) = _RastreioEvento;

  factory RastreioEvento.fromJson(Map<String, dynamic> json) => _$RastreioEventoFromJson(json);
}

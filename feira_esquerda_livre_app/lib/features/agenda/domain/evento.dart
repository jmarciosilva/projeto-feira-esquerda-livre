import 'package:freezed_annotation/freezed_annotation.dart';

import '../../auth/domain/expositor_summary.dart';

part 'evento.freezed.dart';
part 'evento.g.dart';

/// Espelha `EventoResource` no backend.
@freezed
abstract class Evento with _$Evento {
  const factory Evento({
    required int id,
    required String title,
    required String slug,
    String? description,
    String? city,
    String? state,
    String? address,
    double? latitude,
    double? longitude,
    @JsonKey(name: 'start_date') DateTime? startDate,
    @JsonKey(name: 'end_date') DateTime? endDate,
    @JsonKey(name: 'image_url') String? imageUrl,
    @JsonKey(name: 'banner_image_url') String? bannerImageUrl,
    @JsonKey(name: 'is_featured') required bool isFeatured,
    @JsonKey(name: 'capacidade_expositores') int? capacidadeExpositores,
    @JsonKey(name: 'vagas_restantes') int? vagasRestantes,
    List<ExpositorSummary>? expositores,
  }) = _Evento;

  factory Evento.fromJson(Map<String, dynamic> json) => _$EventoFromJson(json);
}

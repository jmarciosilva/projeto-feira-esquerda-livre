import 'package:freezed_annotation/freezed_annotation.dart';

part 'expositor_summary.freezed.dart';
part 'expositor_summary.g.dart';

/// Espelha `ExpositorResource` (`app/Http/Resources/Api/V1/ExpositorResource.php`
/// no backend) — usado dentro da resposta de `/auth/eu` quando o usuário é lojista.
@freezed
abstract class ExpositorSummary with _$ExpositorSummary {
  const factory ExpositorSummary({
    required int id,
    required String name,
    required String slug,
    String? description,
    List<String>? eixos,
    @JsonKey(name: 'logo_url') String? logoUrl,
    @JsonKey(name: 'image_url') String? imageUrl,
    String? city,
    String? state,
    String? whatsapp,
    @JsonKey(name: 'instagram_url') String? instagramUrl,
    @JsonKey(name: 'facebook_url') String? facebookUrl,
    @JsonKey(name: 'is_active') required bool isActive,
  }) = _ExpositorSummary;

  factory ExpositorSummary.fromJson(Map<String, dynamic> json) =>
      _$ExpositorSummaryFromJson(json);
}

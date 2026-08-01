import 'package:freezed_annotation/freezed_annotation.dart';

import 'expositor_summary.dart';

part 'user.freezed.dart';
part 'user.g.dart';

/// Espelha `UserResource` (`app/Http/Resources/Api/V1/UserResource.php` no
/// backend) — retornado por `/auth/registrar`, `/auth/entrar` e `/auth/eu`.
@freezed
abstract class User with _$User {
  const User._();

  const factory User({
    required int id,
    required String name,
    required String email,
    String? whatsapp,
    required String role,
    @JsonKey(name: 'role_label') required String roleLabel,
    @JsonKey(name: 'is_active') required bool isActive,
    @JsonKey(name: 'marketplace_status') String? marketplaceStatus,
    ExpositorSummary? expositor,
  }) = _User;

  factory User.fromJson(Map<String, dynamic> json) => _$UserFromJson(json);

  bool get isLojista => role == 'lojista';

  bool get isCliente => role == 'user';
}

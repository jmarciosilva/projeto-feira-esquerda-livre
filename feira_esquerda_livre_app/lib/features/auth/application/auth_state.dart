import 'package:freezed_annotation/freezed_annotation.dart';

import '../domain/user.dart';

part 'auth_state.freezed.dart';

/// Estado usado pelo roteador para decidir qual área mostrar. Loading/erro
/// de formulário (login, cadastro) é tratado localmente nas telas, não aqui.
@freezed
sealed class AuthState with _$AuthState {
  /// Ainda não sabemos se há uma sessão válida (app acabou de abrir).
  const factory AuthState.unknown() = AuthUnknown;

  const factory AuthState.unauthenticated() = AuthUnauthenticated;

  const factory AuthState.authenticated(User user) = AuthAuthenticated;
}

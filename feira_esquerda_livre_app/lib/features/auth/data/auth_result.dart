import '../domain/user.dart';

/// Resposta de `/auth/registrar` e `/auth/entrar`: `{ "user": {...}, "token": "..." }`.
class AuthResult {
  AuthResult({required this.user, required this.token});

  final User user;
  final String token;
}

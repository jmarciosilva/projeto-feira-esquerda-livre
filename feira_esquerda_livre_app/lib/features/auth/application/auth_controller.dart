import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/storage/token_storage.dart';
import '../data/auth_api.dart';
import 'auth_state.dart';

/// Única fonte de verdade sobre "quem está logado" no app. O roteador
/// (`core/router`) escuta esse provider para decidir entre login, área do
/// cliente e área do lojista.
class AuthController extends Notifier<AuthState> {
  late final AuthApi _authApi;
  late final TokenStorage _tokenStorage;

  @override
  AuthState build() {
    _authApi = ref.watch(authApiProvider);
    _tokenStorage = ref.watch(tokenStorageProvider);

    // Qualquer 401 em qualquer chamada da API derruba a sessão local.
    final subscription = ref.watch(apiClientProvider).onUnauthorized.listen((_) {
      state = const AuthState.unauthenticated();
    });
    ref.onDispose(subscription.cancel);

    return const AuthState.unknown();
  }

  /// Chamado uma vez, ao abrir o app: tenta restaurar a sessão a partir do
  /// token salvo. Deve ser aguardado antes do roteador decidir a rota inicial.
  Future<void> restoreSession() async {
    final token = await _tokenStorage.readToken();
    if (token == null) {
      state = const AuthState.unauthenticated();
      return;
    }

    try {
      final user = await _authApi.me();
      state = AuthState.authenticated(user);
    } catch (_) {
      // Token inválido/expirado — o interceptor já limpou o storage no 401.
      state = const AuthState.unauthenticated();
    }
  }

  /// Lança [ApiException] em caso de erro — a tela de login trata e exibe.
  Future<void> login({required String email, required String password}) async {
    final result = await _authApi.login(email: email, password: password);
    await _tokenStorage.saveToken(result.token);
    state = AuthState.authenticated(result.user);
  }

  /// Lança [ApiException] em caso de erro — a tela de cadastro trata e exibe.
  Future<void> register({
    required String name,
    required String email,
    required String whatsapp,
    required String password,
    required String passwordConfirmation,
  }) async {
    final result = await _authApi.register(
      name: name,
      email: email,
      whatsapp: whatsapp,
      password: password,
      passwordConfirmation: passwordConfirmation,
    );
    await _tokenStorage.saveToken(result.token);
    state = AuthState.authenticated(result.user);
  }

  Future<void> logout() async {
    try {
      await _authApi.logout();
    } catch (_) {
      // Mesmo se a chamada falhar (ex.: sem internet), a sessão local cai.
    }
    await _tokenStorage.clearToken();
    state = const AuthState.unauthenticated();
  }
}

final authControllerProvider = NotifierProvider<AuthController, AuthState>(AuthController.new);

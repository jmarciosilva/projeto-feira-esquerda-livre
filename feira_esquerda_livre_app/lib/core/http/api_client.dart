import 'dart:async';

import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../storage/token_storage.dart';

/// URL base da API. Em dev, aponta por padrão para o `php artisan serve`
/// local visto do emulador Android (10.0.2.2 é o loopback do host).
/// Sobrescrever com `--dart-define=API_BASE_URL=https://...` em outros
/// ambientes (dispositivo físico, staging, produção).
const _defaultBaseUrl = 'http://10.0.2.2:8000/api/v1';

const apiBaseUrl = String.fromEnvironment('API_BASE_URL', defaultValue: _defaultBaseUrl);

/// Wrapper fino sobre o Dio: anexa o token Bearer salvo, força
/// `Accept: application/json` (exigido pela API) e expõe um stream de
/// "sessão expirada" para quem escuta (ex.: o router) reagir a um 401.
class ApiClient {
  ApiClient(this._tokenStorage) {
    dio = Dio(
      BaseOptions(
        baseUrl: apiBaseUrl,
        headers: const {'Accept': 'application/json'},
        connectTimeout: const Duration(seconds: 15),
        receiveTimeout: const Duration(seconds: 15),
      ),
    );

    dio.interceptors.add(
      InterceptorsWrapper(
        onRequest: (options, handler) async {
          final token = await _tokenStorage.readToken();
          if (token != null) {
            options.headers['Authorization'] = 'Bearer $token';
          }
          handler.next(options);
        },
        onError: (error, handler) async {
          if (error.response?.statusCode == 401) {
            await _tokenStorage.clearToken();
            _unauthorizedController.add(null);
          }
          handler.next(error);
        },
      ),
    );
  }

  final TokenStorage _tokenStorage;
  late final Dio dio;

  final _unauthorizedController = StreamController<void>.broadcast();

  /// Emite um evento sempre que um request recebe 401 — o token já foi
  /// limpo quando isso dispara.
  Stream<void> get onUnauthorized => _unauthorizedController.stream;

  void dispose() {
    _unauthorizedController.close();
  }
}

final apiClientProvider = Provider<ApiClient>((ref) {
  final client = ApiClient(ref.watch(tokenStorageProvider));
  ref.onDispose(client.dispose);
  return client;
});

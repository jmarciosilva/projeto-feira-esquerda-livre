import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../domain/user.dart';
import 'auth_result.dart';

/// Chamadas HTTP puras do grupo `/auth` (ver `docs/API.md` no backend).
/// Não guarda estado — quem decide o que fazer com o resultado é o
/// `AuthController` (camada de application).
class AuthApi {
  AuthApi(this._dio);

  final Dio _dio;

  Future<AuthResult> register({
    required String name,
    required String email,
    required String whatsapp,
    required String password,
    required String passwordConfirmation,
  }) {
    return _postAuth('/auth/registrar', {
      'name': name,
      'email': email,
      'whatsapp': whatsapp,
      'password': password,
      'password_confirmation': passwordConfirmation,
      'device_name': 'app-mobile',
    });
  }

  Future<AuthResult> login({required String email, required String password}) {
    return _postAuth('/auth/entrar', {
      'email': email,
      'password': password,
      'device_name': 'app-mobile',
    });
  }

  Future<void> logout() async {
    try {
      await _dio.post('/auth/sair');
    } on DioException catch (error) {
      // Se o token já estiver inválido, não há problema — a sessão local
      // será limpa de qualquer forma pelo AuthController.
      if (error.response?.statusCode != 401) {
        throw ApiException.fromDioException(error);
      }
    }
  }

  Future<User> me() async {
    try {
      final response = await _dio.get('/auth/eu');
      return User.fromJson(response.data['user'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<AuthResult> _postAuth(String path, Map<String, dynamic> body) async {
    try {
      final response = await _dio.post(path, data: body);
      final data = response.data as Map<String, dynamic>;
      return AuthResult(
        user: User.fromJson(data['user'] as Map<String, dynamic>),
        token: data['token'] as String,
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final authApiProvider = Provider<AuthApi>((ref) => AuthApi(ref.watch(apiClientProvider).dio));

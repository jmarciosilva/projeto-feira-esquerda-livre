import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../domain/contato_info.dart';

class ContatoApi {
  ContatoApi(this._dio);

  final Dio _dio;

  Future<ContatoInfo> buscar() async {
    try {
      final response = await _dio.get('/contato');
      return ContatoInfo.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  /// Mesmo fluxo do formulário de contato do site, sem sair do app.
  Future<void> enviarMensagem({
    required String name,
    required String email,
    String? phone,
    required String subject,
    required String message,
  }) async {
    try {
      await _dio.post('/contato', data: {
        'name': name,
        'email': email,
        if (phone != null && phone.isNotEmpty) 'phone': phone,
        'subject': subject,
        'message': message,
      });
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final contatoApiProvider = Provider<ContatoApi>(
  (ref) => ContatoApi(ref.watch(apiClientProvider).dio),
);

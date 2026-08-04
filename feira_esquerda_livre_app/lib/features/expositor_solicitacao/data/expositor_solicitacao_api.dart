import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';

class ExpositorSolicitacaoApi {
  ExpositorSolicitacaoApi(this._dio);

  final Dio _dio;

  /// Mesmo fluxo do formulário "Seja um Expositor" do site, sem sair do
  /// app. `payload` já vem no formato esperado pela API (snake_case).
  Future<void> enviar(Map<String, dynamic> payload) async {
    try {
      await _dio.post('/seja-um-expositor', data: payload);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final expositorSolicitacaoApiProvider = Provider<ExpositorSolicitacaoApi>(
  (ref) => ExpositorSolicitacaoApi(ref.watch(apiClientProvider).dio),
);

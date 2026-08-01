import 'package:dio/dio.dart';

/// Erro de API já traduzido para o usuário final, com os erros de
/// validação (422) organizados por campo — formato documentado em
/// `docs/API.md` do backend: `{ "message": "...", "errors": { "campo": ["..."] } }`.
class ApiException implements Exception {
  ApiException(this.message, {this.fieldErrors = const {}, this.statusCode});

  final String message;
  final Map<String, List<String>> fieldErrors;
  final int? statusCode;

  String? errorFor(String field) => fieldErrors[field]?.first;

  factory ApiException.fromDioException(DioException error) {
    final statusCode = error.response?.statusCode;
    final data = error.response?.data;

    if (statusCode == 422 && data is Map<String, dynamic>) {
      final rawErrors = data['errors'];
      final fieldErrors = <String, List<String>>{};
      if (rawErrors is Map<String, dynamic>) {
        rawErrors.forEach((key, value) {
          if (value is List) {
            fieldErrors[key] = value.map((e) => e.toString()).toList();
          }
        });
      }
      return ApiException(
        (data['message'] as String?) ?? 'Verifique os dados informados.',
        fieldErrors: fieldErrors,
        statusCode: statusCode,
      );
    }

    if (statusCode == 401) {
      return ApiException('Sessão expirada. Entre novamente.', statusCode: statusCode);
    }

    if (statusCode == 403) {
      return ApiException('Você não tem permissão para fazer isso.', statusCode: statusCode);
    }

    if (statusCode == 404) {
      return ApiException('Não encontrado.', statusCode: statusCode);
    }

    if (error.type == DioExceptionType.connectionTimeout ||
        error.type == DioExceptionType.receiveTimeout ||
        error.type == DioExceptionType.connectionError) {
      return ApiException('Sem conexão com o servidor. Verifique sua internet e tente novamente.');
    }

    final data0 = data;
    if (data0 is Map<String, dynamic> && data0['message'] is String) {
      return ApiException(data0['message'] as String, statusCode: statusCode);
    }

    return ApiException('Algo deu errado. Tente novamente em instantes.', statusCode: statusCode);
  }
}

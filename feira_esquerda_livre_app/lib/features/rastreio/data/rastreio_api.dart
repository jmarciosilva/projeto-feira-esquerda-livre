import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../domain/rastreio.dart';

class RastreioApi {
  RastreioApi(this._dio);

  final Dio _dio;

  Future<Rastreio> consultar(String trackingCode) async {
    try {
      final response = await _dio.get('/rastreio/$trackingCode');
      return Rastreio.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final rastreioApiProvider = Provider<RastreioApi>(
  (ref) => RastreioApi(ref.watch(apiClientProvider).dio),
);

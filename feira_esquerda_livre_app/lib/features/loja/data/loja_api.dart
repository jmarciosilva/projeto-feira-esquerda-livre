import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../../auth/domain/expositor_summary.dart';
import 'loja_detalhe.dart';

class LojaApi {
  LojaApi(this._dio);

  final Dio _dio;

  Future<Paginated<ExpositorSummary>> listar({int page = 1}) async {
    try {
      final response = await _dio.get('/lojas', queryParameters: {'page': page});
      return Paginated.fromJson(
        response.data as Map<String, dynamic>,
        (json) => ExpositorSummary.fromJson(json),
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<LojaDetalhe> detalhe(String slug) async {
    try {
      final response = await _dio.get('/lojas/$slug');
      return LojaDetalhe.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final lojaApiProvider = Provider<LojaApi>((ref) => LojaApi(ref.watch(apiClientProvider).dio));

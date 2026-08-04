import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../domain/noticia.dart';
import 'noticia_detalhe.dart';

class NoticiaApi {
  NoticiaApi(this._dio);

  final Dio _dio;

  Future<Paginated<Noticia>> listar({int page = 1}) async {
    try {
      final response = await _dio.get('/noticias', queryParameters: {'page': page});
      return Paginated.fromJson(
        response.data as Map<String, dynamic>,
        (json) => Noticia.fromJson(json),
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<NoticiaDetalhe> detalhe(String slug) async {
    try {
      final response = await _dio.get('/noticias/$slug');
      return NoticiaDetalhe.fromJson(response.data as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final noticiaApiProvider = Provider<NoticiaApi>(
  (ref) => NoticiaApi(ref.watch(apiClientProvider).dio),
);

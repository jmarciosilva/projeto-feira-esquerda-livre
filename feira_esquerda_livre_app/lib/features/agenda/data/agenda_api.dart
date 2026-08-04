import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../domain/evento.dart';

class AgendaApi {
  AgendaApi(this._dio);

  final Dio _dio;

  Future<Paginated<Evento>> listar({int page = 1, String? estado, int? mes, int? ano}) async {
    try {
      final response = await _dio.get(
        '/agenda',
        queryParameters: {
          'page': page,
          if (estado != null && estado.isNotEmpty) 'estado': estado,
          if (mes != null) 'mes': mes,
          if (ano != null) 'ano': ano,
        },
      );
      return Paginated.fromJson(
        response.data as Map<String, dynamic>,
        (json) => Evento.fromJson(json),
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<Evento> detalhe(String slug) async {
    try {
      final response = await _dio.get('/agenda/$slug');
      return Evento.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final agendaApiProvider = Provider<AgendaApi>((ref) => AgendaApi(ref.watch(apiClientProvider).dio));

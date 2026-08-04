import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../domain/categoria.dart';
import '../domain/product.dart';
import '../domain/product_question.dart';

/// Eixos do marketplace — a mesma rota de catálogo é reaproveitada pelos
/// três, só muda o segmento da URL (ver docs/API.md do backend).
enum Eixo {
  produto('produtos'),
  servico('servicos'),
  cuidado('cuidados');

  const Eixo(this.path);
  final String path;
}

/// Chamadas HTTP do catálogo público (produtos, categorias e perguntas).
class CatalogoApi {
  CatalogoApi(this._dio);

  final Dio _dio;

  Future<Paginated<Product>> listar(
    Eixo eixo, {
    int page = 1,
    String? busca,
    int? categoriaId,
  }) async {
    try {
      final response = await _dio.get(
        '/${eixo.path}',
        queryParameters: {
          'page': page,
          if (busca != null && busca.isNotEmpty) 'busca': busca,
          if (categoriaId != null) 'categoria': categoriaId,
        },
      );
      return Paginated.fromJson(
        response.data as Map<String, dynamic>,
        (json) => Product.fromJson(json),
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<Product> detalhe(int productId) async {
    try {
      final response = await _dio.get('/produtos/$productId');
      return Product.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<List<Categoria>> categorias({String? eixo}) async {
    try {
      final response = await _dio.get(
        '/categorias',
        queryParameters: {if (eixo != null) 'eixo': eixo},
      );
      return (response.data['data'] as List)
          .map((e) => Categoria.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<List<ProductQuestion>> perguntas(int productId) async {
    try {
      final response = await _dio.get('/produtos/$productId/perguntas');
      return (response.data['data'] as List)
          .map((e) => ProductQuestion.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  /// Requer usuário autenticado (o backend retorna 401 caso contrário).
  Future<ProductQuestion> perguntar(int productId, String question) async {
    try {
      final response = await _dio.post(
        '/produtos/$productId/perguntas',
        data: {'question': question},
      );
      return ProductQuestion.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final catalogoApiProvider = Provider<CatalogoApi>(
  (ref) => CatalogoApi(ref.watch(apiClientProvider).dio),
);

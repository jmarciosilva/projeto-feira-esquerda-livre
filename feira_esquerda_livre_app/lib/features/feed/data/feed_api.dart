import 'package:dio/dio.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/http/api_client.dart';
import '../../../core/http/api_exception.dart';
import '../../../core/pagination/paginated.dart';
import '../domain/feed_comment.dart';
import '../domain/feed_post.dart';

class FeedApi {
  FeedApi(this._dio);

  final Dio _dio;

  Future<Paginated<FeedPost>> listar({int page = 1}) async {
    try {
      final response = await _dio.get('/feed', queryParameters: {'page': page});
      return Paginated.fromJson(
        response.data as Map<String, dynamic>,
        (json) => FeedPost.fromJson(json),
      );
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  Future<List<FeedComment>> comentarios(int postId) async {
    try {
      final response = await _dio.get('/feed/$postId/comentarios');
      return (response.data['data'] as List)
          .map((e) => FeedComment.fromJson(e as Map<String, dynamic>))
          .toList();
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  /// Requer login. Retorna `(liked, likesCount)`.
  Future<(bool, int)> curtir(int postId) async {
    try {
      final response = await _dio.post('/feed/$postId/curtir');
      final data = response.data as Map<String, dynamic>;
      return (data['liked'] as bool, data['likes_count'] as int);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  /// Requer login.
  Future<FeedComment> comentar(int postId, String content) async {
    try {
      final response = await _dio.post('/feed/$postId/comentarios', data: {'content': content});
      return FeedComment.fromJson(response.data['data'] as Map<String, dynamic>);
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }

  /// Requer login.
  Future<String> denunciar(int postId, String reason) async {
    try {
      final response = await _dio.post('/feed/$postId/denunciar', data: {'reason': reason});
      return (response.data as Map<String, dynamic>)['message'] as String;
    } on DioException catch (error) {
      throw ApiException.fromDioException(error);
    }
  }
}

final feedApiProvider = Provider<FeedApi>((ref) => FeedApi(ref.watch(apiClientProvider).dio));

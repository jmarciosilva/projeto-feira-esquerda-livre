import 'package:freezed_annotation/freezed_annotation.dart';

part 'noticia.freezed.dart';
part 'noticia.g.dart';

/// Espelha `PostResource` no backend — usado no carrossel "Nossas Notícias
/// e Blog" da Home e na tela de detalhe da notícia.
@freezed
abstract class Noticia with _$Noticia {
  const factory Noticia({
    required int id,
    required String title,
    required String slug,
    String? excerpt,
    String? content,
    @JsonKey(name: 'image_url') String? imageUrl,
    @JsonKey(name: 'author_name') String? authorName,
    @JsonKey(name: 'published_at') DateTime? publishedAt,
  }) = _Noticia;

  factory Noticia.fromJson(Map<String, dynamic> json) => _$NoticiaFromJson(json);
}

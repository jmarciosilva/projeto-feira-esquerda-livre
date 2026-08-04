import '../domain/noticia.dart';

/// Resposta de `GET /noticias/{slug}`: `{ noticia, relacionadas }`.
class NoticiaDetalhe {
  NoticiaDetalhe({required this.noticia, required this.relacionadas});

  final Noticia noticia;
  final List<Noticia> relacionadas;

  factory NoticiaDetalhe.fromJson(Map<String, dynamic> json) {
    return NoticiaDetalhe(
      noticia: Noticia.fromJson(json['noticia'] as Map<String, dynamic>),
      relacionadas: (json['relacionadas'] as List)
          .map((e) => Noticia.fromJson(e as Map<String, dynamic>))
          .toList(),
    );
  }
}

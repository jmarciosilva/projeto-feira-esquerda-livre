import 'package:freezed_annotation/freezed_annotation.dart';

part 'categoria.freezed.dart';
part 'categoria.g.dart';

/// Espelha `CategoriaResource` no backend.
@freezed
abstract class Categoria with _$Categoria {
  const factory Categoria({
    required int id,
    required String name,
    required String slug,
    String? description,
    String? eixo,
  }) = _Categoria;

  factory Categoria.fromJson(Map<String, dynamic> json) => _$CategoriaFromJson(json);
}

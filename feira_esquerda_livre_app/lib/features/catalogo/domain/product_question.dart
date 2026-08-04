import 'package:freezed_annotation/freezed_annotation.dart';

part 'product_question.freezed.dart';
part 'product_question.g.dart';

/// Espelha `ProductQuestionResource` no backend.
@freezed
abstract class ProductQuestion with _$ProductQuestion {
  const factory ProductQuestion({
    required int id,
    required String question,
    String? answer,
    @JsonKey(name: 'asker_first_name') required String askerFirstName,
    @JsonKey(name: 'answered_at') DateTime? answeredAt,
    @JsonKey(name: 'created_at') DateTime? createdAt,
  }) = _ProductQuestion;

  factory ProductQuestion.fromJson(Map<String, dynamic> json) => _$ProductQuestionFromJson(json);
}

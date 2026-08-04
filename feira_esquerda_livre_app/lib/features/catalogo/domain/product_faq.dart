import 'package:freezed_annotation/freezed_annotation.dart';

part 'product_faq.freezed.dart';
part 'product_faq.g.dart';

@freezed
abstract class ProductFaq with _$ProductFaq {
  const factory ProductFaq({required String question, required String answer}) = _ProductFaq;

  factory ProductFaq.fromJson(Map<String, dynamic> json) => _$ProductFaqFromJson(json);
}

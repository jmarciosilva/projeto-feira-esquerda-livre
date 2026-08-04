import 'package:freezed_annotation/freezed_annotation.dart';

part 'feed_expositor.freezed.dart';
part 'feed_expositor.g.dart';

/// Loja resumida embutida em um post (`FeedPostResource.expositor`) —
/// só os campos que o feed realmente precisa exibir.
@freezed
abstract class FeedExpositor with _$FeedExpositor {
  const factory FeedExpositor({
    required int id,
    required String name,
    required String slug,
    @JsonKey(name: 'logo_url') String? logoUrl,
  }) = _FeedExpositor;

  factory FeedExpositor.fromJson(Map<String, dynamic> json) => _$FeedExpositorFromJson(json);
}

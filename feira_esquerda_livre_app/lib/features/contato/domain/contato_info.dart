import 'package:freezed_annotation/freezed_annotation.dart';

part 'contato_info.freezed.dart';
part 'contato_info.g.dart';

/// WhatsApp e e-mail públicos de contato da plataforma — usados na seção
/// "Quer vender seus produtos na Feira?" da Home.
@freezed
abstract class ContatoInfo with _$ContatoInfo {
  const factory ContatoInfo({String? whatsapp, String? email}) = _ContatoInfo;

  factory ContatoInfo.fromJson(Map<String, dynamic> json) => _$ContatoInfoFromJson(json);
}

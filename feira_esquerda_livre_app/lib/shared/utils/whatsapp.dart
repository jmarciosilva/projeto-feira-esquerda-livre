import 'package:url_launcher/url_launcher.dart';

/// Monta e abre um link `wa.me` a partir de um telefone em formato livre
/// (com ou sem DDI/DDD já incluídos), igual ao botão "Falar no WhatsApp" do
/// site. Retorna `false` se não foi possível abrir (ex.: nenhum app instalado).
Future<bool> abrirWhatsApp(String telefone, {String? mensagem}) async {
  final digitos = telefone.replaceAll(RegExp(r'[^0-9]'), '');
  final numero = digitos.length <= 11 ? '55$digitos' : digitos;

  final uri = Uri.https('wa.me', '/$numero', {
    if (mensagem != null && mensagem.isNotEmpty) 'text': mensagem,
  });

  return launchUrl(uri, mode: LaunchMode.externalApplication);
}

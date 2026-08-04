import 'package:flutter/material.dart';

import '../../core/theme/app_colors.dart';
import '../utils/whatsapp.dart';

/// Botão "Falar no WhatsApp" padrão do app — sempre verde da marca
/// WhatsApp, nunca a cor de destaque do app, em qualquer tela que o usar.
class WhatsAppButton extends StatelessWidget {
  const WhatsAppButton({
    super.key,
    required this.telefone,
    this.mensagem,
    this.label = 'WhatsApp',
  });

  final String telefone;
  final String? mensagem;
  final String label;

  @override
  Widget build(BuildContext context) {
    return ElevatedButton.icon(
      onPressed: () => abrirWhatsApp(telefone, mensagem: mensagem),
      icon: const Icon(Icons.chat_bubble_outline),
      label: Text(label),
      style: ElevatedButton.styleFrom(
        backgroundColor: AppColors.whatsappGreen,
        foregroundColor: Colors.white,
      ),
    );
  }
}

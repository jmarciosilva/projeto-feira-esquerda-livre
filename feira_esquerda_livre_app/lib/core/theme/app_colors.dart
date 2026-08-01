import 'package:flutter/material.dart';

/// Paleta oficial da Feira Esquerda Livre, reaproveitada do site
/// (`resources/css/app.css` e temas dos painéis admin/lojista/cliente).
abstract final class AppColors {
  // Verde da marca (usado no site público e no checkout).
  static const Color brand = Color(0xFF1A472A);
  static const Color brandMid = Color(0xFF2D6A4F);
  static const Color brandLight = Color(0xFF52B788);
  static const Color brandPale = Color(0xFFB7E4C7);

  // Amarelo/marrom (usado nos painéis internos admin/lojista/cliente).
  static const Color accentYellow = Color(0xFFF4E294);
  static const Color brown = Color(0xFF3D3000);
  static const Color brownLight = Color(0xFF5C4500);

  static const Color warning = Color(0xFFC47A00);
  static const Color success = Color(0xFF5C8A3C);
  static const Color danger = Color(0xFFDC2626);

  static const Color background = Color(0xFFFAFAFA);
  static const Color surface = Colors.white;
  static const Color textPrimary = Color(0xFF1F1F1F);
  static const Color textSecondary = Color(0xFF6B6B6B);
}

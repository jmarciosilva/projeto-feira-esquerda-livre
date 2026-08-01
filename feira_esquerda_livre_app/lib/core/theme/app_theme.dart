import 'package:flutter/material.dart';

import 'app_colors.dart';

/// Tema visual do app. Usa a mesma identidade dos painéis internos do site
/// (admin/lojista/cliente): marrom `#3D3000` para cabeçalhos e texto de
/// marca, amarelo `#F4E294` como destaque, botão primário `#C47A00`. O
/// verde (`AppColors.brand`) é exclusivo do site público de marketing e
/// não é usado aqui. Segue os mesmos princípios do site (público 40+):
/// fonte mínima de 16px, botões com altura mínima de 52px e área de toque
/// generosa, feedback visual claro.
abstract final class AppTheme {
  static ThemeData light() {
    final colorScheme = ColorScheme.fromSeed(
      seedColor: AppColors.warning,
      primary: AppColors.warning,
      secondary: AppColors.accentYellow,
      error: AppColors.danger,
      brightness: Brightness.light,
    );

    final base = ThemeData(colorScheme: colorScheme, useMaterial3: true);

    return base.copyWith(
      scaffoldBackgroundColor: AppColors.background,
      textTheme: base.textTheme.apply(
        bodyColor: AppColors.textPrimary,
        displayColor: AppColors.textPrimary,
        fontSizeFactor: 1.0,
      ).copyWith(
        bodyMedium: const TextStyle(fontSize: 16, height: 1.4, color: AppColors.textPrimary),
        bodyLarge: const TextStyle(fontSize: 18, height: 1.4, color: AppColors.textPrimary),
        titleLarge: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold, color: AppColors.brown),
        titleMedium: const TextStyle(fontSize: 18, fontWeight: FontWeight.w600, color: AppColors.brown),
      ),
      appBarTheme: const AppBarTheme(
        backgroundColor: AppColors.brown,
        foregroundColor: Colors.white,
        centerTitle: false,
        elevation: 0,
      ),
      elevatedButtonTheme: ElevatedButtonThemeData(
        style: ElevatedButton.styleFrom(
          backgroundColor: AppColors.warning,
          foregroundColor: Colors.white,
          minimumSize: const Size.fromHeight(52),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      outlinedButtonTheme: OutlinedButtonThemeData(
        style: OutlinedButton.styleFrom(
          foregroundColor: AppColors.brown,
          minimumSize: const Size.fromHeight(52),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
          side: const BorderSide(color: AppColors.brown),
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
        ),
      ),
      textButtonTheme: TextButtonThemeData(
        style: TextButton.styleFrom(
          foregroundColor: AppColors.brown,
          minimumSize: const Size(48, 48),
          textStyle: const TextStyle(fontSize: 16, fontWeight: FontWeight.w600),
        ),
      ),
      inputDecorationTheme: InputDecorationTheme(
        filled: true,
        fillColor: AppColors.surface,
        contentPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
        border: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFD0D0D0)),
        ),
        enabledBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: Color(0xFFD0D0D0)),
        ),
        focusedBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.warning, width: 2),
        ),
        errorBorder: OutlineInputBorder(
          borderRadius: BorderRadius.circular(12),
          borderSide: const BorderSide(color: AppColors.danger),
        ),
        labelStyle: const TextStyle(fontSize: 16, color: AppColors.textSecondary),
      ),
      cardTheme: CardThemeData(
        color: AppColors.surface,
        elevation: 1,
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      ),
      snackBarTheme: SnackBarThemeData(
        behavior: SnackBarBehavior.floating,
        backgroundColor: AppColors.brown,
        contentTextStyle: const TextStyle(color: Colors.white, fontSize: 16),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(12)),
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/app_colors.dart';
import '../application/auth_controller.dart';

/// Primeira tela ao abrir o app: tenta restaurar a sessão a partir do token
/// salvo. O `redirect` do router cuida de navegar para a tela certa assim
/// que o estado deixar de ser `AuthState.unknown()`.
class SplashScreen extends ConsumerStatefulWidget {
  const SplashScreen({super.key});

  @override
  ConsumerState<SplashScreen> createState() => _SplashScreenState();
}

class _SplashScreenState extends ConsumerState<SplashScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(authControllerProvider.notifier).restoreSession();
    });
  }

  @override
  Widget build(BuildContext context) {
    return const Scaffold(
      backgroundColor: AppColors.brown,
      body: Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Image(
              image: AssetImage('assets/images/logo_bird_transparent.png'),
              height: 110,
            ),
            SizedBox(height: 20),
            Text(
              'Feira Esquerda Livre',
              style: TextStyle(color: Colors.white, fontSize: 22, fontWeight: FontWeight.bold),
            ),
            SizedBox(height: 4),
            Text(
              'Entrando na plataforma...',
              style: TextStyle(color: AppColors.accentYellow, fontSize: 16),
            ),
            SizedBox(height: 24),
            CircularProgressIndicator(color: AppColors.accentYellow),
          ],
        ),
      ),
    );
  }
}

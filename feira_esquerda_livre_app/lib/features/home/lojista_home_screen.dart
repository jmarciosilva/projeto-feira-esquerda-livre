import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../auth/application/auth_controller.dart';
import '../auth/application/auth_state.dart';

/// Placeholder da área do lojista — o painel de verdade (produtos, pedidos,
/// perguntas) entra na Fase 6 do roadmap.
class LojistaHomeScreen extends ConsumerWidget {
  const LojistaHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authControllerProvider);
    final user = switch (authState) { AuthAuthenticated(:final user) => user, _ => null };

    return Scaffold(
      backgroundColor: AppColors.background,
      appBar: AppBar(
        backgroundColor: AppColors.brown,
        title: const Text('Painel do Lojista'),
        actions: [
          IconButton(
            icon: const Icon(Icons.logout),
            tooltip: 'Sair',
            onPressed: () => ref.read(authControllerProvider.notifier).logout(),
          ),
        ],
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              const Icon(Icons.storefront, color: AppColors.warning, size: 56),
              const SizedBox(height: 16),
              Text(
                'Olá, ${user?.name ?? ''}!',
                style: Theme.of(context).textTheme.titleLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                user?.expositor?.name ?? 'Sua loja',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              Text(
                'Produtos, pedidos e perguntas chegam na Fase 6 do roadmap do app.',
                textAlign: TextAlign.center,
                style: Theme.of(context).textTheme.bodyMedium,
              ),
            ],
          ),
        ),
      ),
    );
  }
}

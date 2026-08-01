import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../core/theme/app_colors.dart';
import '../auth/application/auth_controller.dart';
import '../auth/application/auth_state.dart';

/// Placeholder da área do cliente — o catálogo de verdade entra na Fase 2
/// do roadmap. Por enquanto, só confirma que login/sessão/logout funcionam
/// ponta a ponta contra o backend real.
class ClienteHomeScreen extends ConsumerWidget {
  const ClienteHomeScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authControllerProvider);
    final user = switch (authState) { AuthAuthenticated(:final user) => user, _ => null };

    return Scaffold(
      appBar: AppBar(
        title: const Text('Feira Esquerda Livre'),
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
              const Icon(Icons.check_circle, color: AppColors.success, size: 56),
              const SizedBox(height: 16),
              Text(
                'Bem-vindo(a), ${user?.name ?? ''}!',
                style: Theme.of(context).textTheme.titleLarge,
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 8),
              Text(
                user?.email ?? '',
                style: Theme.of(context).textTheme.bodyMedium,
              ),
              const SizedBox(height: 24),
              Text(
                'O catálogo de produtos, carrinho e pedidos chegam na próxima fase do app.',
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

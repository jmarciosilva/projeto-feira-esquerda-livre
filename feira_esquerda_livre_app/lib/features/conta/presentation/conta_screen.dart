import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../../auth/application/auth_controller.dart';
import '../../auth/application/auth_state.dart';

/// Aba "Conta" do shell principal. Continua acessível sem login (igual ao
/// site — só pede autenticação na hora de uma ação específica). Pedidos,
/// endereços e aprendizado chegam nas próximas fases do roadmap do app.
class ContaScreen extends ConsumerWidget {
  const ContaScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: const FelAppBar(title: 'Minha Conta'),
      body: switch (authState) {
        AuthAuthenticated(:final user) => ListView(
            children: [
              Padding(
                padding: const EdgeInsets.all(20),
                child: Row(
                  children: [
                    const CircleAvatar(
                      radius: 28,
                      backgroundColor: AppColors.accentYellow,
                      child: Icon(Icons.person, color: AppColors.brown, size: 28),
                    ),
                    const SizedBox(width: 16),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(user.name, style: Theme.of(context).textTheme.titleMedium),
                          Text(user.email, style: const TextStyle(color: AppColors.textSecondary)),
                        ],
                      ),
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              _rastrearPedidoTile(context),
              const _ContaMenuPlaceholder(icon: Icons.receipt_long_outlined, label: 'Meus Pedidos'),
              const _ContaMenuPlaceholder(icon: Icons.location_on_outlined, label: 'Meus Endereços'),
              const _ContaMenuPlaceholder(icon: Icons.school_outlined, label: 'Meu Aprendizado'),
              const Divider(height: 1),
              ListTile(
                leading: const Icon(Icons.logout, color: AppColors.danger),
                title: const Text('Sair', style: TextStyle(color: AppColors.danger)),
                onTap: () => ref.read(authControllerProvider.notifier).logout(),
              ),
            ],
          ),
        _ => ListView(
            children: [
              Padding(
                padding: const EdgeInsets.all(20),
                child: Column(
                  children: [
                    const Icon(Icons.person_outline, size: 48, color: AppColors.textSecondary),
                    const SizedBox(height: 12),
                    Text(
                      'Entre ou crie sua conta',
                      style: Theme.of(context).textTheme.titleMedium,
                      textAlign: TextAlign.center,
                    ),
                    const SizedBox(height: 4),
                    const Text(
                      'Acompanhe pedidos, faça perguntas aos lojistas e muito mais.',
                      textAlign: TextAlign.center,
                      style: TextStyle(color: AppColors.textSecondary),
                    ),
                    const SizedBox(height: 16),
                    Row(
                      children: [
                        Expanded(
                          child: OutlinedButton(
                            onPressed: () => context.push('/registrar'),
                            child: const Text('Cadastrar'),
                          ),
                        ),
                        const SizedBox(width: 12),
                        Expanded(
                          child: ElevatedButton(
                            onPressed: () => context.push('/login'),
                            child: const Text('Entrar'),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Divider(height: 1),
              _rastrearPedidoTile(context),
            ],
          ),
      },
    );
  }

  Widget _rastrearPedidoTile(BuildContext context) {
    return ListTile(
      leading: const Icon(Icons.local_shipping_outlined),
      title: const Text('Rastrear Pedido'),
      trailing: const Icon(Icons.chevron_right),
      onTap: () => context.push('/rastreio'),
    );
  }
}

class _ContaMenuPlaceholder extends StatelessWidget {
  const _ContaMenuPlaceholder({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: Icon(icon, color: AppColors.textSecondary),
      title: Text(label),
      subtitle: const Text('Em breve'),
      enabled: false,
    );
  }
}

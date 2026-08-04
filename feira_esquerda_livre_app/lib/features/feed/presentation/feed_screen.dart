import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../../auth/application/auth_controller.dart';
import '../../auth/application/auth_state.dart';
import '../application/feed_controller.dart';
import '../application/feed_state.dart';
import 'feed_post_card.dart';

class FeedScreen extends ConsumerWidget {
  const FeedScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(feedControllerProvider);
    final controller = ref.read(feedControllerProvider.notifier);

    return Scaffold(
      appBar: const FelAppBar(title: 'Comunidade'),
      body: RefreshIndicator(
        onRefresh: controller.tentarNovamente,
        child: _buildBody(context, ref, state, controller),
      ),
    );
  }

  Widget _buildBody(
    BuildContext context,
    WidgetRef ref,
    FeedState state,
    FeedController controller,
  ) {
    if (state.isLoading) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.error != null && state.items.isEmpty) {
      return _ErrorState(message: state.error!, onRetry: controller.tentarNovamente);
    }

    if (state.items.isEmpty) {
      return ListView(
        children: const [
          SizedBox(height: 120),
          Icon(Icons.forum_outlined, size: 48, color: AppColors.textSecondary),
          SizedBox(height: 12),
          Center(child: Text('Nenhuma novidade por aqui ainda.')),
        ],
      );
    }

    return ListView.builder(
      padding: const EdgeInsets.all(16),
      itemCount: state.items.length + 1,
      itemBuilder: (context, index) {
        if (index == state.items.length) {
          if (!state.hasMore) return const SizedBox.shrink();
          return Padding(
            padding: const EdgeInsets.only(bottom: 16),
            child: Center(
              child: state.isLoadingMore
                  ? const SizedBox(
                      height: 20,
                      width: 20,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    )
                  : OutlinedButton(
                      onPressed: controller.carregarMais,
                      child: const Text('Carregar mais'),
                    ),
            ),
          );
        }

        final post = state.items[index];
        return FeedPostCard(
          post: post,
          onTapLoja: () {
            final slug = post.expositor?.slug;
            if (slug != null) context.push('/lojas/$slug');
          },
          onTapCurtir: () {
            final authState = ref.read(authControllerProvider);
            if (authState is! AuthAuthenticated) {
              context.push('/login');
              return;
            }
            controller.curtir(post.id);
          },
          onTapComentarios: () => context.push('/comunidade/${post.id}'),
        );
      },
    );
  }
}

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, required this.onRetry});

  final String message;
  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            const Icon(Icons.wifi_off_rounded, size: 48, color: AppColors.textSecondary),
            const SizedBox(height: 12),
            Text(message, textAlign: TextAlign.center),
            const SizedBox(height: 16),
            OutlinedButton(onPressed: onRetry, child: const Text('Tentar novamente')),
          ],
        ),
      ),
    );
  }
}

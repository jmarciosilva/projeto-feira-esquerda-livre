import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/agenda/presentation/agenda_detail_screen.dart';
import '../../features/agenda/presentation/agenda_list_screen.dart';
import '../../features/auth/application/auth_controller.dart';
import '../../features/auth/application/auth_state.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../features/auth/presentation/splash_screen.dart';
import '../../features/catalogo/data/catalogo_api.dart';
import '../../features/catalogo/presentation/catalogo_list_screen.dart';
import '../../features/catalogo/presentation/product_detail_screen.dart';
import '../../features/conta/presentation/conta_screen.dart';
import '../../features/contato/presentation/contato_form_screen.dart';
import '../../features/expositor_solicitacao/presentation/seja_expositor_screen.dart';
import '../../features/feed/presentation/feed_post_detail_screen.dart';
import '../../features/feed/presentation/feed_screen.dart';
import '../../features/home/lojista_home_screen.dart';
import '../../features/home/presentation/home_screen.dart';
import '../../features/loja/presentation/loja_screen.dart';
import '../../features/loja/presentation/lojas_list_screen.dart';
import '../../features/noticias/presentation/noticia_detail_screen.dart';
import '../../features/noticias/presentation/noticias_list_screen.dart';
import '../../features/rastreio/presentation/rastreio_screen.dart';
import 'app_shell.dart';

class AppRoutes {
  static const splash = '/splash';
  static const login = '/login';
  static const register = '/registrar';
  static const inicio = '/inicio';
  static const lojistaHome = '/lojista';
}

/// Ponte entre o estado do Riverpod (não é um Listenable) e o
/// `refreshListenable` do go_router, que precisa ser notificado sempre que
/// o estado de autenticação mudar para reavaliar o `redirect`.
class _RouterRefreshNotifier extends ChangeNotifier {
  _RouterRefreshNotifier(Ref ref) {
    ref.listen<AuthState>(authControllerProvider, (previous, next) => notifyListeners());
  }
}

final routerProvider = Provider<GoRouter>((ref) {
  final refreshNotifier = _RouterRefreshNotifier(ref);
  ref.onDispose(refreshNotifier.dispose);

  return GoRouter(
    initialLocation: AppRoutes.splash,
    refreshListenable: refreshNotifier,
    redirect: (context, state) {
      final authState = ref.read(authControllerProvider);
      final path = state.matchedLocation;
      final isAuthRoute = path == AppRoutes.login || path == AppRoutes.register;
      final isSplash = path == AppRoutes.splash;
      final isLojistaRoute = path.startsWith(AppRoutes.lojistaHome);

      // Catálogo, loja, agenda e rastreio são públicos — igual ao site, só
      // pedimos login na hora de uma ação específica (perguntar, carrinho,
      // checkout), nunca para simplesmente navegar/olhar o conteúdo.
      return switch (authState) {
        AuthUnknown() => isSplash ? null : AppRoutes.splash,
        AuthUnauthenticated() when isSplash => AppRoutes.inicio,
        AuthUnauthenticated() when isLojistaRoute => AppRoutes.login,
        AuthUnauthenticated() => null,
        AuthAuthenticated(:final user) when isSplash || isAuthRoute =>
          user.isLojista ? AppRoutes.lojistaHome : AppRoutes.inicio,
        AuthAuthenticated(:final user) when isLojistaRoute && !user.isLojista =>
          AppRoutes.inicio,
        AuthAuthenticated() => null,
      };
    },
    routes: [
      GoRoute(path: AppRoutes.splash, builder: (context, state) => const SplashScreen()),
      GoRoute(path: AppRoutes.login, builder: (context, state) => const LoginScreen()),
      GoRoute(path: AppRoutes.register, builder: (context, state) => const RegisterScreen()),
      GoRoute(path: AppRoutes.lojistaHome, builder: (context, state) => const LojistaHomeScreen()),

      // Todas as páginas do app do cliente ficam dentro do
      // StatefulShellRoute — mesmo as que não são aba própria (produtos,
      // lojas, rastreio...) — para que a barra inferior nunca desapareça,
      // só o conteúdo troca dentro da pilha da aba que a contém.
      StatefulShellRoute.indexedStack(
        builder: (context, state, navigationShell) => AppShell(navigationShell: navigationShell),
        branches: [
          StatefulShellBranch(
            routes: [
              GoRoute(path: AppRoutes.inicio, builder: (context, state) => const HomeScreen()),
              GoRoute(path: '/lojas', builder: (context, state) => const LojasListScreen()),
              GoRoute(
                path: '/lojas/:slug',
                builder: (context, state) => LojaScreen(slug: state.pathParameters['slug']!),
              ),
              _catalogoRoute(Eixo.produto, 'Produtos'),
              _catalogoRoute(Eixo.servico, 'Serviços'),
              _catalogoRoute(Eixo.cuidado, 'Cuidados & Bem Viver'),
              GoRoute(path: '/noticias', builder: (context, state) => const NoticiasListScreen()),
              GoRoute(
                path: '/noticias/:slug',
                builder: (context, state) =>
                    NoticiaDetailScreen(slug: state.pathParameters['slug']!),
              ),
              GoRoute(
                path: '/seja-um-expositor',
                builder: (context, state) => const SejaExpositorScreen(),
              ),
              GoRoute(path: '/contato', builder: (context, state) => const ContatoFormScreen()),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/agenda',
                builder: (context, state) => const AgendaListScreen(),
                routes: [
                  GoRoute(
                    path: ':slug',
                    builder: (context, state) =>
                        AgendaDetailScreen(slug: state.pathParameters['slug']!),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(
                path: '/comunidade',
                builder: (context, state) => const FeedScreen(),
                routes: [
                  GoRoute(
                    path: ':id',
                    builder: (context, state) => FeedPostDetailScreen(
                      postId: int.parse(state.pathParameters['id']!),
                    ),
                  ),
                ],
              ),
            ],
          ),
          StatefulShellBranch(
            routes: [
              GoRoute(path: '/conta', builder: (context, state) => const ContaScreen()),
              GoRoute(path: '/rastreio', builder: (context, state) => const RastreioScreen()),
            ],
          ),
        ],
      ),
    ],
  );
});

GoRoute _catalogoRoute(Eixo eixo, String titulo) {
  return GoRoute(
    path: '/${eixo.path}',
    builder: (context, state) => CatalogoListScreen(
      eixo: eixo,
      title: titulo,
      initialCategoriaId: int.tryParse(state.uri.queryParameters['categoria'] ?? ''),
    ),
    routes: [
      GoRoute(
        path: ':id',
        builder: (context, state) => ProductDetailScreen(
          productId: int.parse(state.pathParameters['id']!),
        ),
      ),
    ],
  );
}

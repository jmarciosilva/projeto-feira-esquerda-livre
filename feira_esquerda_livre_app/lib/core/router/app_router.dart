import 'package:flutter/foundation.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../features/auth/application/auth_controller.dart';
import '../../features/auth/application/auth_state.dart';
import '../../features/auth/presentation/login_screen.dart';
import '../../features/auth/presentation/register_screen.dart';
import '../../features/auth/presentation/splash_screen.dart';
import '../../features/home/cliente_home_screen.dart';
import '../../features/home/lojista_home_screen.dart';

class AppRoutes {
  static const splash = '/splash';
  static const login = '/login';
  static const register = '/registrar';
  static const home = '/home';
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

      return switch (authState) {
        AuthUnknown() => isSplash ? null : AppRoutes.splash,
        AuthUnauthenticated() => isAuthRoute ? null : AppRoutes.login,
        AuthAuthenticated(:final user) when isSplash || isAuthRoute =>
          user.isLojista ? AppRoutes.lojistaHome : AppRoutes.home,
        AuthAuthenticated() => null,
      };
    },
    routes: [
      GoRoute(path: AppRoutes.splash, builder: (context, state) => const SplashScreen()),
      GoRoute(path: AppRoutes.login, builder: (context, state) => const LoginScreen()),
      GoRoute(path: AppRoutes.register, builder: (context, state) => const RegisterScreen()),
      GoRoute(path: AppRoutes.home, builder: (context, state) => const ClienteHomeScreen()),
      GoRoute(path: AppRoutes.lojistaHome, builder: (context, state) => const LojistaHomeScreen()),
    ],
  );
});

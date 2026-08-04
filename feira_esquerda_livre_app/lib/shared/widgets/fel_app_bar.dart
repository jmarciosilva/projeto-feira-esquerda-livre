import 'package:flutter/material.dart';

/// AppBar padrão do app — identifica a marca Feira Esquerda Livre com o
/// selo do pássaro ao lado do título, em toda tela principal do app.
class FelAppBar extends StatelessWidget implements PreferredSizeWidget {
  const FelAppBar({super.key, this.title, this.actions});

  final String? title;
  final List<Widget>? actions;

  @override
  Widget build(BuildContext context) {
    return AppBar(
      titleSpacing: 12,
      title: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Image.asset('assets/images/logo_bird_transparent.png', height: 28),
          const SizedBox(width: 10),
          Flexible(
            child: Text(
              title ?? 'Feira Esquerda Livre',
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
      actions: actions,
    );
  }

  @override
  Size get preferredSize => const Size.fromHeight(kToolbarHeight);
}

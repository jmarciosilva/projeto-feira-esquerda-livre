import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/formatters.dart';
import '../../auth/application/auth_controller.dart';
import '../../auth/application/auth_state.dart';
import '../application/feed_controller.dart';
import '../data/feed_api.dart';
import '../domain/feed_comment.dart';

class FeedPostDetailScreen extends ConsumerStatefulWidget {
  const FeedPostDetailScreen({super.key, required this.postId});

  final int postId;

  @override
  ConsumerState<FeedPostDetailScreen> createState() => _FeedPostDetailScreenState();
}

class _FeedPostDetailScreenState extends ConsumerState<FeedPostDetailScreen> {
  List<FeedComment> _comentarios = [];
  bool _isLoading = true;
  String? _error;

  final _comentarioController = TextEditingController();
  bool _enviando = false;
  String? _erroEnvio;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  @override
  void dispose() {
    _comentarioController.dispose();
    super.dispose();
  }

  Future<void> _carregar() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final comentarios = await ref.read(feedApiProvider).comentarios(widget.postId);
      if (!mounted) return;
      setState(() {
        _comentarios = comentarios;
        _isLoading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.message;
        _isLoading = false;
      });
    }
  }

  Future<void> _enviar() async {
    final texto = _comentarioController.text.trim();
    if (texto.isEmpty) {
      setState(() => _erroEnvio = 'Escreva um comentário.');
      return;
    }

    setState(() {
      _enviando = true;
      _erroEnvio = null;
    });

    try {
      final comentario = await ref.read(feedApiProvider).comentar(widget.postId, texto);
      if (!mounted) return;
      setState(() => _comentarios = [..._comentarios, comentario]);
      ref
          .read(feedControllerProvider.notifier)
          .atualizarContagemComentarios(widget.postId, _comentarios.length);
      _comentarioController.clear();
    } on ApiException catch (error) {
      setState(() => _erroEnvio = error.errorFor('content') ?? error.message);
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authControllerProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('Comentários')),
      body: Column(
        children: [
          Expanded(
            child: _isLoading
                ? const Center(child: CircularProgressIndicator())
                : _error != null
                    ? Center(child: Text(_error!))
                    : _comentarios.isEmpty
                        ? const Center(child: Text('Nenhum comentário ainda.'))
                        : RefreshIndicator(
                            onRefresh: _carregar,
                            child: ListView.builder(
                              padding: const EdgeInsets.all(16),
                              itemCount: _comentarios.length,
                              itemBuilder: (context, index) {
                                final comentario = _comentarios[index];
                                return Padding(
                                  padding: const EdgeInsets.only(bottom: 16),
                                  child: Column(
                                    crossAxisAlignment: CrossAxisAlignment.start,
                                    children: [
                                      Text(
                                        comentario.userName ?? 'Usuário',
                                        style: const TextStyle(fontWeight: FontWeight.w600),
                                      ),
                                      const SizedBox(height: 2),
                                      Text(comentario.content),
                                      const SizedBox(height: 2),
                                      Text(
                                        formatarDataHora(comentario.createdAt),
                                        style: const TextStyle(
                                          fontSize: 12,
                                          color: AppColors.textSecondary,
                                        ),
                                      ),
                                    ],
                                  ),
                                );
                              },
                            ),
                          ),
          ),
          const Divider(height: 1),
          Padding(
            padding: EdgeInsets.only(
              left: 16,
              right: 16,
              top: 12,
              bottom: MediaQuery.of(context).viewInsets.bottom + 12,
            ),
            child: switch (authState) {
              AuthAuthenticated() => Row(
                  crossAxisAlignment: CrossAxisAlignment.end,
                  children: [
                    Expanded(
                      child: TextField(
                        controller: _comentarioController,
                        maxLength: 500,
                        maxLines: 3,
                        minLines: 1,
                        decoration: InputDecoration(
                          hintText: 'Escreva um comentário...',
                          errorText: _erroEnvio,
                        ),
                      ),
                    ),
                    const SizedBox(width: 8),
                    IconButton(
                      onPressed: _enviando ? null : _enviar,
                      icon: _enviando
                          ? const SizedBox(
                              height: 20,
                              width: 20,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send, color: AppColors.warning),
                    ),
                  ],
                ),
              _ => OutlinedButton(
                  onPressed: () => context.push('/login'),
                  child: const Text('Faça login para comentar'),
                ),
            },
          ),
        ],
      ),
    );
  }
}

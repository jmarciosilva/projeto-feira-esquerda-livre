import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_html/flutter_html.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../data/noticia_api.dart';
import '../data/noticia_detalhe.dart';
import '../domain/noticia.dart';

/// Notícia completa, renderizada nativamente no app (o corpo vem em HTML do
/// CMS) — o app nunca sai para o site, nem para abrir uma matéria do blog.
class NoticiaDetailScreen extends ConsumerStatefulWidget {
  const NoticiaDetailScreen({super.key, required this.slug});

  final String slug;

  @override
  ConsumerState<NoticiaDetailScreen> createState() => _NoticiaDetailScreenState();
}

class _NoticiaDetailScreenState extends ConsumerState<NoticiaDetailScreen> {
  NoticiaDetalhe? _detalhe;
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    try {
      final detalhe = await ref.read(noticiaApiProvider).detalhe(widget.slug);
      if (!mounted) return;
      setState(() {
        _detalhe = detalhe;
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

  @override
  Widget build(BuildContext context) {
    if (_isLoading) {
      return const Scaffold(body: Center(child: CircularProgressIndicator()));
    }

    if (_error != null || _detalhe == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(_error ?? 'Notícia não encontrada.')),
      );
    }

    final noticia = _detalhe!.noticia;
    final relacionadas = _detalhe!.relacionadas;
    final bodyFont = Theme.of(context).textTheme.bodyMedium?.fontFamily;

    return Scaffold(
      body: CustomScrollView(
        slivers: [
          SliverAppBar(
            expandedHeight: noticia.imageUrl != null ? 200 : 100,
            pinned: true,
            flexibleSpace: FlexibleSpaceBar(
              background: noticia.imageUrl != null
                  ? CachedNetworkImage(imageUrl: noticia.imageUrl!, fit: BoxFit.cover)
                  : Container(color: AppColors.brown),
            ),
          ),
          SliverToBoxAdapter(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    noticia.title,
                    style: const TextStyle(fontSize: 22, fontWeight: FontWeight.bold),
                  ),
                  const SizedBox(height: 8),
                  Text(
                    [
                      if (noticia.authorName != null) noticia.authorName!,
                      if (noticia.publishedAt != null)
                        DateFormat('dd/MM/yyyy').format(noticia.publishedAt!),
                    ].join(' · '),
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                  const Divider(height: 32),
                  if (noticia.content != null)
                    Html(
                      data: noticia.content!,
                      style: {
                        'body': Style(
                          margin: Margins.zero,
                          fontFamily: bodyFont,
                          fontSize: FontSize(16),
                          lineHeight: const LineHeight(1.4),
                          color: AppColors.textPrimary,
                        ),
                      },
                    ),
                  if (relacionadas.isNotEmpty) ...[
                    const Divider(height: 32),
                    Text(
                      'Leia também',
                      style: Theme.of(context).textTheme.titleMedium,
                    ),
                    const SizedBox(height: 12),
                    for (final item in relacionadas) _NoticiaRelacionadaTile(noticia: item),
                  ],
                ],
              ),
            ),
          ),
        ],
      ),
    );
  }
}

class _NoticiaRelacionadaTile extends StatelessWidget {
  const _NoticiaRelacionadaTile({required this.noticia});

  final Noticia noticia;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      contentPadding: EdgeInsets.zero,
      leading: SizedBox(
        width: 56,
        height: 56,
        child: noticia.imageUrl != null
            ? ClipRRect(
                borderRadius: BorderRadius.circular(8),
                child: CachedNetworkImage(imageUrl: noticia.imageUrl!, fit: BoxFit.cover),
              )
            : Container(
                decoration: BoxDecoration(
                  color: const Color(0xFFF0F0F0),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: const Icon(Icons.article_outlined, color: Color(0xFFBDBDBD)),
              ),
      ),
      title: Text(noticia.title, maxLines: 2, overflow: TextOverflow.ellipsis),
      onTap: () => context.push('/noticias/${noticia.slug}'),
    );
  }
}

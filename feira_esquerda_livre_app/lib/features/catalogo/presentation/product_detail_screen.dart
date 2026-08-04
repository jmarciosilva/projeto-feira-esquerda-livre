import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/whatsapp.dart';
import '../../auth/application/auth_controller.dart';
import '../../auth/application/auth_state.dart';
import '../data/catalogo_api.dart';
import '../domain/product.dart';
import '../domain/product_question.dart';

class ProductDetailScreen extends ConsumerStatefulWidget {
  const ProductDetailScreen({super.key, required this.productId});

  final int productId;

  @override
  ConsumerState<ProductDetailScreen> createState() => _ProductDetailScreenState();
}

class _ProductDetailScreenState extends ConsumerState<ProductDetailScreen> {
  Product? _product;
  List<ProductQuestion> _perguntas = [];
  bool _isLoading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _carregar();
  }

  Future<void> _carregar() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final api = ref.read(catalogoApiProvider);
      final product = await api.detalhe(widget.productId);
      final perguntas = await api.perguntas(widget.productId);
      if (!mounted) return;
      setState(() {
        _product = product;
        _perguntas = perguntas;
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

    if (_error != null || _product == null) {
      return Scaffold(
        appBar: AppBar(),
        body: Center(child: Text(_error ?? 'Produto não encontrado.')),
      );
    }

    final product = _product!;

    return Scaffold(
      appBar: AppBar(title: Text(product.name, overflow: TextOverflow.ellipsis)),
      body: ListView(
        padding: const EdgeInsets.only(bottom: 32),
        children: [
          _Galeria(product: product),
          Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(product.name, style: Theme.of(context).textTheme.titleLarge),
                const SizedBox(height: 8),
                Text(
                  product.priceLabel,
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppColors.warning,
                  ),
                ),
                if (product.modality != null || product.durationMin != null) ...[
                  const SizedBox(height: 12),
                  Wrap(
                    spacing: 8,
                    children: [
                      if (product.modality != null)
                        Chip(label: Text(_modalidadeLabel(product.modality!))),
                      if (product.durationMin != null)
                        Chip(label: Text('${product.durationMin} min')),
                    ],
                  ),
                ],
                if (product.expositor != null) ...[
                  const SizedBox(height: 16),
                  InkWell(
                    onTap: () => context.push('/lojas/${product.expositor!.slug}'),
                    child: Row(
                      children: [
                        const Icon(Icons.storefront_outlined, size: 20, color: AppColors.brown),
                        const SizedBox(width: 8),
                        Expanded(
                          child: Text(
                            product.expositor!.name,
                            style: const TextStyle(fontWeight: FontWeight.w600),
                          ),
                        ),
                        const Icon(Icons.chevron_right, size: 20),
                      ],
                    ),
                  ),
                ],
                if (product.description != null && product.description!.isNotEmpty) ...[
                  const SizedBox(height: 16),
                  Text(product.description!, style: Theme.of(context).textTheme.bodyMedium),
                ],
                if (product.expositor?.whatsapp != null &&
                    product.expositor!.whatsapp!.isNotEmpty) ...[
                  const SizedBox(height: 20),
                  OutlinedButton.icon(
                    onPressed: () => abrirWhatsApp(
                      product.expositor!.whatsapp!,
                      mensagem: 'Olá! Tenho interesse no produto "${product.name}" da Feira Esquerda Livre.',
                    ),
                    icon: const Icon(Icons.chat_bubble_outline),
                    label: const Text('Falar com o lojista no WhatsApp'),
                  ),
                ],
                if (product.faqs != null && product.faqs!.isNotEmpty) ...[
                  const SizedBox(height: 24),
                  Text('Perguntas Frequentes', style: Theme.of(context).textTheme.titleMedium),
                  const SizedBox(height: 8),
                  for (final faq in product.faqs!)
                    ExpansionTile(
                      title: Text(faq.question, style: const TextStyle(fontWeight: FontWeight.w600)),
                      childrenPadding: const EdgeInsets.fromLTRB(16, 0, 16, 16),
                      expandedCrossAxisAlignment: CrossAxisAlignment.start,
                      children: [Text(faq.answer)],
                    ),
                ],
                const SizedBox(height: 24),
                Text('Perguntas e Respostas', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                _QandASection(
                  productId: widget.productId,
                  perguntas: _perguntas,
                  onPerguntaEnviada: (pergunta) {
                    setState(() => _perguntas = [pergunta, ..._perguntas]);
                  },
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }

  String _modalidadeLabel(String modality) {
    return switch (modality) {
      'presencial' => 'Presencial',
      'online' => 'Online',
      'ambos' => 'Presencial ou Online',
      _ => modality,
    };
  }
}

class _Galeria extends StatefulWidget {
  const _Galeria({required this.product});

  final Product product;

  @override
  State<_Galeria> createState() => _GaleriaState();
}

class _GaleriaState extends State<_Galeria> {
  int _pagina = 0;

  @override
  Widget build(BuildContext context) {
    final urls = widget.product.images
        .map((img) => img.mediumUrl)
        .whereType<String>()
        .toList(growable: false);

    if (urls.isEmpty) {
      return AspectRatio(
        aspectRatio: 1,
        child: Container(
          color: const Color(0xFFF0F0F0),
          child: const Icon(Icons.image_not_supported_outlined, size: 48, color: Color(0xFFBDBDBD)),
        ),
      );
    }

    return Column(
      children: [
        AspectRatio(
          aspectRatio: 1,
          child: PageView.builder(
            itemCount: urls.length,
            onPageChanged: (i) => setState(() => _pagina = i),
            itemBuilder: (context, index) => CachedNetworkImage(
              imageUrl: urls[index],
              fit: BoxFit.cover,
              placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
            ),
          ),
        ),
        if (urls.length > 1) ...[
          const SizedBox(height: 8),
          Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: List.generate(urls.length, (i) {
              return Container(
                margin: const EdgeInsets.symmetric(horizontal: 3),
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: i == _pagina ? AppColors.warning : const Color(0xFFE0E0E0),
                ),
              );
            }),
          ),
        ],
      ],
    );
  }
}

class _QandASection extends ConsumerStatefulWidget {
  const _QandASection({
    required this.productId,
    required this.perguntas,
    required this.onPerguntaEnviada,
  });

  final int productId;
  final List<ProductQuestion> perguntas;
  final ValueChanged<ProductQuestion> onPerguntaEnviada;

  @override
  ConsumerState<_QandASection> createState() => _QandASectionState();
}

class _QandASectionState extends ConsumerState<_QandASection> {
  final _perguntaController = TextEditingController();
  bool _enviando = false;
  String? _erro;

  @override
  void dispose() {
    _perguntaController.dispose();
    super.dispose();
  }

  Future<void> _enviar() async {
    final texto = _perguntaController.text.trim();
    if (texto.length < 5) {
      setState(() => _erro = 'Escreva pelo menos 5 caracteres.');
      return;
    }

    setState(() {
      _enviando = true;
      _erro = null;
    });

    try {
      final pergunta = await ref.read(catalogoApiProvider).perguntar(widget.productId, texto);
      widget.onPerguntaEnviada(pergunta);
      _perguntaController.clear();
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Pergunta enviada! O lojista vai responder em breve.')),
        );
      }
    } on ApiException catch (error) {
      setState(() => _erro = error.errorFor('question') ?? error.message);
    } finally {
      if (mounted) setState(() => _enviando = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authControllerProvider);
    final answered = widget.perguntas.where((p) => p.answer != null).toList();

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        if (answered.isEmpty)
          const Padding(
            padding: EdgeInsets.symmetric(vertical: 8),
            child: Text('Nenhuma pergunta ainda. Seja o primeiro a perguntar!'),
          )
        else
          for (final pergunta in answered)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    '${pergunta.askerFirstName} perguntou:',
                    style: const TextStyle(fontWeight: FontWeight.w600, fontSize: 14),
                  ),
                  Text(pergunta.question),
                  const SizedBox(height: 4),
                  Text(
                    'Resposta: ${pergunta.answer}',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ],
              ),
            ),
        const SizedBox(height: 12),
        switch (authState) {
          AuthAuthenticated() => Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                TextField(
                  controller: _perguntaController,
                  maxLength: 500,
                  maxLines: 3,
                  decoration: InputDecoration(
                    hintText: 'Escreva sua pergunta para o lojista...',
                    errorText: _erro,
                  ),
                ),
                Align(
                  alignment: Alignment.centerRight,
                  child: ElevatedButton(
                    onPressed: _enviando ? null : _enviar,
                    child: _enviando
                        ? const SizedBox(
                            height: 20,
                            width: 20,
                            child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                          )
                        : const Text('Enviar pergunta'),
                  ),
                ),
              ],
            ),
          _ => OutlinedButton(
              onPressed: () => context.push('/login'),
              child: const Text('Faça login para perguntar'),
            ),
        },
      ],
    );
  }
}

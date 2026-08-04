import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../data/expositor_solicitacao_api.dart';

const _pixTipos = ['CPF', 'CNPJ', 'email', 'telefone', 'aleatoria'];
const _bancoTiposConta = [
  MapEntry('corrente', 'Conta Corrente'),
  MapEntry('poupanca', 'Conta Poupança'),
  MapEntry('pagamento', 'Conta de Pagamento'),
];
const _eixos = [
  MapEntry('produto', 'Produtos'),
  MapEntry('servico', 'Serviços'),
  MapEntry('cuidado', 'Cuidados & Bem Viver'),
];

/// Formulário "Seja um Expositor", direto no app — mesmo fluxo do
/// `/seja-um-expositor` do site, sem sair do app. A aprovação continua
/// manual, pelo admin.
class SejaExpositorScreen extends ConsumerStatefulWidget {
  const SejaExpositorScreen({super.key});

  @override
  ConsumerState<SejaExpositorScreen> createState() => _SejaExpositorScreenState();
}

class _SejaExpositorScreenState extends ConsumerState<SejaExpositorScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nomeLojaController = TextEditingController();
  final _responsavelController = TextEditingController();
  final _cpfCnpjController = TextEditingController();
  final _whatsappController = TextEditingController();
  final _emailController = TextEditingController();
  final _instagramController = TextEditingController();
  final _facebookController = TextEditingController();
  final _pixChaveController = TextEditingController();
  final _bancoNomeController = TextEditingController();
  final _bancoAgenciaController = TextEditingController();
  final _bancoContaController = TextEditingController();
  final _descricaoController = TextEditingController();

  String? _pixTipo;
  String? _bancoTipoConta;
  final Set<String> _eixosSelecionados = {};

  bool _isLoading = false;
  String? _generalError;
  ApiException? _apiError;

  @override
  void dispose() {
    _nomeLojaController.dispose();
    _responsavelController.dispose();
    _cpfCnpjController.dispose();
    _whatsappController.dispose();
    _emailController.dispose();
    _instagramController.dispose();
    _facebookController.dispose();
    _pixChaveController.dispose();
    _bancoNomeController.dispose();
    _bancoAgenciaController.dispose();
    _bancoContaController.dispose();
    _descricaoController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;
    if (_pixTipo == null) {
      setState(() => _generalError = 'Selecione o tipo da chave PIX.');
      return;
    }

    setState(() {
      _isLoading = true;
      _generalError = null;
      _apiError = null;
    });

    try {
      await ref.read(expositorSolicitacaoApiProvider).enviar({
        'nome_loja': _nomeLojaController.text.trim(),
        'responsavel': _responsavelController.text.trim(),
        'cpf_cnpj': _cpfCnpjController.text.trim(),
        'whatsapp': _whatsappController.text.trim(),
        'email': _emailController.text.trim(),
        'instagram_url': _instagramController.text.trim(),
        if (_facebookController.text.trim().isNotEmpty)
          'facebook_url': _facebookController.text.trim(),
        'pix_tipo': _pixTipo,
        'pix_chave': _pixChaveController.text.trim(),
        if (_bancoNomeController.text.trim().isNotEmpty)
          'banco_nome': _bancoNomeController.text.trim(),
        if (_bancoAgenciaController.text.trim().isNotEmpty)
          'banco_agencia': _bancoAgenciaController.text.trim(),
        if (_bancoContaController.text.trim().isNotEmpty)
          'banco_conta': _bancoContaController.text.trim(),
        if (_bancoTipoConta != null) 'banco_tipo_conta': _bancoTipoConta,
        if (_descricaoController.text.trim().isNotEmpty)
          'descricao': _descricaoController.text.trim(),
        if (_eixosSelecionados.isNotEmpty) 'eixos': _eixosSelecionados.toList(),
      });
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text(
            'Solicitação enviada! Enviamos um e-mail de confirmação e nossa equipe '
            'entrará em contato em até 3 dias úteis.',
          ),
        ),
      );
      context.pop();
    } on ApiException catch (error) {
      setState(() {
        _apiError = error;
        _generalError = error.fieldErrors.isEmpty ? error.message : null;
      });
    } catch (_) {
      setState(() => _generalError = 'Algo deu errado. Tente novamente.');
    } finally {
      if (mounted) setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: const FelAppBar(title: 'Seja um Expositor'),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Preencha os dados abaixo para solicitar seu espaço na Feira Esquerda '
                  'Livre. Nossa equipe analisa e entra em contato em até 3 dias úteis.',
                ),
                const SizedBox(height: 20),
                if (_generalError != null) ...[
                  _ErrorBanner(message: _generalError!),
                  const SizedBox(height: 16),
                ],
                Text('Sobre a loja', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 12),
                TextFormField(
                  controller: _nomeLojaController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Nome da loja',
                    errorText: _apiError?.errorFor('nome_loja'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe o nome da loja.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _responsavelController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Nome do responsável',
                    errorText: _apiError?.errorFor('responsavel'),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? 'Informe o nome do responsável.'
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _cpfCnpjController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'CPF ou CNPJ',
                    errorText: _apiError?.errorFor('cpf_cnpj'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe o CPF ou CNPJ.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _whatsappController,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'WhatsApp',
                    hintText: '(11) 99999-9999',
                    errorText: _apiError?.errorFor('whatsapp'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe o WhatsApp.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _emailController,
                  keyboardType: TextInputType.emailAddress,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'E-mail',
                    errorText: _apiError?.errorFor('email'),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) return 'Informe o e-mail.';
                    if (!value.contains('@')) return 'Informe um e-mail válido.';
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _instagramController,
                  keyboardType: TextInputType.url,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Instagram (URL)',
                    hintText: 'https://instagram.com/sualoja',
                    errorText: _apiError?.errorFor('instagram_url'),
                  ),
                  validator: (value) => (value == null || value.trim().isEmpty)
                      ? 'Informe o endereço do Instagram.'
                      : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _facebookController,
                  keyboardType: TextInputType.url,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Facebook (opcional)',
                    errorText: _apiError?.errorFor('facebook_url'),
                  ),
                ),
                const SizedBox(height: 24),
                Text('Recebimento (PIX)', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  initialValue: _pixTipo,
                  decoration: InputDecoration(
                    labelText: 'Tipo da chave',
                    errorText: _apiError?.errorFor('pix_tipo'),
                  ),
                  items: _pixTipos
                      .map((tipo) => DropdownMenuItem(value: tipo, child: Text(tipo)))
                      .toList(),
                  onChanged: (value) => setState(() => _pixTipo = value),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _pixChaveController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Chave PIX',
                    hintText: 'Ex.: 11999887766 / email@exemplo.com',
                    errorText: _apiError?.errorFor('pix_chave'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe a chave PIX.' : null,
                ),
                const SizedBox(height: 8),
                ExpansionTile(
                  tilePadding: EdgeInsets.zero,
                  title: const Text('Dados bancários (opcional)'),
                  children: [
                    TextFormField(
                      controller: _bancoNomeController,
                      decoration: const InputDecoration(labelText: 'Banco'),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _bancoAgenciaController,
                      decoration: const InputDecoration(labelText: 'Agência'),
                    ),
                    const SizedBox(height: 16),
                    TextFormField(
                      controller: _bancoContaController,
                      decoration: const InputDecoration(labelText: 'Conta'),
                    ),
                    const SizedBox(height: 16),
                    DropdownButtonFormField<String>(
                      initialValue: _bancoTipoConta,
                      decoration: const InputDecoration(labelText: 'Tipo de conta'),
                      items: _bancoTiposConta
                          .map((e) => DropdownMenuItem(value: e.key, child: Text(e.value)))
                          .toList(),
                      onChanged: (value) => setState(() => _bancoTipoConta = value),
                    ),
                    const SizedBox(height: 8),
                  ],
                ),
                const SizedBox(height: 16),
                Text('O que você quer expor?', style: Theme.of(context).textTheme.titleMedium),
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: [
                    for (final eixo in _eixos)
                      FilterChip(
                        label: Text(eixo.value),
                        selected: _eixosSelecionados.contains(eixo.key),
                        selectedColor: AppColors.accentYellow,
                        onSelected: (selected) => setState(() {
                          selected
                              ? _eixosSelecionados.add(eixo.key)
                              : _eixosSelecionados.remove(eixo.key);
                        }),
                      ),
                  ],
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _descricaoController,
                  maxLines: 4,
                  decoration: const InputDecoration(
                    labelText: 'Conte um pouco sobre o que você faz (opcional)',
                    alignLabelWithHint: true,
                  ),
                ),
                const SizedBox(height: 24),
                ElevatedButton(
                  onPressed: _isLoading ? null : _submit,
                  child: _isLoading
                      ? const SizedBox(
                          height: 22,
                          width: 22,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Enviar Solicitação'),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }
}

class _ErrorBanner extends StatelessWidget {
  const _ErrorBanner({required this.message});

  final String message;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: Theme.of(context).colorScheme.error.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(12),
        border: Border.all(color: Theme.of(context).colorScheme.error.withValues(alpha: 0.3)),
      ),
      child: Row(
        children: [
          Icon(Icons.error_outline, color: Theme.of(context).colorScheme.error, size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(message, style: TextStyle(color: Theme.of(context).colorScheme.error)),
          ),
        ],
      ),
    );
  }
}

import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/http/api_exception.dart';
import '../../../shared/widgets/fel_app_bar.dart';
import '../data/contato_api.dart';

/// Formulário de contato, direto no app — mesmo fluxo do `/contato` do
/// site, mas sem sair do app: a mensagem vai para o e-mail da plataforma.
class ContatoFormScreen extends ConsumerStatefulWidget {
  const ContatoFormScreen({super.key});

  @override
  ConsumerState<ContatoFormScreen> createState() => _ContatoFormScreenState();
}

class _ContatoFormScreenState extends ConsumerState<ContatoFormScreen> {
  final _formKey = GlobalKey<FormState>();
  final _nameController = TextEditingController();
  final _emailController = TextEditingController();
  final _phoneController = TextEditingController();
  final _subjectController = TextEditingController();
  final _messageController = TextEditingController();

  bool _isLoading = false;
  String? _generalError;
  ApiException? _apiError;

  @override
  void dispose() {
    _nameController.dispose();
    _emailController.dispose();
    _phoneController.dispose();
    _subjectController.dispose();
    _messageController.dispose();
    super.dispose();
  }

  Future<void> _submit() async {
    if (!_formKey.currentState!.validate()) return;

    setState(() {
      _isLoading = true;
      _generalError = null;
      _apiError = null;
    });

    try {
      await ref.read(contatoApiProvider).enviarMensagem(
            name: _nameController.text.trim(),
            email: _emailController.text.trim(),
            phone: _phoneController.text.trim(),
            subject: _subjectController.text.trim(),
            message: _messageController.text.trim(),
          );
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Mensagem enviada com sucesso! Nossa equipe retornará em breve.'),
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
      appBar: const FelAppBar(title: 'Fale Conosco'),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.all(24),
          child: Form(
            key: _formKey,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.stretch,
              children: [
                const Text(
                  'Tem uma dúvida, sugestão ou quer vender seus produtos na feira? '
                  'Escreva pra gente.',
                ),
                const SizedBox(height: 20),
                if (_generalError != null) ...[
                  _ErrorBanner(message: _generalError!),
                  const SizedBox(height: 16),
                ],
                TextFormField(
                  controller: _nameController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Nome',
                    errorText: _apiError?.errorFor('name'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe seu nome.' : null,
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
                    if (value == null || value.trim().isEmpty) return 'Informe seu e-mail.';
                    if (!value.contains('@')) return 'Informe um e-mail válido.';
                    return null;
                  },
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _phoneController,
                  keyboardType: TextInputType.phone,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Telefone (opcional)',
                    hintText: '(11) 99999-9999',
                    errorText: _apiError?.errorFor('phone'),
                  ),
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _subjectController,
                  textInputAction: TextInputAction.next,
                  decoration: InputDecoration(
                    labelText: 'Assunto',
                    errorText: _apiError?.errorFor('subject'),
                  ),
                  validator: (value) =>
                      (value == null || value.trim().isEmpty) ? 'Informe o assunto.' : null,
                ),
                const SizedBox(height: 16),
                TextFormField(
                  controller: _messageController,
                  maxLines: 5,
                  textInputAction: TextInputAction.newline,
                  decoration: InputDecoration(
                    labelText: 'Mensagem',
                    alignLabelWithHint: true,
                    errorText: _apiError?.errorFor('message'),
                  ),
                  validator: (value) {
                    if (value == null || value.trim().isEmpty) return 'Escreva sua mensagem.';
                    if (value.trim().length < 10) {
                      return 'A mensagem deve ter pelo menos 10 caracteres.';
                    }
                    return null;
                  },
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
                      : const Text('Enviar Mensagem'),
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

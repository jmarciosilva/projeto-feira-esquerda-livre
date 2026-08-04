import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:url_launcher/url_launcher.dart';

import '../../../core/http/api_exception.dart';
import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/formatters.dart';
import '../data/rastreio_api.dart';
import '../domain/rastreio.dart';

class RastreioScreen extends ConsumerStatefulWidget {
  const RastreioScreen({super.key});

  @override
  ConsumerState<RastreioScreen> createState() => _RastreioScreenState();
}

class _RastreioScreenState extends ConsumerState<RastreioScreen> {
  final _codigoController = TextEditingController();
  Rastreio? _rastreio;
  bool _isLoading = false;
  String? _error;

  @override
  void dispose() {
    _codigoController.dispose();
    super.dispose();
  }

  Future<void> _consultar() async {
    final codigo = _codigoController.text.trim();
    if (codigo.isEmpty) return;

    setState(() {
      _isLoading = true;
      _error = null;
      _rastreio = null;
    });

    try {
      final rastreio = await ref.read(rastreioApiProvider).consultar(codigo);
      if (!mounted) return;
      setState(() {
        _rastreio = rastreio;
        _isLoading = false;
      });
    } on ApiException catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.statusCode == 404
            ? 'Nenhuma entrega encontrada com esse código.'
            : error.message;
        _isLoading = false;
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('Rastrear Pedido')),
      body: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: [
            Row(
              children: [
                Expanded(
                  child: TextField(
                    controller: _codigoController,
                    textCapitalization: TextCapitalization.characters,
                    decoration: const InputDecoration(hintText: 'Código de rastreio'),
                    onSubmitted: (_) => _consultar(),
                  ),
                ),
                const SizedBox(width: 12),
                ElevatedButton(
                  onPressed: _isLoading ? null : _consultar,
                  child: _isLoading
                      ? const SizedBox(
                          height: 20,
                          width: 20,
                          child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                        )
                      : const Text('Buscar'),
                ),
              ],
            ),
            const SizedBox(height: 24),
            if (_error != null) Text(_error!, style: const TextStyle(color: AppColors.danger)),
            if (_rastreio != null) Expanded(child: _RastreioResultado(rastreio: _rastreio!)),
          ],
        ),
      ),
    );
  }
}

class _RastreioResultado extends StatelessWidget {
  const _RastreioResultado({required this.rastreio});

  final Rastreio rastreio;

  @override
  Widget build(BuildContext context) {
    return ListView(
      children: [
        Card(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  _statusLabel(rastreio.status),
                  style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
                ),
                if (rastreio.carrier != null)
                  Text('Transportadora: ${rastreio.carrier} ${rastreio.serviceName ?? ''}'),
                if (rastreio.trackingCode != null) Text('Código: ${rastreio.trackingCode}'),
                if (rastreio.estimatedDeliveryDate != null)
                  Text('Previsão de entrega: ${formatarData(rastreio.estimatedDeliveryDate)}'),
                if (rastreio.carrierTrackingUrl != null) ...[
                  const SizedBox(height: 8),
                  TextButton(
                    onPressed: () => launchUrl(
                      Uri.parse(rastreio.carrierTrackingUrl!),
                      mode: LaunchMode.externalApplication,
                    ),
                    child: const Text('Rastrear no site da transportadora'),
                  ),
                ],
              ],
            ),
          ),
        ),
        const SizedBox(height: 16),
        if (rastreio.events.isEmpty)
          const Padding(
            padding: EdgeInsets.all(8),
            child: Text('Ainda não há eventos de rastreio registrados.'),
          )
        else
          for (final evento in rastreio.events)
            Padding(
              padding: const EdgeInsets.only(bottom: 16),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Padding(
                    padding: EdgeInsets.only(top: 4),
                    child: Icon(Icons.circle, size: 12, color: AppColors.warning),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(evento.description, style: const TextStyle(fontWeight: FontWeight.w600)),
                        if (evento.location != null) Text(evento.location!),
                        Text(
                          formatarDataHora(evento.happenedAt),
                          style: const TextStyle(color: AppColors.textSecondary, fontSize: 13),
                        ),
                      ],
                    ),
                  ),
                ],
              ),
            ),
      ],
    );
  }

  String _statusLabel(String? status) {
    return switch (status) {
      'pending' => 'Pendente',
      'label_generated' => 'Etiqueta gerada',
      'shipped' => 'Enviado',
      'in_transit' => 'Em trânsito',
      'delivered' => 'Entregue',
      'returned' => 'Devolvido',
      'failed' => 'Problema na entrega',
      _ => 'Status desconhecido',
    };
  }
}

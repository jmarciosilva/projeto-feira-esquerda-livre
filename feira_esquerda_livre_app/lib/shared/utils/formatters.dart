import 'package:intl/intl.dart';

// Formatos puramente numéricos (+ literais entre aspas) não dependem de
// tabelas de locale carregadas via initializeDateFormatting(), por isso
// funcionam sem configuração extra mesmo com locale 'pt_BR'.
final _dataFormatter = DateFormat('dd/MM/yyyy');
final _dataHoraFormatter = DateFormat('dd/MM/yyyy \'às\' HH:mm');

String formatarData(DateTime? data) => data == null ? '' : _dataFormatter.format(data);

String formatarDataHora(DateTime? data) => data == null ? '' : _dataHoraFormatter.format(data);

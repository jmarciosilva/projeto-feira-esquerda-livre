import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter_test/flutter_test.dart';

import 'package:feira_esquerda_livre_app/main.dart';

void main() {
  testWidgets('App inicia mostrando a splash screen', (WidgetTester tester) async {
    await tester.pumpWidget(const ProviderScope(child: FeiraEsquerdaLivreApp()));
    await tester.pump();

    expect(find.text('Feira Esquerda Livre'), findsOneWidget);
    expect(find.byType(CircularProgressIndicator), findsOneWidget);
  });
}

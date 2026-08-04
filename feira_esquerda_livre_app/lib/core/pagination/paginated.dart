/// Envelope de paginação padrão do Laravel: `{ data, links, meta }`.
/// Ver "Envelope das respostas" em docs/API.md do backend.
class Paginated<T> {
  Paginated({
    required this.data,
    required this.currentPage,
    required this.lastPage,
    required this.total,
  });

  final List<T> data;
  final int currentPage;
  final int lastPage;
  final int total;

  bool get hasMore => currentPage < lastPage;

  factory Paginated.fromJson(
    Map<String, dynamic> json,
    T Function(Map<String, dynamic>) fromJsonT,
  ) {
    final meta = json['meta'] as Map<String, dynamic>;
    return Paginated(
      data: (json['data'] as List)
          .map((e) => fromJsonT(e as Map<String, dynamic>))
          .toList(),
      currentPage: meta['current_page'] as int,
      lastPage: meta['last_page'] as int,
      total: meta['total'] as int,
    );
  }

  /// Concatena outra página no final desta (usado no "Carregar mais").
  Paginated<T> appendPage(Paginated<T> next) {
    return Paginated(
      data: [...data, ...next.data],
      currentPage: next.currentPage,
      lastPage: next.lastPage,
      total: next.total,
    );
  }
}

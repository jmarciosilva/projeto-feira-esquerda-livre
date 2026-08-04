import 'package:cached_network_image/cached_network_image.dart';
import 'package:flutter/material.dart';

import '../../../core/theme/app_colors.dart';
import '../../../shared/utils/formatters.dart';
import '../domain/feed_post.dart';

class FeedPostCard extends StatelessWidget {
  const FeedPostCard({
    super.key,
    required this.post,
    required this.onTapLoja,
    required this.onTapCurtir,
    required this.onTapComentarios,
  });

  final FeedPost post;
  final VoidCallback onTapLoja;
  final VoidCallback onTapCurtir;
  final VoidCallback onTapComentarios;

  @override
  Widget build(BuildContext context) {
    final imagem = post.images.isNotEmpty ? post.images.first.mediumUrl : null;

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: BoxDecoration(
        color: AppColors.surface,
        borderRadius: BorderRadius.circular(16),
        border: Border.all(color: const Color(0xFFE5E5E5)),
      ),
      clipBehavior: Clip.antiAlias,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          InkWell(
            onTap: onTapLoja,
            child: Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  CircleAvatar(
                    radius: 18,
                    backgroundColor: AppColors.accentYellow,
                    backgroundImage: post.expositor?.logoUrl != null
                        ? CachedNetworkImageProvider(post.expositor!.logoUrl!)
                        : null,
                    child: post.expositor?.logoUrl == null
                        ? const Icon(Icons.storefront_rounded, color: AppColors.brown, size: 18)
                        : null,
                  ),
                  const SizedBox(width: 10),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          post.expositor?.name ?? 'Feira Esquerda Livre',
                          style: const TextStyle(fontWeight: FontWeight.w600),
                        ),
                        Text(
                          formatarDataHora(post.createdAt),
                          style: const TextStyle(fontSize: 12, color: AppColors.textSecondary),
                        ),
                      ],
                    ),
                  ),
                  if (post.typeLabel != null)
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppColors.accentYellow,
                        borderRadius: BorderRadius.circular(20),
                      ),
                      child: Text(
                        post.typeLabel!,
                        style: const TextStyle(fontSize: 11, color: AppColors.brown, fontWeight: FontWeight.w600),
                      ),
                    ),
                ],
              ),
            ),
          ),
          if (post.content.isNotEmpty)
            Padding(
              padding: const EdgeInsets.fromLTRB(12, 0, 12, 12),
              child: Text(post.content, maxLines: 6, overflow: TextOverflow.ellipsis),
            ),
          if (imagem != null)
            AspectRatio(
              aspectRatio: 16 / 10,
              child: CachedNetworkImage(
                imageUrl: imagem,
                fit: BoxFit.cover,
                placeholder: (context, url) => Container(color: const Color(0xFFF0F0F0)),
              ),
            ),
          Padding(
            padding: const EdgeInsets.all(8),
            child: Row(
              children: [
                TextButton.icon(
                  onPressed: onTapCurtir,
                  icon: Icon(
                    post.likedByMe ? Icons.favorite : Icons.favorite_border,
                    color: post.likedByMe ? AppColors.danger : AppColors.textSecondary,
                    size: 20,
                  ),
                  label: Text(
                    '${post.likesCount}',
                    style: TextStyle(color: post.likedByMe ? AppColors.danger : AppColors.textSecondary),
                  ),
                ),
                TextButton.icon(
                  onPressed: onTapComentarios,
                  icon: const Icon(Icons.mode_comment_outlined, size: 20, color: AppColors.textSecondary),
                  label: Text(
                    '${post.commentsCount}',
                    style: const TextStyle(color: AppColors.textSecondary),
                  ),
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

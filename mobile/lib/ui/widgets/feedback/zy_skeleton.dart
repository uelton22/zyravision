import 'package:flutter/material.dart';
import 'package:shimmer/shimmer.dart';

import '../../../core/theme/zy_spacing.dart';

class ZySkeleton extends StatelessWidget {
  final double? width;
  final double height;
  final double borderRadius;

  const ZySkeleton({
    super.key,
    this.width,
    required this.height,
    this.borderRadius = ZySpacing.radiusMd,
  });

  const ZySkeleton.rect({
    super.key,
    this.width,
    this.height = 20,
    this.borderRadius = ZySpacing.radiusMd,
  });

  const ZySkeleton.circle({
    super.key,
    double size = 48,
  })  : width = size,
        height = size,
        borderRadius = ZySpacing.radiusFull;

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final baseColor = isDark ? const Color(0xFF334155) : const Color(0xFFE2E8F0);
    final highlightColor = isDark ? const Color(0xFF475569) : const Color(0xFFF1F5F9);

    return Shimmer.fromColors(
      baseColor: baseColor,
      highlightColor: highlightColor,
      child: Container(
        width: width,
        height: height,
        decoration: BoxDecoration(
          color: baseColor,
          borderRadius: BorderRadius.circular(borderRadius),
        ),
      ),
    );
  }

  static Widget statCard() {
    return Builder(builder: (context) {
      return const Padding(
        padding: ZySpacing.cardInsets,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            ZySkeleton.rect(width: 80, height: 14),
            SizedBox(height: ZySpacing.sm),
            ZySkeleton.rect(width: 60, height: 28),
            SizedBox(height: ZySpacing.xs),
            ZySkeleton.rect(width: 40, height: 12),
          ],
        ),
      );
    });
  }

  static Widget list({int itemCount = 5, double itemHeight = 72}) {
    return Builder(builder: (context) {
      return Column(
        children: List.generate(itemCount, (i) {
          return Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: ZySpacing.base,
              vertical: ZySpacing.xs,
            ),
            child: ZySkeleton.rect(height: itemHeight, borderRadius: ZySpacing.radiusLg),
          );
        }),
      );
    });
  }

  static Widget cameraCard() {
    return Builder(builder: (context) {
      return const Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          ZySkeleton.rect(height: 120, borderRadius: ZySpacing.radiusLg),
          SizedBox(height: ZySpacing.sm),
          ZySkeleton.rect(width: 120, height: 16),
          SizedBox(height: ZySpacing.xs),
          ZySkeleton.rect(width: 80, height: 12),
        ],
      );
    });
  }
}

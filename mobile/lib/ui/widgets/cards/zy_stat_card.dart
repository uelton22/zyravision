import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';
import 'zy_card.dart';

class ZyStatCard extends StatelessWidget {
  final String label;
  final String value;
  final String? subtitle;
  final IconData icon;
  final Color? iconColor;
  final double? progress;
  final Color? progressColor;
  final VoidCallback? onTap;

  const ZyStatCard({
    super.key,
    required this.label,
    required this.value,
    this.subtitle,
    required this.icon,
    this.iconColor,
    this.progress,
    this.progressColor,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final effectiveIconColor = iconColor ?? theme.colorScheme.primary;

    return ZyCard(
      onTap: onTap,
      padding: const EdgeInsets.all(ZySpacing.md),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 18, color: effectiveIconColor),
              const SizedBox(width: ZySpacing.xs),
              Expanded(
                child: Text(
                  label,
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: theme.colorScheme.outline,
                  ),
                  overflow: TextOverflow.ellipsis,
                ),
              ),
            ],
          ),
          const SizedBox(height: ZySpacing.sm),
          Text(
            value,
            style: theme.textTheme.displaySmall?.copyWith(
              fontWeight: FontWeight.w700,
            ),
          ),
          if (subtitle != null) ...[
            const SizedBox(height: 2),
            Text(
              subtitle!,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
              ),
            ),
          ],
          if (progress != null) ...[
            const SizedBox(height: ZySpacing.sm),
            ClipRRect(
              borderRadius: ZySpacing.borderRadiusFull,
              child: LinearProgressIndicator(
                value: progress!.clamp(0.0, 1.0),
                backgroundColor:
                    theme.colorScheme.outline.withValues(alpha: 0.15),
                color: progressColor ?? effectiveIconColor,
                minHeight: 4,
              ),
            ),
          ],
        ],
      ),
    );
  }
}

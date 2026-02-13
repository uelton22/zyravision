import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/analytics_event_summary.dart';

class ZyAlertCard extends StatelessWidget {
  final AnalyticsEventSummary event;
  final VoidCallback? onTap;

  const ZyAlertCard({
    super.key,
    required this.event,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final config = _getConfig(event.type);

    return GestureDetector(
      onTap: onTap,
      child: Container(
        decoration: BoxDecoration(
          color: theme.colorScheme.surface,
          borderRadius: ZySpacing.borderRadiusLg,
          border: Border.all(
            color: theme.colorScheme.outline.withValues(alpha: 0.2),
          ),
        ),
        child: IntrinsicHeight(
          child: Row(
            children: [
              // Colored left border
              Container(
                width: 4,
                decoration: BoxDecoration(
                  color: config.color,
                  borderRadius: const BorderRadius.only(
                    topLeft: Radius.circular(ZySpacing.radiusLg),
                    bottomLeft: Radius.circular(ZySpacing.radiusLg),
                  ),
                ),
              ),
              // Content
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.all(ZySpacing.md),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Row(
                        children: [
                          Icon(config.icon, size: 16, color: config.color),
                          const SizedBox(width: ZySpacing.sm),
                          Expanded(
                            child: Text(
                              _formatType(event.type),
                              style: theme.textTheme.labelLarge,
                              maxLines: 1,
                              overflow: TextOverflow.ellipsis,
                            ),
                          ),
                        ],
                      ),
                      if (event.cameraName != null) ...[
                        const SizedBox(height: ZySpacing.xs),
                        Text(
                          event.cameraName!,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.outline,
                          ),
                        ),
                      ],
                      if (event.startedAt != null) ...[
                        const SizedBox(height: ZySpacing.xs),
                        Text(
                          _formatTime(event.startedAt!),
                          style: theme.textTheme.labelSmall?.copyWith(
                            color: theme.colorScheme.outline,
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              // Arrow
              if (onTap != null)
                Padding(
                  padding: const EdgeInsets.only(right: ZySpacing.md),
                  child: Icon(
                    Icons.chevron_right,
                    size: 20,
                    color: theme.colorScheme.outline,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  _AlertConfig _getConfig(String type) {
    if (type.contains('offline') || type.contains('error')) {
      return const _AlertConfig(
        color: ZyColors.danger,
        icon: Icons.error_outline,
      );
    }
    if (type.contains('warning') || type.contains('storage')) {
      return const _AlertConfig(
        color: ZyColors.warning,
        icon: Icons.warning_amber,
      );
    }
    return const _AlertConfig(
      color: ZyColors.info,
      icon: Icons.info_outline,
    );
  }

  String _formatType(String type) {
    return type
        .replaceAll('_', ' ')
        .split(' ')
        .map((w) => w.isNotEmpty ? '${w[0].toUpperCase()}${w.substring(1)}' : '')
        .join(' ');
  }

  String _formatTime(DateTime time) {
    final now = DateTime.now();
    final diff = now.difference(time);

    if (diff.inMinutes < 1) return 'Agora';
    if (diff.inMinutes < 60) return 'ha ${diff.inMinutes} min';
    if (diff.inHours < 24) return 'ha ${diff.inHours}h';
    return 'ha ${diff.inDays}d';
  }
}

class _AlertConfig {
  final Color color;
  final IconData icon;

  const _AlertConfig({required this.color, required this.icon});
}

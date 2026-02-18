import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/recording.dart';
import '../../../domain/enums/recording_status.dart';
import '../feedback/zy_badge.dart';

class ZyRecordingCard extends StatelessWidget {
  final Recording recording;
  final VoidCallback? onTap;
  final VoidCallback? onPlayback;

  const ZyRecordingCard({
    super.key,
    required this.recording,
    this.onTap,
    this.onPlayback,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

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
        child: Padding(
          padding: ZySpacing.cardInsets,
          child: Row(
            children: [
              // Thumbnail/Icon
              Container(
                width: 56,
                height: 56,
                decoration: BoxDecoration(
                  color: _statusColor.withValues(alpha: 0.1),
                  borderRadius: ZySpacing.borderRadiusMd,
                ),
                child: Icon(
                  _statusIcon,
                  color: _statusColor,
                  size: 28,
                ),
              ),
              const SizedBox(width: ZySpacing.md),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Expanded(
                          child: Text(
                            recording.cameraName ?? 'Camera',
                            style: theme.textTheme.titleSmall?.copyWith(
                              fontWeight: FontWeight.w600,
                            ),
                            maxLines: 1,
                            overflow: TextOverflow.ellipsis,
                          ),
                        ),
                        const SizedBox(width: ZySpacing.sm),
                        _buildStatusBadge(),
                      ],
                    ),
                    const SizedBox(height: ZySpacing.xs),
                    // Date & Duration
                    Row(
                      children: [
                        Icon(
                          Icons.calendar_today_outlined,
                          size: 14,
                          color: theme.colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          _formatDate(recording.startedAt),
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.outline,
                          ),
                        ),
                        const SizedBox(width: ZySpacing.md),
                        Icon(
                          Icons.schedule_outlined,
                          size: 14,
                          color: theme.colorScheme.outline,
                        ),
                        const SizedBox(width: 4),
                        Text(
                          recording.durationHuman ?? _formatDuration(recording.durationSeconds),
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.outline,
                          ),
                        ),
                      ],
                    ),
                    const SizedBox(height: 2),
                    // File size & resolution
                    Row(
                      children: [
                        if (recording.fileSizeHuman != null) ...[
                          Icon(
                            Icons.storage_outlined,
                            size: 14,
                            color: theme.colorScheme.outline,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            recording.fileSizeHuman!,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.outline,
                            ),
                          ),
                        ],
                        if (recording.resolution != null) ...[
                          const SizedBox(width: ZySpacing.md),
                          Icon(
                            Icons.high_quality_outlined,
                            size: 14,
                            color: theme.colorScheme.outline,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            recording.resolution!,
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.outline,
                            ),
                          ),
                        ],
                      ],
                    ),
                  ],
                ),
              ),
              // Play button
              if (recording.hasPlayback)
                IconButton(
                  onPressed: onPlayback,
                  icon: Container(
                    width: 40,
                    height: 40,
                    decoration: BoxDecoration(
                      color: ZyColors.primary.withValues(alpha: 0.1),
                      shape: BoxShape.circle,
                    ),
                    child: const Icon(
                      Icons.play_arrow_rounded,
                      color: ZyColors.primary,
                      size: 24,
                    ),
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStatusBadge() {
    switch (recording.status) {
      case RecordingStatus.recording:
        return const ZyBadge(
          label: 'Gravando',
          variant: ZyBadgeVariant.danger,
          size: ZyBadgeSize.sm,
          pulse: true,
        );
      case RecordingStatus.completed:
        return const ZyBadge(
          label: 'Completa',
          variant: ZyBadgeVariant.success,
          size: ZyBadgeSize.sm,
        );
      case RecordingStatus.failed:
        return const ZyBadge(
          label: 'Falha',
          variant: ZyBadgeVariant.danger,
          size: ZyBadgeSize.sm,
        );
      default:
        return const SizedBox.shrink();
    }
  }

  Color get _statusColor {
    switch (recording.status) {
      case RecordingStatus.recording:
        return ZyColors.danger;
      case RecordingStatus.completed:
        return ZyColors.success;
      case RecordingStatus.failed:
        return ZyColors.danger;
      default:
        return ZyColors.info;
    }
  }

  IconData get _statusIcon {
    switch (recording.status) {
      case RecordingStatus.recording:
        return Icons.fiber_manual_record;
      case RecordingStatus.completed:
        return Icons.videocam_outlined;
      case RecordingStatus.failed:
        return Icons.error_outline;
      default:
        return Icons.video_file_outlined;
    }
  }

  String _formatDate(DateTime? date) {
    if (date == null) return '--';
    final d = date.toLocal();
    return '${d.day.toString().padLeft(2, '0')}/${d.month.toString().padLeft(2, '0')} '
        '${d.hour.toString().padLeft(2, '0')}:${d.minute.toString().padLeft(2, '0')}';
  }

  String _formatDuration(int seconds) {
    final h = seconds ~/ 3600;
    final m = (seconds % 3600) ~/ 60;
    final s = seconds % 60;
    if (h > 0) return '${h}h ${m.toString().padLeft(2, '0')}m';
    return '${m}m ${s.toString().padLeft(2, '0')}s';
  }
}

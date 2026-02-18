import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/camera_summary.dart';
import '../camera/zy_camera_live_preview.dart';
import '../feedback/zy_badge.dart';

class ZyCameraCard extends StatelessWidget {
  final CameraSummary camera;
  final VoidCallback? onTap;
  final double thumbnailHeight;

  const ZyCameraCard({
    super.key,
    required this.camera,
    this.onTap,
    this.thumbnailHeight = 120,
  });

  const ZyCameraCard.compact({
    super.key,
    required this.camera,
    this.onTap,
  }) : thumbnailHeight = 80;

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
        clipBehavior: Clip.antiAlias,
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          mainAxisSize: MainAxisSize.min,
          children: [
            // Thumbnail com snapshot real da camera
            Stack(
              children: [
                ZyCameraLivePreview(
                  hlsPath: camera.hlsPath,
                  isOnline: camera.isOnline,
                  height: thumbnailHeight,
                ),
                // Badges
                if (camera.isOnline)
                  const Positioned(
                    top: 6,
                    left: 6,
                    child: ZyBadge(
                      label: 'LIVE',
                      variant: ZyBadgeVariant.success,
                      size: ZyBadgeSize.sm,
                    ),
                  ),
                if (camera.isRecording)
                  const Positioned(
                    top: 6,
                    right: 6,
                    child: ZyBadge(
                      label: 'REC',
                      variant: ZyBadgeVariant.danger,
                      size: ZyBadgeSize.sm,
                      pulse: true,
                    ),
                  ),
                if (!camera.isOnline)
                  Positioned.fill(
                    child: Container(
                      color: Colors.black38,
                      child: const Center(
                        child: ZyBadge(
                          label: 'OFFLINE',
                          variant: ZyBadgeVariant.danger,
                        ),
                      ),
                    ),
                  ),
              ],
            ),
            // Info
            Padding(
              padding: const EdgeInsets.all(ZySpacing.sm),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    camera.name,
                    style: theme.textTheme.bodyMedium?.copyWith(
                      fontWeight: FontWeight.w600,
                    ),
                    maxLines: 1,
                    overflow: TextOverflow.ellipsis,
                  ),
                  const SizedBox(height: 2),
                  Row(
                    children: [
                      Container(
                        width: 8,
                        height: 8,
                        decoration: BoxDecoration(
                          shape: BoxShape.circle,
                          color: camera.isOnline
                              ? ZyColors.success
                              : ZyColors.danger,
                        ),
                      ),
                      const SizedBox(width: ZySpacing.xs),
                      Text(
                        camera.isOnline ? 'Online' : 'Offline',
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: theme.colorScheme.outline,
                        ),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ],
        ),
      ),
    );
  }
}

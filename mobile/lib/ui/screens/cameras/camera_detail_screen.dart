import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/camera.dart';
import '../../../state/camera/camera_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/camera/zy_camera_live_preview.dart';
import '../../widgets/feedback/zy_badge.dart';
import '../../widgets/feedback/zy_empty_state.dart';
import '../../widgets/feedback/zy_skeleton.dart';

class CameraDetailScreen extends ConsumerWidget {
  final String cameraId;

  const CameraDetailScreen({super.key, required this.cameraId});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final cameraAsync = ref.watch(cameraDetailProvider(cameraId));

    final camera = cameraAsync.valueOrNull;

    return Scaffold(
      appBar: AppBar(
        leading: IconButton(
          icon: const Icon(Icons.arrow_back),
          onPressed: () => context.go(RouteNames.cameras),
        ),
        title: Text(camera?.name ?? 'Camera'),
        actions: [
          if (camera != null && camera.isOnline)
            IconButton(
              icon: const Icon(Icons.play_circle_filled_rounded),
              tooltip: 'Live View',
              onPressed: () {
                final encodedName = Uri.encodeComponent(camera.name);
                context.go(
                  '${RouteNames.liveViewPath(camera.id)}?name=$encodedName',
                );
              },
            ),
        ],
      ),
      body: cameraAsync.when(
        data: (cam) => _CameraDetailContent(camera: cam),
        loading: () => const _DetailSkeleton(),
        error: (error, _) => ZyEmptyState(
          icon: Icons.error_outline,
          message: 'Erro ao carregar camera',
          description: error.toString(),
          actionLabel: 'Tentar novamente',
          onAction: () =>
              ref.read(cameraDetailProvider(cameraId).notifier).refresh(),
        ),
      ),
    );
  }
}

class _CameraDetailContent extends ConsumerWidget {
  final Camera camera;

  const _CameraDetailContent({required this.camera});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return RefreshIndicator(
      onRefresh: () =>
          ref.read(cameraDetailProvider(camera.id).notifier).refresh(),
      child: ListView(
        padding: const EdgeInsets.all(ZySpacing.base),
        children: [
          // Preview card
          _PreviewCard(camera: camera),
          const SizedBox(height: ZySpacing.base),

          // Live view button
          if (camera.isOnline) ...[
            _LiveViewButton(camera: camera),
            const SizedBox(height: ZySpacing.base),
          ],

          // Stream controls
          _StreamControls(camera: camera),
          const SizedBox(height: ZySpacing.base),

          // Camera info
          _InfoSection(
            title: 'Informacoes',
            children: [
              _InfoRow('Nome', camera.name),
              if (camera.manufacturer != null)
                _InfoRow('Fabricante', camera.manufacturer!),
              if (camera.model != null) _InfoRow('Modelo', camera.model!),
              if (camera.ipAddress != null) _InfoRow('IP', camera.ipAddress!),
              if (camera.location != null)
                _InfoRow('Localizacao', camera.location!),
            ],
          ),
          const SizedBox(height: ZySpacing.base),

          // Stream info
          _InfoSection(
            title: 'Stream',
            children: [
              _InfoRow(
                'Status',
                camera.stream?.status ?? 'Sem stream',
                valueColor: _streamStatusColor(camera.stream?.status),
              ),
              if (camera.codec != null) _InfoRow('Codec', camera.codec!),
              if (camera.resolution != null)
                _InfoRow('Resolucao', camera.resolution!),
              if (camera.fps != null) _InfoRow('FPS', '${camera.fps}'),
              if (camera.recordingQuality != null)
                _InfoRow('Qualidade', camera.recordingQuality!),
              if (camera.stream?.mediamtxPath != null)
                _InfoRow('Path', camera.stream!.mediamtxPath!),
            ],
          ),
          const SizedBox(height: ZySpacing.base),

          // Group info
          if (camera.groupName != null)
            _InfoSection(
              title: 'Grupo',
              children: [
                _InfoRow('Grupo', camera.groupName!),
              ],
            ),

          const SizedBox(height: ZySpacing.xxxl),
        ],
      ),
    );
  }

  Color? _streamStatusColor(String? status) {
    switch (status) {
      case 'active':
        return ZyColors.success;
      case 'starting':
      case 'reconnecting':
        return ZyColors.warning;
      case 'error':
        return ZyColors.danger;
      default:
        return null;
    }
  }
}

class _PreviewCard extends StatelessWidget {
  final Camera camera;

  const _PreviewCard({required this.camera});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      clipBehavior: Clip.antiAlias,
      decoration: BoxDecoration(
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.2),
        ),
      ),
      child: AspectRatio(
        aspectRatio: 16 / 9,
        child: Stack(
          fit: StackFit.expand,
          children: [
            // Live preview para cameras online com stream ativo
            ZyCameraLivePreview(
              hlsPath: camera.stream?.urls?.hls,
              mediamtxPath: camera.stream?.mediamtxPath,
              isOnline: camera.isOnline,
            ),
            // Badges
            Positioned(
              top: ZySpacing.sm,
              left: ZySpacing.sm,
              child: Row(
                children: [
                  if (camera.isOnline)
                    const ZyBadge(
                      label: 'LIVE',
                      variant: ZyBadgeVariant.success,
                    ),
                  if (camera.isRecording) ...[
                    const SizedBox(width: ZySpacing.xs),
                    const ZyBadge(
                      label: 'REC',
                      variant: ZyBadgeVariant.danger,
                      pulse: true,
                    ),
                  ],
                ],
              ),
            ),
            if (!camera.isOnline)
              Container(
                color: Colors.black45,
                child: const Center(
                  child: ZyBadge(
                    label: 'OFFLINE',
                    variant: ZyBadgeVariant.danger,
                    size: ZyBadgeSize.md,
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _LiveViewButton extends StatelessWidget {
  final Camera camera;

  const _LiveViewButton({required this.camera});

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: double.infinity,
      height: 48,
      child: FilledButton.icon(
        onPressed: () {
          final encodedName = Uri.encodeComponent(camera.name);
          context.go(
            '${RouteNames.liveViewPath(camera.id)}?name=$encodedName',
          );
        },
        icon: const Icon(Icons.play_arrow_rounded),
        label: const Text('Assistir ao Vivo'),
        style: FilledButton.styleFrom(
          backgroundColor: ZyColors.primary,
          shape: RoundedRectangleBorder(
            borderRadius: ZySpacing.borderRadiusLg,
          ),
        ),
      ),
    );
  }
}

class _StreamControls extends ConsumerWidget {
  final Camera camera;

  const _StreamControls({required this.camera});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);
    final isStreamActive =
        camera.stream?.status == 'active' || camera.isOnline;

    return Container(
      padding: const EdgeInsets.all(ZySpacing.base),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.2),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            'Controles do Stream',
            style: theme.textTheme.titleSmall,
          ),
          const SizedBox(height: ZySpacing.md),
          Row(
            children: [
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: isStreamActive
                      ? null
                      : () => ref
                          .read(cameraDetailProvider(camera.id).notifier)
                          .startStream(),
                  icon: const Icon(Icons.play_arrow_rounded, size: 20),
                  label: const Text('Iniciar'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: ZyColors.success,
                    side: BorderSide(
                      color: isStreamActive
                          ? theme.colorScheme.outline.withValues(alpha: 0.2)
                          : ZyColors.success,
                    ),
                  ),
                ),
              ),
              const SizedBox(width: ZySpacing.md),
              Expanded(
                child: OutlinedButton.icon(
                  onPressed: !isStreamActive
                      ? null
                      : () => ref
                          .read(cameraDetailProvider(camera.id).notifier)
                          .stopStream(),
                  icon: const Icon(Icons.stop_rounded, size: 20),
                  label: const Text('Parar'),
                  style: OutlinedButton.styleFrom(
                    foregroundColor: ZyColors.danger,
                    side: BorderSide(
                      color: !isStreamActive
                          ? theme.colorScheme.outline.withValues(alpha: 0.2)
                          : ZyColors.danger,
                    ),
                  ),
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _InfoSection extends StatelessWidget {
  final String title;
  final List<Widget> children;

  const _InfoSection({required this.title, required this.children});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.all(ZySpacing.base),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.2),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(title, style: theme.textTheme.titleSmall),
          const SizedBox(height: ZySpacing.md),
          ...children,
        ],
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  final Color? valueColor;

  const _InfoRow(this.label, this.value, {this.valueColor});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: ZySpacing.sm),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(
            label,
            style: theme.textTheme.bodyMedium?.copyWith(
              color: theme.colorScheme.outline,
            ),
          ),
          Flexible(
            child: Text(
              value,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w500,
                color: valueColor,
              ),
              textAlign: TextAlign.end,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

class _DetailSkeleton extends StatelessWidget {
  const _DetailSkeleton();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(ZySpacing.base),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const ZySkeleton.rect(height: 200, borderRadius: 12),
          const SizedBox(height: ZySpacing.base),
          const ZySkeleton.rect(height: 48, borderRadius: 12),
          const SizedBox(height: ZySpacing.base),
          const ZySkeleton.rect(height: 100, borderRadius: 12),
          const SizedBox(height: ZySpacing.base),
          const ZySkeleton.rect(height: 160, borderRadius: 12),
        ],
      ),
    );
  }
}

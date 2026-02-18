import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/camera_recording_summary.dart';
import '../../../state/recording/recording_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/feedback/zy_empty_state.dart';

class RecordingListScreen extends ConsumerWidget {
  const RecordingListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(recordingListProvider);
    final notifier = ref.read(recordingListProvider.notifier);
    final theme = Theme.of(context);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Gravacoes'),
      ),
      body: _buildBody(context, state, notifier, theme),
    );
  }

  Widget _buildBody(
    BuildContext context,
    dynamic state,
    dynamic notifier,
    ThemeData theme,
  ) {
    if (state.isLoading && state.cameras.isEmpty) {
      return const Center(child: CircularProgressIndicator());
    }

    if (state.hasError && state.cameras.isEmpty) {
      return ZyEmptyState(
        icon: Icons.error_outline,
        message: 'Erro ao carregar gravacoes',
        description: state.errorMessage,
        actionLabel: 'Tentar novamente',
        onAction: notifier.refresh,
      );
    }

    if (state.isEmpty) {
      return const ZyEmptyState(
        icon: Icons.videocam_off_outlined,
        message: 'Nenhuma gravacao encontrada',
        description: 'Quando suas cameras gravarem,\nelas aparecerao aqui',
      );
    }

    return RefreshIndicator(
      onRefresh: notifier.refresh,
      child: ListView(
        padding: const EdgeInsets.all(ZySpacing.base),
        children: [
          // Header stats
          _buildStatsHeader(context, state, theme),
          const SizedBox(height: ZySpacing.base),
          // Info text
          Padding(
            padding: const EdgeInsets.only(bottom: ZySpacing.md),
            child: Text(
              'Selecione uma camera para visualizar a timeline',
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
              ),
            ),
          ),
          // Camera cards
          ...state.cameras.map<Widget>((camera) => Padding(
                padding: const EdgeInsets.only(bottom: ZySpacing.sm),
                child: _CameraRecordingCard(
                  summary: camera,
                  onTap: () {
                    context.push(
                      RouteNames.recordingPlaybackPath(
                        camera.cameraId,
                        cameraName: camera.cameraName,
                      ),
                    );
                  },
                ),
              )),
          const SizedBox(height: ZySpacing.xxl),
        ],
      ),
    );
  }

  Widget _buildStatsHeader(
    BuildContext context,
    dynamic state,
    ThemeData theme,
  ) {
    return Container(
      padding: ZySpacing.cardInsets,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            ZyColors.primary.withValues(alpha: 0.08),
            ZyColors.primary.withValues(alpha: 0.02),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: ZyColors.primary.withValues(alpha: 0.15),
        ),
      ),
      child: Row(
        children: [
          _StatItem(
            icon: Icons.videocam_outlined,
            value: '${state.totalCameras}',
            label: 'Cameras',
            theme: theme,
          ),
          _divider(theme),
          _StatItem(
            icon: Icons.schedule_outlined,
            value: '${state.totalHours}h',
            label: 'Gravado',
            theme: theme,
          ),
          _divider(theme),
          _StatItem(
            icon: Icons.calendar_month_outlined,
            value: '${state.totalDays}',
            label: 'Dias',
            theme: theme,
          ),
        ],
      ),
    );
  }

  Widget _divider(ThemeData theme) {
    return Container(
      width: 1,
      height: 32,
      color: theme.colorScheme.outline.withValues(alpha: 0.15),
    );
  }
}

class _StatItem extends StatelessWidget {
  final IconData icon;
  final String value;
  final String label;
  final ThemeData theme;

  const _StatItem({
    required this.icon,
    required this.value,
    required this.label,
    required this.theme,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Icon(icon, size: 20, color: ZyColors.primary),
          const SizedBox(height: ZySpacing.xs),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          Text(
            label,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.outline,
            ),
          ),
        ],
      ),
    );
  }
}

class _CameraRecordingCard extends StatelessWidget {
  final CameraRecordingSummary summary;
  final VoidCallback onTap;

  const _CameraRecordingCard({
    required this.summary,
    required this.onTap,
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
            color: theme.colorScheme.outline.withValues(alpha: 0.15),
          ),
        ),
        child: Padding(
          padding: ZySpacing.cardInsets,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              // Camera name + status
              Row(
                children: [
                  Container(
                    width: 44,
                    height: 44,
                    decoration: BoxDecoration(
                      color: ZyColors.primary.withValues(alpha: 0.1),
                      borderRadius: ZySpacing.borderRadiusMd,
                    ),
                    child: Icon(
                      Icons.videocam_outlined,
                      color: ZyColors.primary,
                      size: 24,
                    ),
                  ),
                  const SizedBox(width: ZySpacing.md),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(
                          summary.cameraName,
                          style: theme.textTheme.titleSmall?.copyWith(
                            fontWeight: FontWeight.w600,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                        const SizedBox(height: 2),
                        Row(
                          children: [
                            Container(
                              width: 7,
                              height: 7,
                              decoration: BoxDecoration(
                                shape: BoxShape.circle,
                                color: summary.isOnline
                                    ? ZyColors.success
                                    : ZyColors.danger,
                              ),
                            ),
                            const SizedBox(width: 4),
                            Text(
                              summary.isOnline ? 'Online' : 'Offline',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: theme.colorScheme.outline,
                                fontSize: 11,
                              ),
                            ),
                            if (summary.isRecording) ...[
                              const SizedBox(width: ZySpacing.sm),
                              Container(
                                padding: const EdgeInsets.symmetric(
                                  horizontal: 6,
                                  vertical: 1,
                                ),
                                decoration: BoxDecoration(
                                  color: ZyColors.danger.withValues(alpha: 0.1),
                                  borderRadius: ZySpacing.borderRadiusFull,
                                ),
                                child: Text(
                                  'REC',
                                  style: TextStyle(
                                    color: ZyColors.danger,
                                    fontSize: 9,
                                    fontWeight: FontWeight.bold,
                                  ),
                                ),
                              ),
                            ],
                          ],
                        ),
                      ],
                    ),
                  ),
                  // Arrow
                  Icon(
                    Icons.chevron_right,
                    color: theme.colorScheme.outline.withValues(alpha: 0.5),
                  ),
                ],
              ),
              const SizedBox(height: ZySpacing.md),
              // Stats row
              Container(
                padding: const EdgeInsets.all(ZySpacing.sm),
                decoration: BoxDecoration(
                  color: theme.colorScheme.surfaceContainerHighest
                      .withValues(alpha: 0.3),
                  borderRadius: ZySpacing.borderRadiusMd,
                ),
                child: Row(
                  children: [
                    _miniStat(
                      Icons.calendar_month_outlined,
                      summary.dateRange,
                      theme,
                    ),
                    const SizedBox(width: ZySpacing.base),
                    _miniStat(
                      Icons.schedule_outlined,
                      summary.totalDurationHuman ?? '${summary.totalHours.toStringAsFixed(0)}h',
                      theme,
                    ),
                    const SizedBox(width: ZySpacing.base),
                    _miniStat(
                      Icons.storage_outlined,
                      summary.totalSizeHuman ?? '--',
                      theme,
                    ),
                    const SizedBox(width: ZySpacing.base),
                    _miniStat(
                      Icons.today_outlined,
                      '${summary.totalDays} dias',
                      theme,
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _miniStat(IconData icon, String value, ThemeData theme) {
    return Expanded(
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 13, color: theme.colorScheme.outline),
          const SizedBox(width: 3),
          Flexible(
            child: Text(
              value,
              style: theme.textTheme.bodySmall?.copyWith(
                color: theme.colorScheme.outline,
                fontSize: 11,
              ),
              maxLines: 1,
              overflow: TextOverflow.ellipsis,
            ),
          ),
        ],
      ),
    );
  }
}

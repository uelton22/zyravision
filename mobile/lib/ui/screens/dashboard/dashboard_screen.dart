import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/dashboard_data.dart';
import '../../../domain/entities/dashboard_stats.dart';
import '../../../state/auth/auth_providers.dart';
import '../../../state/dashboard/dashboard_providers.dart';
import '../../../state/settings/settings_providers.dart';
import '../../widgets/cards/zy_alert_card.dart';
import '../../widgets/cards/zy_camera_card.dart';
import '../../widgets/cards/zy_stat_card.dart';
import '../../widgets/feedback/zy_empty_state.dart';
import '../../widgets/feedback/zy_skeleton.dart';
import '../../widgets/navigation/zy_app_bar.dart';

class DashboardScreen extends ConsumerWidget {
  const DashboardScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final dashboardAsync = ref.watch(dashboardProvider);

    return Scaffold(
      appBar: ZyAppBar(
        title: 'ZyraVision',
        actions: [
          IconButton(
            icon: Icon(
              ref.watch(themeNotifierProvider) == ThemeMode.dark
                  ? Icons.light_mode
                  : Icons.dark_mode,
            ),
            onPressed: () {
              ref.read(themeNotifierProvider.notifier).toggle();
            },
          ),
          IconButton(
            icon: const Icon(Icons.logout),
            onPressed: () {
              ref.read(authNotifierProvider.notifier).logout();
            },
          ),
        ],
      ),
      body: dashboardAsync.when(
        data: (data) => _DashboardContent(data: data),
        loading: () => const _DashboardSkeleton(),
        error: (error, _) => ZyEmptyState(
          icon: Icons.error_outline,
          message: 'Erro ao carregar dashboard',
          description: error.toString(),
          actionLabel: 'Tentar novamente',
          onAction: () => ref.read(dashboardProvider.notifier).refresh(),
        ),
      ),
    );
  }
}

class _DashboardContent extends ConsumerWidget {
  final DashboardData data;

  const _DashboardContent({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);

    return RefreshIndicator(
      onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
      child: ListView(
        padding: const EdgeInsets.all(ZySpacing.base),
        children: [
          // Stats grid 2x2
          _StatsGrid(stats: data.stats),
          const SizedBox(height: ZySpacing.base),

          // Storage bar
          _StorageBar(stats: data.stats),
          const SizedBox(height: ZySpacing.xl),

          // Cameras section
          if (data.cameras.isNotEmpty) ...[
            Text(
              'Cameras',
              style: theme.textTheme.headlineMedium,
            ),
            const SizedBox(height: ZySpacing.md),
            SizedBox(
              height: 185,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: data.cameras.length,
                separatorBuilder: (_, __) =>
                    const SizedBox(width: ZySpacing.md),
                itemBuilder: (context, index) {
                  return SizedBox(
                    width: 160,
                    child: ZyCameraCard.compact(
                      camera: data.cameras[index],
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Alerts section
          if (data.recentEvents.isNotEmpty) ...[
            Text(
              'Alertas Recentes',
              style: theme.textTheme.headlineMedium,
            ),
            const SizedBox(height: ZySpacing.md),
            ...data.recentEvents.map(
              (event) => Padding(
                padding: const EdgeInsets.only(bottom: ZySpacing.sm),
                child: ZyAlertCard(event: event),
              ),
            ),
          ],

          // Empty state if no cameras
          if (data.cameras.isEmpty && data.recentEvents.isEmpty)
            const Padding(
              padding: EdgeInsets.only(top: ZySpacing.xxxl),
              child: ZyEmptyState(
                icon: Icons.videocam_outlined,
                message: 'Nenhuma camera cadastrada',
                description:
                    'Adicione cameras pelo painel web para comecar a monitorar.',
              ),
            ),
        ],
      ),
    );
  }
}

class _StatsGrid extends StatelessWidget {
  final DashboardStats stats;

  const _StatsGrid({required this.stats});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: ZySpacing.md,
      mainAxisSpacing: ZySpacing.md,
      childAspectRatio: 1.5,
      children: [
        ZyStatCard(
          label: 'Total Cameras',
          value: '${stats.totalCameras}',
          icon: Icons.videocam,
          iconColor: ZyColors.primary,
        ),
        ZyStatCard(
          label: 'Online',
          value: '${stats.onlineCameras}',
          subtitle: 'de ${stats.totalCameras}',
          icon: Icons.check_circle,
          iconColor: ZyColors.success,
        ),
        ZyStatCard(
          label: 'Offline',
          value: '${stats.offlineCameras}',
          icon: Icons.error,
          iconColor: ZyColors.danger,
        ),
        ZyStatCard(
          label: 'Gravando',
          value: '${stats.recordingCameras}',
          icon: Icons.fiber_manual_record,
          iconColor: ZyColors.danger,
        ),
      ],
    );
  }
}

class _StorageBar extends StatelessWidget {
  final DashboardStats stats;

  const _StorageBar({required this.stats});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final percentage = stats.storagePercentage;
    final color = percentage > 90
        ? ZyColors.danger
        : percentage > 75
            ? ZyColors.warning
            : ZyColors.primary;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          mainAxisAlignment: MainAxisAlignment.spaceBetween,
          children: [
            Text(
              'Armazenamento',
              style: theme.textTheme.labelLarge,
            ),
            Text(
              '${stats.storageUsedGb.toStringAsFixed(1)} GB',
              style: theme.textTheme.labelLarge?.copyWith(
                color: color,
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: ZySpacing.sm),
        ClipRRect(
          borderRadius: ZySpacing.borderRadiusFull,
          child: LinearProgressIndicator(
            value: stats.storageFraction,
            backgroundColor:
                theme.colorScheme.outline.withValues(alpha: 0.15),
            color: color,
            minHeight: 8,
          ),
        ),
        const SizedBox(height: ZySpacing.xs),
        Text(
          '${percentage.toStringAsFixed(0)}% utilizado',
          style: theme.textTheme.bodySmall?.copyWith(
            color: theme.colorScheme.outline,
          ),
        ),
      ],
    );
  }
}

class _DashboardSkeleton extends StatelessWidget {
  const _DashboardSkeleton();

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.all(ZySpacing.base),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          // Stats skeleton
          GridView.count(
            crossAxisCount: 2,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: ZySpacing.md,
            mainAxisSpacing: ZySpacing.md,
            childAspectRatio: 1.5,
            children: List.generate(
              4,
              (_) => const ZySkeleton.rect(height: 100, borderRadius: 12),
            ),
          ),
          const SizedBox(height: ZySpacing.xl),
          const ZySkeleton.rect(width: 120, height: 20),
          const SizedBox(height: ZySpacing.md),
          const ZySkeleton.rect(height: 8, borderRadius: 99),
          const SizedBox(height: ZySpacing.xl),
          const ZySkeleton.rect(width: 100, height: 20),
          const SizedBox(height: ZySpacing.md),
          Row(
            children: List.generate(
              3,
              (_) => const Padding(
                padding: EdgeInsets.only(right: ZySpacing.md),
                child: ZySkeleton.rect(width: 160, height: 160, borderRadius: 12),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

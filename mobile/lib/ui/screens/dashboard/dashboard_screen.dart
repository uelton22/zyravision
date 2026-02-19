import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/dashboard_data.dart';
import '../../../domain/entities/dashboard_stats.dart';
import '../../../domain/entities/plan_info.dart';
import '../../../domain/entities/resource_quota.dart';
import '../../../domain/entities/system_health.dart';
import '../../../state/auth/auth_providers.dart';
import '../../../state/dashboard/dashboard_providers.dart';
import '../../../state/settings/settings_providers.dart';
import '../../router/route_names.dart';
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
        data: (data) => data.isSuperAdmin
            ? _AdminDashboard(data: data)
            : _TenantDashboard(data: data),
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

// ─── ADMIN DASHBOARD ────────────────────────────────────

class _AdminDashboard extends ConsumerWidget {
  final DashboardData data;

  const _AdminDashboard({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);

    return RefreshIndicator(
      onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
      child: ListView(
        padding: const EdgeInsets.all(ZySpacing.base),
        children: [
          // Role badge
          _RoleBadge(label: 'Super Admin', color: ZyColors.warning),
          const SizedBox(height: ZySpacing.md),

          // Platform stats grid (3 cols)
          _SectionTitle(title: 'Visao Geral da Plataforma', theme: theme),
          const SizedBox(height: ZySpacing.sm),
          _AdminStatsGrid(stats: data.stats),
          const SizedBox(height: ZySpacing.xl),

          // System Health
          if (data.hasSystemHealth) ...[
            _SectionTitle(title: 'Saude do Sistema', theme: theme),
            const SizedBox(height: ZySpacing.sm),
            _SystemHealthCard(health: data.systemHealth!),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Workers
          if (data.hasWorkers) ...[
            _SectionTitle(title: 'Workers do Sistema', theme: theme),
            const SizedBox(height: ZySpacing.sm),
            _WorkersCard(workers: data.workers!),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Cameras section
          if (data.cameras.isNotEmpty) ...[
            _SectionTitle(
              title: 'Cameras Ativas',
              subtitle: '${data.cameras.length} camera(s)',
              theme: theme,
            ),
            const SizedBox(height: ZySpacing.md),
            SizedBox(
              height: 145,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: data.cameras.length,
                separatorBuilder: (_, __) =>
                    const SizedBox(width: ZySpacing.md),
                itemBuilder: (context, index) {
                  final cam = data.cameras[index];
                  return SizedBox(
                    width: 160,
                    child: ZyCameraCard.compact(
                      camera: cam,
                      onTap: () =>
                          context.go(RouteNames.cameraDetailPath(cam.id)),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Recent events
          if (data.recentEvents.isNotEmpty) ...[
            _SectionTitle(title: 'Atividades Recentes', theme: theme),
            const SizedBox(height: ZySpacing.md),
            ...data.recentEvents.take(10).map(
                  (event) => Padding(
                    padding: const EdgeInsets.only(bottom: ZySpacing.sm),
                    child: ZyAlertCard(event: event),
                  ),
                ),
          ],

          const SizedBox(height: ZySpacing.xxl),
        ],
      ),
    );
  }
}

// ─── TENANT DASHBOARD ───────────────────────────────────

class _TenantDashboard extends ConsumerWidget {
  final DashboardData data;

  const _TenantDashboard({required this.data});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final theme = Theme.of(context);

    return RefreshIndicator(
      onRefresh: () => ref.read(dashboardProvider.notifier).refresh(),
      child: ListView(
        padding: const EdgeInsets.all(ZySpacing.base),
        children: [
          // Camera stats
          _TenantStatsRow(stats: data.stats),
          const SizedBox(height: ZySpacing.xl),

          // Cameras ao vivo
          if (data.cameras.isNotEmpty) ...[
            _SectionTitle(
              title: 'Cameras ao Vivo',
              subtitle: '${data.cameras.length} camera(s)',
              theme: theme,
            ),
            const SizedBox(height: ZySpacing.md),
            SizedBox(
              height: 145,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: data.cameras.length,
                separatorBuilder: (_, __) =>
                    const SizedBox(width: ZySpacing.md),
                itemBuilder: (context, index) {
                  final cam = data.cameras[index];
                  return SizedBox(
                    width: 160,
                    child: ZyCameraCard.compact(
                      camera: cam,
                      onTap: () =>
                          context.go(RouteNames.cameraDetailPath(cam.id)),
                    ),
                  );
                },
              ),
            ),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Resource quotas
          if (data.hasQuotas) ...[
            _SectionTitle(title: 'Consumo de Recursos', theme: theme),
            const SizedBox(height: ZySpacing.sm),
            _QuotasCard(quotas: data.quotas!),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Plan info
          if (data.hasPlan) ...[
            _SectionTitle(title: 'Assinatura', theme: theme),
            const SizedBox(height: ZySpacing.sm),
            _PlanCard(plan: data.plan!),
            const SizedBox(height: ZySpacing.xl),
          ],

          // Recent events
          if (data.recentEvents.isNotEmpty) ...[
            _SectionTitle(title: 'Alertas Recentes', theme: theme),
            const SizedBox(height: ZySpacing.md),
            ...data.recentEvents.take(10).map(
                  (event) => Padding(
                    padding: const EdgeInsets.only(bottom: ZySpacing.sm),
                    child: ZyAlertCard(event: event),
                  ),
                ),
          ],

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

          const SizedBox(height: ZySpacing.xxl),
        ],
      ),
    );
  }
}

// ─── SHARED WIDGETS ─────────────────────────────────────

class _RoleBadge extends StatelessWidget {
  final String label;
  final Color color;

  const _RoleBadge({required this.label, required this.color});

  @override
  Widget build(BuildContext context) {
    return Align(
      alignment: Alignment.centerRight,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 4),
        decoration: BoxDecoration(
          color: color.withValues(alpha: 0.15),
          borderRadius: ZySpacing.borderRadiusFull,
          border: Border.all(color: color.withValues(alpha: 0.3)),
        ),
        child: Text(
          label,
          style: TextStyle(
            color: color,
            fontSize: 12,
            fontWeight: FontWeight.bold,
          ),
        ),
      ),
    );
  }
}

class _SectionTitle extends StatelessWidget {
  final String title;
  final String? subtitle;
  final ThemeData theme;

  const _SectionTitle({
    required this.title,
    this.subtitle,
    required this.theme,
  });

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Text(title, style: theme.textTheme.titleMedium?.copyWith(
          fontWeight: FontWeight.bold,
        )),
        if (subtitle != null) ...[
          const Spacer(),
          Text(
            subtitle!,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.outline,
            ),
          ),
        ],
      ],
    );
  }
}

// ─── TENANT STATS ROW ───────────────────────────────────

class _TenantStatsRow extends StatelessWidget {
  final DashboardStats stats;

  const _TenantStatsRow({required this.stats});

  @override
  Widget build(BuildContext context) {
    return Row(
      children: [
        Expanded(
          child: _MiniStatCard(
            icon: Icons.wifi,
            iconColor: ZyColors.success,
            value: '${stats.onlineCameras}',
            label: 'Online',
          ),
        ),
        const SizedBox(width: ZySpacing.sm),
        Expanded(
          child: _MiniStatCard(
            icon: Icons.wifi_off,
            iconColor: ZyColors.danger,
            value: '${stats.offlineCameras}',
            label: 'Offline',
          ),
        ),
        const SizedBox(width: ZySpacing.sm),
        Expanded(
          child: _MiniStatCard(
            icon: Icons.fiber_manual_record,
            iconColor: ZyColors.danger,
            value: '${stats.recordingCameras}',
            label: 'Gravando',
          ),
        ),
      ],
    );
  }
}

class _MiniStatCard extends StatelessWidget {
  final IconData icon;
  final Color iconColor;
  final String value;
  final String label;

  const _MiniStatCard({
    required this.icon,
    required this.iconColor,
    required this.value,
    required this.label,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.md,
        vertical: ZySpacing.md,
      ),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        children: [
          Icon(icon, color: iconColor, size: 28),
          const SizedBox(height: ZySpacing.xs),
          Text(
            value,
            style: theme.textTheme.headlineMedium?.copyWith(
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

// ─── ADMIN STATS GRID ───────────────────────────────────

class _AdminStatsGrid extends StatelessWidget {
  final DashboardStats stats;

  const _AdminStatsGrid({required this.stats});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 3,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisSpacing: ZySpacing.sm,
      mainAxisSpacing: ZySpacing.sm,
      childAspectRatio: 1.0,
      children: [
        ZyStatCard(
          label: 'Online',
          value: '${stats.onlineCameras}',
          icon: Icons.wifi,
          iconColor: ZyColors.success,
        ),
        ZyStatCard(
          label: 'Offline',
          value: '${stats.offlineCameras}',
          icon: Icons.wifi_off,
          iconColor: ZyColors.danger,
        ),
        ZyStatCard(
          label: 'Total Cameras',
          value: '${stats.totalCameras}',
          icon: Icons.videocam,
          iconColor: ZyColors.primary,
        ),
        ZyStatCard(
          label: 'Dispositivos',
          value: '${stats.totalDevices}',
          icon: Icons.devices,
          iconColor: ZyColors.info,
        ),
        ZyStatCard(
          label: 'Empresas',
          value: '${stats.totalTenants}',
          icon: Icons.business,
          iconColor: ZyColors.warning,
        ),
        ZyStatCard(
          label: 'Usuarios',
          value: '${stats.totalUsers}',
          icon: Icons.people,
          iconColor: ZyColors.primary,
        ),
      ],
    );
  }
}

// ─── SYSTEM HEALTH CARD ─────────────────────────────────

class _SystemHealthCard extends StatelessWidget {
  final SystemHealth health;

  const _SystemHealthCard({required this.health});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: ZySpacing.cardInsets,
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        children: [
          Row(
            children: [
              Expanded(
                child: _HealthMetric(
                  icon: Icons.memory,
                  label: 'CPU Load',
                  value: health.cpuLoadDisplay,
                  subtitle: '5m: ${health.cpuLoad.length > 1 ? health.cpuLoad[1].toStringAsFixed(2) : "0"} / '
                      '15m: ${health.cpuLoad.length > 2 ? health.cpuLoad[2].toStringAsFixed(2) : "0"}',
                  valueColor: _cpuColor(health.cpuLoad.isNotEmpty ? health.cpuLoad.first : 0),
                  theme: theme,
                ),
              ),
              Expanded(
                child: _HealthMetric(
                  icon: Icons.storage,
                  label: 'Memoria (PHP)',
                  value: '${health.memoryUsageMb.toStringAsFixed(0)} MB',
                  subtitle: 'Pico: ${health.memoryPeakMb.toStringAsFixed(0)} MB',
                  theme: theme,
                ),
              ),
            ],
          ),
          const SizedBox(height: ZySpacing.md),
          Row(
            children: [
              Expanded(
                child: _HealthMetric(
                  icon: Icons.disc_full,
                  label: 'Disco',
                  value: '${health.diskUsedPercent.toStringAsFixed(0)}%',
                  subtitle:
                      '${health.diskFreeGb.toStringAsFixed(1)} GB livre de ${health.diskTotalGb.toStringAsFixed(1)} GB',
                  valueColor: _diskColor(health.diskUsedPercent),
                  theme: theme,
                  showBar: true,
                  barValue: health.diskUsedPercent / 100,
                ),
              ),
              Expanded(
                child: _HealthMetric(
                  icon: Icons.timer,
                  label: 'Uptime',
                  value: health.uptime,
                  subtitle:
                      'PHP ${health.phpVersion} / Laravel ${health.laravelVersion}',
                  theme: theme,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Color _cpuColor(double load) {
    if (load > 4) return ZyColors.danger;
    if (load > 2) return ZyColors.warning;
    return ZyColors.success;
  }

  Color _diskColor(double percent) {
    if (percent > 90) return ZyColors.danger;
    if (percent > 75) return ZyColors.warning;
    return ZyColors.success;
  }
}

class _HealthMetric extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final String? subtitle;
  final Color? valueColor;
  final ThemeData theme;
  final bool showBar;
  final double barValue;

  const _HealthMetric({
    required this.icon,
    required this.label,
    required this.value,
    this.subtitle,
    this.valueColor,
    required this.theme,
    this.showBar = false,
    this.barValue = 0,
  });

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: ZySpacing.xs),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Icon(icon, size: 14, color: theme.colorScheme.outline),
              const SizedBox(width: 4),
              Text(
                label,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.outline,
                ),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            value,
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
              color: valueColor,
            ),
          ),
          if (showBar) ...[
            const SizedBox(height: 4),
            ClipRRect(
              borderRadius: ZySpacing.borderRadiusFull,
              child: LinearProgressIndicator(
                value: barValue.clamp(0, 1),
                backgroundColor:
                    theme.colorScheme.outline.withValues(alpha: 0.1),
                color: valueColor ?? ZyColors.primary,
                minHeight: 4,
              ),
            ),
          ],
          if (subtitle != null) ...[
            const SizedBox(height: 2),
            Text(
              subtitle!,
              style: theme.textTheme.bodySmall?.copyWith(
                fontSize: 10,
                color: theme.colorScheme.outline,
              ),
              maxLines: 2,
              overflow: TextOverflow.ellipsis,
            ),
          ],
        ],
      ),
    );
  }
}

// ─── WORKERS CARD ───────────────────────────────────────

class _WorkersCard extends StatelessWidget {
  final WorkersInfo workers;

  const _WorkersCard({required this.workers});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final statusColor =
        workers.isHealthy ? ZyColors.success : ZyColors.warning;

    return Container(
      padding: ZySpacing.cardInsets,
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        children: [
          // Status header
          Row(
            children: [
              Icon(Icons.settings_suggest, size: 18, color: ZyColors.primary),
              const SizedBox(width: ZySpacing.sm),
              Text(
                'Workers do Sistema',
                style: theme.textTheme.titleSmall?.copyWith(
                  fontWeight: FontWeight.w600,
                ),
              ),
              const Spacer(),
              Container(
                width: 8,
                height: 8,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  color: statusColor,
                ),
              ),
              const SizedBox(width: 4),
              Text(
                workers.statusLabel,
                style: theme.textTheme.bodySmall?.copyWith(
                  color: statusColor,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
          const SizedBox(height: ZySpacing.md),
          // Metrics
          Row(
            children: [
              Expanded(
                child: _WorkerMetric(
                  icon: Icons.ondemand_video,
                  label: 'Transcoders',
                  value: '${workers.transcoderHealthy}/${workers.transcoderCount}',
                  subtitle: '${workers.transcoderCapacityPercent}% cap.',
                  theme: theme,
                ),
              ),
              Expanded(
                child: _WorkerMetric(
                  icon: Icons.queue,
                  label: 'Queue',
                  value: '${workers.queueProcesses} proc.',
                  subtitle: workers.queueStatus == 'running'
                      ? 'Ativo'
                      : workers.queueStatus,
                  statusColor: workers.queueStatus == 'running'
                      ? ZyColors.success
                      : ZyColors.danger,
                  theme: theme,
                ),
              ),
              Expanded(
                child: _WorkerMetric(
                  icon: Icons.pending_actions,
                  label: 'Jobs Pend.',
                  value: '${workers.queueJobsPending}',
                  subtitle:
                      '${workers.queueRecentFailed} falhas',
                  statusColor: workers.queueRecentFailed > 0
                      ? ZyColors.warning
                      : null,
                  theme: theme,
                ),
              ),
              Expanded(
                child: _WorkerMetric(
                  icon: Icons.warning_amber,
                  label: 'Alertas',
                  value: '${workers.alertsCount}',
                  subtitle: workers.alertsCount == 0
                      ? 'Tudo normal'
                      : 'Verificar',
                  statusColor: workers.alertsCount > 0
                      ? ZyColors.danger
                      : ZyColors.success,
                  theme: theme,
                ),
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _WorkerMetric extends StatelessWidget {
  final IconData icon;
  final String label;
  final String value;
  final String? subtitle;
  final Color? statusColor;
  final ThemeData theme;

  const _WorkerMetric({
    required this.icon,
    required this.label,
    required this.value,
    this.subtitle,
    this.statusColor,
    required this.theme,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Icon(icon, size: 18, color: theme.colorScheme.outline),
        const SizedBox(height: 4),
        Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            fontSize: 10,
            color: theme.colorScheme.outline,
          ),
        ),
        const SizedBox(height: 2),
        Text(
          value,
          style: theme.textTheme.titleSmall?.copyWith(
            fontWeight: FontWeight.bold,
          ),
        ),
        if (subtitle != null)
          Text(
            subtitle!,
            style: theme.textTheme.bodySmall?.copyWith(
              fontSize: 10,
              color: statusColor ?? theme.colorScheme.outline,
            ),
          ),
      ],
    );
  }
}

// ─── QUOTAS CARD ────────────────────────────────────────

class _QuotasCard extends StatelessWidget {
  final DashboardQuotas quotas;

  const _QuotasCard({required this.quotas});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: ZySpacing.cardInsets,
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        children: [
          _QuotaRow(
            icon: Icons.videocam_outlined,
            label: 'Cameras',
            used: quotas.cameras.used,
            limit: quotas.cameras.limit,
            fraction: quotas.cameras.fraction,
            color: _quotaColor(quotas.cameras.percentage),
            theme: theme,
          ),
          const SizedBox(height: ZySpacing.md),
          _QuotaRow(
            icon: Icons.people_outlined,
            label: 'Usuarios',
            used: quotas.users.used,
            limit: quotas.users.limit,
            fraction: quotas.users.fraction,
            color: _quotaColor(quotas.users.percentage),
            theme: theme,
          ),
          const SizedBox(height: ZySpacing.md),
          _QuotaRow(
            icon: Icons.storage_outlined,
            label: 'Armazenamento',
            displayValue:
                '${quotas.storage.usedGb.toStringAsFixed(1)} / ${quotas.storage.limitGb.toStringAsFixed(0)}',
            displayUnit: 'GB',
            fraction: quotas.storage.fraction,
            color: _quotaColor(quotas.storage.percentage),
            theme: theme,
          ),
          const SizedBox(height: ZySpacing.md),
          _QuotaRow(
            icon: Icons.stream,
            label: 'Streams',
            used: quotas.streams.used,
            limit: quotas.streams.limit,
            fraction: quotas.streams.fraction,
            color: _quotaColor(quotas.streams.percentage),
            theme: theme,
          ),
        ],
      ),
    );
  }

  Color _quotaColor(double percentage) {
    if (percentage > 90) return ZyColors.danger;
    if (percentage > 75) return ZyColors.warning;
    return ZyColors.primary;
  }
}

class _QuotaRow extends StatelessWidget {
  final IconData icon;
  final String label;
  final int? used;
  final int? limit;
  final String? displayValue;
  final String? displayUnit;
  final double fraction;
  final Color color;
  final ThemeData theme;

  const _QuotaRow({
    required this.icon,
    required this.label,
    this.used,
    this.limit,
    this.displayValue,
    this.displayUnit,
    required this.fraction,
    required this.color,
    required this.theme,
  });

  @override
  Widget build(BuildContext context) {
    final valueText = displayValue ?? '$used / $limit';

    return Column(
      children: [
        Row(
          children: [
            Icon(icon, size: 16, color: theme.colorScheme.outline),
            const SizedBox(width: ZySpacing.sm),
            Text(
              label,
              style: theme.textTheme.bodyMedium,
            ),
            const Spacer(),
            Text(
              displayUnit != null ? '$valueText $displayUnit' : valueText,
              style: theme.textTheme.bodyMedium?.copyWith(
                fontWeight: FontWeight.w600,
              ),
            ),
          ],
        ),
        const SizedBox(height: 4),
        ClipRRect(
          borderRadius: ZySpacing.borderRadiusFull,
          child: LinearProgressIndicator(
            value: fraction.clamp(0, 1),
            backgroundColor:
                theme.colorScheme.outline.withValues(alpha: 0.1),
            color: color,
            minHeight: 6,
          ),
        ),
      ],
    );
  }
}

// ─── PLAN CARD ──────────────────────────────────────────

class _PlanCard extends StatelessWidget {
  final PlanInfo plan;

  const _PlanCard({required this.plan});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: ZySpacing.cardInsets,
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        borderRadius: ZySpacing.borderRadiusLg,
        border: Border.all(
          color: theme.colorScheme.outline.withValues(alpha: 0.12),
        ),
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      plan.name,
                      style: theme.textTheme.titleLarge?.copyWith(
                        fontWeight: FontWeight.bold,
                      ),
                    ),
                    const SizedBox(height: 2),
                    Container(
                      padding: const EdgeInsets.symmetric(
                          horizontal: 8, vertical: 2),
                      decoration: BoxDecoration(
                        color: ZyColors.success.withValues(alpha: 0.1),
                        borderRadius: ZySpacing.borderRadiusFull,
                      ),
                      child: Text(
                        'Ativo',
                        style: TextStyle(
                          color: ZyColors.success,
                          fontSize: 11,
                          fontWeight: FontWeight.w600,
                        ),
                      ),
                    ),
                  ],
                ),
              ),
              Column(
                crossAxisAlignment: CrossAxisAlignment.end,
                children: [
                  Text(
                    plan.priceFormatted,
                    style: theme.textTheme.headlineSmall?.copyWith(
                      fontWeight: FontWeight.bold,
                    ),
                  ),
                  Text(
                    plan.cycleLabel,
                    style: theme.textTheme.bodySmall?.copyWith(
                      color: theme.colorScheme.outline,
                    ),
                  ),
                ],
              ),
            ],
          ),
          const SizedBox(height: ZySpacing.md),
          Divider(
              color: theme.colorScheme.outline.withValues(alpha: 0.1)),
          const SizedBox(height: ZySpacing.sm),
          Text(
            'Recursos do plano:',
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.colorScheme.outline,
            ),
          ),
          const SizedBox(height: ZySpacing.sm),
          Wrap(
            spacing: ZySpacing.base,
            runSpacing: ZySpacing.sm,
            children: [
              _PlanFeature(
                label: 'Cameras: ${plan.maxCameras}',
                theme: theme,
              ),
              _PlanFeature(
                label: 'Usuarios: ${plan.maxUsers}',
                theme: theme,
              ),
              _PlanFeature(
                label: 'Storage: ${plan.maxStorageGb}GB',
                theme: theme,
              ),
              _PlanFeature(
                label: 'Retencao: ${plan.retentionDays}d',
                theme: theme,
              ),
              if (plan.maxResolution != null)
                _PlanFeature(
                  label: 'Resolucao: ${plan.maxResolution}',
                  theme: theme,
                ),
              _PlanFeature(
                label: 'FPS: ${plan.maxFps}',
                theme: theme,
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _PlanFeature extends StatelessWidget {
  final String label;
  final ThemeData theme;

  const _PlanFeature({required this.label, required this.theme});

  @override
  Widget build(BuildContext context) {
    return Text(
      label,
      style: theme.textTheme.bodySmall?.copyWith(
        color: theme.colorScheme.onSurface,
      ),
    );
  }
}

// ─── SKELETON ───────────────────────────────────────────

class _DashboardSkeleton extends StatelessWidget {
  const _DashboardSkeleton();

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(ZySpacing.base),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          GridView.count(
            crossAxisCount: 3,
            shrinkWrap: true,
            physics: const NeverScrollableScrollPhysics(),
            crossAxisSpacing: ZySpacing.sm,
            mainAxisSpacing: ZySpacing.sm,
            childAspectRatio: 1.0,
            children: List.generate(
              6,
              (_) => const ZySkeleton.rect(height: 100, borderRadius: 12),
            ),
          ),
          const SizedBox(height: ZySpacing.xl),
          const ZySkeleton.rect(height: 120, borderRadius: 12),
          const SizedBox(height: ZySpacing.xl),
          const ZySkeleton.rect(height: 80, borderRadius: 12),
          const SizedBox(height: ZySpacing.xl),
          const ZySkeleton.rect(width: 100, height: 20),
          const SizedBox(height: ZySpacing.md),
          SizedBox(
            height: 160,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: List.generate(
                3,
                (_) => const Padding(
                  padding: EdgeInsets.only(right: ZySpacing.md),
                  child: ZySkeleton.rect(
                      width: 160, height: 160, borderRadius: 12),
                ),
              ),
            ),
          ),
        ],
      ),
    );
  }
}

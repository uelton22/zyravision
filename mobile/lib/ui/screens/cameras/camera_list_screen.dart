import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/camera.dart';
import '../../../state/camera/camera_list_state.dart';
import '../../../state/camera/camera_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/camera/zy_camera_thumbnail.dart';
import '../../widgets/feedback/zy_badge.dart';
import '../../widgets/feedback/zy_empty_state.dart';
import '../../widgets/feedback/zy_skeleton.dart';
import '../../widgets/inputs/zy_search_bar.dart';

class CameraListScreen extends ConsumerWidget {
  const CameraListScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final state = ref.watch(cameraListProvider);

    return Scaffold(
      appBar: AppBar(
        title: const Text('Cameras'),
        actions: [
          IconButton(
            icon: const Icon(Icons.view_comfy_rounded),
            tooltip: 'Multi-view',
            onPressed: () => _openMultiView(context, state),
          ),
        ],
      ),
      body: Column(
        children: [
          _SearchAndFilters(state: state, ref: ref),
          Expanded(
            child: state.isLoading
                ? _buildSkeleton()
                : state.hasError && state.cameras.isEmpty
                    ? _buildError(context, ref, state)
                    : state.isEmpty
                        ? _buildEmpty(ref, state)
                        : _buildCameraList(context, ref, state),
          ),
        ],
      ),
    );
  }

  void _openMultiView(BuildContext context, CameraListState state) {
    final onlineCameras =
        state.cameras.where((c) => c.isOnline).take(4).toList();
    if (onlineCameras.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('Nenhuma camera online para multi-view')),
      );
      return;
    }
    final ids = onlineCameras.map((c) => c.id).join(',');
    context.go('${RouteNames.cameras}/multi-view?ids=$ids');
  }

  Widget _buildSkeleton() {
    return ListView.builder(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.base,
        vertical: ZySpacing.sm,
      ),
      itemCount: 6,
      itemBuilder: (_, __) => const Padding(
        padding: EdgeInsets.only(bottom: ZySpacing.sm),
        child: ZySkeleton.rect(height: 80, borderRadius: 12),
      ),
    );
  }

  Widget _buildError(
      BuildContext context, WidgetRef ref, CameraListState state) {
    return ZyEmptyState(
      icon: Icons.error_outline,
      message: 'Erro ao carregar cameras',
      description: state.errorMessage,
      actionLabel: 'Tentar novamente',
      onAction: () => ref.read(cameraListProvider.notifier).refresh(),
    );
  }

  Widget _buildEmpty(WidgetRef ref, CameraListState state) {
    if (state.hasFilters) {
      return ZyEmptyState(
        icon: Icons.search_off,
        message: 'Nenhuma camera encontrada',
        description: 'Tente ajustar os filtros de busca.',
        actionLabel: 'Limpar filtros',
        onAction: () => ref.read(cameraListProvider.notifier).clearFilters(),
      );
    }
    return const ZyEmptyState(
      icon: Icons.videocam_off_outlined,
      message: 'Nenhuma camera cadastrada',
      description: 'Adicione cameras pelo painel web para comecar a monitorar.',
    );
  }

  Widget _buildCameraList(
      BuildContext context, WidgetRef ref, CameraListState state) {
    return RefreshIndicator(
      onRefresh: () => ref.read(cameraListProvider.notifier).refresh(),
      child: NotificationListener<ScrollNotification>(
        onNotification: (notification) {
          if (notification is ScrollEndNotification &&
              notification.metrics.pixels >=
                  notification.metrics.maxScrollExtent - 200) {
            ref.read(cameraListProvider.notifier).loadMore();
          }
          return false;
        },
        child: ListView.builder(
          padding: const EdgeInsets.symmetric(
            horizontal: ZySpacing.base,
            vertical: ZySpacing.sm,
          ),
          itemCount:
              state.cameras.length + (state.isLoadingMore ? 1 : 0),
          itemBuilder: (context, index) {
            if (index >= state.cameras.length) {
              return const Padding(
                padding: EdgeInsets.only(bottom: ZySpacing.sm),
                child: ZySkeleton.rect(height: 80, borderRadius: 12),
              );
            }
            return _CameraListTile(camera: state.cameras[index]);
          },
        ),
      ),
    );
  }
}

// ─── SEARCH & FILTERS ───────────────────────────────────

class _SearchAndFilters extends StatelessWidget {
  final CameraListState state;
  final WidgetRef ref;

  const _SearchAndFilters({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(
        ZySpacing.base,
        ZySpacing.sm,
        ZySpacing.base,
        0,
      ),
      child: Column(
        children: [
          ZySearchBar(
            hint: 'Buscar cameras...',
            onChanged: (q) =>
                ref.read(cameraListProvider.notifier).setSearch(q),
          ),
          const SizedBox(height: ZySpacing.sm),
          SizedBox(
            height: 36,
            child: ListView(
              scrollDirection: Axis.horizontal,
              children: [
                _FilterChip(
                  label: 'Todas',
                  selected: state.statusFilter == null,
                  onTap: () => ref
                      .read(cameraListProvider.notifier)
                      .setStatusFilter(null),
                ),
                const SizedBox(width: ZySpacing.sm),
                _FilterChip(
                  label: 'Online',
                  selected: state.statusFilter == 'online',
                  color: ZyColors.success,
                  onTap: () => ref
                      .read(cameraListProvider.notifier)
                      .setStatusFilter('online'),
                ),
                const SizedBox(width: ZySpacing.sm),
                _FilterChip(
                  label: 'Offline',
                  selected: state.statusFilter == 'offline',
                  color: ZyColors.danger,
                  onTap: () => ref
                      .read(cameraListProvider.notifier)
                      .setStatusFilter('offline'),
                ),
                const SizedBox(width: ZySpacing.sm),
                _FilterChip(
                  label: 'Erro',
                  selected: state.statusFilter == 'error',
                  color: ZyColors.warning,
                  onTap: () => ref
                      .read(cameraListProvider.notifier)
                      .setStatusFilter('error'),
                ),
              ],
            ),
          ),
          const SizedBox(height: ZySpacing.sm),
          Row(
            children: [
              Text(
                '${state.total} camera${state.total != 1 ? 's' : ''}',
                style: Theme.of(context).textTheme.bodySmall?.copyWith(
                      color: Theme.of(context).colorScheme.outline,
                    ),
              ),
            ],
          ),
          const SizedBox(height: ZySpacing.xs),
        ],
      ),
    );
  }
}

// ─── FILTER CHIP ────────────────────────────────────────

class _FilterChip extends StatelessWidget {
  final String label;
  final bool selected;
  final Color? color;
  final VoidCallback onTap;

  const _FilterChip({
    required this.label,
    required this.selected,
    this.color,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final activeColor = color ?? theme.colorScheme.primary;

    return GestureDetector(
      onTap: onTap,
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 200),
        padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 6),
        decoration: BoxDecoration(
          color: selected
              ? activeColor.withValues(alpha: 0.15)
              : theme.colorScheme.surfaceContainerHighest,
          borderRadius: ZySpacing.borderRadiusFull,
          border: Border.all(
            color: selected
                ? activeColor
                : theme.colorScheme.outline.withValues(alpha: 0.2),
          ),
        ),
        child: Text(
          label,
          style: theme.textTheme.labelMedium?.copyWith(
            color: selected ? activeColor : theme.colorScheme.onSurface,
            fontWeight: selected ? FontWeight.w600 : FontWeight.w400,
          ),
        ),
      ),
    );
  }
}

// ─── CAMERA LIST TILE (estilo Camerite) ─────────────────

class _CameraListTile extends StatelessWidget {
  final Camera camera;

  const _CameraListTile({required this.camera});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: ZySpacing.sm),
      child: InkWell(
        borderRadius: ZySpacing.borderRadiusLg,
        onTap: () {
          final encodedName = Uri.encodeComponent(camera.name);
          context.go(
            '${RouteNames.liveViewPath(camera.id)}?name=$encodedName',
          );
        },
        child: Container(
          decoration: BoxDecoration(
            color: theme.colorScheme.surface,
            borderRadius: ZySpacing.borderRadiusLg,
            border: Border.all(
              color: theme.colorScheme.outline.withValues(alpha: 0.12),
            ),
          ),
          clipBehavior: Clip.antiAlias,
          child: Row(
            children: [
              // Miniatura esquerda
              SizedBox(
                width: 110,
                height: 75,
                child: Stack(
                  fit: StackFit.expand,
                  children: [
                    ZyCameraThumbnail(
                      cameraId: camera.id,
                      snapshotUrl: camera.snapshotUrl,
                      isOnline: camera.isOnline,
                      height: 75,
                      refreshInterval: const Duration(seconds: 10),
                    ),
                    // Badge de status
                    if (camera.isOnline)
                      const Positioned(
                        bottom: 4,
                        right: 4,
                        child: ZyBadge(
                          label: 'LIVE',
                          variant: ZyBadgeVariant.success,
                          size: ZyBadgeSize.sm,
                        ),
                      ),
                    if (!camera.isOnline)
                      Positioned.fill(
                        child: Container(
                          color: Colors.black38,
                          child: const Center(
                            child: Icon(
                              Icons.videocam_off,
                              color: Colors.white54,
                              size: 24,
                            ),
                          ),
                        ),
                      ),
                  ],
                ),
              ),
              // Info direita
              Expanded(
                child: Padding(
                  padding: const EdgeInsets.symmetric(
                    horizontal: ZySpacing.md,
                    vertical: ZySpacing.sm,
                  ),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: [
                      Text(
                        camera.name,
                        style: theme.textTheme.bodyMedium?.copyWith(
                          fontWeight: FontWeight.w600,
                        ),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                      const SizedBox(height: 4),
                      Row(
                        children: [
                          Container(
                            width: 7,
                            height: 7,
                            decoration: BoxDecoration(
                              shape: BoxShape.circle,
                              color: camera.isOnline
                                  ? ZyColors.success
                                  : ZyColors.danger,
                            ),
                          ),
                          const SizedBox(width: 4),
                          Text(
                            camera.isOnline ? 'Online' : 'Offline',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: theme.colorScheme.outline,
                              fontSize: 11,
                            ),
                          ),
                          if (camera.isRecording) ...[
                            const SizedBox(width: ZySpacing.sm),
                            Icon(
                              Icons.fiber_manual_record,
                              size: 8,
                              color: ZyColors.danger,
                            ),
                            const SizedBox(width: 2),
                            Text(
                              'REC',
                              style: theme.textTheme.bodySmall?.copyWith(
                                color: ZyColors.danger,
                                fontWeight: FontWeight.w600,
                                fontSize: 10,
                              ),
                            ),
                          ],
                        ],
                      ),
                      if (camera.location != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          camera.location!,
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: ZyColors.primary,
                            fontSize: 11,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ] else if (camera.manufacturer != null) ...[
                        const SizedBox(height: 2),
                        Text(
                          [camera.manufacturer, camera.resolution]
                              .where((e) => e != null)
                              .join(' · '),
                          style: theme.textTheme.bodySmall?.copyWith(
                            color: theme.colorScheme.outline,
                            fontSize: 11,
                          ),
                          maxLines: 1,
                          overflow: TextOverflow.ellipsis,
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              // Error indicator
              if (!camera.isOnline)
                Padding(
                  padding: const EdgeInsets.only(right: ZySpacing.md),
                  child: Icon(
                    Icons.warning_amber_rounded,
                    color: ZyColors.danger.withValues(alpha: 0.6),
                    size: 20,
                  ),
                ),
            ],
          ),
        ),
      ),
    );
  }
}


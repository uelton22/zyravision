import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/camera.dart';
import '../../../domain/entities/camera_summary.dart';
import '../../../state/camera/camera_list_state.dart';
import '../../../state/camera/camera_providers.dart';
import '../../router/route_names.dart';
import '../../widgets/cards/zy_camera_card.dart';
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
            icon: Icon(
              state.viewMode == CameraViewMode.grid
                  ? Icons.view_list_rounded
                  : Icons.grid_view_rounded,
            ),
            tooltip: state.viewMode == CameraViewMode.grid
                ? 'Modo lista'
                : 'Modo grade',
            onPressed: () {
              ref.read(cameraListProvider.notifier).toggleViewMode();
            },
          ),
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
                ? _buildSkeleton(state.viewMode)
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

  Widget _buildSkeleton(CameraViewMode viewMode) {
    if (viewMode == CameraViewMode.grid) {
      return Padding(
        padding: const EdgeInsets.all(ZySpacing.base),
        child: GridView.count(
          crossAxisCount: 2,
          crossAxisSpacing: ZySpacing.md,
          mainAxisSpacing: ZySpacing.md,
          childAspectRatio: 0.95,
          children: List.generate(
            6,
            (_) => const ZySkeleton.rect(height: 200, borderRadius: 12),
          ),
        ),
      );
    }
    return ZySkeleton.list(itemCount: 6, itemHeight: 80);
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
        child: state.viewMode == CameraViewMode.grid
            ? _CameraGrid(cameras: state.cameras, isLoadingMore: state.isLoadingMore)
            : _CameraListView(cameras: state.cameras, isLoadingMore: state.isLoadingMore),
      ),
    );
  }
}

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
            onChanged: (q) => ref.read(cameraListProvider.notifier).setSearch(q),
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
                  onTap: () =>
                      ref.read(cameraListProvider.notifier).setStatusFilter(null),
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

class _CameraGrid extends StatelessWidget {
  final List<Camera> cameras;
  final bool isLoadingMore;

  const _CameraGrid({required this.cameras, this.isLoadingMore = false});

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      padding: const EdgeInsets.all(ZySpacing.base),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        crossAxisSpacing: ZySpacing.md,
        mainAxisSpacing: ZySpacing.md,
        childAspectRatio: 0.95,
      ),
      itemCount: cameras.length + (isLoadingMore ? 2 : 0),
      itemBuilder: (context, index) {
        if (index >= cameras.length) {
          return const ZySkeleton.rect(height: 200, borderRadius: 12);
        }
        final camera = cameras[index];
        return _CameraGridItem(camera: camera);
      },
    );
  }
}

class _CameraGridItem extends StatelessWidget {
  final Camera camera;

  const _CameraGridItem({required this.camera});

  @override
  Widget build(BuildContext context) {
    final summary = _toSummary(camera);

    return ZyCameraCard(
      camera: summary,
      onTap: () => context.go(RouteNames.cameraDetailPath(camera.id)),
    );
  }
}

class _CameraListView extends StatelessWidget {
  final List<Camera> cameras;
  final bool isLoadingMore;

  const _CameraListView({required this.cameras, this.isLoadingMore = false});

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.base,
        vertical: ZySpacing.sm,
      ),
      itemCount: cameras.length + (isLoadingMore ? 1 : 0),
      itemBuilder: (context, index) {
        if (index >= cameras.length) {
          return const Padding(
            padding: EdgeInsets.symmetric(vertical: ZySpacing.sm),
            child: ZySkeleton.rect(height: 72, borderRadius: 12),
          );
        }
        return _CameraListItem(camera: cameras[index]);
      },
    );
  }
}

class _CameraListItem extends StatelessWidget {
  final Camera camera;

  const _CameraListItem({required this.camera});

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.only(bottom: ZySpacing.sm),
      child: InkWell(
        borderRadius: ZySpacing.borderRadiusLg,
        onTap: () => context.go(RouteNames.cameraDetailPath(camera.id)),
        child: Container(
          padding: const EdgeInsets.all(ZySpacing.md),
          decoration: BoxDecoration(
            color: theme.colorScheme.surface,
            borderRadius: ZySpacing.borderRadiusLg,
            border: Border.all(
              color: theme.colorScheme.outline.withValues(alpha: 0.2),
            ),
          ),
          child: Row(
            children: [
              // Status indicator
              Container(
                width: 48,
                height: 48,
                decoration: BoxDecoration(
                  color: camera.isOnline
                      ? ZyColors.success.withValues(alpha: 0.1)
                      : ZyColors.danger.withValues(alpha: 0.1),
                  borderRadius: ZySpacing.borderRadiusMd,
                ),
                child: Icon(
                  camera.isOnline
                      ? Icons.videocam_rounded
                      : Icons.videocam_off_rounded,
                  color: camera.isOnline ? ZyColors.success : ZyColors.danger,
                ),
              ),
              const SizedBox(width: ZySpacing.md),
              // Info
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      camera.name,
                      style: theme.textTheme.titleSmall,
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 2),
                    Row(
                      children: [
                        Container(
                          width: 6,
                          height: 6,
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
                        if (camera.isRecording) ...[
                          const SizedBox(width: ZySpacing.sm),
                          Icon(
                            Icons.fiber_manual_record,
                            size: 10,
                            color: ZyColors.danger,
                          ),
                          const SizedBox(width: 2),
                          Text(
                            'REC',
                            style: theme.textTheme.bodySmall?.copyWith(
                              color: ZyColors.danger,
                              fontWeight: FontWeight.w600,
                              fontSize: 11,
                            ),
                          ),
                        ],
                      ],
                    ),
                    if (camera.manufacturer != null ||
                        camera.resolution != null) ...[
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
              const Icon(Icons.chevron_right, size: 20),
            ],
          ),
        ),
      ),
    );
  }
}

CameraSummary _toSummary(Camera camera) {
  return CameraSummary(
    id: camera.id,
    name: camera.name,
    status: camera.status,
    isOnline: camera.isOnline,
    isRecording: camera.isRecording,
    snapshotUrl: camera.snapshotUrl,
    hlsPath: camera.stream?.urls?.hls,
    manufacturer: camera.manufacturer,
    location: camera.location,
  );
}

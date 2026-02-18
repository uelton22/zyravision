import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';

class ZyPlaybackControls extends StatelessWidget {
  final bool isPlaying;
  final double playbackSpeed;
  final VoidCallback onPlayPause;
  final VoidCallback? onSkipBackward;
  final VoidCallback? onSkipForward;
  final ValueChanged<double> onSpeedChanged;
  final VoidCallback? onFullscreen;

  const ZyPlaybackControls({
    super.key,
    required this.isPlaying,
    this.playbackSpeed = 1.0,
    required this.onPlayPause,
    this.onSkipBackward,
    this.onSkipForward,
    required this.onSpeedChanged,
    this.onFullscreen,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: ZySpacing.base,
        vertical: ZySpacing.sm,
      ),
      decoration: BoxDecoration(
        color: theme.colorScheme.surface,
        border: Border(
          top: BorderSide(
            color: theme.colorScheme.outline.withValues(alpha: 0.1),
          ),
        ),
      ),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          // Speed selector
          _SpeedChip(
            speed: playbackSpeed,
            onTap: () => _showSpeedPicker(context),
          ),
          const Spacer(),
          // Skip back 10s
          IconButton(
            onPressed: onSkipBackward,
            icon: const Icon(Icons.replay_10),
            iconSize: 28,
            color: theme.colorScheme.onSurface,
          ),
          const SizedBox(width: ZySpacing.sm),
          // Play/Pause
          Container(
            decoration: BoxDecoration(
              color: ZyColors.primary,
              shape: BoxShape.circle,
            ),
            child: IconButton(
              onPressed: onPlayPause,
              icon: Icon(
                isPlaying ? Icons.pause_rounded : Icons.play_arrow_rounded,
              ),
              iconSize: 32,
              color: Colors.white,
            ),
          ),
          const SizedBox(width: ZySpacing.sm),
          // Skip forward 10s
          IconButton(
            onPressed: onSkipForward,
            icon: const Icon(Icons.forward_10),
            iconSize: 28,
            color: theme.colorScheme.onSurface,
          ),
          const Spacer(),
          // Fullscreen
          IconButton(
            onPressed: onFullscreen,
            icon: const Icon(Icons.fullscreen),
            iconSize: 24,
            color: theme.colorScheme.outline,
          ),
        ],
      ),
    );
  }

  void _showSpeedPicker(BuildContext context) {
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(16)),
      ),
      builder: (context) {
        return _SpeedPickerSheet(
          currentSpeed: playbackSpeed,
          onSpeedSelected: (speed) {
            onSpeedChanged(speed);
            Navigator.pop(context);
          },
        );
      },
    );
  }
}

class _SpeedChip extends StatelessWidget {
  final double speed;
  final VoidCallback onTap;

  const _SpeedChip({
    required this.speed,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final label = speed == 1.0 ? '1x' : '${speed}x';

    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(
          horizontal: ZySpacing.sm,
          vertical: ZySpacing.xs,
        ),
        decoration: BoxDecoration(
          color: speed != 1.0
              ? ZyColors.primary.withValues(alpha: 0.1)
              : theme.colorScheme.outline.withValues(alpha: 0.1),
          borderRadius: ZySpacing.borderRadiusFull,
        ),
        child: Text(
          label,
          style: theme.textTheme.bodySmall?.copyWith(
            fontWeight: FontWeight.w600,
            color: speed != 1.0
                ? ZyColors.primary
                : theme.colorScheme.onSurface,
          ),
        ),
      ),
    );
  }
}

class _SpeedPickerSheet extends StatelessWidget {
  final double currentSpeed;
  final ValueChanged<double> onSpeedSelected;

  const _SpeedPickerSheet({
    required this.currentSpeed,
    required this.onSpeedSelected,
  });

  static const _speeds = [0.25, 0.5, 1.0, 1.5, 2.0, 4.0, 8.0];

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Padding(
      padding: const EdgeInsets.all(ZySpacing.xl),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          // Handle
          Center(
            child: Container(
              width: 40,
              height: 4,
              decoration: BoxDecoration(
                color: theme.colorScheme.outline.withValues(alpha: 0.3),
                borderRadius: ZySpacing.borderRadiusFull,
              ),
            ),
          ),
          const SizedBox(height: ZySpacing.xl),
          Text(
            'Velocidade de reproducao',
            style: theme.textTheme.titleMedium?.copyWith(
              fontWeight: FontWeight.bold,
            ),
          ),
          const SizedBox(height: ZySpacing.base),
          Wrap(
            spacing: ZySpacing.sm,
            runSpacing: ZySpacing.sm,
            children: _speeds.map((speed) {
              final isSelected = speed == currentSpeed;
              return ChoiceChip(
                label: Text('${speed}x'),
                selected: isSelected,
                onSelected: (_) => onSpeedSelected(speed),
              );
            }).toList(),
          ),
          SizedBox(
            height: MediaQuery.of(context).padding.bottom + ZySpacing.base,
          ),
        ],
      ),
    );
  }
}

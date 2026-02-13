import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';

enum ZyBadgeVariant { success, danger, warning, info, neutral, primary }

enum ZyBadgeSize { sm, md }

class ZyBadge extends StatelessWidget {
  final String label;
  final ZyBadgeVariant variant;
  final ZyBadgeSize size;
  final IconData? icon;
  final bool pulse;

  const ZyBadge({
    super.key,
    required this.label,
    this.variant = ZyBadgeVariant.neutral,
    this.size = ZyBadgeSize.md,
    this.icon,
    this.pulse = false,
  });

  @override
  Widget build(BuildContext context) {
    final isDark = Theme.of(context).brightness == Brightness.dark;
    final colors = _getColors(isDark);
    final isSmall = size == ZyBadgeSize.sm;

    Widget badge = Container(
      padding: EdgeInsets.symmetric(
        horizontal: isSmall ? 6 : 8,
        vertical: isSmall ? 2 : 3,
      ),
      decoration: BoxDecoration(
        color: colors.$1,
        borderRadius: ZySpacing.borderRadiusSm,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (icon != null) ...[
            Icon(icon, size: isSmall ? 12 : 14, color: colors.$2),
            SizedBox(width: isSmall ? 2 : 4),
          ],
          Text(
            label,
            style: TextStyle(
              color: colors.$2,
              fontSize: isSmall ? 11 : 12,
              fontWeight: FontWeight.w500,
              height: isSmall ? 1.27 : 1.33,
            ),
          ),
        ],
      ),
    );

    if (pulse) {
      badge = _PulseWrapper(child: badge);
    }

    return badge;
  }

  (Color background, Color text) _getColors(bool isDark) {
    switch (variant) {
      case ZyBadgeVariant.success:
        return (
          isDark ? ZyColors.successLightDarkMode : ZyColors.successLight,
          isDark ? ZyColors.successDarkMode : ZyColors.success,
        );
      case ZyBadgeVariant.danger:
        return (
          isDark ? ZyColors.dangerLightDarkMode : ZyColors.dangerLight,
          isDark ? ZyColors.dangerDarkMode : ZyColors.danger,
        );
      case ZyBadgeVariant.warning:
        return (
          isDark ? ZyColors.warningLightDarkMode : ZyColors.warningLight,
          isDark ? ZyColors.warningDarkMode : ZyColors.warning,
        );
      case ZyBadgeVariant.info:
        return (
          isDark ? ZyColors.infoLightDarkMode : ZyColors.infoLight,
          isDark ? ZyColors.infoDarkMode : ZyColors.info,
        );
      case ZyBadgeVariant.neutral:
        return (
          isDark ? ZyColors.surfaceVariantDark : ZyColors.surfaceVariantLight,
          isDark ? ZyColors.textSecondaryDark : ZyColors.textSecondaryLight,
        );
      case ZyBadgeVariant.primary:
        return (
          isDark ? ZyColors.primaryLightDarkMode : ZyColors.primaryLight,
          isDark ? ZyColors.primaryDarkMode : ZyColors.primary,
        );
    }
  }
}

class _PulseWrapper extends StatefulWidget {
  final Widget child;
  const _PulseWrapper({required this.child});

  @override
  State<_PulseWrapper> createState() => _PulseWrapperState();
}

class _PulseWrapperState extends State<_PulseWrapper>
    with SingleTickerProviderStateMixin {
  late final AnimationController _controller;
  late final Animation<double> _animation;

  @override
  void initState() {
    super.initState();
    _controller = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 1500),
    )..repeat(reverse: true);
    _animation = Tween<double>(begin: 0.4, end: 1.0).animate(_controller);
  }

  @override
  void dispose() {
    _controller.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return FadeTransition(opacity: _animation, child: widget.child);
  }
}

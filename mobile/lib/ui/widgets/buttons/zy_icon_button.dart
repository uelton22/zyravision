import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';

class ZyIconButton extends StatelessWidget {
  final IconData icon;
  final VoidCallback? onPressed;
  final double size;
  final double iconSize;
  final String semanticLabel;
  final Color? color;
  final Color? backgroundColor;

  const ZyIconButton({
    super.key,
    required this.icon,
    required this.semanticLabel,
    this.onPressed,
    this.size = 40,
    this.iconSize = 20,
    this.color,
    this.backgroundColor,
  });

  @override
  Widget build(BuildContext context) {
    return Semantics(
      label: semanticLabel,
      button: true,
      child: Material(
        color: backgroundColor ?? Colors.transparent,
        borderRadius: ZySpacing.borderRadiusMd,
        child: InkWell(
          onTap: onPressed,
          borderRadius: ZySpacing.borderRadiusMd,
          child: SizedBox(
            width: size,
            height: size,
            child: Icon(
              icon,
              size: iconSize,
              color: color ?? Theme.of(context).colorScheme.onSurface,
            ),
          ),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';

class ZyCard extends StatelessWidget {
  final Widget child;
  final EdgeInsets? padding;
  final VoidCallback? onTap;
  final Color? backgroundColor;
  final double? borderRadius;
  final bool elevated;
  final Widget? header;
  final Widget? footer;

  const ZyCard({
    super.key,
    required this.child,
    this.padding,
    this.onTap,
    this.backgroundColor,
    this.borderRadius,
    this.elevated = true,
    this.header,
    this.footer,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final radius = borderRadius ?? ZySpacing.radiusLg;

    Widget content = Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (header != null)
          Padding(
            padding: const EdgeInsets.fromLTRB(
              ZySpacing.base, ZySpacing.base, ZySpacing.base, 0,
            ),
            child: header!,
          ),
        Padding(
          padding: padding ?? ZySpacing.cardInsets,
          child: child,
        ),
        if (footer != null)
          Padding(
            padding: const EdgeInsets.fromLTRB(
              ZySpacing.base, 0, ZySpacing.base, ZySpacing.base,
            ),
            child: footer!,
          ),
      ],
    );

    return Material(
      color: backgroundColor ?? theme.cardTheme.color ?? theme.colorScheme.surface,
      borderRadius: BorderRadius.circular(radius),
      elevation: elevated ? 1 : 0,
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(radius),
        child: Container(
          decoration: BoxDecoration(
            borderRadius: BorderRadius.circular(radius),
            border: Border.all(
              color: theme.colorScheme.outline.withValues(alpha: 0.2),
            ),
          ),
          child: content,
        ),
      ),
    );
  }
}

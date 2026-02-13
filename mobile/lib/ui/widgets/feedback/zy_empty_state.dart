import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';
import '../buttons/zy_button.dart';

class ZyEmptyState extends StatelessWidget {
  final String message;
  final String? description;
  final IconData? icon;
  final Widget? illustration;
  final String? actionLabel;
  final VoidCallback? onAction;

  const ZyEmptyState({
    super.key,
    required this.message,
    this.description,
    this.icon,
    this.illustration,
    this.actionLabel,
    this.onAction,
  });

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: ZySpacing.xxl),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            if (illustration != null)
              illustration!
            else
              Icon(
                icon ?? Icons.inbox_outlined,
                size: 64,
                color: theme.colorScheme.outline,
              ),
            const SizedBox(height: ZySpacing.base),
            Text(
              message,
              style: theme.textTheme.headlineMedium,
              textAlign: TextAlign.center,
            ),
            if (description != null) ...[
              const SizedBox(height: ZySpacing.sm),
              Text(
                description!,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: theme.colorScheme.outline,
                ),
                textAlign: TextAlign.center,
              ),
            ],
            if (actionLabel != null && onAction != null) ...[
              const SizedBox(height: ZySpacing.xl),
              ZyButton(label: actionLabel!, onPressed: onAction),
            ],
          ],
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';

class ZyLoadingIndicator extends StatelessWidget {
  final double size;
  final Color? color;
  final String? message;
  final bool fullScreen;

  const ZyLoadingIndicator({
    super.key,
    this.size = 24,
    this.color,
    this.message,
    this.fullScreen = false,
  });

  const ZyLoadingIndicator.fullScreen({
    super.key,
    this.message,
  })  : size = 40,
        color = null,
        fullScreen = true;

  @override
  Widget build(BuildContext context) {
    final indicator = SizedBox(
      width: size,
      height: size,
      child: CircularProgressIndicator(
        strokeWidth: size > 30 ? 3 : 2,
        color: color ?? Theme.of(context).colorScheme.primary,
      ),
    );

    if (fullScreen) {
      return Container(
        color: Colors.black26,
        child: Center(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              indicator,
              if (message != null) ...[
                const SizedBox(height: ZySpacing.base),
                Text(
                  message!,
                  style: Theme.of(context).textTheme.bodyMedium?.copyWith(
                        color: Colors.white,
                      ),
                ),
              ],
            ],
          ),
        ),
      );
    }

    if (message != null) {
      return Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          indicator,
          const SizedBox(height: ZySpacing.sm),
          Text(message!, style: Theme.of(context).textTheme.bodySmall),
        ],
      );
    }

    return Center(child: indicator);
  }
}

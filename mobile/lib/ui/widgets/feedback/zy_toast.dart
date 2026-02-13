import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';

enum ZyToastVariant { success, error, warning, info }

class ZyToast {
  static void show(
    BuildContext context, {
    required String message,
    ZyToastVariant variant = ZyToastVariant.info,
    Duration duration = const Duration(seconds: 3),
    String? actionLabel,
    VoidCallback? onAction,
  }) {
    ScaffoldMessenger.of(context).hideCurrentSnackBar();

    final colors = _getColors(variant);

    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            Icon(colors.$3, color: Colors.white, size: 20),
            const SizedBox(width: ZySpacing.sm),
            Expanded(
              child: Text(
                message,
                style: const TextStyle(color: Colors.white, fontSize: 14),
              ),
            ),
          ],
        ),
        backgroundColor: colors.$1,
        behavior: SnackBarBehavior.floating,
        shape: RoundedRectangleBorder(
          borderRadius: ZySpacing.borderRadiusMd,
        ),
        margin: const EdgeInsets.fromLTRB(16, 0, 16, 16),
        duration: duration,
        action: actionLabel != null
            ? SnackBarAction(
                label: actionLabel,
                textColor: Colors.white,
                onPressed: onAction ?? () {},
              )
            : null,
        dismissDirection: DismissDirection.horizontal,
      ),
    );
  }

  static (Color bg, Color text, IconData icon) _getColors(ZyToastVariant variant) {
    switch (variant) {
      case ZyToastVariant.success:
        return (ZyColors.success, Colors.white, Icons.check_circle);
      case ZyToastVariant.error:
        return (ZyColors.danger, Colors.white, Icons.error);
      case ZyToastVariant.warning:
        return (ZyColors.warning, Colors.white, Icons.warning);
      case ZyToastVariant.info:
        return (ZyColors.info, Colors.white, Icons.info);
    }
  }
}

import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';

enum ZyButtonVariant { primary, secondary, danger, ghost, outline }

enum ZyButtonSize { sm, md, lg }

class ZyButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final ZyButtonVariant variant;
  final ZyButtonSize size;
  final bool loading;
  final bool disabled;
  final IconData? leadingIcon;
  final IconData? trailingIcon;
  final bool fullWidth;

  const ZyButton({
    super.key,
    required this.label,
    this.onPressed,
    this.variant = ZyButtonVariant.primary,
    this.size = ZyButtonSize.md,
    this.loading = false,
    this.disabled = false,
    this.leadingIcon,
    this.trailingIcon,
    this.fullWidth = false,
  });

  @override
  Widget build(BuildContext context) {
    final isDisabled = disabled || loading || onPressed == null;
    final sizeConfig = _getSizeConfig();

    Widget child;
    if (loading) {
      child = SizedBox(
        width: sizeConfig.iconSize,
        height: sizeConfig.iconSize,
        child: CircularProgressIndicator(
          strokeWidth: 2,
          color: _getForegroundColor(context),
        ),
      );
    } else {
      child = Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          if (leadingIcon != null) ...[
            Icon(leadingIcon, size: sizeConfig.iconSize),
            const SizedBox(width: ZySpacing.sm),
          ],
          Text(
            label,
            style: TextStyle(
              fontSize: sizeConfig.fontSize,
              fontWeight: FontWeight.w500,
            ),
          ),
          if (trailingIcon != null) ...[
            const SizedBox(width: ZySpacing.sm),
            Icon(trailingIcon, size: sizeConfig.iconSize),
          ],
        ],
      );
    }

    final buttonStyle = _getButtonStyle(context, sizeConfig);

    Widget button;
    switch (variant) {
      case ZyButtonVariant.primary:
      case ZyButtonVariant.danger:
        button = ElevatedButton(
          onPressed: isDisabled ? null : onPressed,
          style: buttonStyle,
          child: child,
        );
      case ZyButtonVariant.secondary:
      case ZyButtonVariant.outline:
        button = OutlinedButton(
          onPressed: isDisabled ? null : onPressed,
          style: buttonStyle,
          child: child,
        );
      case ZyButtonVariant.ghost:
        button = TextButton(
          onPressed: isDisabled ? null : onPressed,
          style: buttonStyle,
          child: child,
        );
    }

    if (fullWidth) {
      return SizedBox(width: double.infinity, child: button);
    }

    return button;
  }

  Color _getBackgroundColor(BuildContext context) {
    switch (variant) {
      case ZyButtonVariant.primary:
        return Theme.of(context).colorScheme.primary;
      case ZyButtonVariant.danger:
        return ZyColors.danger;
      case ZyButtonVariant.secondary:
      case ZyButtonVariant.outline:
      case ZyButtonVariant.ghost:
        return Colors.transparent;
    }
  }

  Color _getForegroundColor(BuildContext context) {
    switch (variant) {
      case ZyButtonVariant.primary:
        return ZyColors.onPrimary;
      case ZyButtonVariant.danger:
        return ZyColors.onPrimary;
      case ZyButtonVariant.secondary:
        return Theme.of(context).colorScheme.onSurface;
      case ZyButtonVariant.outline:
      case ZyButtonVariant.ghost:
        return Theme.of(context).colorScheme.primary;
    }
  }

  ButtonStyle _getButtonStyle(BuildContext context, _SizeConfig config) {
    final bg = _getBackgroundColor(context);
    final fg = _getForegroundColor(context);

    switch (variant) {
      case ZyButtonVariant.primary:
      case ZyButtonVariant.danger:
        return ElevatedButton.styleFrom(
          backgroundColor: bg,
          foregroundColor: fg,
          minimumSize: Size(0, config.height),
          padding: EdgeInsets.symmetric(horizontal: config.paddingH),
          shape: RoundedRectangleBorder(
            borderRadius: ZySpacing.borderRadiusMd,
          ),
          elevation: 0,
        );
      case ZyButtonVariant.secondary:
        return OutlinedButton.styleFrom(
          foregroundColor: fg,
          minimumSize: Size(0, config.height),
          padding: EdgeInsets.symmetric(horizontal: config.paddingH),
          shape: RoundedRectangleBorder(
            borderRadius: ZySpacing.borderRadiusMd,
          ),
          side: BorderSide(color: Theme.of(context).colorScheme.outline),
        );
      case ZyButtonVariant.outline:
        return OutlinedButton.styleFrom(
          foregroundColor: fg,
          minimumSize: Size(0, config.height),
          padding: EdgeInsets.symmetric(horizontal: config.paddingH),
          shape: RoundedRectangleBorder(
            borderRadius: ZySpacing.borderRadiusMd,
          ),
          side: BorderSide(color: Theme.of(context).colorScheme.primary),
        );
      case ZyButtonVariant.ghost:
        return TextButton.styleFrom(
          foregroundColor: fg,
          minimumSize: Size(0, config.height),
          padding: EdgeInsets.symmetric(horizontal: config.paddingH),
          shape: RoundedRectangleBorder(
            borderRadius: ZySpacing.borderRadiusMd,
          ),
        );
    }
  }

  _SizeConfig _getSizeConfig() {
    switch (size) {
      case ZyButtonSize.sm:
        return const _SizeConfig(height: 32, paddingH: 12, fontSize: 12, iconSize: 16);
      case ZyButtonSize.md:
        return const _SizeConfig(height: 40, paddingH: 16, fontSize: 14, iconSize: 20);
      case ZyButtonSize.lg:
        return const _SizeConfig(height: 48, paddingH: 20, fontSize: 16, iconSize: 24);
    }
  }
}

class _SizeConfig {
  final double height;
  final double paddingH;
  final double fontSize;
  final double iconSize;

  const _SizeConfig({
    required this.height,
    required this.paddingH,
    required this.fontSize,
    required this.iconSize,
  });
}

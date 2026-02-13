import 'package:flutter/material.dart';

class ZyAppBar extends StatelessWidget implements PreferredSizeWidget {
  final String title;
  final bool showBack;
  final List<Widget>? actions;
  final Widget? leading;
  final bool transparent;
  final PreferredSizeWidget? bottom;

  const ZyAppBar({
    super.key,
    required this.title,
    this.showBack = false,
    this.actions,
    this.leading,
    this.transparent = false,
    this.bottom,
  });

  @override
  Widget build(BuildContext context) {
    return AppBar(
      title: Text(title),
      leading: leading,
      automaticallyImplyLeading: showBack,
      actions: actions,
      backgroundColor: transparent ? Colors.transparent : null,
      elevation: transparent ? 0 : null,
      bottom: bottom,
    );
  }

  @override
  Size get preferredSize =>
      Size.fromHeight(kToolbarHeight + (bottom?.preferredSize.height ?? 0));
}

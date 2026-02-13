import 'dart:async';

import 'package:flutter/material.dart';

import '../../../core/theme/zy_spacing.dart';

class ZySearchBar extends StatefulWidget {
  final String hint;
  final ValueChanged<String> onChanged;
  final VoidCallback? onClear;
  final Duration debounce;
  final bool autofocus;
  final TextEditingController? controller;

  const ZySearchBar({
    super.key,
    this.hint = 'Buscar...',
    required this.onChanged,
    this.onClear,
    this.debounce = const Duration(milliseconds: 300),
    this.autofocus = false,
    this.controller,
  });

  @override
  State<ZySearchBar> createState() => _ZySearchBarState();
}

class _ZySearchBarState extends State<ZySearchBar> {
  late final TextEditingController _controller;
  Timer? _debounceTimer;

  @override
  void initState() {
    super.initState();
    _controller = widget.controller ?? TextEditingController();
  }

  @override
  void dispose() {
    _debounceTimer?.cancel();
    if (widget.controller == null) _controller.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounceTimer?.cancel();
    _debounceTimer = Timer(widget.debounce, () {
      widget.onChanged(value);
    });
    setState(() {});
  }

  void _onClear() {
    _controller.clear();
    widget.onChanged('');
    widget.onClear?.call();
    setState(() {});
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SizedBox(
      height: 44,
      child: TextField(
        controller: _controller,
        autofocus: widget.autofocus,
        onChanged: _onChanged,
        decoration: InputDecoration(
          hintText: widget.hint,
          filled: true,
          fillColor: theme.colorScheme.surfaceContainerHighest,
          prefixIcon: Icon(
            Icons.search,
            color: theme.colorScheme.outline,
            size: 20,
          ),
          suffixIcon: _controller.text.isNotEmpty
              ? IconButton(
                  icon: const Icon(Icons.clear, size: 18),
                  onPressed: _onClear,
                )
              : null,
          border: OutlineInputBorder(
            borderRadius: ZySpacing.borderRadiusFull,
            borderSide: BorderSide.none,
          ),
          enabledBorder: OutlineInputBorder(
            borderRadius: ZySpacing.borderRadiusFull,
            borderSide: BorderSide.none,
          ),
          focusedBorder: OutlineInputBorder(
            borderRadius: ZySpacing.borderRadiusFull,
            borderSide: BorderSide(
              color: theme.colorScheme.primary,
              width: 2,
            ),
          ),
          contentPadding: const EdgeInsets.symmetric(horizontal: 16),
        ),
      ),
    );
  }
}

import 'package:flutter/material.dart';

import '../../../core/theme/zy_colors.dart';
import '../../../core/theme/zy_spacing.dart';
import '../../../domain/entities/timeline_data.dart';

/// Interactive 24-hour timeline bar inspired by Camerite.
/// Shows recording availability as colored ranges and allows scrubbing.
class ZyTimelineBar extends StatefulWidget {
  final DateTime selectedDate;
  final List<TimelineRange> ranges;
  final DateTime? currentTime;
  final ValueChanged<DateTime>? onTimeSelected;
  final ValueChanged<DateTime>? onScrubbing;
  final double height;

  const ZyTimelineBar({
    super.key,
    required this.selectedDate,
    required this.ranges,
    this.currentTime,
    this.onTimeSelected,
    this.onScrubbing,
    this.height = 64,
  });

  @override
  State<ZyTimelineBar> createState() => _ZyTimelineBarState();
}

class _ZyTimelineBarState extends State<ZyTimelineBar> {
  final ScrollController _scrollController = ScrollController();
  bool _isScrubbing = false;
  static const double _pixelsPerHour = 120.0;
  static const double _totalWidth = _pixelsPerHour * 24;

  DateTime get _dayStart => DateTime(
        widget.selectedDate.year,
        widget.selectedDate.month,
        widget.selectedDate.day,
      );

  DateTime get _dayEnd => _dayStart.add(const Duration(days: 1));

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToCurrentTime());
  }

  @override
  void didUpdateWidget(ZyTimelineBar oldWidget) {
    super.didUpdateWidget(oldWidget);
    if (oldWidget.selectedDate != widget.selectedDate) {
      WidgetsBinding.instance.addPostFrameCallback((_) => _scrollToCurrentTime());
    }
  }

  void _scrollToCurrentTime() {
    final time = widget.currentTime ?? DateTime.now();
    if (time.isBefore(_dayStart) || time.isAfter(_dayEnd)) return;

    final offset = _timeToOffset(time);
    final screenWidth = MediaQuery.of(context).size.width;
    final targetOffset = (offset - screenWidth / 2).clamp(0.0, _totalWidth - screenWidth);

    if (_scrollController.hasClients) {
      _scrollController.animateTo(
        targetOffset,
        duration: const Duration(milliseconds: 300),
        curve: Curves.easeOut,
      );
    }
  }

  double _timeToOffset(DateTime time) {
    final diff = time.difference(_dayStart);
    return (diff.inMilliseconds / const Duration(hours: 1).inMilliseconds) *
        _pixelsPerHour;
  }

  DateTime _offsetToTime(double offset) {
    final hours = offset / _pixelsPerHour;
    return _dayStart.add(Duration(milliseconds: (hours * 3600 * 1000).round()));
  }

  @override
  void dispose() {
    _scrollController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Timeline bar
        SizedBox(
          height: widget.height,
          child: GestureDetector(
            onHorizontalDragStart: (_) => setState(() => _isScrubbing = true),
            onHorizontalDragEnd: (_) {
              setState(() => _isScrubbing = false);
              if (widget.currentTime != null) {
                widget.onTimeSelected?.call(widget.currentTime!);
              }
            },
            onTapUp: (details) {
              final renderBox = context.findRenderObject() as RenderBox;
              final localPos = renderBox.globalToLocal(details.globalPosition);
              final offset = _scrollController.offset + localPos.dx;
              final time = _offsetToTime(offset);
              widget.onTimeSelected?.call(time);
            },
            child: SingleChildScrollView(
              controller: _scrollController,
              scrollDirection: Axis.horizontal,
              physics: _isScrubbing
                  ? const NeverScrollableScrollPhysics()
                  : const BouncingScrollPhysics(),
              child: SizedBox(
                width: _totalWidth,
                height: widget.height,
                child: CustomPaint(
                  painter: _TimelinePainter(
                    ranges: widget.ranges,
                    dayStart: _dayStart,
                    currentTime: widget.currentTime,
                    pixelsPerHour: _pixelsPerHour,
                    isDark: theme.brightness == Brightness.dark,
                  ),
                ),
              ),
            ),
          ),
        ),
        // Current time indicator label
        if (widget.currentTime != null)
          Padding(
            padding: const EdgeInsets.symmetric(
              horizontal: ZySpacing.base,
              vertical: ZySpacing.xs,
            ),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: [
                Icon(
                  Icons.access_time,
                  size: 14,
                  color: ZyColors.primary,
                ),
                const SizedBox(width: 4),
                Text(
                  _formatTime(widget.currentTime!),
                  style: theme.textTheme.bodySmall?.copyWith(
                    color: ZyColors.primary,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ],
            ),
          ),
      ],
    );
  }

  String _formatTime(DateTime time) {
    final t = time.toLocal();
    return '${t.hour.toString().padLeft(2, '0')}:'
        '${t.minute.toString().padLeft(2, '0')}:'
        '${t.second.toString().padLeft(2, '0')}';
  }
}

class _TimelinePainter extends CustomPainter {
  final List<TimelineRange> ranges;
  final DateTime dayStart;
  final DateTime? currentTime;
  final double pixelsPerHour;
  final bool isDark;

  _TimelinePainter({
    required this.ranges,
    required this.dayStart,
    this.currentTime,
    required this.pixelsPerHour,
    required this.isDark,
  });

  @override
  void paint(Canvas canvas, Size size) {
    final bgPaint = Paint()
      ..color = isDark
          ? ZyColors.surfaceDark
          : ZyColors.surfaceVariantLight;

    // Background
    canvas.drawRect(
      Rect.fromLTWH(0, 0, size.width, size.height),
      bgPaint,
    );

    // Grid lines and labels
    _drawGrid(canvas, size);

    // Recording ranges
    _drawRanges(canvas, size);

    // Current time indicator
    if (currentTime != null) {
      _drawCurrentTimeIndicator(canvas, size);
    }
  }

  void _drawGrid(Canvas canvas, Size size) {
    final gridPaint = Paint()
      ..color = isDark
          ? ZyColors.borderDark.withValues(alpha: 0.3)
          : ZyColors.borderLight.withValues(alpha: 0.5)
      ..strokeWidth = 0.5;

    final textStyle = TextStyle(
      color: isDark ? ZyColors.textTertiaryDark : ZyColors.textTertiaryLight,
      fontSize: 10,
    );

    for (var hour = 0; hour <= 24; hour++) {
      final x = hour * pixelsPerHour;

      // Hour line
      canvas.drawLine(
        Offset(x, 0),
        Offset(x, size.height),
        gridPaint,
      );

      // Hour label
      if (hour < 24) {
        final label = '${hour.toString().padLeft(2, '0')}:00';
        final textPainter = TextPainter(
          text: TextSpan(text: label, style: textStyle),
          textDirection: TextDirection.ltr,
        );
        textPainter.layout();
        textPainter.paint(canvas, Offset(x + 4, 2));
      }

      // 30-minute mark
      if (hour < 24) {
        final halfX = x + pixelsPerHour / 2;
        canvas.drawLine(
          Offset(halfX, size.height - 8),
          Offset(halfX, size.height),
          gridPaint,
        );
      }
    }
  }

  void _drawRanges(Canvas canvas, Size size) {
    final rangePaint = Paint()
      ..color = ZyColors.primary.withValues(alpha: 0.4);
    final rangeBorderPaint = Paint()
      ..color = ZyColors.primary.withValues(alpha: 0.7)
      ..strokeWidth = 1;

    final barTop = size.height * 0.35;
    final barHeight = size.height * 0.45;

    for (final range in ranges) {
      final startX = _timeToX(range.start);
      final endX = _timeToX(range.end);

      final rect = RRect.fromLTRBR(
        startX,
        barTop,
        endX,
        barTop + barHeight,
        const Radius.circular(3),
      );

      canvas.drawRRect(rect, rangePaint);
      canvas.drawRRect(rect, rangeBorderPaint..style = PaintingStyle.stroke);
    }
  }

  void _drawCurrentTimeIndicator(Canvas canvas, Size size) {
    final x = _timeToX(currentTime!);
    final paint = Paint()
      ..color = ZyColors.danger
      ..strokeWidth = 2;

    // Vertical line
    canvas.drawLine(
      Offset(x, 0),
      Offset(x, size.height),
      paint,
    );

    // Triangle at top
    final path = Path()
      ..moveTo(x - 6, 0)
      ..lineTo(x + 6, 0)
      ..lineTo(x, 8)
      ..close();
    canvas.drawPath(path, paint..style = PaintingStyle.fill);
  }

  double _timeToX(DateTime time) {
    final diff = time.difference(dayStart);
    return (diff.inMilliseconds / const Duration(hours: 1).inMilliseconds) *
        pixelsPerHour;
  }

  @override
  bool shouldRepaint(covariant _TimelinePainter oldDelegate) {
    return oldDelegate.ranges != ranges ||
        oldDelegate.currentTime != currentTime ||
        oldDelegate.isDark != isDark;
  }
}
